<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

use Vortex\Database\QueryBuilder;

/**
 * Index table filter: reads a query-string value and applies a constraint to {@see QueryBuilder}.
 */
abstract class TableFilter
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
    ) {
    }

    /**
     * Query string key ({@code f_}{@code name}).
     */
    public function queryParam(): string
    {
        return 'f_' . $this->name;
    }

    /**
     * {@code text} — single-line input; {@code select} — dropdown from {@see options()}.
     */
    abstract public function inputKind(): string;

    /**
     * @return array<string, string> option value => label (select filters only).
     */
    public function options(): array
    {
        return [];
    }

    abstract public function apply(QueryBuilder $query, mixed $raw): void;

    protected static function defaultLabel(string $name): string
    {
        $name = str_replace('_', ' ', $name);

        return ucwords($name);
    }
}
