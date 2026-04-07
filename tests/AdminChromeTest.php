<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\AdminChrome;
use Vortex\Config\Repository;

final class AdminChromeTest extends TestCase
{
    public function testDefaultsWhenChromeMissing(): void
    {
        $base = sys_get_temp_dir() . '/vortex_chrome_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents($base . '/config/admin.php', "<?php\nreturn [];\n");
        Repository::setInstance(new Repository($base . '/config'));
        try {
            $c = AdminChrome::viewData();
            self::assertTrue($c['search']['enabled']);
            self::assertSame('/admin', $c['search']['action']);
            self::assertSame('q', $c['search']['query_param']);
            self::assertSame('Search…', $c['search']['placeholder']);
            self::assertNull($c['user']);
        } finally {
            Repository::forgetInstance();
        }
    }

    public function testSearchUsesPathWhenSet(): void
    {
        $base = sys_get_temp_dir() . '/vortex_chrome_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['chrome' => ['search' => ['path' => '/admin/find']]];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        try {
            $c = AdminChrome::viewData();
            self::assertSame('/admin/find', $c['search']['action']);
        } finally {
            Repository::forgetInstance();
        }
    }

    public function testSearchDisabled(): void
    {
        $base = sys_get_temp_dir() . '/vortex_chrome_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['chrome' => ['search' => ['enabled' => false]]];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        try {
            $c = AdminChrome::viewData();
            self::assertFalse($c['search']['enabled']);
        } finally {
            Repository::forgetInstance();
        }
    }

    public function testUserMenuNormalized(): void
    {
        $base = sys_get_temp_dir() . '/vortex_chrome_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn " . var_export([
                'chrome' => [
                    'user' => [
                        'name' => 'Alex Pat',
                        'email' => 'alex@example.test',
                        'menu' => [
                            ['label' => 'Profile', 'href' => '/me'],
                            ['label' => 'Out', 'href' => '/out', 'danger' => true, 'external' => true],
                        ],
                    ],
                ],
            ], true) . ";\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        try {
            $c = AdminChrome::viewData();
            self::assertNotNull($c['user']);
            self::assertSame('Alex Pat', $c['user']['name']);
            self::assertSame('alex@example.test', $c['user']['email']);
            self::assertSame('AP', $c['user']['initials']);
            self::assertCount(2, $c['user']['menu']);
            self::assertTrue($c['user']['menu'][1]['danger']);
            self::assertTrue($c['user']['menu'][1]['external']);
        } finally {
            Repository::forgetInstance();
        }
    }

    public function testUserInitialsSingleWord(): void
    {
        $base = sys_get_temp_dir() . '/vortex_chrome_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['chrome' => ['user' => ['name' => 'Taylor', 'email' => '', 'menu' => [['label' => 'x', 'href' => '/']]]]];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        try {
            self::assertSame('TA', AdminChrome::viewData()['user']['initials']);
        } finally {
            Repository::forgetInstance();
        }
    }

    public function testEmptyUserReturnsNull(): void
    {
        $base = sys_get_temp_dir() . '/vortex_chrome_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['chrome' => ['user' => ['name' => '', 'email' => '', 'menu' => []]]];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        try {
            self::assertNull(AdminChrome::viewData()['user']);
        } finally {
            Repository::forgetInstance();
        }
    }
}
