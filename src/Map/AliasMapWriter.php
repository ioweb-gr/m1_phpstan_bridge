<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Map;

final class AliasMapWriter
{
    /**
     * @param array<string, string> $aliases
     */
    public function render(array $aliases): string
    {
        ksort($aliases, SORT_STRING);

        $lines = [
            '<?php',
            '',
            'return [',
        ];

        foreach ($aliases as $alias => $className) {
            $lines[] = sprintf(
                "    '%s' => \\%s::class,",
                $this->escapeSingleQuoted($alias),
                ltrim($className, '\\')
            );
        }

        $lines[] = '];';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function escapeSingleQuoted(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
