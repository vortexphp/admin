<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Vortex\Admin\Forms\Form;
use Vortex\Admin\Tables\Table;
use Vortex\Database\Model;
use Vortex\Database\QueryBuilder;

/**
 * Filament-style resource: binds an Eloquent-like {@see Model} to admin CRUD routes.
 *
 * Register in {@code config/admin.php} ({@code resources}) and/or auto-discover via {@code discover} (see {@see ResourceDiscovery}).
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
     * Index table: use {@see Table} and column classes under {@see \Vortex\Admin\Tables\Columns} (e.g. {@see \Vortex\Admin\Tables\Columns\TextColumn}).
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

    /**
     * Extra {@see \Vortex\Database\QueryBuilder::with()} paths for the index query (merged with paths inferred from {@see table()} columns).
     *
     * @return list<string>
     */
    public static function indexQueryWith(): array
    {
        return [];
    }

    /**
     * Adjust the index {@see QueryBuilder} after filters / eager loads and before sort + pagination (scopes, joins, extra constraints).
     */
    public static function modifyIndexQuery(QueryBuilder $query): void
    {
    }

    /**
     * Default ORDER BY when the request has no {@code sort} param. Key {@code column} is a single SQL identifier; {@code direction} is {@code asc} or {@code desc}.
     *
     * @return array{column: string, direction: string}|null
     */
    public static function defaultTableSort(): ?array
    {
        return null;
    }

    /**
     * Form field values for Twig. {@code $record} is null on create. Override to hide or normalize attributes (e.g. blank password on edit).
     *
     * @return array<string, mixed>
     */
    public static function formValues(?Model $record): array
    {
        $values = [];
        foreach (static::form()->fieldNames() as $f) {
            $values[$f] = $record === null ? '' : ($record->{$f} ?? '');
        }

        return $values;
    }
}
