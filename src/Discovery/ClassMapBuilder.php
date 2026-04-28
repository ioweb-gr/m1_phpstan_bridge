<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Discovery;

final class ClassMapBuilder
{
    /**
     * @return array{
     *     map: array<string, string>,
     *     duplicates: array<string, list<string>>,
     *     scannedFiles: int,
     *     skippedUnsafeFiles: list<string>
     * }
     */
    public function build(string $projectRoot, bool $includeZend = true): array
    {
        $roots = [
            $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'core',
            $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'community',
            $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'local',
            $projectRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Varien',
        ];

        if ($includeZend) {
            $roots[] = $projectRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Zend';
        }

        $found = [];
        $scannedFiles = 0;
        $skippedUnsafeFiles = [];

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            foreach ($this->phpFiles($root) as $file) {
                if ($this->isExcludedPath($file)) {
                    continue;
                }

                $scannedFiles++;
                if (!$this->isAutoloadSafe($file)) {
                    $skippedUnsafeFiles[] = $file;
                    continue;
                }

                foreach ($this->declaredSymbols($file) as $className) {
                    $found[$className] ??= [];
                    $found[$className][] = $file;
                }

                $fallbackClass = $this->fallbackClassName($projectRoot, $file);
                if ($fallbackClass !== null) {
                    $found[$fallbackClass] ??= [];
                    $found[$fallbackClass][] = $file;
                }
            }
        }

        $map = [];
        $duplicates = [];

        foreach ($found as $className => $files) {
            $files = array_values(array_unique($files));
            sort($files, SORT_STRING);
            $map[$className] = $files[0];

            if (count($files) > 1) {
                $duplicates[$className] = $files;
            }
        }

        ksort($map, SORT_STRING);
        ksort($duplicates, SORT_STRING);

        return [
            'map' => $map,
            'duplicates' => $duplicates,
            'scannedFiles' => $scannedFiles,
            'skippedUnsafeFiles' => $skippedUnsafeFiles,
        ];
    }

    /**
     * @return iterable<string>
     */
    private function phpFiles(string $root): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                yield $file->getPathname();
            }
        }
    }

    /**
     * @return list<string>
     */
    private function declaredSymbols(string $file): array
    {
        $code = file_get_contents($file);
        if ($code === false) {
            return [];
        }

        $tokens = token_get_all($code);
        $namespace = '';
        $symbols = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->readNamespace($tokens, $i + 1);
                continue;
            }

            if (!in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                continue;
            }

            if ($token[0] === T_CLASS && $this->previousMeaningfulTokenIsNew($tokens, $i - 1)) {
                continue;
            }

            $name = $this->readSymbolName($tokens, $i + 1);
            if ($name === null) {
                continue;
            }

            $symbols[] = ltrim($namespace . '\\' . $name, '\\');
        }

        return array_values(array_unique($symbols));
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function readNamespace(array $tokens, int $offset): string
    {
        $parts = [];
        $count = count($tokens);

        for ($i = $offset; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === ';' || $token === '{') {
                break;
            }

            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                $parts[] = $token[1];
            }
        }

        return implode('', $parts);
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function readSymbolName(array $tokens, int $offset): ?string
    {
        $count = count($tokens);
        for ($i = $offset; $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_STRING) {
                return $token[1];
            }

            if ($token === '{') {
                return null;
            }
        }

        return null;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function previousMeaningfulTokenIsNew(array $tokens, int $offset): bool
    {
        for ($i = $offset; $i >= 0; $i--) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return is_array($token) && $token[0] === T_NEW;
        }

        return false;
    }

    private function fallbackClassName(string $projectRoot, string $file): ?string
    {
        $normalizedFile = str_replace('\\', '/', $file);
        $normalizedRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');

        $prefixes = [
            $normalizedRoot . '/app/code/core/',
            $normalizedRoot . '/app/code/community/',
            $normalizedRoot . '/app/code/local/',
            $normalizedRoot . '/lib/',
        ];

        foreach ($prefixes as $prefix) {
            if (strpos($normalizedFile, $prefix) !== 0) {
                continue;
            }

            $relative = substr($normalizedFile, strlen($prefix));
            if (substr($relative, -4) !== '.php') {
                return null;
            }

            return str_replace('/', '_', substr($relative, 0, -4));
        }

        return null;
    }

    private function isExcludedPath(string $file): bool
    {
        $normalized = '/' . trim(str_replace('\\', '/', $file), '/') . '/';

        foreach (['/Test/', '/test/', '/tests/', '/tmp/', '/vendor/', '/node_modules/'] as $segment) {
            if (strpos($normalized, $segment) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isAutoloadSafe(string $file): bool
    {
        $code = file_get_contents($file);
        if ($code === false) {
            return false;
        }

        $tokens = token_get_all($code);

        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if (is_array($token) && in_array($token[0], [T_DECLARE, T_NAMESPACE, T_USE, T_ABSTRACT, T_FINAL, T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                return true;
            }

            return false;
        }

        return true;
    }
}
