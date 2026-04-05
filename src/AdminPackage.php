<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Vortex\Admin\Http\DashboardController;
use Vortex\Admin\Http\ResourceController;
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

        Route::get('/admin/{slug}/create', [ResourceController::class, 'create'])->name('admin.resource.create');
        Route::post('/admin/{slug}', [ResourceController::class, 'store'])->name('admin.resource.store');
        Route::get('/admin/{slug}/{id}/edit', [ResourceController::class, 'edit'])->name('admin.resource.edit');
        Route::post('/admin/{slug}/{id}/delete', [ResourceController::class, 'destroy'])->name('admin.resource.destroy');
        Route::post('/admin/{slug}/{id}', [ResourceController::class, 'update'])->name('admin.resource.update');
        Route::get('/admin/{slug}', [ResourceController::class, 'index'])->name('admin.resource.index');
    }
}
