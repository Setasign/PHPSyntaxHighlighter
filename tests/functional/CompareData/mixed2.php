<?php

declare(strict_types=1);

use \DateTime as Schnuffi;
use DateTime as Blub;

$test = new Schnuffi();
$test->format('Y-m-d');

function test(Blub $test = new Blub()) {
    return $test->format('Y-m-d');
}

function test2(\DateTimeImmutable $test) {
    return $test->format('Y-m-d');
}

function test3($test = new Blub()) {
    return $test->format('Y-m-d');
}

function test4(\DateTimeImmutable ...$test) {
    return $test->format('Y-m-d');
}