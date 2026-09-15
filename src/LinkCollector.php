<?php

declare(strict_types=1);

namespace setasign\PhpSyntaxHighlighter;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use setasign\PhpSyntaxHighlighter\Manuals\LinkBuilder;

class LinkCollector extends NodeVisitorAbstract
{
    /**
     * @var non-empty-array<int, array{
     *     namespace: ?string,
     *     classes: array<string, string>,
     *     functions: array<string, string>
     *  }>
     */
    private array $namespaces = [
        [
            'namespace' => null,
            'classes' => [],
            'functions' => [],
//            'constants' => [],
        ]
    ];

    /**
     * @var non-empty-array<int, array<string, string[]>>
     */
    private array $variableScopes = [
        []
    ];

    /**
     * Local classes
     *
     * @var array<string, array{
     *     parent: ?string,
     *     traits: string[],
     *     properties: array<string, string[]>,
     *     methodReturnTypes: array<string, string[]>
     * }>
     */
    private array $classes = [];

    /**
     * @var array<int, ?string>
     */
    private array $classNameStack = [];

    /** @var array<int, string> Map of start offset in the source code -> URL */
    public array $linkMap = [];

    public function __construct(
        private LinkBuilder $linkBuilder,
    ) {
    }

    /**
     * @param Node $node
     * @return null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->handleNamespace($node);
            return null;
        }

        if ($node instanceof Node\Stmt\Use_ || $node instanceof Node\Stmt\GroupUse) {
            $this->handleUse($node);
            return null;
        }

        if ($node instanceof Node\Stmt\ClassLike) {
            $this->handleClassLike($node);
            return null;
        }

        // handle new variable scopes
        if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
            $this->handleFunctionLikeScope($node);
            return null;
        } elseif ($node instanceof Node\Expr\Closure) {
            $this->handleClosure($node);
            return null;
        } elseif ($node instanceof Node\Expr\ArrowFunction) {
            // create a copy of the current scope
            $this->variableScopes[] = $this->variableScopes[\array_key_last($this->variableScopes)];
            return null;
        }

        // Handle assignment ($date = new DateTime("now"))
        if ($node instanceof Node\Expr\Assign) {
            $this->handleAssign($node);
            return null;
        }

        // Collect type hints in function parameters (function check(DateTimeZone $zone))
        if ($node instanceof Node\Param) {
            $this->handleParam($node);
            return null;
        }

        // Variable reads ($zone->getName() or $date)
        if ($node instanceof Node\Expr\Variable && \is_string($node->name)) {
            $this->handleVariable($node);
            return null;
        }

        // Link actual class names (NO string literals, NO function names!)
        if ($node instanceof Node\Name) {
            $this->handleName($node);
            return null;
        }

        // Link method calls ($zone->getName() or (new DateTime())->add())
        if ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall) {
            $this->handleMethodCalls($node);
            return null;
        }

        // Functions (explode, array_map)
        if ($node instanceof Node\Expr\FuncCall) {
            $this->handleFunctionCalls($node);
            return null;
        }

        // Class constants (DateTime::ATOM)
        if ($node instanceof Node\Expr\ClassConstFetch) {
            $this->handleClassConst($node);
        }
        return null;
    }

    /**
     * @param Node $node
     * @return null
     * @throws Exception
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            if (\count($this->namespaces) <= 1) {
                throw new Exception('Invalid leaving namespace!');
            }

            // @phpstan-ignore assign.propertyType
            \array_pop($this->namespaces);
        }

        if ($node instanceof Node\Stmt\ClassLike) {
            if ($this->classNameStack === []) {
                throw new Exception('Invalid leaving class scope!');
            }

            \array_pop($this->classNameStack);
        }

        if (
            $node instanceof Node\Stmt\Function_
            || $node instanceof Node\Stmt\ClassMethod
            || $node instanceof Node\Expr\Closure
            || $node instanceof Node\Expr\ArrowFunction
        ) {
            if (\count($this->variableScopes) <= 1) {
                throw new Exception('Invalid leaving variable scope!');
            }

            // @phpstan-ignore assign.propertyType
            \array_pop($this->variableScopes);
        }
        return null;
    }

    /**
     * @param Node\Expr $expr
     * @return string[]
     */
    private function extractTypeFromExpr(Node\Expr $expr): array
    {
        if ($expr instanceof Node\Expr\New_ && $expr->class instanceof Node\Name) {
            return $this->resolveClassName($expr->class);
        }

        if (
            ($expr instanceof Node\Expr\MethodCall || $expr instanceof Node\Expr\StaticCall)
        ) {
            if ($expr instanceof Node\Expr\StaticCall) {
                $classes = $this->resolveClassName($expr->class);
            } else {
                $classes = $this->extractTypeFromExpr($expr->var);
            }

            if ($classes !== [] && $expr->name instanceof Node\Identifier) {
                $methodName = $expr->name->toString();
                $types = [];
                foreach ($classes as $class) {
                    foreach ($this->lookupInClassHierarchy($class, 'methodReturnTypes', $methodName) as $type) {
                        $types[] = $type;
                    }
                    foreach ($this->classAndRelatedCandidates($class) as $candidate) {
                        $candidateTypes = $this->linkBuilder->getMethodReturnType($candidate, $methodName);
                        if ($candidateTypes !== []) {
                            foreach ($candidateTypes as $type) {
                                $types[] = $type;
                            }
                            break;
                        }
                    }
                }
                return $types;
            }
        }

        if ($expr instanceof Node\Expr\FuncCall && $expr->name instanceof Node\Name) {
            $functionName = $this->resolveFunctionName($expr->name);
            $types = [];
            if ($functionName !== null) {
                foreach ($this->linkBuilder->getFunctionReturnType($functionName) as $type) {
                    $types[] = $type;
                }
            }
            return $types;
        }

        // Local property reads ($this->dateTime, or (new Test())->dateTime)
        if ($expr instanceof Node\Expr\PropertyFetch && $expr->name instanceof Node\Identifier) {
            $propertyName = $expr->name->toString();
            $types = [];
            foreach ($this->extractTypeFromExpr($expr->var) as $class) {
                foreach ($this->lookupInClassHierarchy($class, 'properties', $propertyName) as $type) {
                    $types[] = $type;
                }
            }
            return $types;
        }

        if ($expr instanceof Node\Expr\Variable && \is_string($expr->name)) {
            return $this->variableScopes[\array_key_last($this->variableScopes)][$expr->name] ?? [];
        }
        return [];
    }

