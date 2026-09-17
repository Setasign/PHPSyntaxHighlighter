<?php

declare(strict_types=1);

namespace setasign\PhpSyntaxHighlighter\Tests\functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use setasign\PhpSyntaxHighlighter\PhpSyntaxHighlighter;

class CompareTest extends TestCase
{
    public static function getCompareData(): array
    {
        $result = [];
        $testDirectory = __DIR__ . '/CompareData/';
        $files = \glob($testDirectory . '*.php');
        foreach ($files as $file) {
            $expectedOutputFile = \dirname($file) . '/' . \basename($file, '.php') . '.html';
            $testName = \substr($file, \strlen($testDirectory));
            $result[$testName] = [
                \file_get_contents($file),
                (
                    \file_exists($expectedOutputFile)
                    ? self::prettyPrintTokenHtml(\file_get_contents($expectedOutputFile))
                    : ''
                ),
                $testName
            ];
        }
        return $result;
    }

    private static function prettyPrintTokenHtml(string $html): string
    {
        $depth = 0;
        $output = '';
        $parts = \preg_split('/(<[^>]+>)/', $html, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY);

        foreach ($parts as $part) {
            if ($part[0] === '<') {
                $isClosing = \str_starts_with($part, '</');
                if ($isClosing) {
                    $depth--;
                }
                $output .= \str_repeat('    ', \max($depth, 0)) . $part . "\n";
                if (!$isClosing) {
                    $depth++;
                }
            } else {
                $output .= \str_repeat('    ', \max($depth, 0)) . $part . "\n";
            }
        }

        return \rtrim($output, "\n");
    }

    #[DataProvider('getCompareData')]
    public function testCompare(string $code, string $expectedResult, string $testName)
    {
        try {
            $highlighter = new PhpSyntaxHighlighter();
            $highlighter->linkBuilder->addManual(new CompareDataManualBuilder());
            $actualResult = self::prettyPrintTokenHtml($highlighter->highlight($code));
            $this->assertEquals($expectedResult, $actualResult);
        } catch (ExpectationFailedException $e) {
            if (!isset($_SERVER['argv']) || \array_last($_SERVER['argv']) !== '--teamcity') {
                throw $e;
            }
            // phpstorm cuts the content so a usable comparison isn't possible - this is a workaround for that
            $escape = function (string $string): string {
                return \str_replace(
                    ['|', "'", "\n", "\r", ']', '['],
                    ['||', "|'", '|n', '|r', '|]', '|['],
                    $string,
                );
            };
            echo '##teamcity[testFailed name=\'testCompare with data set "' . $escape($testName) . '"\''
                . ' message=\'Failed asserting that two strings are equal.\' duration=\'16\' type=\'comparisonFailure\''
                . ' actual=\'' . $escape($actualResult) . '\' expected=\'' . $escape($expectedResult) . '\''
                . ' flowId=\'29872\']';
            $this->fail('Failed asserting that two strings are equal.');
        }
    }

    public function testCompareWithVariableTypehint()
    {
        $highlighter = new PhpSyntaxHighlighter();
        $code = 'echo $date->format("c");';
        $expectedResult = '<span class="php-token php-token-t-echo">echo</span>'
            . '<span class="php-token php-token-t-whitespace"> </span>'
            . '<span class="php-token php-token-t-variable">'
            . '<a href="https://www.php.net/manual/en/class.datetime.php" target="_blank" class="manual-link">$date</a>'
            . '</span><span class="php-token php-token-t-object-operator">-&gt;</span>'
            . '<span class="php-token php-token-t-string">'
            . '<a href="https://www.php.net/manual/en/datetime.format.php" target="_blank" class="manual-link">'
            . 'format</a></span><span class="php-token php-token-char">(</span>'
            . '<span class="php-token php-token-t-constant-encapsed-string">&quot;c&quot;</span>'
            . '<span class="php-token php-token-char">)</span><span class="php-token php-token-char">;</span>';
        $this->assertEquals($expectedResult, $highlighter->highlight($code, ['variables' => ['date' => 'DateTime']]));
    }

    public function testCompareWithClassTypehint()
    {
        $highlighter = new PhpSyntaxHighlighter();
        $code = 'echo new Blub()->format("c");';
        $expectedResult = '<span class="php-token php-token-t-echo">echo</span>'
            . '<span class="php-token php-token-t-whitespace"> </span>'
            . '<span class="php-token php-token-t-new">new</span>'
            . '<span class="php-token php-token-t-whitespace"> </span>'
            . '<span class="php-token php-token-t-string">'
            . '<a href="https://www.php.net/manual/en/class.datetime.php" target="_blank" class="manual-link">Blub</a>'
            . '</span><span class="php-token php-token-char">(</span><span class="php-token php-token-char">)</span>'
            . '<span class="php-token php-token-t-object-operator">-&gt;</span>'
            . '<span class="php-token php-token-t-string">'
            . '<a href="https://www.php.net/manual/en/datetime.format.php" target="_blank" class="manual-link">format'
            . '</a></span><span class="php-token php-token-char">(</span>'
            . '<span class="php-token php-token-t-constant-encapsed-string">&quot;c&quot;</span>'
            . '<span class="php-token php-token-char">)</span><span class="php-token php-token-char">;</span>';
        $this->assertEquals($expectedResult, $highlighter->highlight($code, ['classes' => ['Blub' => 'DateTime']]));
    }

    public function testCompareWithFunctionTypehint()
    {
        $highlighter = new PhpSyntaxHighlighter();
        $code = 'echo blub_create()->format("c");';
        $expectedResult = '<span class="php-token php-token-t-echo">echo</span>'
            . '<span class="php-token php-token-t-whitespace"> </span>'
            . '<span class="php-token php-token-t-string">'
            . '<a href="https://www.php.net/manual/en/function.date-create.php" target="_blank" class="manual-link">'
            . 'blub_create</a>'
            . '</span><span class="php-token php-token-char">(</span><span class="php-token php-token-char">)</span>'
            . '<span class="php-token php-token-t-object-operator">-&gt;</span>'
            . '<span class="php-token php-token-t-string">'
            . '<a href="https://www.php.net/manual/en/datetime.format.php" target="_blank" class="manual-link">format'
            . '</a></span><span class="php-token php-token-char">(</span>'
            . '<span class="php-token php-token-t-constant-encapsed-string">&quot;c&quot;</span>'
            . '<span class="php-token php-token-char">)</span><span class="php-token php-token-char">;</span>';
        $this->assertEquals(
            $expectedResult,
            $highlighter->highlight($code, ['functions' => ['blub_create' => 'date_create']])
        );
    }
}
