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
echo new DateTime()->format("c");
';
echo '<pre><code>' . $highlighter->highlight($code, ['date' => 'DateTime']) . '</code></pre>';