    /**
     * @param Node $node
     * @param bool $globalNamespaceFallback
     * @return string[]
     */
    private function resolveClassName(Node $node, bool $globalNamespaceFallback = true): array
    {
        if ($node instanceof Node\UnionType || $node instanceof Node\IntersectionType) {
            $result = [];
            foreach ($node->types as $type) {
                foreach ($this->resolveClassName($type, $globalNamespaceFallback) as $className) {
                    $result[] = $className;
                }
            }
            return $result;
        }

        if ($node instanceof Node\NullableType) {
            $node = $node->type;
        }

        if ($node instanceof Node\Name) {
            $className = $node->toString();
            if ($node->isFullyQualified()) {
                return [$className];
            }

            if ($node->isQualified()) {
                $usedAlias = $node->getFirst();
                if (isset($this->namespaces[\array_key_last($this->namespaces)]['classes'][$usedAlias])) {
                    $prefix = $this->namespaces[\array_key_last($this->namespaces)]['classes'][$usedAlias];
                    return [$prefix . \substr($className, \strlen($usedAlias))];
                }
            } else {
                if ($className === 'self' || $className === 'static') {
                    $currentClassName = $this->currentClassName();
                    return $currentClassName !== null ? [$currentClassName] : [];
                }
                if ($className === 'parent') {
                    $currentClassName = $this->currentClassName();
                    $parentClassName = $currentClassName !== null
                        ? ($this->classes[$currentClassName]['parent'] ?? null)
                        : null;
                    return $parentClassName !== null ? [$parentClassName] : [];
                }

                if (isset($this->namespaces[\array_key_last($this->namespaces)]['classes'][$className])) {
                    return [$this->namespaces[\array_key_last($this->namespaces)]['classes'][$className]];
                }
            }

            $currentNamespace = $this->namespaces[\array_key_last($this->namespaces)]['namespace'];
            if ($globalNamespaceFallback && $currentNamespace !== null) {
                return [$currentNamespace . '\\' . $className];
            }

            return [$className];
        }

        return [];
    }

