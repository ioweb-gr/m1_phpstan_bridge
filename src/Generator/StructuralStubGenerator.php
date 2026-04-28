<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Generator;

final class StructuralStubGenerator
{
    /**
     * @param array<string, array{
     *     kind: 'class'|'interface',
     *     extends: list<string>,
     *     implements: list<string>,
     *     methods: array<string, string>
     * }> $classes
     */
    public function mageFactories(array $classes): string
    {
        return $this->generate($this->onlyClasses($classes, static fn (string $className): bool => $className === 'Mage'));
    }

    /**
     * @param array<string, array{
     *     kind: 'class'|'interface',
     *     extends: list<string>,
     *     implements: list<string>,
     *     methods: array<string, string>
     * }> $classes
     */
    public function magentoCore(array $classes): string
    {
        return $this->generate($this->onlyClasses(
            $classes,
            static fn (string $className): bool => str_starts_with($className, 'Mage_')
        ));
    }

    /**
     * @param array<string, array{
     *     kind: 'class'|'interface',
     *     extends: list<string>,
     *     implements: list<string>,
     *     methods: array<string, string>
     * }> $classes
     */
    public function varien(array $classes): string
    {
        return $this->generate($this->onlyClasses(
            $classes,
            static fn (string $className): bool => str_starts_with($className, 'Varien_')
        ));
    }

    /**
     * @param array<string, array{
     *     kind: 'class'|'interface',
     *     extends: list<string>,
     *     implements: list<string>,
     *     methods: array<string, string>
     * }> $classes
     */
    private function generate(array $classes): string
    {
        foreach ($this->methodOverrides() as $className => $methods) {
            if (!isset($classes[$className])) {
                continue;
            }

            foreach ($methods as $methodName => $method) {
                $classes[$className]['methods'][$methodName] = $method;
            }
        }

        ksort($classes, SORT_STRING);

        $chunks = ['<?php', ''];
        foreach ($classes as $className => $class) {
            $chunks[] = $this->renderClass($className, $class);
            $chunks[] = '';
        }

        return rtrim(implode("\n", $chunks)) . "\n";
    }

    /**
     * @param array<string, array{
     *     kind: 'class'|'interface',
     *     extends: list<string>,
     *     implements: list<string>,
     *     methods: array<string, string>
     * }> $classes
     * @return array<string, array{
     *     kind: 'class'|'interface',
     *     extends: list<string>,
     *     implements: list<string>,
     *     methods: array<string, string>
     * }>
     */
    private function onlyClasses(array $classes, callable $predicate): array
    {
        return array_filter($classes, $predicate, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param array{
     *     kind: 'class'|'interface',
     *     extends: list<string>,
     *     implements: list<string>,
     *     methods: array<string, string>
     * } $class
     */
    private function renderClass(string $className, array $class): string
    {
        $declaration = $class['kind'] . ' ' . $className;

        if ($class['extends'] !== []) {
            $declaration .= ' extends ' . implode(', ', $class['extends']);
        }

        if ($class['kind'] === 'class' && $class['implements'] !== []) {
            $declaration .= ' implements ' . implode(', ', $class['implements']);
        }

        if ($class['methods'] === []) {
            return $declaration . ' {}';
        }

        ksort($class['methods'], SORT_STRING);

        $lines = [
            $declaration,
            '{',
        ];

        foreach ($class['methods'] as $method) {
            foreach (explode("\n", $method) as $methodLine) {
                $lines[] = '    ' . $methodLine;
            }

            $lines[] = '';
        }

        if (end($lines) === '') {
            array_pop($lines);
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function methodOverrides(): array
    {
        return [
            'Mage' => [
                'getBlockSingleton' => <<<'PHP'
/**
 * @param string $blockClass
 * @param mixed $arguments
 * @return object|null
 */
public static function getBlockSingleton($blockClass = '', $arguments = []) {}
PHP,
                'getModel' => <<<'PHP'
/**
 * @param string $modelClass
 * @param mixed $arguments
 * @return object|null
 */
public static function getModel($modelClass = '', $arguments = []) {}
PHP,
                'getResourceModel' => <<<'PHP'
/**
 * @param string $modelClass
 * @param mixed $arguments
 * @return object|null
 */
public static function getResourceModel($modelClass, $arguments = []) {}
PHP,
                'getSingleton' => <<<'PHP'
/**
 * @param string $modelClass
 * @param mixed $arguments
 * @return object|null
 */
public static function getSingleton($modelClass = '', $arguments = []) {}
PHP,
                'helper' => <<<'PHP'
/**
 * @param string $name
 * @return object
 */
public static function helper($name) {}
PHP,
                'throwException' => <<<'PHP'
/**
 * @param string $message
 * @return never
 */
public static function throwException($message) {}
PHP,
            ],
            'Varien_Object' => [
                'addData' => <<<'PHP'
/**
 * @param array<string, mixed> $arr
 * @return $this
 */
public function addData(array $arr) {}
PHP,
                'getData' => <<<'PHP'
/**
 * @param string $key
 * @param mixed $index
 * @return mixed
 */
public function getData($key = '', $index = null) {}
PHP,
                'setData' => <<<'PHP'
/**
 * @param string|array<string, mixed> $key
 * @param mixed $value
 * @return $this
 */
public function setData($key, $value = null) {}
PHP,
            ],
        ];
    }
}
