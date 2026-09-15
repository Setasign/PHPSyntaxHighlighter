<?php

declare(strict_types=1);

namespace setasign\PhpSyntaxHighlighter\Manuals;

class LinkBuilder
{
    /**
     * @var array{
     *     class: array<string, null|string>,
     *     method: array<string, null|string>,
     *     class_const: array<string, null|string>,
     *     function: array<string, null|string>,
     *     return_functions: array<string, string[]>,
     *     return_methods: array<string, string[]>,
     *   }
     */
    private array $memoize;

    /**
     * @var ManualLinkBuilderInterface[]
     */
    private array $manualLinkBuilders = [];

    public function __construct()
    {
        $this->resetMemoization();
    }

    private function resetMemoization(): void
    {
        $this->memoize = [
            'class' => [],
            'method' => [],
            'class_const' => [],
            'function' => [],
            'return_functions' => [],
            'return_methods' => [],
        ];
    }

    public function addManual(ManualLinkBuilderInterface $manualLinkBuilder): void
    {
        $this->manualLinkBuilders[] = $manualLinkBuilder;
        $this->resetMemoization();
    }

    public function getClassLink(string $className): ?string
    {
        if (\array_key_exists($className, $this->memoize['class'])) {
            return $this->memoize['class'][$className];
        }

        foreach ($this->manualLinkBuilders as $manualLinkBuilder) {
            $result = $manualLinkBuilder->getClassLink($className);
            if ($result !== null) {
                $this->memoize['class'][$className] = $result;
                return $result;
            }
        }

        $this->memoize['class'][$className] = null;
        return null;
    }

    public function getClassMethodLink(string $className, string $methodName): ?string
    {
        $k = $className . '::' . $methodName;
        if (\array_key_exists($k, $this->memoize['method'])) {
            return $this->memoize['method'][$k];
        }

        foreach ($this->manualLinkBuilders as $manualLinkBuilder) {
            $result = $manualLinkBuilder->getClassMethodLink($className, $methodName);
            if ($result !== null) {
                $this->memoize['method'][$k] = $result;
                return $result;
            }
        }

        $this->memoize['method'][$k] = null;
        return null;
    }

    public function getClassConstantLink(string $className, string $constantName): ?string
    {
        $k = $className . '::' . $constantName;
        if (\array_key_exists($k, $this->memoize['class_const'])) {
            return $this->memoize['class_const'][$k];
        }

        foreach ($this->manualLinkBuilders as $manualLinkBuilder) {
            $result = $manualLinkBuilder->getClassConstantLink($className, $constantName);
            if ($result !== null) {
                $this->memoize['class_const'][$k] = $result;
                return $result;
            }
        }

        $this->memoize['class_const'][$k] = null;
        return null;
    }

    public function getFunctionLink(string $functionName): ?string
    {
        if (\array_key_exists($functionName, $this->memoize['function'])) {
            return $this->memoize['function'][$functionName];
        }

        foreach ($this->manualLinkBuilders as $manualLinkBuilder) {
            $result = $manualLinkBuilder->getFunctionLink($functionName);
            if ($result !== null) {
                $this->memoize['function'][$functionName] = $result;
                return $result;
            }
        }

        $this->memoize['function'][$functionName] = null;
        return null;
    }

    /**
     * @param string $functionName
     * @return string[]
     */
    public function getFunctionReturnType(string $functionName): array
    {
        if (\array_key_exists($functionName, $this->memoize['return_functions'])) {
            return $this->memoize['return_functions'][$functionName];
        }

        foreach ($this->manualLinkBuilders as $manualLinkBuilder) {
            $result = $manualLinkBuilder->getFunctionReturnType($functionName);
            if ($result !== null) {
                $this->memoize['return_functions'][$functionName] = $result;
                return $result;
            }
        }

        $this->memoize['return_functions'][$functionName] = [];
        return [];
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return string[]
     */
    public function getMethodReturnType(string $className, string $methodName): array
    {
        $k = $className . '::' . $methodName;
        if (\array_key_exists($k, $this->memoize['return_methods'])) {
            return $this->memoize['return_methods'][$k];
        }

        foreach ($this->manualLinkBuilders as $manualLinkBuilder) {
            $result = $manualLinkBuilder->getMethodReturnType($className, $methodName);
            if ($result !== null) {
                $this->memoize['return_methods'][$k] = $result;
                return $result;
            }
        }

        $this->memoize['return_methods'][$k] = [];
        return [];
    }
}
