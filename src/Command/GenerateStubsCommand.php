<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Command;

use Ioweb\M1PhpStanBridge\Discovery\ClassMapBuilder;
use Ioweb\M1PhpStanBridge\Generator\BridgeFileWriter;
use Ioweb\M1PhpStanBridge\Generator\ClassMapGenerator;
use Ioweb\M1PhpStanBridge\Generator\PhpStanExtensionGenerator;
use Ioweb\M1PhpStanBridge\Generator\PhpStanConfigGenerator;
use Ioweb\M1PhpStanBridge\Generator\StructuralStubGenerator;
use Ioweb\M1PhpStanBridge\Map\AliasMapWriter;
use Ioweb\M1PhpStanBridge\Map\MapBuilder;
use Ioweb\M1PhpStanBridge\Map\MetadataCategoryMapper;
use Ioweb\M1PhpStanBridge\Parser\MetaParser;
use Throwable;

final class GenerateStubsCommand
{
    public function run(array $argv): int
    {
        $options = $this->parseArguments($argv);

        if ($options['help']) {
            $this->writeUsage();

            return 0;
        }

        try {
            $projectRoot = $this->resolveProjectRoot($options['project-root']);
            $metaDirectory = $options['meta-path'] ?? $this->discoverMetaDirectory($projectRoot);

            if ($metaDirectory === null) {
                throw new \RuntimeException(
                    'Unable to find .phpstorm.meta.php metadata. Run n98-magerun dev:ide:phpstorm:meta first.'
                );
            }

            $parser = new MetaParser();
            $builder = new MapBuilder();
            $classMapBuilder = new ClassMapBuilder();
            $classMapGenerator = new ClassMapGenerator();
            $categoryMapper = new MetadataCategoryMapper();
            $mapWriter = new AliasMapWriter();
            $fileWriter = new BridgeFileWriter();
            $structuralStubs = new StructuralStubGenerator();
            $configGenerator = new PhpStanConfigGenerator();
            $extensionGenerator = new PhpStanExtensionGenerator();

            $overrides = $parser->parseDirectory($metaDirectory);
            $maps = $builder->build($overrides);
            $categories = $categoryMapper->fromFactoryMaps($maps['factories']);

            $bridgeDirectory = $projectRoot . DIRECTORY_SEPARATOR . '.m1_phpstan_bridge';
            $generatedDirectory = $bridgeDirectory . DIRECTORY_SEPARATOR . 'generated';

            $mapFiles = [
                'model' => $generatedDirectory . DIRECTORY_SEPARATOR . 'model-map.php',
                'singleton' => $generatedDirectory . DIRECTORY_SEPARATOR . 'singleton-map.php',
                'resource-model' => $generatedDirectory . DIRECTORY_SEPARATOR . 'resource-model-map.php',
                'helper' => $generatedDirectory . DIRECTORY_SEPARATOR . 'helper-map.php',
                'block' => $generatedDirectory . DIRECTORY_SEPARATOR . 'block-map.php',
            ];

            $written = [];
            foreach ($mapFiles as $category => $path) {
                if ($fileWriter->writeIfChanged($path, $mapWriter->render($categories[$category] ?? []))) {
                    $written[] = $path;
                }
            }

            $classMap = $classMapBuilder->build($projectRoot);
            $classMapFile = $generatedDirectory . DIRECTORY_SEPARATOR . 'class-map.php';
            $classMapReportFile = $generatedDirectory . DIRECTORY_SEPARATOR . 'classmap-report.md';
            $classMapAutoloadFile = $bridgeDirectory . DIRECTORY_SEPARATOR . 'classmap-autoload.php';

            if ($fileWriter->writeIfChanged($classMapFile, $classMapGenerator->renderMap($classMap['map']))) {
                $written[] = $classMapFile;
            }

            if ($fileWriter->writeIfChanged(
                $classMapAutoloadFile,
                $classMapGenerator->renderAutoload(
                    $classMapFile,
                    $bridgeDirectory . DIRECTORY_SEPARATOR . 'mage-factories.stub.php'
                )
            )) {
                $written[] = $classMapAutoloadFile;
            }

            if ($fileWriter->writeIfChanged(
                $classMapReportFile,
                $classMapGenerator->renderReport(
                    $classMap['duplicates'],
                    $classMap['skippedUnsafeFiles'],
                    $classMap['skippedReferenceOnlyFiles'],
                    $classMap['scannedFiles'],
                    count($classMap['map'])
                )
            )) {
                $written[] = $classMapReportFile;
            }

            $stubFiles = [
                $bridgeDirectory . DIRECTORY_SEPARATOR . 'mage-factories.stub.php' => $structuralStubs->mageFactories(),
                $bridgeDirectory . DIRECTORY_SEPARATOR . 'magento-core.stub.php' => $structuralStubs->magentoCore(),
                $bridgeDirectory . DIRECTORY_SEPARATOR . 'varien.stub.php' => $structuralStubs->varien(),
            ];

            foreach ($stubFiles as $path => $contents) {
                if ($fileWriter->writeIfChanged($path, $contents)) {
                    $written[] = $path;
                }
            }

            foreach ($extensionGenerator->files($bridgeDirectory) as $path => $contents) {
                if ($fileWriter->writeIfChanged($path, $contents)) {
                    $written[] = $path;
                }
            }

            if ($this->ensureComposerAutoloadDev($projectRoot, $fileWriter)) {
                $written[] = $projectRoot . DIRECTORY_SEPARATOR . 'composer.json';
            }

            $configPath = $bridgeDirectory . DIRECTORY_SEPARATOR . 'phpstan-magento.neon';
            if ($fileWriter->writeIfChanged(
                $configPath,
                $configGenerator->generate($projectRoot, $bridgeDirectory, $mapFiles)
            )) {
                $written[] = $configPath;
            }

            $diagnosticsPath = $generatedDirectory . DIRECTORY_SEPARATOR . 'diagnostics.json';
            $diagnostics = $this->diagnostics($overrides, $categories, $projectRoot);
            if ($fileWriter->writeIfChanged($diagnosticsPath, json_encode($diagnostics, JSON_PRETTY_PRINT) . "\n")) {
                $written[] = $diagnosticsPath;
            }

            $obsoleteGiantStub = $bridgeDirectory . DIRECTORY_SEPARATOR . 'mage.stub.php';
            if (is_file($obsoleteGiantStub) && !unlink($obsoleteGiantStub)) {
                throw new \RuntimeException(sprintf('Unable to remove obsolete generated stub: %s', $obsoleteGiantStub));
            }

            $this->writeSummary($metaDirectory, $categories, $diagnostics, $written);

            if ($options['validate']) {
                return $this->validateGeneratedBridge($projectRoot, $bridgeDirectory, $mapFiles);
            }

            return 0;
        } catch (Throwable $throwable) {
            fwrite(STDERR, $throwable->getMessage() . "\n");

            return 1;
        }
    }

