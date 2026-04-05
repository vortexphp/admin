<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/**
 * Index table definition: ordered {@see TableColumn} instances.
 */
final class Table
{
    /**
     * @param list<TableColumn> $columns
     */
    public function __construct(
        private readonly array $columns,
    ) {
    }

    public static function make(TableColumn ...$columns): self
    {
        return new self(array_values($columns));
    }

    /**
     * @return list<TableColumn>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return list<string> Model attribute names in display order.
     */
    public function columnNames(): array
    {
        return array_map(static fn (TableColumn $c): string => $c->name, $this->columns);
    }
}
