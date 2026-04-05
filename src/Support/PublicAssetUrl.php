<?php

declare(strict_types=1);

namespace Vortex\Admin\Support;

use Vortex\Config\Repository;

/**
 * Turns stored upload paths into values suitable for {@code <img src>} (absolute URL when {@code app.url} is set).
 */
final class PublicAssetUrl
{
    public static function forImgSrc(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $s = trim((string) $value);
        if ($s === '') {
            return '';
        }
        if (preg_match('#^(javascript:|data:|vbscript:)#i', $s) === 1) {
            return '';
        }
        if (preg_match('#^https?://#i', $s) === 1 || str_starts_with($s, '//')) {
            return $s;
        }
        $path = str_starts_with($s, '/') ? $s : '/' . ltrim($s, '/');
        if (! Repository::initialized()) {
            return $path;
        }
        $base = rtrim((string) Repository::get('app.url', ''), '/');
        if ($base === '') {
            return $path;
        }

        return $base . $path;
    }
}
