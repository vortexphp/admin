<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Vortex\Admin\Forms\Form;
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
     * Rows per admin index page when {@code per_page} is absent or invalid in the query string.
     * If this value is missing from {@see tablePerPageOptions()}, it is added automatically.
     */
    public static function tablePerPage(): int
    {
        return 15;
    }

    /**
     * Allowed “per page” values for the index UI and {@code per_page} query param (each bound {@code 1…100}).
     * A single value hides the selector; two or more show a dropdown.
     *
     * @return list<int>
     */
    public static function tablePerPageOptions(): array
    {
        return [10, 15, 25, 50];
    }

    /**
     * Create / edit form: use {@see Form} and concrete fields ({@see \Vortex\Admin\Forms\TextField}, {@see \Vortex\Admin\Forms\TextareaField}, …).
     */
    abstract public static function form(): Form;
}
