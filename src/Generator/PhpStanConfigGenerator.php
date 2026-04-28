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
        ];

        $scanFiles = [
            $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php',
            $bridgeDirectory . DIRECTORY_SEPARATOR . 'varien.stub.php',
            $bridgeDirectory . DIRECTORY_SEPARATOR . 'magento-core.stub.php',
        ];

        $scanDirectories = [
            $projectRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Varien',
        ];

        $lines = [
            'parameters:',
            '    stubFiles:',
        ];

        foreach ($stubFiles as $stubFile) {
            $lines[] = sprintf('        - %s', $this->neonPath($stubFile));
        }

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
        $lines[] = '        class: Ioweb\M1PhpStanBridge\PHPStan\MageGetModelDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['model']));
        $lines[] = '        tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]';
        $lines[] = '    -';
        $lines[] = '        class: Ioweb\M1PhpStanBridge\PHPStan\MageGetSingletonDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['singleton']));
        $lines[] = '        tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]';
        $lines[] = '    -';
        $lines[] = '        class: Ioweb\M1PhpStanBridge\PHPStan\MageGetResourceModelDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['resource-model']));
        $lines[] = '        tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]';
        $lines[] = '    -';
        $lines[] = '        class: Ioweb\M1PhpStanBridge\PHPStan\MageHelperDynamicReturnTypeExtension';
        $lines[] = sprintf('        arguments: [%s]', $this->neonString($mapFiles['helper']));
        $lines[] = '        tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]';
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
