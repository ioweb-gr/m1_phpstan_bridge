<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Discovery;

use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\UnionType;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

final class StructuralClassParser
{
    /**
     * @param array<string, string> $classFiles
     * @return array<string, array{
     *     kind: 'class'|'interface',
     *     extends: list<string>,
     *     implements: list<string>,
     *     methods: array<string, string>
     * }>
     */
    public function parse(array $classFiles): array
    {
        $classes = [];

        foreach ($classFiles as $className => $file) {
            if (!is_file($file)) {
                continue;
            }

            $parsed = $this->parseFile($file);
            if (isset($parsed[$className])) {
                $classes[$className] = $parsed[$className];
            }
        }

        ksort($classes, SORT_STRING);

        return $classes;
    }

    /**
     * @return array<string, array{
     *     kind: 'class'|'interface',
     *     extends: list<string>,
     *     implements: list<string>,
     *     methods: array<string, string>
     * }>
     */
    private function parseFile(string $file): array
    {
        $code = file_get_contents($file);
        if ($code === false) {
            return [];
        }

        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        try {
            $ast = $parser->parse($code);
        } catch (Error) {
            return [];
        }

        if ($ast === null) {
            return [];
        }

        $classes = [];
        $nodeFinder = new NodeFinder();
        $prettyPrinter = new Standard();

        foreach ($nodeFinder->findInstanceOf($ast, Class_::class) as $class) {
            if (!$class instanceof Class_ || $class->name === null) {
                continue;
            }

            $className = ltrim($this->namespacePrefix($class) . $class->name->toString(), '\\');
            $classes[$className] = [
                'kind' => 'class',
                'extends' => $class->extends === null ? [] : [$class->extends->toString()],
                'implements' => array_map(
                    static fn (Node\Name $name): string => $name->toString(),
                    $class->implements
                ),
                'methods' => $this->methods($class->getMethods(), false, $prettyPrinter),
            ];
        }

        foreach ($nodeFinder->findInstanceOf($ast, Interface_::class) as $interface) {
            if (!$interface instanceof Interface_) {
                continue;
            }

            $className = ltrim($this->namespacePrefix($interface) . $interface->name->toString(), '\\');
            $classes[$className] = [
                'kind' => 'interface',
                'extends' => array_map(
                    static fn (Node\Name $name): string => $name->toString(),
                    $interface->extends
                ),
                'implements' => [],
                'methods' => $this->methods($interface->getMethods(), true, $prettyPrinter),
            ];
        }

        return $classes;
    }

    /**
     * @param list<ClassMethod> $methods
     * @return array<string, string>
     */
    private function methods(array $methods, bool $interfaceMethod, Standard $prettyPrinter): array
    {
        $rendered = [];

        foreach ($methods as $method) {
            if (!$interfaceMethod && $method->isPrivate()) {
                continue;
            }

            $rendered[$method->name->toString()] = $this->renderMethod($method, $interfaceMethod, $prettyPrinter);
        }

        ksort($rendered, SORT_STRING);

        return $rendered;
    }

    private function renderMethod(ClassMethod $method, bool $interfaceMethod, Standard $prettyPrinter): string
    {
        $lines = [];
        $docComment = $method->getDocComment();
        if ($docComment instanceof Doc) {
            $lines[] = $docComment->getText();
        }

        $parts = [];
        if (!$interfaceMethod) {
            if ($method->isPublic()) {
                $parts[] = 'public';
            } elseif ($method->isProtected()) {
                $parts[] = 'protected';
            }

            if ($method->isStatic()) {
                $parts[] = 'static';
            }
        }

        $signature = implode(' ', $parts);
        if ($signature !== '') {
            $signature .= ' ';
        }

        $signature .= 'function ';
        $signature .= $method->name->toString() . '(';
        $signature .= implode(', ', array_map(
            fn (Node\Param $param): string => $this->renderParam($param, $prettyPrinter),
            $method->params
        ));
        $signature .= ')';

        if ($method->returnType !== null) {
            $signature .= ': ' . $this->renderType($method->returnType);
        }

        $signature .= $interfaceMethod ? ';' : ' {}';
        $lines[] = $signature;

        return implode("\n", $lines);
    }

    private function renderParam(Node\Param $param, Standard $prettyPrinter): string
    {
        $signature = '';

        if ($param->type !== null) {
            $signature .= $this->renderType($param->type) . ' ';
        }

        if ($param->byRef) {
            $signature .= '&';
        }

        if ($param->variadic) {
            $signature .= '...';
        }

        $signature .= '$' . $param->var->name;

        if ($param->default !== null) {
            $signature .= ' = ' . $prettyPrinter->prettyPrintExpr($param->default);
        }

        return $signature;
    }

    private function renderType(Node $type): string
    {
        if ($type instanceof Identifier || $type instanceof Node\Name) {
            return $type->toString();
        }

        if ($type instanceof NullableType) {
            return '?' . $this->renderType($type->type);
        }

        if ($type instanceof UnionType) {
            return implode('|', array_map(fn (Node $inner): string => $this->renderType($inner), $type->types));
        }

        if ($type instanceof IntersectionType) {
            return implode('&', array_map(fn (Node $inner): string => $this->renderType($inner), $type->types));
        }

        return 'mixed';
    }

    private function namespacePrefix(Node $node): string
    {
        $namespace = $node->getAttribute('namespacedName');
        if ($namespace instanceof Node\Name) {
            $parts = $namespace->parts;
            array_pop($parts);

            return $parts === [] ? '' : implode('\\', $parts) . '\\';
        }

        return '';
    }
}
