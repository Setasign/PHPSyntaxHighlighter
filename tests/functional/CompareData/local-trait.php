<?php

declare(strict_types=1);

trait HasDateTime {
    private DateTimeInterface $dt;

    public function getDt(): DateTimeInterface
    {
        return $this->dt;
    }
}

class Foo {
    use HasDateTime;
    use SomethingTrait;
}

(new Foo())->getDt()->format('c');

$t = (new Foo())->doSomething();