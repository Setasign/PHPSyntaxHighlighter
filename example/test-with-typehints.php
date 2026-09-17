<?php

declare(strict_types=1);

use setasign\PhpSyntaxHighlighter\PhpSyntaxHighlighter;

if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

ini_set('display_errors', 1);
error_reporting(E_ALL);


require '../vendor/autoload.php';

echo '<style>' . PhpSyntaxHighlighter::getStyling() . '</style>';

$highlighter = new PhpSyntaxHighlighter();
$code = 'echo $date->format("c");
$date = new \DateTimeImmutable();
$date->format("c");
$test->format("c");
echo new DateTime()->format("c");

new Blub()->format("c");
blub_create()->format("c");
';
echo '<pre><code>' . $highlighter->highlight($code, [
    'variables' => [
        'date' => 'DateTime',
        '$test' => '\DateTimeImmutable',
    ],
    'classes' => [
        'Blub' => 'DateTime'
    ],
    'functions' => [
        'blub_create' => 'date_create'
    ]
]) . '</code></pre>';