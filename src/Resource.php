<?php

declare(strict_types=1);

namespace Vortex\Admin;

use ReflectionClass;
use Vortex\Admin\Tables\Table;
use Vortex\Database\Model;

/**
 * Filament-style resource: binds an Eloquent-like {@see Model} to admin CRUD routes.
 *
 * Register concrete classes in {@code config/admin.php} under {@code resources}.
 */
abstract class Resource
{
    /**
     * @return class-string<Model>
     */
    abstract public static function model(): string;

    /**
     * URL segment under {@code /admin/{slug}}.
     */
    abstract public static function slug(): string;

    /**
     * Singular label (forms, headings).
     */
    public static function label(): string
    {
        return ucwords(str_replace(['-', '_'], ' ', static::slug()));
    }

    /**
     * Plural label (index title, dashboard).
     */
    public static function pluralLabel(): string
    {
        return static::label() . 's';
    }

    /**
     * Index table: use {@see Table} and {@see \Vortex\Admin\Tables\TableColumn} to register columns and labels.
     */
    abstract public static function table(): Table;

    /**
     * Form fields for create / edit. Defaults: model {@code $fillable} minus {@see excludedFromForm()}.
     *
     * @return list<string>
     */
    public static function formAttributes(): array
    {
        $fillable = static::resolvedFillable();

        return array_values(array_diff($fillable, static::excludedFromForm()));
    }

    /**
     * Hidden fields on forms (use policies or custom resources for passwords).
     *
     * @return list<string>
     */
    public static function excludedFromForm(): array
    {
        return ['password', 'password_confirmation', 'remember_token', 'api_token'];
    }

    /**
     * @return list<string>
     */
    protected static function resolvedFillable(): array
    {
        $modelClass = static::model();
        $ref = new ReflectionClass($modelClass);
        $p = $ref->getProperty('fillable');
        $p->setAccessible(true);
        /** @var list<string>|array<int, string> $raw */
        $raw = $p->getValue(null) ?? [];

        return array_values(array_filter($raw, static fn ($k): bool => is_string($k) && $k !== ''));
    }
}
