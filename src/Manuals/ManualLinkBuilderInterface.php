<?php

declare(strict_types=1);

namespace setasign\PhpSyntaxHighlighter\Manuals;

interface ManualLinkBuilderInterface
{
    /**
     * Returns the link to the manual of the given class/interface/trait.
     * If the class isn't found in this manual, this method should return null.
     *
     * @param string $className
     * @return string|null
     */
    public function getClassLink(string $className): ?string;

    /**
     * Returns the link to the manual of the given method. If the method isn't found in this manual, this method should
     * return null.
     *
     * @param string $className
     * @param string $methodName
     * @return string|null
     */
    public function getClassMethodLink(string $className, string $methodName): ?string;

    /**
     * Returns the link to the manual of the given class constant. If the class constant isn't found in this manual,
     * this method should return null.
     *
     * @param string $className
     * @param string $constantName
     * @return string|null
     */
    public function getClassConstantLink(string $className, string $constantName): ?string;

    /**
     * Returns the link to the manual of the given function. If the function isn't found in this manual,
     * this method should return null.
     *
     * @param string $functionName
     * @return string|null
     */
    public function getFunctionLink(string $functionName): ?string;

    /**
     * Returns the all documented return object types of the given function. If the function isn't found in this manual,
     * this method should return null.
     *
     * @param string $functionName
     * @return null|string[]
     */
    public function getFunctionReturnType(string $functionName): ?array;

    /**
     * Returns the all documented return object types of the given method. If the method isn't found in this manual,
     * this method should return null.
     *
     * @param string $className
     * @param string $methodName
     * @return null|string[]
     */
    public function getMethodReturnType(string $className, string $methodName): ?array;
}
