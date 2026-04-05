<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Vortex\Config\Repository;

/**
 * Maps URL slugs to {@see Resource} classes from {@code admin.resources} config.
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
     * @return array<string, class-string<Resource>> ordered as in config
     */
    public static function slugToClass(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        /** @var mixed $raw */
        $raw = Repository::get('admin.resources', []);
        if (! is_array($raw)) {
            return self::$map = [];
        }

        $out = [];
        foreach ($raw as $class) {
            if (! is_string($class) || $class === '' || ! class_exists($class)) {
                continue;
            }
            if (! is_subclass_of($class, Resource::class)) {
                continue;
            }

            $slug = $class::slug();
            if ($slug === '' || isset($out[$slug])) {
                continue;
            }

            $modelClass = $class::model();
            if (! is_subclass_of($modelClass, \Vortex\Database\Model::class)) {
                continue;
            }

            $out[$slug] = $class;
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
}
