<?php

declare(strict_types=1);

use Foo\{Bar, Baz, Blub};

new Bar()->format();
new Baz()->format();
$test = new Blub()->add(new Bar());
