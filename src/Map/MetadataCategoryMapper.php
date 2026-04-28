<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Map;

final class MetadataCategoryMapper
{
    private const TARGET_TO_CATEGORY = [
        'Mage::getModel' => 'model',
        'Mage::getSingleton' => 'singleton',
        'Mage::getResourceModel' => 'resource-model',
        'Mage::helper' => 'helper',
        'Mage::getBlockSingleton' => 'block',
        'Mage_Core_Model_Layout::createBlock' => 'block',
    ];

    /**
     * @param array<string, array<string, string>> $factories
     * @return array<string, array<string, string>>
     */
    public function fromFactoryMaps(array $factories): array
    {
        $categories = [
            'model' => [],
            'singleton' => [],
            'resource-model' => [],
            'helper' => [],
            'block' => [],
        ];

        foreach (self::TARGET_TO_CATEGORY as $target => $category) {
            if (!isset($factories[$target])) {
                continue;
            }

            $categories[$category] = $factories[$target];
            ksort($categories[$category], SORT_STRING);
        }

        return $categories;
    }
}
