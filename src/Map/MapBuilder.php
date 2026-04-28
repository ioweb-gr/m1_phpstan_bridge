<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Map;

final class MapBuilder
{
    private const FACTORY_TARGETS = [
        'Mage::getModel',
        'Mage::helper',
        'Mage::getResourceModel',
        'Mage::getSingleton',
        'Mage::getBlockSingleton',
        'Mage_Core_Model_Layout::createBlock',
    ];

    /**
     * @param array<int, array{target: string, entries: array<string, string>}> $overrides
     * @return array{
     *     factories: array<string, array<string, string>>,
     *     methods: array<string, array<string, string>>
     * }
     */
    public function build(array $overrides): array
    {
        $factories = [];
        $methods = [];

        foreach ($overrides as $override) {
            $target = ltrim($override['target'], '\\');

            if (in_array($target, self::FACTORY_TARGETS, true)) {
                $factories[$target] ??= [];
                foreach ($override['entries'] as $alias => $className) {
                    $factories[$target][$alias] = $this->normalizeClassName($className);
                }
                continue;
            }

            $methodTarget = $this->parseMethodTarget($target);
            if ($methodTarget === null) {
                continue;
            }

            [$className, $methodName] = $methodTarget;
            $methods[$className] ??= [];

            foreach ($override['entries'] as $returnType) {
                $methods[$className][$methodName] = $this->normalizeClassName($returnType);
            }
        }

        $this->sortNestedMap($factories);
        $this->sortNestedMap($methods);

        return [
            'factories' => $factories,
            'methods' => $methods,
        ];
    }

    /**
     * @return array{string, string}|null
     */
    private function parseMethodTarget(string $target): ?array
    {
            $separator = strpos($target, '::') !== false ? '::' : (strpos($target, '->') !== false ? '->' : null);
        if ($separator === null) {
            return null;
        }

        [$className, $methodName] = explode($separator, $target, 2);
        if ($className === '' || $className[0] === '$' || $methodName === '') {
            return null;
        }

        return [$this->normalizeClassName($className), $methodName];
    }

    private function normalizeClassName(string $className): string
    {
        return ltrim($className, '\\');
    }

    /**
     * @param array<string, array<string, string>> $map
     */
    private function sortNestedMap(array &$map): void
    {
        ksort($map, SORT_STRING);
        foreach ($map as &$entries) {
            ksort($entries, SORT_STRING);
        }
    }
}
