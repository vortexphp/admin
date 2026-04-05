<?php

declare(strict_types=1);

namespace Vortex\Admin;

use ReflectionClass;
use Vortex\Config\Repository;
use Vortex\Database\Model;

/**
 * Maps URL slugs to {@see Resource} classes from {@code admin.resources} plus optional {@see ResourceDiscovery}.
 */
final class ResourceRegistry
{
    /** @var array<string, class-string<Resource>>|null */
    private static ?array $map = null;

    public static function forget(): void
    {
        self::$map = null;
    }

    /**
     * @return array<string, class-string<Resource>> explicit config order first, then discovered (by slug).
     */
    public static function slugToClass(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $out = [];

        /** @var mixed $raw */
        $raw = Repository::get('admin.resources', []);
        if (is_array($raw)) {
            foreach ($raw as $class) {
                self::tryRegister($out, $class);
            }
        }

        foreach (ResourceDiscovery::classes() as $class) {
            self::tryRegister($out, $class);
        }

        return self::$map = $out;
    }

    /**
     * @return class-string<Resource>|null
     */
    public static function classForSlug(string $slug): ?string
    {
        return self::slugToClass()[$slug] ?? null;
    }

    /**
     * @param array<string, class-string<Resource>> $out
     * @param class-string|mixed $class
     */
    private static function tryRegister(array &$out, mixed $class): void
    {
        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return;
        }
        if (! is_subclass_of($class, Resource::class)) {
            return;
        }
        if ((new ReflectionClass($class))->isAbstract()) {
            return;
        }

        $slug = $class::slug();
        if ($slug === '' || isset($out[$slug])) {
            return;
        }

        $modelClass = $class::model();
        if (! is_subclass_of($modelClass, Model::class)) {
            return;
        }

        $out[$slug] = $class;
    }
}
