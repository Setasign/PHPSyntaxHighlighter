<?php

declare(strict_types=1);

$phpunitDirectory = __DIR__ . '/../phpunit/vendor';
if (is_dir($phpunitDirectory)) {
    require_once $phpunitDirectory . '/autoload.php';
}
