<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Vortex\Config\Repository;

/**
 * Panel chrome from {@code config/admin.php} key {@code branding}.
 */
final class AdminBranding
{
    /**
     * @return array{
     *     name: string,
     *     logo: string|null,
     *     logo_alt: string,
     *     footer_vendor: string,
     *     footer_tagline: string,
     * }
     */
    public static function viewData(): array
    {
        /** @var mixed $raw */
        $raw = Repository::get('admin.branding', []);
        if (! is_array($raw)) {
            $raw = [];
        }

        $defaults = [
            'name' => 'Admin',
            'logo' => '/img/vortexadmin.svg',
            'logo_alt' => 'Admin',
            'footer_vendor' => 'Vortex',
            'footer_tagline' => 'control panel',
        ];

        $out = $defaults;
        foreach (['name', 'logo', 'logo_alt', 'footer_vendor', 'footer_tagline'] as $key) {
            if (! array_key_exists($key, $raw)) {
                continue;
            }
            $val = $raw[$key];
            if ($key === 'logo') {
                $out['logo'] = self::normalizeLogo($val, $defaults['logo']);
                continue;
            }
            $out[$key] = self::clampString(is_string($val) ? $val : '', 120);
        }

        if ($out['logo_alt'] === '' && $out['name'] !== '') {
            $out['logo_alt'] = $out['name'];
        }

        return $out;
    }

    private static function normalizeLogo(mixed $value, string $packageDefault): ?string
    {
        if ($value === null) {
            return null;
        }
        if (! is_string($value)) {
            return $packageDefault;
        }
        $v = trim($value);
        if ($v === '') {
            return null;
        }
        if (str_starts_with($v, 'http://') || str_starts_with($v, 'https://')) {
            return $v;
        }
        if (str_starts_with($v, '/') && ! str_contains($v, '..')) {
            return $v;
        }

        return $packageDefault;
    }

    private static function clampString(string $s, int $max): string
    {
        if (strlen($s) <= $max) {
            return $s;
        }

        return substr($s, 0, $max);
    }
}
