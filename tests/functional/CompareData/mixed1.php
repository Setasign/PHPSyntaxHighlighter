<?php
$test = new DateTime("now");
echo $test->format(DateTime::ATOM);

$test = new \DateTimeImmutable();
echo $test->format('Y-m-d H:i:s');

$reflection = new ReflectionClass(DateTime::class);

$parts = explode(",", "a,b,c");
$mapped = array_map("trim", $parts);