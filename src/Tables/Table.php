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
        private readonly ?string $emptyMessage = null,
        private readonly bool $columnPickerUiEnabled = true,
    ) {
    }

    public static function make(TableColumn ...$columns): self
    {
        return new self(
            array_values($columns),
            [],
            [EditRowAction::make(), DeleteRowAction::make()],
            null,
            true,
        );
    }

    /**
     * @return static
     */
    public function withFilters(TableFilter ...$filters): self
    {
        return new self($this->columns, array_values($filters), $this->actions, $this->emptyMessage, $this->columnPickerUiEnabled);
    }

    /**
     * Replace default index row actions (e.g. only edit, or an empty list for no actions column).
     *
     * @return static
     */
    public function withActions(TableRowAction ...$actions): self
    {
        return new self($this->columns, $this->filters, array_values($actions), $this->emptyMessage, $this->columnPickerUiEnabled);
    }

    /**
     * Add a search field that OR-matches {@code LIKE} across the given columns ({@see GlobalSearchFilter}).
     *
     * @param list<string> $columns Allowlisted attribute names on the resource model’s table.
     * @return static
     */
    public function withGlobalSearch(array $columns, ?string $label = null, string $paramName = 'search'): self
    {
        $filter = GlobalSearchFilter::make($paramName, $label ?? 'Search', $columns);

        return new self($this->columns, [...$this->filters, $filter], $this->actions, $this->emptyMessage, $this->columnPickerUiEnabled);
    }

    /**
     * Copy shown above the table when there are zero rows.
     *
     * @return static
     */
    public function withEmptyMessage(string $message): self
    {
        return new self($this->columns, $this->filters, $this->actions, $message, $this->columnPickerUiEnabled);
    }

    /**
     * Disable the index “Columns” visibility UI entirely.
     *
     * @return static
     */
    public function withoutColumnPicker(): self
    {
        return new self($this->columns, $this->filters, $this->actions, $this->emptyMessage, false);
    }

    public function emptyMessage(): ?string
    {
        return $this->emptyMessage;
    }

    public function columnPickerUiEnabled(): bool
    {
        return $this->columnPickerUiEnabled;
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
