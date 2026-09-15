<?php

declare(strict_types=1);

namespace Foo\Blub;

use Foo\Bar;

use function date_create;
use function Foo\Blub\blub;
use function date_create as blubber;

$t = date_create();
$t2 = date_create_immutable();
$t3 = blub();
$t4 = blubber();
$t5 = \blubber();
$t6 = \date_create_immutable();
$t7 = Bar\date_create();