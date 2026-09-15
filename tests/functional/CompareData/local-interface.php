<?php

declare(strict_types=1);

interface Test {
    public function format(): \DateTime;
}

function blub (Test $test) {
    $test->format()->format('c');
}