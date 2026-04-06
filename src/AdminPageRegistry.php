<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Vortex\Config\Repository;
use Vortex\Routing\Route;

/**
 * Optional custom admin screens from {@code config/admin.php} key {@code pages} (GET routes + sidebar rows).
 * Register routes in {@see AdminPackage::boot()} before {@code /admin/{slug}} so paths are not captured as resource slugs.
 *
 * Controllers should extend {@see Http\AdminHttpController} and pass {@code adminPage} (same string as {@code id}) to
 * {@see Http\AdminHttpController::adminView()} for sidebar highlighting.
 */
final class AdminPageRegistry
{
    /** @var list<array<string, mixed>>|null */
    private static ?array $configPagesCache = null;

    public static function forget(): void
    {
        self::$configPagesCache = null;
    }

    public static function registerRoutes(): void
    {
        foreach (self::configPages() as $p) {
            Route::get($p['path'], $p['action'])->name($p['name']);
        }
    }

    /**
     * Sidebar rows: application {@code pages} first, then package defaults (e.g. table showcase).
     *
     * @return list<array{id: string, label: string, route: string, routeParams: array<string, string|int>, navIcon: string|null}>
     */
    public static function sidebarEntries(): array
    {
        $rows = [];
        foreach (self::configPages() as $p) {
            $rows[] = [
                'id' => $p['id'],
                'label' => $p['label'],
                'route' => $p['name'],
                'routeParams' => $p['routeParams'] ?? [],
                'navIcon' => $p['icon'] ?? null,
            ];
        }

        return [...$rows, ...self::packageSidebarEntries()];
    }

    /**
     * @return list<array{id: string, label: string, route: string, routeParams: array<string, string|int>, navIcon: string|null}>
     */
    private static function packageSidebarEntries(): array
    {
        return [
            [
                'id' => 'showcase-tables',
                'label' => 'Table showcase',
                'route' => 'admin.showcase.tables',
                'routeParams' => [],
                'navIcon' => 'table',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function configPages(): array
    {
        if (self::$configPagesCache !== null) {
            return self::$configPagesCache;
        }

        /** @var mixed $raw */
        $raw = Repository::get('admin.pages', []);
        if (! is_array($raw) || $raw === []) {
            return self::$configPagesCache = [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = isset($row['id']) && is_string($row['id']) ? trim($row['id']) : '';
            $path = isset($row['path']) && is_string($row['path']) ? trim($row['path']) : '';
            $name = isset($row['name']) && is_string($row['name']) ? trim($row['name']) : '';
            $label = isset($row['label']) && is_string($row['label']) ? trim($row['label']) : '';
            if ($id === '' || $path === '' || $name === '' || $label === '') {
                continue;
            }
            if (! self::isSafeAdminPath($path)) {
                continue;
            }
            $action = self::normalizeAction($row['action'] ?? null);
            if ($action === null) {
                continue;
            }
            $icon = isset($row['icon']) && is_string($row['icon']) && $row['icon'] !== '' ? $row['icon'] : null;
            /** @var array<string, string|int> $routeParams */
            $routeParams = [];
            if (isset($row['routeParams']) && is_array($row['routeParams'])) {
                foreach ($row['routeParams'] as $k => $v) {
                    if (! is_string($k) || $k === '') {
                        continue;
                    }
                    if (is_string($v) || is_int($v)) {
                        $routeParams[$k] = $v;
                    }
                }
            }
            $out[] = [
                'id' => $id,
                'path' => $path,
                'name' => $name,
                'action' => $action,
                'label' => $label,
                'icon' => $icon,
                'routeParams' => $routeParams,
            ];
        }

        return self::$configPagesCache = $out;
    }

    private static function isSafeAdminPath(string $path): bool
    {
        if (str_contains($path, '..')) {
            return false;
        }
        if (! str_starts_with($path, '/admin/')) {
            return false;
        }
        if ($path === '/admin/' || strlen($path) < 8) {
            return false;
        }

        return true;
    }

    /**
     * @return array{0: class-string, 1: string}|null
     */
    private static function normalizeAction(mixed $action): ?array
    {
        if (is_string($action) && $action !== '' && class_exists($action)) {
            return [$action, '__invoke'];
        }
        if (! is_array($action) || count($action) !== 2) {
            return null;
        }
        $c = $action[0] ?? null;
        $m = $action[1] ?? null;
        if (! is_string($c) || ! is_string($m) || $c === '' || $m === '' || ! class_exists($c)) {
            return null;
        }
        if (! method_exists($c, $m)) {
            return null;
        }

        return [$c, $m];
    }
}
