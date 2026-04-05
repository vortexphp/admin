<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/**
 * Index table definition: columns, optional {@see TableFilter}s, and optional {@see TableRowAction}s.
 */
final class Table
{
    /**
     * @param list<TableColumn> $columns
     * @param list<TableFilter> $filters
     * @param list<TableRowAction> $actions
     */
    public function __construct(
        private readonly array $columns,
        private readonly array $filters = [],
        private readonly array $actions = [],
    ) {
    }

    public static function make(TableColumn ...$columns): self
    {
        return new self(
            array_values($columns),
            [],
            [EditAction::make(), DeleteAction::make()],
        );
    }

    /**
     * @return static
     */
    public function withFilters(TableFilter ...$filters): self
    {
        return new self($this->columns, array_values($filters), $this->actions);
    }

    /**
     * Replace default index row actions (e.g. only edit, or an empty list for no actions column).
     *
     * @return static
     */
    public function withActions(TableRowAction ...$actions): self
    {
        return new self($this->columns, $this->filters, array_values($actions));
    }

    /**
     * @return list<TableRowAction>
     */
    public function actions(): array
    {
        return $this->actions;
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

    /**
     * @return list<string>
     */
    public function eagerRelationPaths(): array
    {
        $seen = [];
        foreach ($this->columns as $c) {
            foreach ($c->eagerRelationPaths() as $p) {
                if ($p !== '' && ! isset($seen[$p])) {
                    $seen[$p] = true;
                }
            }
        }

        return array_keys($seen);
    }
}
