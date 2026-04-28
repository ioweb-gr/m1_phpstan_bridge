<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Generator;

final class StructuralStubGenerator
{
    /**
     * @param array<string, string> $classMap
     * @return array<string, string>
     */
    public function files(string $bridgeDirectory, string $projectRoot, array $classMap): array
    {
        $files = [];

        if (is_file($projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php')) {
            $files[$this->stubPath($bridgeDirectory, 'Mage')] = $this->renderClass('Mage', $this->methodOverrides()['Mage']);
        }

        if (isset($classMap['Varien_Object'])) {
            $files[$this->stubPath($bridgeDirectory, 'Varien_Object')] = $this->renderClass(
                'Varien_Object',
                $this->methodOverrides()['Varien_Object']
            );
        }

        ksort($files, SORT_STRING);

        return $files;
    }

    /**
     * @return array<string, string>
     */
    public function namespacesByFile(string $bridgeDirectory): array
    {
        return [
            $this->stubPath($bridgeDirectory, 'Mage') => 'Mage',
            $this->stubPath($bridgeDirectory, 'Varien_Object') => 'Varien_Object',
        ];
    }

    /**
     * @param array<string, string> $methods
     */
    private function renderClass(string $className, array $methods): string
    {
        ksort($methods, SORT_STRING);

        $lines = [
            '<?php',
            '',
            'class ' . $className,
            '{',
        ];

        foreach ($methods as $method) {
            foreach (explode("\n", $method) as $methodLine) {
                $lines[] = '    ' . $methodLine;
            }

            $lines[] = '';
        }

        if (end($lines) === '') {
            array_pop($lines);
        }

        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function stubPath(string $bridgeDirectory, string $className): string
    {
        return $bridgeDirectory
            . DIRECTORY_SEPARATOR
            . 'stubs'
            . DIRECTORY_SEPARATOR
            . str_replace('_', DIRECTORY_SEPARATOR, $className)
            . '.stub.php';
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
