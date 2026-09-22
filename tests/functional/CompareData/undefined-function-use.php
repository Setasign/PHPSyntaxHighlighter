<?php

declare(strict_types=1);

$test = $data['blub'];
$loader = function () use ($test) {
    $test->format('c');
    $test = new \DateTime();
};
$test->format('c');

$test2 = $data['blub'];
$loader = function () use (&$test2) {
    $test2->format('c');
    $test2 = new \DateTime();
};
$test2->format('c');