    private function resolveFunctionName(Node\Name $name): ?string
    {
        $functionName = $name->toString();
        if ($name->isFullyQualified()) {
            return $functionName;
        }

        if ($name->isQualified()) {
            $usedAlias = $name->getFirst();
            if (isset($this->namespaces[\array_key_last($this->namespaces)]['classes'][$usedAlias])) {
                $prefix = $this->namespaces[\array_key_last($this->namespaces)]['classes'][$usedAlias];
                return $prefix . \substr($functionName, \strlen($usedAlias));
            }
        } else {
            if (isset($this->namespaces[\array_key_last($this->namespaces)]['functions'][$functionName])) {
                return $this->namespaces[\array_key_last($this->namespaces)]['functions'][$functionName];
            }
        }

        if ($this->linkBuilder->getFunctionLink($functionName) !== null) {
            return $functionName;
        }

        $currentNamespace = $this->namespaces[\array_key_last($this->namespaces)]['namespace'];
        if ($currentNamespace !== null) {
            $functionName = $currentNamespace . '\\' . $functionName;
            if ($this->linkBuilder->getFunctionLink($functionName) !== null) {
                return $functionName;
            }
        }
        return null;
    }

    private function currentClassName(): ?string
    {
        if ($this->classNameStack === []) {
            return null;
        }

        return $this->classNameStack[\array_key_last($this->classNameStack)];
    }

    /**
     * @param string $class
     * @param 'properties'|'methodReturnTypes' $key
     * @param string $name
     * @return string[]
     */
    private function lookupInClassHierarchy(string $class, string $key, string $name): array
    {
        $visited = [];
        $current = $class;
        while ($current !== null && !isset($visited[$current])) {
            $visited[$current] = true;
            if (isset($this->classes[$current][$key][$name])) {
                return $this->classes[$current][$key][$name];
            }
            $current = $this->classes[$current]['parent'] ?? null;
        }
        return [];
    }

    /**
     * @return string[]
     */
    private function classAndRelatedCandidates(string $className): array
    {
        $result = [];
        $visited = [];
        $queue = [$className];
        while ($queue !== []) {
            $current = \array_shift($queue);
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            $result[] = $current;

            $parent = $this->classes[$current]['parent'] ?? null;
            if ($parent !== null) {
                $queue[] = $parent;
            }
            foreach ($this->classes[$current]['traits'] ?? [] as $trait) {
                $queue[] = $trait;
            }
        }
        return $result;
    }

    private function handleNamespace(Node\Stmt\Namespace_ $node): void
    {
        $this->namespaces[] = [
            'namespace' => $node->name?->toString(),
            'classes' => [],
            'functions' => [],
//            'constants' => [],
        ];
    }

    private function handleUse(Node\Stmt\Use_|Node\Stmt\GroupUse $node): void
    {
        $prefix = $node instanceof Node\Stmt\GroupUse ? $node->prefix->toString() . '\\' : '';

        foreach ($node->uses as $useNode) {
            // In mixed group imports, each UseItem can carry its own type;
            // otherwise the type of the enclosing Use_/GroupUse statement applies.
            $type = $useNode->type !== Node\Stmt\Use_::TYPE_UNKNOWN ? $useNode->type : $node->type;
            if ($type !== Node\Stmt\Use_::TYPE_NORMAL && $type !== Node\Stmt\Use_::TYPE_FUNCTION) {
                // ignore "use const"
                continue;
            }


            $fullName = $prefix . $useNode->name->toString();
            $alias = (string) $useNode->getAlias();
            $key = match ($type) {
                Node\Stmt\Use_::TYPE_NORMAL => 'classes',
                Node\Stmt\Use_::TYPE_FUNCTION => 'functions',
//                Node\Stmt\Use_::TYPE_CONSTANT => 'constants',
            };
            $this->namespaces[\array_key_last($this->namespaces)][$key][$alias] = $fullName;
        }
    }

    private function handleClassLike(Node\Stmt\ClassLike $node): void
    {
        $localName = $node->name?->toString();
        $currentNamespace = $this->namespaces[\array_key_last($this->namespaces)]['namespace'];
        $className = match (true) {
            $localName === null => null,
            $currentNamespace !== null => $currentNamespace . '\\' . $localName,
            default => $localName,
        };

        if ($className !== null) {
            $parentClassName = null;
            if ($node instanceof Node\Stmt\Class_ && $node->extends !== null) {
                $parentClassName = \array_first($this->resolveClassName($node->extends));
            }

            $this->classes[$className] = [
                'parent' => $parentClassName,
                'traits' => [],
                'properties' => [],
                'methodReturnTypes' => [],
            ];
            $this->classNameStack[] = $className;

            $properties = [];
            $methodReturnTypes = [];
            $traits = [];
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\TraitUse) {
                    foreach ($stmt->traits as $traitName) {
                        $resolvedTraitName = \array_first($this->resolveClassName($traitName));
                        if ($resolvedTraitName === null) {
                            continue;
                        }

                        $traits[] = $resolvedTraitName;

                        if (!isset($this->classes[$resolvedTraitName])) {
                            continue;
                        }

                        $methodReturnTypes = [
                            ...$methodReturnTypes,
                            ...$this->classes[$resolvedTraitName]['methodReturnTypes'],
                        ];

                        // Only classes and traits can declare (promoted) properties.
                        if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Trait_) {
                            $properties = [...$properties, ...$this->classes[$resolvedTraitName]['properties']];
                        }
                    }
                    continue;
                }

