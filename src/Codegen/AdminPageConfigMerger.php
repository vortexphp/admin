<?php

declare(strict_types=1);

namespace Vortex\Admin\Codegen;

/**
 * Inserts a {@code pages[]} row into {@code config/admin.php} source when a {@code 'pages' => [ ... ]} block exists or is added.
 */
final class AdminPageConfigMerger
{
    /**
     * @param non-empty-string $pageBlock indented lines (with trailing newline) for one page entry, e.g. "        [\n            'id' => 'x',\n ..."
     */
    public static function merge(string $adminPhpContents, string $pageBlock): ?string
    {
        $pagesKey = "'pages' => [";
        $pos = strpos($adminPhpContents, $pagesKey);
        if ($pos !== false) {
            return self::insertIntoExistingPages($adminPhpContents, $pos + strlen($pagesKey), $pageBlock);
        }

        return self::insertNewPagesKey($adminPhpContents, $pageBlock);
    }

    /**
     * @param positive-int $afterBracket Index immediately after {@code [} of {@code 'pages' => [}
     */
    private static function insertIntoExistingPages(string $src, int $afterBracket, string $pageBlock): ?string
    {
        $close = self::findMatchingBracketClose($src, $afterBracket - 1);
        if ($close === null) {
            return null;
        }
        $innerStart = $afterBracket;
        $innerEnd = $close;
        $inner = substr($src, $innerStart, $innerEnd - $innerStart);
        $trimmed = trim($inner);
        if ($trimmed === '') {
            $insert = "\n" . $pageBlock . '    ';
        } else {
            $insert = ',' . "\n" . $pageBlock . '    ';
        }

        return substr($src, 0, $innerEnd) . $insert . substr($src, $innerEnd);
    }

    private static function insertNewPagesKey(string $src, string $pageBlock): ?string
    {
        if (preg_match('/return\s*\[/', $src) !== 1) {
            return null;
        }
        $block = "    'pages' => [\n" . $pageBlock . "    ],\n";
        if (preg_match('/return\s*\[\s*(?:\R\s*)?\]/', $src) === 1) {
            return (string) preg_replace(
                '/return\s*\[\s*(?:\R\s*)?\]/',
                "return [\n" . $block . ']',
                $src,
                1,
            );
        }

        return (string) preg_replace(
            '/return\s*\[\R/',
            "return [\n" . $block,
            $src,
            1,
        );
    }

    /**
     * @param positive-int $openBracket Position of {@code [}
     */
    private static function findMatchingBracketClose(string $src, int $openBracket): ?int
    {
        $len = strlen($src);
        $depth = 0;
        for ($i = $openBracket; $i < $len; ++$i) {
            $c = $src[$i];
            if ($c === '[') {
                ++$depth;
            } elseif ($c === ']') {
                --$depth;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }
}
