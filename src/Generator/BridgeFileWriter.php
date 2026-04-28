<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Generator;

final class BridgeFileWriter
{
    public function writeIfChanged(string $path, string $contents): bool
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory: %s', $directory));
        }

        if (is_file($path) && file_get_contents($path) === $contents) {
            return false;
        }

        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException(sprintf('Unable to write file: %s', $path));
        }

        return true;
    }
}
