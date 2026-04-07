<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\AdminBranding;
use Vortex\Config\Repository;

final class AdminBrandingTest extends TestCase
{
    public function testDefaultsWhenBrandingMissing(): void
    {
        $base = sys_get_temp_dir() . '/vortex_brand_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents($base . '/config/admin.php', "<?php\nreturn [];\n");
        Repository::setInstance(new Repository($base . '/config'));
        try {
            $b = AdminBranding::viewData();
            self::assertSame('Admin', $b['name']);
            self::assertSame('/img/vortexadmin.svg', $b['logo']);
            self::assertSame('Admin', $b['logo_alt']);
            self::assertSame('Vortex', $b['footer_vendor']);
            self::assertSame('control panel', $b['footer_tagline']);
        } finally {
            Repository::forgetInstance();
        }
    }

    public function testEmptyLogoHidesImage(): void
    {
        $base = sys_get_temp_dir() . '/vortex_brand_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['branding' => ['logo' => '', 'name' => 'CMS']];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        try {
            $b = AdminBranding::viewData();
            self::assertNull($b['logo']);
            self::assertSame('CMS', $b['name']);
        } finally {
            Repository::forgetInstance();
        }
    }

    public function testCustomBranding(): void
    {
        $base = sys_get_temp_dir() . '/vortex_brand_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn " . var_export([
                'branding' => [
                    'name' => 'Acme',
                    'logo' => 'https://cdn.example/logo.svg',
                    'logo_alt' => 'Acme Inc',
                    'footer_vendor' => 'Acme',
                    'footer_tagline' => 'Operations',
                ],
            ], true) . ";\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        try {
            $b = AdminBranding::viewData();
            self::assertSame('Acme', $b['name']);
            self::assertSame('https://cdn.example/logo.svg', $b['logo']);
            self::assertSame('Acme Inc', $b['logo_alt']);
            self::assertSame('Acme', $b['footer_vendor']);
            self::assertSame('Operations', $b['footer_tagline']);
        } finally {
            Repository::forgetInstance();
        }
    }
}
