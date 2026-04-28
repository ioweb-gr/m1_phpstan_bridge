<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Generator;

final class StubGenerator
{
    /**
     * @param array<string, array<string, string>> $factories
     * @param array<string, array<string, string>> $methods
     */
    public function generate(array $factories, array $methods): string
    {
        $mageMethods = $methods['Mage'] ?? [];
        unset($methods['Mage']);

        return implode("\n", [
            '<?php',
            '',
            $this->generateMageStub($factories, $mageMethods),
            '',
            $this->generateClassStubs($methods),
        ]);
    }

    /**
     * @param array<string, array<string, string>> $factories
     */
    private function generateMageStub(array $factories, array $methods): string
    {
        $lines = [
            $this->generateMageDocBlock($factories),
            'class Mage',
            '{',
        ];

        foreach ($methods as $methodName => $returnType) {
            $lines[] = '    /**';
            $lines[] = sprintf('     * @return \\%s', $returnType);
            $lines[] = '     */';
            $lines[] = sprintf('    public function %s() {}', $methodName);
            $lines[] = '';
        }

        if (end($lines) === '') {
            array_pop($lines);
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, array<string, string>> $factories
     */
    private function generateMageDocBlock(array $factories): string
    {
        $lines = ['/**'];

        foreach ($factories as $target => $entries) {
            $methodName = substr($target, strlen('Mage::'));
            foreach ($entries as $alias => $className) {
                $lines[] = sprintf(
                    " * @method static \\%s %s('%s')",
                    $className,
                    $methodName,
                    $this->escapeSingleQuoted($alias)
                );
            }
        }

        $lines[] = ' */';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, array<string, string>> $methods
     */
    private function generateClassStubs(array $methods): string
    {
        $chunks = [];

        foreach ($methods as $className => $classMethods) {
            $lines = [
                sprintf('class %s', $className),
                '{',
            ];

            foreach ($classMethods as $methodName => $returnType) {
                $lines[] = '    /**';
                $lines[] = sprintf('     * @return \\%s', $returnType);
                $lines[] = '     */';
                $lines[] = sprintf('    public function %s() {}', $methodName);
                $lines[] = '';
            }

            if (end($lines) === '') {
                array_pop($lines);
            }

            $lines[] = '}';
            $chunks[] = implode("\n", $lines);
        }

        return implode("\n\n", $chunks) . "\n";
    }

    private function escapeSingleQuoted(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