    private function writeUsage(): void
    {
        fwrite(STDERR, "Usage: generate-stubs [project-root] [--meta-path=/path/to/.phpstorm.meta.php] [--validate]\n");
    }

    /**
     * @param array<int, string> $argv
     * @return array{project-root: string|null, meta-path: string|null, validate: bool, help: bool}
     */
    private function parseArguments(array $argv): array
    {
        $options = [
            'project-root' => null,
            'meta-path' => null,
            'validate' => false,
            'help' => false,
        ];

        foreach (array_slice($argv, 1) as $argument) {
            if ($argument === '-h' || $argument === '--help') {
                $options['help'] = true;
                continue;
            }

            if ($argument === '--validate') {
                $options['validate'] = true;
                continue;
            }

            if (strpos($argument, '--meta-path=') === 0) {
                $options['meta-path'] = substr($argument, strlen('--meta-path='));
                continue;
            }

            if ($options['project-root'] === null) {
                $options['project-root'] = $argument;
            }
        }

        return $options;
    }

    private function resolveProjectRoot(?string $projectRoot): string
    {
        $projectRoot ??= getcwd();

        if (basename(rtrim($projectRoot, "\\/")) === '.phpstorm.meta.php') {
            return dirname(rtrim($projectRoot, "\\/"));
        }

        if (!is_dir($projectRoot)) {
            throw new \RuntimeException(sprintf('Project root does not exist: %s', $projectRoot));
        }

        $realPath = realpath($projectRoot);

        return $realPath === false ? $projectRoot : $realPath;
    }

