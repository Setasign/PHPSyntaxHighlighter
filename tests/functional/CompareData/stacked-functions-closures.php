<?php

declare(strict_types=1);

$test = new \DateTime();
#[\Deprecated('Just a test')]
function test(DateTimeImmutable $test) {
    $t = function (DateTimeInterface $test) {
        $test->format('c');
    };
    $test->format('c');
}
$test->format('c');