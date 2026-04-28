<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\PHPStan;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

abstract class AbstractMageFactoryDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    /** @var array<string, class-string> */
    private array $map;

    public function __construct(string $mapFile)
    {
        $map = is_file($mapFile) ? require $mapFile : [];
        $this->map = is_array($map) ? $map : [];
    }

    public function getClass(): string
    {
        return 'Mage';
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === $this->methodName();
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        if (!isset($methodCall->getArgs()[0])) {
            return $this->fallbackType();
        }

        $argumentType = $scope->getType($methodCall->getArgs()[0]->value);
        if (!$argumentType instanceof ConstantStringType) {
            return $this->fallbackType();
        }

        $alias = $argumentType->getValue();
        if (!isset($this->map[$alias]) || !is_string($this->map[$alias]) || $this->map[$alias] === '') {
            return $this->fallbackType();
        }

        return new ObjectType(ltrim($this->map[$alias], '\\'));
    }

    abstract protected function methodName(): string;

    protected function fallbackType(): Type
    {
        return new MixedType();
    }
}
