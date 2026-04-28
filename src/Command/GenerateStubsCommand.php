<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Command;

use Ioweb\M1PhpStanBridge\Generator\StubGenerator;
use Ioweb\M1PhpStanBridge\Map\MapBuilder;
use Ioweb\M1PhpStanBridge\Parser\MetaParser;
use Throwable;

final class GenerateStubsCommand
{
    public function run(array $argv): int
    {
        $metaDirectory = $argv[1] ?? null;
        $outputFile = $argv[2] ?? null;

        if ($metaDirectory === null || in_array($metaDirectory, ['-h', '--help'], true)) {
            $this->writeUsage();

            return $metaDirectory === null ? 1 : 0;
        }

        try {
            $parser = new MetaParser();
            $builder = new MapBuilder();
            $generator = new StubGenerator();

            if ($outputFile === null) {
                $outputFile = $this->defaultOutputFile($metaDirectory);
            }

            $maps = $builder->build($parser->parseDirectory($metaDirectory));
            $stub = $generator->generate($maps['factories'], $maps['methods']);

            $outputDirectory = dirname($outputFile);
            if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0777, true) && !is_dir($outputDirectory)) {
                throw new \RuntimeException(sprintf('Unable to create output directory: %s', $outputDirectory));
            }

            if (file_put_contents($outputFile, $stub) === false) {
                throw new \RuntimeException(sprintf('Unable to write output file: %s', $outputFile));
            }

            fwrite(STDOUT, sprintf("Generated %s\n", $outputFile));

            return 0;
        } catch (Throwable $throwable) {
            fwrite(STDERR, $throwable->getMessage() . "\n");

            return 1;
        }
    }

    private function writeUsage(): void
    {
        fwrite(STDERR, "Usage: generate-stubs <meta-directory> [output-file]\n");
    }

    private function defaultOutputFile(string $metaDirectory): string
    {
        $normalizedMetaDirectory = rtrim($metaDirectory, "\\/");
        $targetRoot = basename($normalizedMetaDirectory) === '.phpstorm.meta.php'
            ? dirname($normalizedMetaDirectory)
            : getcwd();

        return $targetRoot
            . DIRECTORY_SEPARATOR
            . '.m1_phpstan_bridge'
            . DIRECTORY_SEPARATOR
            . 'mage.stub.php';
    }
}
