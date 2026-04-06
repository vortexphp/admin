<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\AdminPageRegistry;
use Vortex\Admin\Tests\Fixtures\SampleReportsAdminPage;
use Vortex\Config\Repository;

final class AdminPageRegistryTest extends TestCase
{
    public function testSidebarPackageShowcaseWhenNoPages(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admpage_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['page_discover' => false, 'pages' => []];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        AdminPageRegistry::forget();
        try {
            $rows = AdminPageRegistry::sidebarEntries();
            self::assertCount(1, $rows);
            self::assertSame('showcase-tables', $rows[0]['slug']);
            self::assertSame('admin.showcase.tables', $rows[0]['route']);
        } finally {
            Repository::forgetInstance();
            AdminPageRegistry::forget();
        }
    }

    public function testConfigPageClassesAppearBeforeShowcase(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admpage_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\n\nreturn " . var_export([
                'page_discover' => false,
                'pages' => [SampleReportsAdminPage::class],
            ], true) . ";\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        AdminPageRegistry::forget();
        try {
            $rows = AdminPageRegistry::sidebarEntries();
            self::assertCount(2, $rows);
            self::assertSame('reports', $rows[0]['slug']);
            self::assertSame('Reports', $rows[0]['label']);
            self::assertSame('Quarterly summaries', $rows[0]['description']);
            self::assertSame('admin.pages.reports', $rows[0]['route']);
            self::assertSame('document', $rows[0]['navIcon']);
            self::assertSame('showcase-tables', $rows[1]['slug']);
        } finally {
            Repository::forgetInstance();
            AdminPageRegistry::forget();
        }
    }

    public function testSlugToClassMapsRegisteredPages(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admpage_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\n\nreturn " . var_export([
                'page_discover' => false,
                'pages' => [SampleReportsAdminPage::class],
            ], true) . ";\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        AdminPageRegistry::forget();
        try {
            self::assertSame(
                ['reports' => SampleReportsAdminPage::class],
                AdminPageRegistry::slugToClass(),
            );
            self::assertSame(SampleReportsAdminPage::class, AdminPageRegistry::classForSlug('reports'));
        } finally {
            Repository::forgetInstance();
            AdminPageRegistry::forget();
        }
    }
}
