<?php

declare(strict_types=1);

class A extends DateTime {
    public function getDate(): DateTimeInterface {
        return new DateTime();
    }
}
class B extends A {
    public function test() {
        $this->getDate()->format('c');
    }
}

(new B())->getDate()->format('c');
(new B())->format('c');
(new B())->modify('+1 day')->format('c');