<?php

declare(strict_types=1);

namespace setasign\PhpSyntaxHighlighter;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use setasign\PhpSyntaxHighlighter\Manuals\LinkBuilder;
use setasign\PhpSyntaxHighlighter\Manuals\PhpManualByReflection;

class PhpSyntaxHighlighter
{
    public LinkBuilder $linkBuilder;

    private PhpVersion $phpVersion;

    public function __construct(?PhpVersion $phpVersion = null)
    {
        $this->phpVersion = $phpVersion ?? PhpVersion::getNewestSupported();

        $this->linkBuilder = new LinkBuilder();
        $this->linkBuilder->addManual(new PhpManualByReflection());
    }

    public function highlight(string $code): string
    {
        $hasOpenTag = \str_starts_with($code, '<?php');
        if (!$hasOpenTag) {
            $code = "<?php\n" . $code;
        }

        $parser = new ParserFactory()->createForVersion($this->phpVersion);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());

        $collector = new LinkCollector($this->linkBuilder);
        $traverser->addVisitor($collector);

        try {
            // @phpstan-ignore-next-line argument.type
            $traverser->traverse($parser->parse($code));
        } catch (\Throwable $e) {
            throw new Exception('Cannot parse given php code!', 0, $e);
        }

        // Highlighting & linking deterministically via the source tokenizer
        $currentOffset = 0;

        $linkMap = $collector->linkMap;
        $output = '';
        $tokens = \PhpToken::tokenize($code);
        if (!$hasOpenTag) {
            $currentOffset += \strlen(\array_shift($tokens)->text);
        }
        foreach ($tokens as $token) {
            $text = $token->text;
            $tokenOffset = $currentOffset;
            $currentOffset += \strlen($text);

            $escapedText = \htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

            if (isset($linkMap[$tokenOffset])) {
                $url = $linkMap[$tokenOffset];
                if (\str_contains($url, '"')) {
                    throw new Exception('Unexpected char in url!');
                }
                $escapedText = '<a href="' . $url . '" target="_blank" class="manual-link">' . $escapedText . '</a>';
                unset($linkMap[$tokenOffset]); // consume link
            }

            $tokenClass = $token->getTokenName();
            if (!\is_string($tokenClass) || !\str_starts_with($tokenClass, 'T_')) {
                $tokenClass = 'char';
            }
            $output .= '<span class="php-token php-token-' . self::getTokenClass($tokenClass) . '">'
                . $escapedText
                . '</span>';
        }

        // check for unconsumed links
        if ($linkMap !== []) {
            throw new Exception('Unmatched links found');
        }

        return $output;
    }

    /**
     * Generates a styling definition matching to the given colors. The method is mainly for debugging purpose.
     *
     * @param array<int|'default'|'char',string>|null $colors
     * @return string
     */
    public static function getStyling(?array $colors = null): string
    {
        if ($colors === null) {
            $colors = [
                'default' => '#007700',
                \T_COMMENT => '#FF8000',
                \T_DOC_COMMENT => '#FF8000',
                \T_VARIABLE => '#0000BB',
                \T_STRING => '#0000BB',
                \T_NAME_QUALIFIED => '#0000BB',
                \T_CONSTANT_ENCAPSED_STRING => '#DD0000',
                \T_INLINE_HTML => '#000000',
                \T_ENCAPSED_AND_WHITESPACE => '#DD0000',
            ];
        }

        return (
            $colors
            |> (fn($x) => \array_map(function (string $color, int|string $token): string {
                if ($token === 'default') {
                    $class = '.php-token';
                } elseif ($token === 'char') {
                    $class = '.php-token.php-token-char';
                } else {
                    if (!\is_int($token)) {
                        throw new \InvalidArgumentException('Unknown token name: ' . $token);
                    }

                    $class = '.php-token.php-token-' . self::getTokenClass(\token_name($token));
                }
                return <<<PHP
                $class {
                    color: $color;
                }
                PHP
                ;
            }, $x, \array_keys($x)))
            |> (fn ($x) => \implode("\n", $x))
        );
    }

    protected static function getTokenClass(string $tokenName): string
    {
        return (
            $tokenName
            |> \strtolower(...)
            |> (fn($x) => \str_replace('_', '-', $x))
        );
    }
}
