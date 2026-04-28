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
            'class Mage',
            '{',
        ];

        foreach ($this->factoryMethodOrder() as $target => $parameterName) {
            if (!isset($factories[$target])) {
                continue;
            }

            array_push(
                $lines,
                ...$this->generateFactoryMethod(substr($target, strlen('Mage::')), $parameterName, $factories[$target])
            );
            $lines[] = '';
        }

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
     * @return array<string, string>
     */
    private function factoryMethodOrder(): array
    {
        return [
            'Mage::getModel' => 'modelClass',
            'Mage::getResourceModel' => 'modelClass',
            'Mage::getSingleton' => 'modelClass',
            'Mage::helper' => 'name',
        ];
    }

    /**
     * @param array<string, string> $entries
     * @return list<string>
     */
    private function generateFactoryMethod(string $methodName, string $parameterName, array $entries): array
    {
        $lines = [
            '    /**',
            sprintf('     * @param string $%s', $parameterName),
            '     * @param mixed $arguments',
        ];

        array_push($lines, ...$this->generateConditionalReturnTypeLines($parameterName, $entries));

        $lines[] = '     */';
        $lines[] = sprintf('    public static function %s($%s = \'\', $arguments = []) {}', $methodName, $parameterName);

        return $lines;
    }

    /**
     * @param array<string, string> $entries
     */
    private function generateConditionalReturnTypeLines(string $parameterName, array $entries): array
    {
        $lines = ['     * @phpstan-return ('];

        foreach ($entries as $alias => $className) {
            $lines[] = sprintf(
                "     *     \$%s is '%s' ? \\%s : (",
                $parameterName,
                $this->escapeSingleQuoted($alias),
                $className
            );
        }

        $lines[] = '     *     object';
        $lines[] = '     * ' . str_repeat(')', count($entries) + 1);

        return $lines;
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
