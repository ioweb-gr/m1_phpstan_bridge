<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Generator;

final class PhpStanExtensionGenerator
{
    /**
     * @return array<string, string>
     */
    public function files(string $bridgeDirectory): array
    {
        $sourceDirectory = $bridgeDirectory . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'PHPStan';

        return [
            $bridgeDirectory . DIRECTORY_SEPARATOR . 'autoload.php' => $this->autoload(),
            $sourceDirectory . DIRECTORY_SEPARATOR . 'AbstractMageFactoryDynamicReturnTypeExtension.php' => $this->abstractExtension(),
            $sourceDirectory . DIRECTORY_SEPARATOR . 'MageLayoutCreateBlockDynamicReturnTypeExtension.php' => $this->layoutCreateBlockExtension(),
            $sourceDirectory . DIRECTORY_SEPARATOR . 'MageGetModelDynamicReturnTypeExtension.php' => $this->concreteExtension('MageGetModelDynamicReturnTypeExtension', 'getModel'),
            $sourceDirectory . DIRECTORY_SEPARATOR . 'MageGetSingletonDynamicReturnTypeExtension.php' => $this->concreteExtension('MageGetSingletonDynamicReturnTypeExtension', 'getSingleton'),
            $sourceDirectory . DIRECTORY_SEPARATOR . 'MageGetResourceModelDynamicReturnTypeExtension.php' => $this->concreteExtension('MageGetResourceModelDynamicReturnTypeExtension', 'getResourceModel'),
            $sourceDirectory . DIRECTORY_SEPARATOR . 'MageHelperDynamicReturnTypeExtension.php' => $this->concreteExtension('MageHelperDynamicReturnTypeExtension', 'helper'),
            $sourceDirectory . DIRECTORY_SEPARATOR . 'MageGetBlockSingletonDynamicReturnTypeExtension.php' => $this->concreteExtension('MageGetBlockSingletonDynamicReturnTypeExtension', 'getBlockSingleton'),
        ];
    }

    private function autoload(): string
    {
        return <<<'PHP'
<?php

spl_autoload_register(static function (string $class): void {
    $prefix = 'M1PhpStanBridgeGenerated\\PHPStan\\';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = __DIR__ . '/src/PHPStan/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

PHP;
    }

    private function abstractExtension(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace M1PhpStanBridgeGenerated\PHPStan;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
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
        $constantStrings = $argumentType->getConstantStrings();
        if ($constantStrings === []) {
            return $this->fallbackType();
        }

        $alias = $constantStrings[0]->getValue();
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

PHP;
    }

    private function concreteExtension(string $className, string $methodName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace M1PhpStanBridgeGenerated\\PHPStan;

final class {$className} extends AbstractMageFactoryDynamicReturnTypeExtension
{
    protected function methodName(): string
    {
        return '{$methodName}';
    }
}

PHP;
    }

    private function layoutCreateBlockExtension(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace M1PhpStanBridgeGenerated\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class MageLayoutCreateBlockDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
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
        return 'Mage_Core_Model_Layout';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'createBlock';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        if (!isset($methodCall->getArgs()[0])) {
            return new MixedType();
        }

        $argumentType = $scope->getType($methodCall->getArgs()[0]->value);
        $constantStrings = $argumentType->getConstantStrings();
        if ($constantStrings === []) {
            return new MixedType();
        }

        $alias = $constantStrings[0]->getValue();
        if (!isset($this->map[$alias]) || !is_string($this->map[$alias]) || $this->map[$alias] === '') {
            return new MixedType();
        }

        return new ObjectType(ltrim($this->map[$alias], '\\'));
    }
}

PHP;
    }
}
