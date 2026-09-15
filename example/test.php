<?php

declare(strict_types=1);

use setasign\PhpSyntaxHighlighter\Manuals\ManualLinkBuilderInterface;
use setasign\PhpSyntaxHighlighter\PhpSyntaxHighlighter;
use setasign\PhpSyntaxHighlighter\Tests\functional\CompareDataManualBuilder;

if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

ini_set('display_errors', 1);
error_reporting(E_ALL);


require '../vendor/autoload.php';

if (!isset($_GET['file'])) {
    echo '<ul>';
    foreach (glob(__DIR__ . '/../tests/functional/CompareData/*.php') as $file) {
        $fileName = basename($file, '.php');
        echo '<li><a href="?file=' . $fileName . '">' . $fileName . '</li>';
    }
    echo '</ul>';
    return;
}
echo '<a href="?">Back to file menu</a><br/>';
if (!file_exists(__DIR__ . '/../tests/functional/CompareData/' . $_GET['file'] . '.php') || \str_contains($_GET['file'], '..')) {
    echo 'File not found.';
    return;
}

$highlighter = new PhpSyntaxHighlighter();
$highlighter->linkBuilder->addManual(new CompareDataManualBuilder());
$code = file_get_contents(__DIR__ . '/../tests/functional/CompareData/' .  $_GET['file'] . '.php');

$styling = PhpSyntaxHighlighter::getStyling();
echo '<style>
' . $styling . '
</style>';
echo '<pre><code>';
echo $highlighter
    ->highlight($code);
echo '</code></pre>';