                if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->returnType !== null) {
                    $methodReturnTypes[$stmt->name->toString()] = $this->resolveClassName($stmt->returnType);
                }

                // Only classes and traits can declare (promoted) properties.
                if (!($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Trait_)) {
                    continue;
                }

                if ($stmt instanceof Node\Stmt\Property && $stmt->type !== null) {
                    $types = $this->resolveClassName($stmt->type);
                    foreach ($stmt->props as $prop) {
                        $properties[$prop->name->toString()] = $types;
                    }
                } elseif ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === '__construct') {
                    foreach ($stmt->params as $param) {
                        if (
                            $param->isPromoted()
                            && $param->type !== null
                            && $param->var instanceof Node\Expr\Variable
                            && \is_string($param->var->name)
                        ) {
                            $properties[$param->var->name] = $this->resolveClassName($param->type);
                        }
                    }
                }
            }

            $this->classes[$className]['properties'] = $properties;
            $this->classes[$className]['methodReturnTypes'] = $methodReturnTypes;
            $this->classes[$className]['traits'] = $traits;
        } else {
            $this->classNameStack[] = $className;
        }
    }

    private function handleFunctionLikeScope(Node\Stmt\Function_|Node\Stmt\ClassMethod $node): void
    {
        $newScope = [];

        // Inside a non-static method, $this always refers to the current (local) class.
        if ($node instanceof Node\Stmt\ClassMethod && !$node->isStatic()) {
            $currentClassName = $this->currentClassName();
            if ($currentClassName !== null) {
                $newScope['this'] = [$currentClassName];
            }
        }

        $this->variableScopes[] = $newScope;
    }

    private function handleClosure(Node\Expr\Closure $node): void
    {
        $currentScope = $this->variableScopes[\array_key_last($this->variableScopes)];
        $newScope = [];

        // Unlike other variables, $this is available inside a closure without an explicit "use" -
        // unless the closure is declared "static", in which case it has no $this at all.
        if (!$node->static && isset($currentScope['this'])) {
            $newScope['this'] = $currentScope['this'];
        }

        foreach ($node->uses as $useNode) {
            $varName = $useNode->var->name;
            if (!\is_string($varName)) {
                continue;
            }

            if ($useNode->byRef) {
                $newScope[$varName] =&
                    $this->variableScopes[\array_key_last($this->variableScopes)][$varName];
            } else {
                $newScope[$varName] = $currentScope[$varName];
            }
        }
        $this->variableScopes[] = $newScope;
    }

    private function handleAssign(Node\Expr\Assign $node): void
    {
        if ($node->var instanceof Node\Expr\Variable && \is_string($node->var->name)) {
            $varName = $node->var->name;
            $classNames = $this->extractTypeFromExpr($node->expr);
            if (\count($classNames) === 1) {
                $this->variableScopes[\array_key_last($this->variableScopes)][$varName] = $classNames;
                $link = $this->linkBuilder->getClassLink($classNames[0]);
                if ($link !== null) {
                    $this->linkMap[$node->getStartFilePos()] = $link;
                }
            } else {
                unset($this->variableScopes[\array_key_last($this->variableScopes)][$varName]);
            }
        }
    }

    private function handleParam(Node\Param $node): void
    {
        if (!$node->var instanceof Node\Expr\Variable) {
            return;
        }

        if ($node->variadic) {
            // type is an array of $node->type
            // we currently don't handle typed arrays
            return;
        }

        $var = $node->var->name;
        if (!\is_string($var)) {
            return;
        }

        if ($node->type === null) {
            if ($node->default !== null) {
                $types = $this->extractTypeFromExpr($node->default);
                $this->variableScopes[\array_key_last($this->variableScopes)][$var] = $types;
            }
            return;
        }
        $this->variableScopes[\array_key_last($this->variableScopes)][$var] = $this->resolveClassName($node->type);
    }

    private function handleVariable(Node\Expr\Variable $node): void
    {
        $parent = $node->getAttribute('parent');
        if (!($parent instanceof Node\Expr\Assign && $parent->var === $node)) {
            $varName = $node->name;
            if (!\is_string($varName)) {
                return;
            }
            $variableTypes = $this->variableScopes[\array_key_last($this->variableScopes)][$varName] ?? [];
            foreach ($variableTypes as $variableType) {
                $link = $this->linkBuilder->getClassLink($variableType);
                if ($link !== null) {
                    $this->linkMap[$node->getStartFilePos()] = $link;
                    break;
                }
            }
        }
    }

    private function handleName(Node\Name $node): void
    {
        $parent = $node->getAttribute('parent');

        // The function name of a FuncCall is already linked don't link it again here as a class,
        // or the link would get overwritten.
        $isFunctionCallName = $parent instanceof Node\Expr\FuncCall && $parent->name === $node;

        if (
            !($parent instanceof Node\Scalar\String_)
            && !($parent instanceof Node\Stmt\Namespace_)
            && !$isFunctionCallName
        ) {
            if (
                $parent instanceof Node\UseItem
                && (
                    isset($parent->type) && $parent->type !== Node\Stmt\Use_::TYPE_UNKNOWN
                    ? $parent->type
                    : $parent->getAttribute('parent')?->type //@phpstan-ignore-line property.nonObject
                ) === Node\Stmt\Use_::TYPE_FUNCTION
            ) {
                $link = null;
                $funcName = $this->resolveFunctionName($node);
                if ($funcName !== null) {
                    $link = $this->linkBuilder->getFunctionLink($funcName);
                }
            } else {
                $globalNamespaceFallback = !($parent instanceof Node\UseItem);
                $className = \array_first($this->resolveClassName($node, $globalNamespaceFallback));
                $link = null;
                if ($className !== null) {
                    $link = $this->linkBuilder->getClassLink($className);
                }
            }

            if ($link !== null) {
                $this->linkMap[$node->getStartFilePos()] = $link;
            }
        }
    }

    private function handleMethodCalls(Node\Expr\StaticCall|Node\Expr\MethodCall $node): void
    {
        if ($node instanceof Node\Expr\StaticCall) {
            $classNames = $this->resolveClassName($node->class);
        } else {
            $classNames = $this->extractTypeFromExpr($node->var);
        }
        if ($classNames !== [] && $node->name instanceof Node\Identifier) {
            $methodName = $node->name->toString();
            $pos = $node->name->getStartFilePos();
            $link = null;
            foreach ($classNames as $className) {
                foreach ($this->classAndRelatedCandidates($className) as $candidate) {
                    $link = $this->linkBuilder->getClassMethodLink($candidate, $methodName);
                    if ($link !== null) {
                        break 2;
                    }
                }
            }
            if ($link !== null) {
                $this->linkMap[$pos] = $link;
            }
        }
    }

    private function handleClassConst(Node\Expr\ClassConstFetch $node): void
    {
        if ($node->class instanceof Node\Name && $node->name instanceof Node\Identifier) {
            $constName = $node->name->toString();
            if ($constName !== 'class') {
                $pos = $node->getStartFilePos() + \strlen($node->class->toString()) + 2;
                if ($node->class->isFullyQualified()) {
                    $pos++;
                }
                $className = \array_first($this->resolveClassName($node->class));
                $link = null;
                if ($className !== null) {
                    foreach ($this->classAndRelatedCandidates($className) as $candidate) {
                        $link = $this->linkBuilder->getClassConstantLink($candidate, $constName);
                        if ($link !== null) {
                            break;
                        }
                    }
                }
                if ($link !== null) {
                    $this->linkMap[$pos] = $link;
                }
            }
        }
    }

    private function handleFunctionCalls(Node\Expr\FuncCall $node): void
    {
        if (!$node->name instanceof Node\Name) {
            return;
        }
        $funcName = $this->resolveFunctionName($node->name);
        $link = null;
        if ($funcName !== null) {
            $link = $this->linkBuilder->getFunctionLink($funcName);
        }
        if ($link !== null) {
            $this->linkMap[$node->getStartFilePos()] = $link;
        }
    }
}
