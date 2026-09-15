<?php

declare(strict_types=1);

$test = new \DateTime();

$t = function () use (&$test) {
    $test->format('c');
    $test = new \stdClass();
};

$test->format('c');