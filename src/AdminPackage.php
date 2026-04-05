<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Vortex\Admin\Http\DashboardController;
use Vortex\Admin\Http\ResourceController;
use Vortex\Admin\DashboardWidgets;
use Vortex\Admin\Navigation;
use Vortex\Admin\Widgets\AdminOverviewStatsWidget;
use Vortex\Admin\Widgets\NoticeTone;
use Vortex\Admin\Widgets\NoticeWidget;
use Vortex\Admin\Widgets\ResourceLinksWidget;
use Vortex\Admin\Widgets\TextWidget;
use Vortex\Container;
use Vortex\Package\Package;
use Vortex\Package\PackageRegistry;
use Vortex\Routing\Route;
use Vortex\View\Factory;

final class AdminPackage extends Package
{
    public function register(Container $container, string $basePath): void
    {
        $container->singleton(Navigation::class, static fn (): Navigation => Navigation::make());

        $container->singleton(DashboardWidgets::class, static fn (): DashboardWidgets => DashboardWidgets::make()
            ->add(new NoticeWidget(
                NoticeTone::Info,
                'Use the sidebar for navigation; open a resource below to manage records.',
                'Welcome',
            ))
            ->add(new AdminOverviewStatsWidget())
            ->add(new TextWidget(
                null,
                'Resources load from config/admin.php (discover → app/Admin/Resources, plus optional resources list).',
            ))
            ->add(new ResourceLinksWidget('Resources')));
    }

    public function publicAssets(): array
    {
        return [
            'resources/admin.css' => 'css/admin.css',
            'resources/admin.tables.js' => 'js/admin.tables.js',
            'resources/admin.modal.js' => 'js/admin.modal.js',
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
