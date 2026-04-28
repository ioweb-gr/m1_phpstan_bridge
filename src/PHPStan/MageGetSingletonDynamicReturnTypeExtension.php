<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\PHPStan;

final class MageGetSingletonDynamicReturnTypeExtension extends AbstractMageFactoryDynamicReturnTypeExtension
{
    protected function methodName(): string
    {
        return 'getSingleton';
    }
}
