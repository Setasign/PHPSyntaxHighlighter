<?php

declare(strict_types=1);

function check(null|(DateTimeZone&DateTimeInterface) $zone) {
    echo $zone->format('c');
    return $zone->getName();
}
