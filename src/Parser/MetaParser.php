<?php

declare(strict_types=1);

namespace Ioweb\M1PhpStanBridge\Parser;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use RuntimeException;

final class MetaParser
{
    /**
     * @return array<int, array{target: string, entries: array<string, string>}>
     */
    public function parseDirectory(string $metaDirectory): array
    {
        if (!is_dir($metaDirectory)) {
            throw new RuntimeException(sprintf('Meta directory does not exist: %s', $metaDirectory));
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($metaDirectory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files, SORT_STRING);

        $overrides = [];
        foreach ($files as $file) {
            array_push($overrides, ...$this->parseFile($file));
        }

        return $overrides;
    }

    /**
     * @return array<int, array{target: string, entries: array<string, string>}>
     */
    public function parseFile(string $file): array
    {
        $code = file_get_contents($file);
        if ($code === false) {
            throw new RuntimeException(sprintf('Unable to read meta file: %s', $file));
        }

        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        try {
            $ast = $parser->parse($code);
        } catch (Error $error) {
            throw new RuntimeException(
                sprintf('Unable to parse meta file %s: %s', $file, $error->getMessage()),
                0,
                $error
            );
        }

        if ($ast === null) {
            return [];
        }

        $nodeFinder = new NodeFinder();
        $calls = $nodeFinder->findInstanceOf($ast, Expr\FuncCall::class);

        $overrides = [];
        foreach ($calls as $call) {
            if (!$this->isFunctionCallNamed($call, 'override') || count($call->args) < 2) {
                continue;
            }

            $target = $this->extractTarget($call->args[0]->value);
            $entries = $this->extractMapEntries($call->args[1]->value);

            if ($target !== null && $entries !== []) {
                $overrides[] = [
                    'target' => $target,
                    'entries' => $entries,
                ];
            }
        }

        return $overrides;
    }

    private function extractTarget(Expr $node): ?string
    {
        if (!$node instanceof Expr\StaticCall && !$node instanceof Expr\MethodCall) {
            return null;
        }

        $method = $this->nodeNameToString($node->name);
        if ($method === null) {
            return null;
        }

        if ($node instanceof Expr\StaticCall) {
            $class = $this->classNodeToString($node->class);

            return $class === null ? null : $class . '::' . $method;
        }

        $class = $this->methodCallReceiverToString($node->var);

        return $class === null ? null : $class . '->' . $method;
    }

    /**
     * @return array<string, string>
     */
    private function extractMapEntries(Expr $node): array
    {
        if (!$node instanceof Expr\FuncCall || !$this->isFunctionCallNamed($node, 'map') || count($node->args) === 0) {
            return [];
        }

        $mapArgument = $node->args[0]->value;
        if (!$mapArgument instanceof Expr\Array_) {
            return [];
        }

        $entries = [];
        foreach ($mapArgument->items as $item) {
            if ($item === null || $item->key === null) {
                continue;
            }

            $key = $this->stringValue($item->key);
            $value = $this->classMapValue($item->value);

            if ($key === null || $value === null) {
                continue;
            }

            $entries[$key] = $value;
        }

        return $entries;
    }

    private function classMapValue(Expr $node): ?string
    {
        if ($node instanceof String_) {
            return $node->value;
        }

        if ($node instanceof Expr\ClassConstFetch && $this->nodeNameToString($node->name) === 'class') {
            return $this->classNodeToString($node->class);
        }

        return null;
    }

    private function stringValue(Expr $node): ?string
    {
        if ($node instanceof String_) {
            return $node->value;
        }

        return null;
    }

    private function isFunctionCallNamed(Expr\FuncCall $call, string $name): bool
    {
        return $this->nodeNameToString($call->name) === $name;
    }

    private function classNodeToString(Node $node): ?string
    {
        if ($node instanceof Node\Name) {
            return ltrim($node->toString(), '\\');
        }

        return null;
    }

    private function methodCallReceiverToString(Expr $node): ?string
    {
        if ($node instanceof Expr\Variable && is_string($node->name)) {
            return '$' . $node->name;
        }

        if ($node instanceof Expr\StaticCall) {
            return $this->extractTarget($node);
        }

        return null;
    }

    private function nodeNameToString(Node $node): ?string
    {
        if ($node instanceof Node\Name || $node instanceof Node\Identifier) {
            return $node->toString();
        }

        return null;
    }
}
