<?php

declare(strict_types=1);

namespace Vortex\Admin;

use ReflectionClass;
use Vortex\AppContext;
use Vortex\Admin\Http\AdminPageController;
use Vortex\Config\Repository;
use Vortex\Http\Response;
use Vortex\Routing\Route;

/**
 * Maps admin page slugs to {@see AdminPage} classes from {@code admin.pages} plus {@see PageDiscovery}.
 *
 * Registers literal {@code GET /admin/{slug}} routes before resource {@code /admin/{slug}}.
 */
final class AdminPageRegistry
{
    /** @var array<string, class-string<AdminPage>>|null */
    private static ?array $map = null;

    public static function forget(): void
    {
        self::$map = null;
    }

    /**
     * @return array<string, class-string<AdminPage>>
     */
    public static function slugToClass(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $out = [];

        /** @var mixed $raw */
        $raw = Repository::get('admin.pages', []);
        if (is_array($raw)) {
            foreach ($raw as $class) {
                self::tryRegister($out, $class);
            }
        }

        foreach (PageDiscovery::classes() as $class) {
            self::tryRegister($out, $class);
        }

        return self::$map = $out;
    }

    /**
     * @return class-string<AdminPage>|null
     */
    public static function classForSlug(string $slug): ?string
    {
        return self::slugToClass()[$slug] ?? null;
    }

    public static function registerRoutes(): void
    {
        foreach (self::slugToClass() as $slug => $class) {
            if (! self::isValidUrlSlug($slug)) {
                continue;
            }
            $path = '/admin/' . $slug;
            Route::get($path, static function () use ($class): Response {
                /** @var class-string<AdminPage> $class */
                return AppContext::container()->make(AdminPageController::class)->render($class);
            })->name($class::routeName());
        }
    }

    /**
     * Sidebar rows for pages that {@see AdminPage::showInNavigation()} (order matches registry), then package defaults (table showcase).
     *
     * @return list<array{slug: string, label: string, description: string, route: string, routeParams: array<string, string|int>, navIcon: string|null}>
     */
    public static function sidebarEntries(): array
    {
        $rows = [];
        foreach (self::slugToClass() as $slug => $class) {
            if (! $class::showInNavigation()) {
                continue;
            }
            $icon = $class::navigationIcon();
            $rows[] = [
                'slug' => $slug,
                'label' => $class::title(),
                'description' => $class::description(),
                'route' => $class::routeName(),
                'routeParams' => [],
                'navIcon' => is_string($icon) && $icon !== '' ? $icon : null,
            ];
        }

        return [...$rows, ...self::packageSidebarEntries()];
    }

    /**
     * @return list<array{slug: string, label: string, description: string, route: string, routeParams: array<string, string|int>, navIcon: string|null}>
     */
    private static function packageSidebarEntries(): array
    {
        return [
            [
                'slug' => 'showcase-tables',
                'label' => 'Table showcase',
                'description' => '',
                'route' => 'admin.showcase.tables',
                'routeParams' => [],
                'navIcon' => 'table',
            ],
        ];
    }

    /**
     * @param array<string, class-string<AdminPage>> $out
     */
    private static function tryRegister(array &$out, mixed $class): void
    {
        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return;
        }
        if (! is_subclass_of($class, AdminPage::class)) {
            return;
        }
        if ((new ReflectionClass($class))->isAbstract()) {
            return;
        }

        $slug = $class::slug();
        if ($slug === '' || isset($out[$slug]) || ! self::isValidUrlSlug($slug)) {
            return;
        }

        $out[$slug] = $class;
    }

    private static function isValidUrlSlug(string $slug): bool
    {
        return $slug !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }
}
