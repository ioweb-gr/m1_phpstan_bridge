<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Generator;

final class ClassMapGenerator
{
    /**
     * @param array<string, string> $classMap
     */
    public function renderMap(array $classMap): string
    {
        ksort($classMap, SORT_STRING);

        $lines = [
            '<?php',
            '',
            'return [',
        ];

        foreach ($classMap as $className => $file) {
            $lines[] = sprintf(
                "    '%s' => '%s',",
                $this->escapeSingleQuoted($className),
                $this->escapeSingleQuoted($file)
            );
        }

        $lines[] = '];';
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function renderAutoload(string $classMapFile, string $mageStubFile): string
    {
        $escapedMapFile = $this->escapeSingleQuoted($classMapFile);
        $escapedMageStubFile = $this->escapeSingleQuoted($mageStubFile);

        return <<<PHP
<?php

\$classMap = is_file('{$escapedMapFile}') ? require '{$escapedMapFile}' : [];
\$classMap = is_array(\$classMap) ? \$classMap : [];

spl_autoload_register(static function (string \$class) use (\$classMap): void {
    if (!isset(\$classMap[\$class]) || !is_file(\$classMap[\$class])) {
        return;
    }

    require_once \$classMap[\$class];
});

spl_autoload_register(static function (string \$class): void {
    if (\$class !== 'Mage' || class_exists('Mage', false)) {
        return;
    }

    require_once '{$escapedMageStubFile}';
}, true, true);

PHP;
    }

    /**
     * @param array<string, list<string>> $duplicates
     * @param list<string> $skippedUnsafeFiles
     */
    public function renderReport(array $duplicates, array $skippedUnsafeFiles, int $scannedFiles, int $classCount): string
    {
        $lines = [
            '# Magento Classmap Report',
            '',
            sprintf('- Scanned PHP files: %d', $scannedFiles),
            sprintf('- Classes/interfaces/traits mapped: %d', $classCount),
            sprintf('- Duplicate/conflicting symbols: %d', count($duplicates)),
            sprintf('- Files skipped because they contain executable top-level code: %d', count($skippedUnsafeFiles)),
            '',
        ];

        if ($duplicates === [] && $skippedUnsafeFiles === []) {
            $lines[] = 'No duplicate symbols found.';
            $lines[] = '';

            return implode("\n", $lines);
        }

        if ($duplicates !== []) {
            $lines[] = '## Duplicates';
            $lines[] = '';

            foreach ($duplicates as $className => $files) {
                $lines[] = sprintf('### `%s`', $className);
                foreach ($files as $file) {
                    $lines[] = sprintf('- `%s`', $file);
                }
                $lines[] = '';
            }
        }

        if ($skippedUnsafeFiles !== []) {
            $lines[] = '## Skipped Unsafe Autoload Files';
            $lines[] = '';

            foreach ($skippedUnsafeFiles as $file) {
                $lines[] = sprintf('- `%s`', $file);
            }
        }

        return implode("\n", $lines);
    }

    private function escapeSingleQuoted(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
