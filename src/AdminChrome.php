<?php

declare(strict_types=1);

namespace Vortex\Admin;

use RuntimeException;
use Vortex\AppContext;
use Vortex\Config\Repository;
use Vortex\Routing\Router;

/**
 * Top bar chrome from {@code config/admin.php} key {@code chrome} (search + user menu).
 */
final class AdminChrome
{
    /**
     * @return array{
     *     search: array{enabled: bool, action: string, query_param: string, placeholder: string},
     *     user: array{
     *         name: string,
     *         email: string,
     *         avatar: string|null,
     *         initials: string,
     *         menu: list<array{label: string, href: string, external: bool, danger: bool}>,
     *     }|null,
     * }
     */
    public static function viewData(): array
    {
        /** @var mixed $raw */
        $raw = Repository::get('admin.chrome', []);
        if (! is_array($raw)) {
            $raw = [];
        }

        /** @var mixed $searchRaw */
        $searchRaw = $raw['search'] ?? [];
        if (! is_array($searchRaw)) {
            $searchRaw = [];
        }

        $searchEnabled = ! isset($searchRaw['enabled']) || $searchRaw['enabled'] !== false;
        $queryParam = self::clampString(is_string($searchRaw['query_param'] ?? null) ? $searchRaw['query_param'] : 'q', 32);
        if ($queryParam === '') {
            $queryParam = 'q';
        }
        $placeholder = self::clampString(
            is_string($searchRaw['placeholder'] ?? null) ? $searchRaw['placeholder'] : 'Search…',
            120,
        );

        $action = self::resolveSearchAction($searchRaw);

        /** @var mixed $userRaw */
        $userRaw = $raw['user'] ?? null;
        $user = null;
        if (is_array($userRaw)) {
            $user = self::normalizeUser($userRaw);
        }

        return [
            'search' => [
                'enabled' => $searchEnabled,
                'action' => $action,
                'query_param' => $queryParam,
                'placeholder' => $placeholder,
            ],
            'user' => $user,
        ];
    }

    /**
     * @param array<string, mixed> $searchRaw
     */
    private static function resolveSearchAction(array $searchRaw): string
    {
        if (isset($searchRaw['path']) && is_string($searchRaw['path'])) {
            $p = trim($searchRaw['path']);
            if ($p !== '') {
                return $p;
            }
        }

        $route = is_string($searchRaw['route'] ?? null) ? $searchRaw['route'] : 'admin.dashboard';
        /** @var array<string, string|int|float> $params */
        $params = [];
        if (isset($searchRaw['route_params']) && is_array($searchRaw['route_params'])) {
            foreach ($searchRaw['route_params'] as $k => $v) {
                if (! is_string($k) || $k === '') {
                    continue;
                }
                if (is_string($v) || is_int($v) || is_float($v)) {
                    $params[$k] = $v;
                }
            }
        }

        try {
            $container = AppContext::container();

            return $container->make(Router::class)->path($route, $params);
        } catch (RuntimeException) {
            return '/admin';
        }
    }

    /**
     * @param array<string, mixed> $userRaw
     */
    private static function normalizeUser(array $userRaw): ?array
    {
        $name = self::clampString(is_string($userRaw['name'] ?? null) ? $userRaw['name'] : '', 120);
        $email = self::clampString(is_string($userRaw['email'] ?? null) ? $userRaw['email'] : '', 120);
        $avatar = null;
        if (isset($userRaw['avatar']) && is_string($userRaw['avatar'])) {
            $a = trim($userRaw['avatar']);
            if ($a !== '' && self::isSafeUrlOrPath($a)) {
                $avatar = $a;
            }
        }

        /** @var list<array{label: string, href: string, external: bool, danger: bool}> $menu */
        $menu = [];
        if (isset($userRaw['menu']) && is_array($userRaw['menu'])) {
            foreach ($userRaw['menu'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $label = self::clampString(is_string($item['label'] ?? null) ? $item['label'] : '', 120);
                $href = self::clampString(is_string($item['href'] ?? null) ? $item['href'] : '', 2048);
                if ($label === '' || $href === '') {
                    continue;
                }
                $menu[] = [
                    'label' => $label,
                    'href' => $href,
                    'external' => ! empty($item['external']),
                    'danger' => ! empty($item['danger']),
                ];
            }
        }

        if ($name === '' && $email === '' && $menu === []) {
            return null;
        }

        return [
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
            'initials' => self::initials($name, $email),
            'menu' => $menu,
        ];
    }

    private static function initials(string $name, string $email): string
    {
        if ($name !== '') {
            $trimmed = trim($name);
            $parts = array_values(array_filter(preg_split('/\s+/u', $trimmed) ?: [], static fn (string $p): bool => $p !== ''));
            $letters = '';
            foreach ($parts as $part) {
                $letters .= mb_strtoupper(mb_substr($part, 0, 1));
                if (mb_strlen($letters) >= 2) {
                    return mb_substr($letters, 0, 2);
                }
            }
            if (count($parts) === 1 && mb_strlen($parts[0]) >= 2) {
                return mb_strtoupper(mb_substr($parts[0], 0, 2));
            }
            if ($letters !== '') {
                return $letters;
            }
        }

        if ($email !== '') {
            return mb_strtoupper(mb_substr($email, 0, 1));
        }

        return '?';
    }

    private static function isSafeUrlOrPath(string $value): bool
    {
        if (str_starts_with($value, 'https://') || str_starts_with($value, 'http://')) {
            return true;
        }

        return str_starts_with($value, '/') && ! str_contains($value, '..');
    }

    private static function clampString(string $s, int $max): string
    {
        if (strlen($s) <= $max) {
            return $s;
        }

        return substr($s, 0, $max);
    }
}
