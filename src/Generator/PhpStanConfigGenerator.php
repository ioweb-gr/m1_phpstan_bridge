<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Generator;

final class PhpStanConfigGenerator
{
    /**
     * @param array<string, string> $mapFiles
     */
    public function generate(string $projectRoot, string $bridgeDirectory, array $mapFiles): string
    {
        $stubFiles = [
            $bridgeDirectory . DIRECTORY_SEPARATOR . 'mage-factories.stub.php',
            $bridgeDirectory . DIRECTORY_SEPARATOR . 'varien.stub.php',
            $bridgeDirectory . DIRECTORY_SEPARATOR . 'magento-core.stub.php',
        ];

        $scanFiles = [
            $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php',
        ];

        $scanDirectories = [
            $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'core',
            $projectRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Varien',
        ];

        $lines = [
            'parameters:',
            '    level: 0',
            '    stubFiles:',
        ];

        foreach ($stubFiles as $stubFile) {
            $lines[] = sprintf('        - %s', $this->neonPath($stubFile));
        }

        $lines[] = '    bootstrapFiles:';
        $lines[] = sprintf('        - %s', $this->neonPath($bridgeDirectory . DIRECTORY_SEPARATOR . 'autoload.php'));
        $lines[] = sprintf('        - %s', $this->neonPath($bridgeDirectory . DIRECTORY_SEPARATOR . 'classmap-autoload.php'));

        $lines[] = '    excludePaths:';
        $lines[] = '        analyseAndScan:';
        foreach (['Test', 'tmp', 'vendor', 'node_modules'] as $excludedDirectory) {
            $lines[] = sprintf('            - */%s/*', $excludedDirectory);
        }

        $lines[] = '    scanFiles:';
        foreach ($scanFiles as $scanPath) {
            if (is_file($scanPath)) {
                $lines[] = sprintf('        - %s', $this->neonPath($scanPath));
            }
        }

        $lines[] = '    scanDirectories:';
        foreach ($scanDirectories as $scanPath) {
            if (is_dir($scanPath)) {
                $lines[] = sprintf('        - %s', $this->neonPath($scanPath));
            }
        }

        $lines[] = '';
        $lines[] = 'services:';
        $lines[] = '    -';
        $lines[] = '        class: M1PhpStanBridgeGenerated\PHPStan\MageGetModelDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['model']));
        $lines[] = '        tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]';
        $lines[] = '    -';
        $lines[] = '        class: M1PhpStanBridgeGenerated\PHPStan\MageGetSingletonDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['singleton']));
        $lines[] = '        tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]';
        $lines[] = '    -';
        $lines[] = '        class: M1PhpStanBridgeGenerated\PHPStan\MageGetResourceModelDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['resource-model']));
        $lines[] = '        tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]';
        $lines[] = '    -';
        $lines[] = '        class: M1PhpStanBridgeGenerated\PHPStan\MageHelperDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['helper']));
        $lines[] = '        tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]';
        $lines[] = '    -';
        $lines[] = '        class: M1PhpStanBridgeGenerated\PHPStan\MageGetBlockSingletonDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['block']));
        $lines[] = '        tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]';
        $lines[] = '    -';
        $lines[] = '        class: M1PhpStanBridgeGenerated\PHPStan\MageLayoutCreateBlockDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['block']));
        $lines[] = '        tags: [phpstan.broker.dynamicMethodReturnTypeExtension]';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function neonPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function neonString(string $value): string
    {
        return "'" . str_replace("'", "''", $this->neonPath($value)) . "'";
    }
}
