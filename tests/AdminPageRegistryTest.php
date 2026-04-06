<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\AdminPageRegistry;
use Vortex\Config\Repository;

final class AdminPageRegistryTest extends TestCase
{
    public function testSidebarIncludesPackageShowcaseWhenConfigEmpty(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admpage_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents($base . '/config/admin.php', "<?php\nreturn [];\n");
        Repository::setInstance(new Repository($base . '/config'));
        AdminPageRegistry::forget();
        try {
            $rows = AdminPageRegistry::sidebarEntries();
            self::assertCount(1, $rows);
            self::assertSame('showcase-tables', $rows[0]['id']);
            self::assertSame('admin.showcase.tables', $rows[0]['route']);
        } finally {
            Repository::forgetInstance();
            AdminPageRegistry::forget();
        }
    }

    public function testConfigPagesAppendToSidebarBeforePackage(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admpage_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        $action = var_export([StubAdminPageController::class, 'index'], true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'pages' => [\n        [\n            'id' => 'reports',\n            'path' => '/admin/reports',\n            'name' => 'admin.pages.reports',\n            'action' => {$action},\n            'label' => 'Reports',\n            'icon' => 'document',\n        ],\n    ],\n];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        AdminPageRegistry::forget();
        try {
            $rows = AdminPageRegistry::sidebarEntries();
            self::assertCount(2, $rows);
            self::assertSame('reports', $rows[0]['id']);
            self::assertSame('admin.pages.reports', $rows[0]['route']);
            self::assertSame([], $rows[0]['routeParams']);
            self::assertSame('document', $rows[0]['navIcon']);
            self::assertSame('showcase-tables', $rows[1]['id']);
        } finally {
            Repository::forgetInstance();
            AdminPageRegistry::forget();
        }
    }

    public function testInvalidRowsSkipped(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admpage_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['pages' => [['id'=>'x','path'=>'/evil/../admin/x','name'=>'a','action'=>['stdClass','n'],'label'=>'L']]];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        AdminPageRegistry::forget();
        try {
            $rows = AdminPageRegistry::sidebarEntries();
            self::assertCount(1, $rows);
            self::assertSame('showcase-tables', $rows[0]['id']);
        } finally {
            Repository::forgetInstance();
            AdminPageRegistry::forget();
        }
    }
}