    private function discoverMetaDirectory(string $projectRoot): ?string
    {
        $candidates = [
            $projectRoot . DIRECTORY_SEPARATOR . '.phpstorm.meta.php',
            dirname($projectRoot) . DIRECTORY_SEPARATOR . basename($projectRoot) . '.phpstorm.meta.php',
            getcwd() . DIRECTORY_SEPARATOR . '.phpstorm.meta.php',
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<int, array{target: string, entries: array<string, string>}> $overrides
     * @param array<string, array<string, string>> $categories
     * @return array<string, mixed>
     */
    private function diagnostics(array $overrides, array $categories, string $projectRoot): array
    {
        $duplicates = [];
        $seen = [];

        foreach ($overrides as $override) {
            foreach ($override['entries'] as $alias => $className) {
                $key = $override['target'] . ':' . $alias;
                if (isset($seen[$key]) && $seen[$key] !== $className) {
                    $duplicates[] = [
                        'target' => $override['target'],
                        'alias' => $alias,
                        'first' => $seen[$key],
                        'duplicate' => $className,
                    ];
                }

                $seen[$key] = $className;
            }
        }

        $missingCategories = [];
        foreach (['model', 'singleton', 'resource-model', 'helper'] as $category) {
            if (($categories[$category] ?? []) === []) {
                $missingCategories[] = $category;
            }
        }

        return [
            'aliasCounts' => array_map(static fn (array $aliases): int => count($aliases), $categories),
            'duplicateAliases' => $duplicates,
            'missingMetadataCategories' => $missingCategories,
            'missingExpectedCoreAliases' => $this->missingExpectedCoreAliases($categories),
            'unresolvedClasses' => $this->unresolvedClasses($categories, $projectRoot),
        ];
    }

    /**
     * @param array<string, array<string, string>> $categories
     * @return array<string, string>
     */
    private function missingExpectedCoreAliases(array $categories): array
    {
        $expected = [
            'model' => 'catalog/product',
            'singleton' => 'core/resource',
            'resource-model' => 'sales/order_collection',
            'helper' => 'catalog',
            'block' => 'core/template',
        ];

        $missing = [];
        foreach ($expected as $category => $alias) {
            if (!isset($categories[$category][$alias])) {
                $missing[$category] = $alias;
            }
        }

        return $missing;
    }

    /**
     * @param array<string, array<string, string>> $categories
     * @return array<int, array{category: string, alias: string, class: string}>
     */
    private function unresolvedClasses(array $categories, string $projectRoot): array
    {
        $unresolved = [];
        foreach ($categories as $category => $aliases) {
            foreach ($aliases as $alias => $className) {
                if (!$this->classPathExists($projectRoot, $className)) {
                    $unresolved[] = [
                        'category' => $category,
                        'alias' => $alias,
                        'class' => $className,
                    ];
                }
            }
        }

        return $unresolved;
    }

    private function classPathExists(string $projectRoot, string $className): bool
    {
        $relativePath = str_replace('_', DIRECTORY_SEPARATOR, ltrim($className, '\\')) . '.php';
        $roots = [
            $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'core',
            $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'community',
            $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'local',
            $projectRoot . DIRECTORY_SEPARATOR . 'lib',
        ];

        foreach ($roots as $root) {
            if (is_file($root . DIRECTORY_SEPARATOR . $relativePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, array<string, string>> $categories
     * @param array<string, mixed> $diagnostics
     * @param list<string> $written
     */
    private function writeSummary(string $metaDirectory, array $categories, array $diagnostics, array $written): void
    {
        fwrite(STDOUT, sprintf("Metadata: %s\n", $metaDirectory));
        foreach ($categories as $category => $aliases) {
            fwrite(STDOUT, sprintf("%s aliases: %d\n", $category, count($aliases)));
        }
        fwrite(STDOUT, sprintf("Duplicate aliases: %d\n", count($diagnostics['duplicateAliases'])));
        fwrite(STDOUT, sprintf("Unresolved classes: %d\n", count($diagnostics['unresolvedClasses'])));
        fwrite(STDOUT, sprintf("Files written: %d\n", count($written)));
    }

    /**
     * @param array<string, string> $mapFiles
     */
    private function validateGeneratedBridge(string $projectRoot, string $bridgeDirectory, array $mapFiles): int
    {
        $dumpAutoloadExitCode = $this->dumpComposerAutoload($projectRoot);
        if ($dumpAutoloadExitCode !== 0) {
            return $dumpAutoloadExitCode;
        }

        $phpFiles = array_merge(
            glob($bridgeDirectory . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob($bridgeDirectory . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob($bridgeDirectory . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'PHPStan' . DIRECTORY_SEPARATOR . '*.php') ?: []
        );

        foreach ($phpFiles as $phpFile) {
            passthru(sprintf('php -l %s', escapeshellarg($phpFile)), $exitCode);
            if ($exitCode !== 0) {
                return $exitCode;
            }
        }

        $requiredAliases = [
            'model' => 'catalog/product',
            'singleton' => 'core/resource',
            'resource-model' => 'sales/order_collection',
            'helper' => 'catalog',
        ];

        foreach ($requiredAliases as $category => $alias) {
            $map = is_file($mapFiles[$category]) ? require $mapFiles[$category] : [];
            if (!is_array($map) || !isset($map[$alias])) {
                fwrite(STDERR, sprintf("Missing expected %s alias: %s\n", $category, $alias));

                return 1;
            }
        }

        return $this->runPhpStanSmoke($projectRoot, $bridgeDirectory);
    }

    private function runPhpStanSmoke(string $projectRoot, string $bridgeDirectory): int
    {
        $phpStan = $this->firstExistingFile([
            $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan',
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan',
        ]);

        if ($phpStan === null) {
            fwrite(STDERR, "Unable to find vendor/bin/phpstan for smoke validation.\n");

            return 1;
        }

        $smokeFile = $bridgeDirectory . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR . 'phpstan-smoke.php';
        file_put_contents($smokeFile, <<<'PHP'
<?php

/**
 * @return Mage_Catalog_Model_Product
 */
function m1_phpstan_bridge_smoke_model()
{
    return Mage::getModel('catalog/product');
}

/**
 * @return Mage_Core_Model_Resource
 */
function m1_phpstan_bridge_smoke_singleton()
{
    return Mage::getSingleton('core/resource');
}

/**
 * @return Mage_Sales_Model_Resource_Order_Collection
 */
function m1_phpstan_bridge_smoke_resource_model()
{
    return Mage::getResourceModel('sales/order_collection');
}

/**
 * @return Mage_Catalog_Helper_Data
 */
function m1_phpstan_bridge_smoke_helper()
{
    return Mage::helper('catalog');
}

/**
 * @return Mage_Core_Block_Template
 */
function m1_phpstan_bridge_smoke_block_singleton()
{
    return Mage::getBlockSingleton('core/template');
}

/**
 * @return Mage_Core_Block_Template
 */
function m1_phpstan_bridge_smoke_layout_block()
{
    $layout = new Mage_Core_Model_Layout();

    return $layout->createBlock('core/template');
}

$product = Mage::getModel('catalog/product');
$resource = Mage::getSingleton('core/resource');
$orderCollection = Mage::getResourceModel('sales/order_collection');
$helper = Mage::helper('catalog');
$blockSingleton = Mage::getBlockSingleton('core/template');
$layout = new Mage_Core_Model_Layout();
$createdBlock = $layout->createBlock('core/template');

PHP);

        $command = sprintf(
            'php %s analyse %s --configuration=%s --level=0 --no-progress --error-format=raw --memory-limit=1G',
            escapeshellarg($phpStan),
            escapeshellarg($smokeFile),
            escapeshellarg($bridgeDirectory . DIRECTORY_SEPARATOR . 'phpstan-magento.neon')
        );

        exec($command . ' 2>&1', $output, $exitCode);
        $outputText = implode("\n", $output);

        if ($exitCode !== 0) {
            fwrite(STDERR, "PHPStan smoke validation failed.\n");
            fwrite(STDERR, $outputText . "\n");

            return $exitCode;
        }

        fwrite(STDOUT, "PHPStan smoke validation passed.\n");

        return 0;
    }

    /**
     * @param list<string> $paths
     */
    private function firstExistingFile(array $paths): ?string
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function ensureComposerAutoloadDev(string $projectRoot, BridgeFileWriter $fileWriter): bool
    {
        $composerJsonPath = $projectRoot . DIRECTORY_SEPARATOR . 'composer.json';
        if (!is_file($composerJsonPath)) {
            return false;
        }

        $contents = file_get_contents($composerJsonPath);
        if ($contents === false) {
            throw new \RuntimeException(sprintf('Unable to read composer.json: %s', $composerJsonPath));
        }

        $composer = json_decode($contents, true);
        if (!is_array($composer)) {
            throw new \RuntimeException(sprintf('Unable to parse composer.json: %s', $composerJsonPath));
        }

        $composer['autoload-dev'] ??= [];
        $composer['autoload-dev']['psr-4'] ??= [];
        $composer['autoload-dev']['psr-4']['M1PhpStanBridgeGenerated\\'] = '.m1_phpstan_bridge/src/';

        $encoded = json_encode(
            $composer,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($encoded === false) {
            throw new \RuntimeException(sprintf('Unable to encode composer.json: %s', $composerJsonPath));
        }

        return $fileWriter->writeIfChanged($composerJsonPath, $encoded . "\n");
    }

    private function dumpComposerAutoload(string $projectRoot): int
    {
        $composerCommand = getenv('M1_PHPSTAN_BRIDGE_COMPOSER_COMMAND') ?: null;
        if (is_string($composerCommand) && $composerCommand !== '') {
            passthru(sprintf('%s dump-autoload --working-dir=%s', $composerCommand, escapeshellarg($projectRoot)), $exitCode);

            return $exitCode;
        }

        $projectComposer = $projectRoot . DIRECTORY_SEPARATOR . 'composer.phar';
        if (is_file($projectComposer)) {
            passthru(sprintf('php %s dump-autoload --working-dir=%s', escapeshellarg($projectComposer), escapeshellarg($projectRoot)), $exitCode);

            return $exitCode;
        }

        passthru(sprintf('composer dump-autoload --working-dir=%s', escapeshellarg($projectRoot)), $exitCode);

        return $exitCode;
    }
}
