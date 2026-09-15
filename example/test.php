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

$highlighter = new PhpSyntaxHighlighter();
$highlighter->linkBuilder->addManual(new CompareDataManualBuilder());
$code = file_get_contents(__DIR__ . '/../tests/functional/CompareData/local-class.php');

$styling = PhpSyntaxHighlighter::getStyling();
echo '<style>
' . $styling . '
</style>';
echo '<pre><code>';
echo $highlighter
    ->highlight($code);
echo '</code></pre>';