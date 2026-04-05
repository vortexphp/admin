<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/**
 * Index table definition: ordered {@see TableColumn} instances and optional {@see TableFilter} instances.
 */
final class Table
{
    /**
     * @param list<TableColumn> $columns
     * @param list<TableFilter> $filters
     */
    public function __construct(
        private readonly array $columns,
        private readonly array $filters = [],
    ) {
    }

    public static function make(TableColumn ...$columns): self
    {
        return new self(array_values($columns), []);
    }

    /**
     * @return static
     */
    public function withFilters(TableFilter ...$filters): self
    {
        return new self($this->columns, array_values($filters));
    }

    /**
     * @return list<TableColumn>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return list<TableFilter>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * @return list<string> Model attribute names in display order.
     */
    public function columnNames(): array
    {
        return array_map(static fn (TableColumn $c): string => $c->name, $this->columns);
    }
}
