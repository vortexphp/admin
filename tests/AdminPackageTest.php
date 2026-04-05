<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\AdminPackage;
use Vortex\Package\PackageRegistry;

final class AdminPackageTest extends TestCase
{
    public function testPackageRootContainsComposerJson(): void
    {
        $root = PackageRegistry::packageRootContainingClass(AdminPackage::class);
        self::assertFileExists($root . '/composer.json');
    }

    public function testPublicAssetsMapsCssAndJs(): void
    {
        $pkg = new AdminPackage();
        self::assertSame([
            'resources/admin.css' => 'css/admin.css',
            'resources/admin.tables.js' => 'js/admin.tables.js',
        ], $pkg->publicAssets());
    }
}
