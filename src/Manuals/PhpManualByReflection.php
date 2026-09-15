<?php

declare(strict_types=1);

namespace setasign\PhpSyntaxHighlighter\Manuals;

class PhpManualByReflection implements ManualLinkBuilderInterface
{
    private const array PHPDOC_TYPES = [
        'array',
        'callable',
        'callback',
        'bool',
        'boolean',
        'float',
        'double',
        'int',
        'integer',
        'string',
        'number',
        'scalar',
        'object',
        'null',
        'mixed',
        'void',
        'resource',
        'false',
        'true',
        'self',
        'static',
        '$this'
    ];

    public function __construct()
    {
    }

    private function formatUrlClass(string $className): string
    {
        return (
            $className
                |> (fn($x) => \str_replace('\\', '', $x))
                |> (fn($x) => \ltrim($x, '\\'))
                |> \strtolower(...)
        );
    }

    public function getClassLink(string $className): ?string
    {
        if (!\class_exists($className) && !\interface_exists($className)) {
            return null;
        }

        $reflection = new \ReflectionClass($className);
        if ($reflection->isInternal()) {
            $classUrl = $this->formatUrlClass($className);
            return "https://www.php.net/manual/en/class.{$classUrl}.php";
        }

        return null;
    }

    public function getClassMethodLink(string $className, string $methodName): ?string
    {
        if (
            (!\class_exists($className) && !\interface_exists($className))
            || !\method_exists($className, $methodName)
        ) {
            return null;
        }

        $reflection = \ReflectionMethod::createFromMethodName("$className::$methodName");
        if ($reflection->isInternal()) {
            $classUrl = $this->formatUrlClass($className);
            $methodUrl = \strtolower($methodName);
            return "https://www.php.net/manual/en/{$classUrl}.{$methodUrl}.php";
        }

        return null;
    }

    public function getClassConstantLink(string $className, string $constantName): ?string
    {
        if (!\class_exists($className) && !\interface_exists($className)) {
            return null;
        }

        $classReflection = new \ReflectionClass($className);
        if ($classReflection->isInternal() && $classReflection->hasConstant($constantName) !== false) {
            $classUrl = $this->formatUrlClass($className);
            $constUrl = \strtolower($constantName);
            return (
                "https://www.php.net/manual/en/"
                . "class.{$classUrl}.php#{$classUrl}.constants.{$constUrl}"
            );
        }

        return null;
    }

    public function getFunctionLink(string $functionName): ?string
    {
        if (!\function_exists($functionName)) {
            return null;
        }

        $reflection = new \ReflectionFunction($functionName);
        if ($reflection->isInternal()) {
            $formattedName = \str_replace('_', '-', \strtolower($functionName));
            return "https://www.php.net/manual/en/function.{$formattedName}.php";
        }

        return null;
    }

    /**
     * @param string $functionName
     * @return null|string[]
     */
    public function getFunctionReturnType(string $functionName): ?array
    {
        if (\function_exists($functionName)) {
            $reflection = new \ReflectionFunction($functionName);
            if ($reflection->isInternal()) {
                return $this->resolveReturnType(
                    $reflection->getReturnType()
                    ?? ($reflection->hasTentativeReturnType() ? $reflection->getTentativeReturnType() : null),
                    null,
                    null
                );
            }
        }

        return null;
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return null|string[]
     */
    public function getMethodReturnType(string $className, string $methodName): ?array
    {
        if ((\class_exists($className) || \interface_exists($className)) && \method_exists($className, $methodName)) {
            $reflection = \ReflectionMethod::createFromMethodName("$className::$methodName");
            if ($reflection->isInternal()) {
                return $this->resolveReturnType(
                    $reflection->getReturnType()
                    ?? ($reflection->hasTentativeReturnType() ? $reflection->getTentativeReturnType() : null),
                    $className,
                    $reflection->getDeclaringClass()->name
                );
            }
        }

        return null;
    }

    /**
     * @param \ReflectionType|null $type
     * @param string|null $staticClassContext
     * @param string|null $selfClassContext
     * @return string[]
     */
    protected function resolveReturnType(
        ?\ReflectionType $type,
        ?string $staticClassContext,
        ?string $selfClassContext
    ): array {
        if ($type instanceof \ReflectionIntersectionType || $type instanceof \ReflectionUnionType) {
            $types = $type->getTypes();
            $result = [];
            foreach ($types as $type) {
                foreach ($this->resolveReturnType($type, $staticClassContext, $selfClassContext) as $t) {
                    $result[] = $t;
                }
            }
            return $result;
        }

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            if ($typeName === 'self' && $selfClassContext !== null) {
                return [$selfClassContext];
            }
            if (($typeName === 'static' || $typeName === '$this') && $staticClassContext !== null) {
                return [$staticClassContext];
            }

            if (!\in_array($typeName, self::PHPDOC_TYPES)) {
                return [$typeName];
            }
        }

        return [];
    }
}
