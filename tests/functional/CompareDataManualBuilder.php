<?php

declare(strict_types=1);

namespace setasign\PhpSyntaxHighlighter\Tests\functional;

use setasign\PhpSyntaxHighlighter\Manuals\ManualLinkBuilderInterface;

class CompareDataManualBuilder implements ManualLinkBuilderInterface
{
    public function getClassLink(string $className): ?string
    {
        return match ($className) {
            'Simple7\Test\DateTime' => 'https://www.example-manual.com/api/date-time',
            'Some\Other\DateTime' => 'https://www.example-manual.com/api/date-time',
            'App\DateTimeImmutable' => 'https://www.example-manual.com/api/app.date-time-immutable',
            'Foo\Bar' => 'https://www.example-manual.com/api/foo.bar',
            'Foo\Bar\TestTrait' => 'https://www.example-manual.com/api/foo.bar-trait',
            'Foo\Baz' => 'https://www.example-manual.com/api/foo.baz',
            'Foo\Baz\Test\Blub' => 'https://www.example-manual.com/api/foo.baz.test.blub',
            'Foo\Blub' => 'https://www.example-manual.com/api/foo.blub',
            'SomethingTrait' => 'https://www.example-manual.com/api/something-trait',
            default => null,
        };
    }

    public function getClassMethodLink(string $className, string $methodName): ?string
    {
        $result = null;
        switch ($className) {
            case 'Foo\Bar':
                $result = match ($methodName) {
                    'format' => 'https://www.example-manual.com/api/foo.bar-format',
                    default => null
                };
                break;
            case 'Foo\Baz':
                $result = match ($methodName) {
                    'format' => 'https://www.example-manual.com/api/foo.baz-format',
                    default => null
                };
                break;
            case 'Foo\Blub':
                $result = match ($methodName) {
                    'add' => 'https://www.example-manual.com/api/foo.blub-add',
                    default => null
                };
                break;
            case 'SomethingTrait':
                $result = match ($methodName) {
                    'doSomething' => 'https://www.example-manual.com/api/something-trait',
                    default => null
                };
                break;
        }
        return $result;
    }

    public function getClassConstantLink(string $className, string $constantName): ?string
    {
        $result = null;
        switch ($className) {
            case 'Some\Other\DateTime':
                $result = match ($constantName) {
                    'ATOM' => 'https://www.example-manual.com/api/foo.atom',
                    default => null,
                };
                break;
            case 'App\DateTimeImmutable':
                $result = match ($constantName) {
                    'ATOM' => 'https://www.example-manual.com/api/app.datetimeimmutable.atom',
                    default => null,
                };
                break;
        }

        return $result;
    }

    public function getFunctionLink(string $functionName): ?string
    {
        return match ($functionName) {
            'Foo\Blub\blub' => 'https://www.example-manual.com/api/func-foo.blub.blub',
            'Foo\Bar\date_create' => 'https://www.example-manual.com/api/func-foo.bar.date_create',
            'Foo\baz' => 'https://www.example-manual.com/api/func-foo.baz',
            default => null,
        };
    }

    public function getFunctionReturnType(string $functionName): ?array
    {
        return match ($functionName) {
            'Foo\Blub\blub' => ['DateTime'],
            default => null,
        };
    }

    public function getMethodReturnType(string $className, string $methodName): ?array
    {
        $result = null;
        switch ($className) {
            case 'Foo\Blub':
                $result = match ($methodName) {
                    'add' => ['Foo\Bar'],
                    default => null,
                };
                break;
            case 'SomethingTrait':
                $result = match ($methodName) {
                    'doSomething' => ['Foo\Bar'],
                    default => null,
                };
                break;
        }
        return $result;
    }
}
