<?php

$date = new DateTime("now");
$reflection = new ReflectionClass(DateTime::class);

echo 'DateTime';

function check(DateTimeZone $zone) {
    return $zone->getName();
}

function test($zone) {
    echo $zone;
}

$dateTime = new DateTime()->add(new DateInterval('P1D'))->format('Y-m-d');
