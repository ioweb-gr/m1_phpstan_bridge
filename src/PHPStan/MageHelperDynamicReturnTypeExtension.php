<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\PHPStan;

final class MageHelperDynamicReturnTypeExtension extends AbstractMageFactoryDynamicReturnTypeExtension
{
    protected function methodName(): string
    {
        return 'helper';
    }
}
