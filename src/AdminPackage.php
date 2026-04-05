<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Vortex\Admin\Http\DashboardController;
use Vortex\Container;
use Vortex\Package\Package;
use Vortex\Package\PackageRegistry;
use Vortex\Routing\Route;
use Vortex\View\Factory;

final class AdminPackage extends Package
{
    public function publicAssets(): array
    {
        return [
            'resources/admin.css' => 'css/admin.css',
        ];
    }

    public function boot(Container $container, string $basePath): void
    {
        $root = PackageRegistry::packageRootContainingClass(self::class);
        $container->make(Factory::class)->addTemplatePath($root . '/resources/views');

        Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');
    }
}
