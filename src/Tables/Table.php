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
     * @param (callable(): array<int|string, array<string, mixed>>|list<array<string, mixed>>)|null $recordsCallback
     */
    public function __construct(
        private readonly array $columns,
        private readonly array $filters = [],
        private readonly array $actions = [],
        private readonly ?string $emptyMessage = null,
        private readonly bool $columnPickerUiEnabled = true,
        private $recordsCallback = null,
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
            null,
        );
    }

    /**
     * Use callback-supplied rows instead of the resource model query on the index. Return a list of
     * associative rows or an id-keyed map (missing {@code id} uses each key). Table filters / global
     * search are not applied to SQL; sorting uses in-memory values for the active column.
     *
     * @param callable(): array<int|string, array<string, mixed>>|list<array<string, mixed>> $provider
     * @return static
     */
    public function records(callable $provider): self
    {
        return new self(
            $this->columns,
            $this->filters,
            $this->actions,
            $this->emptyMessage,
            $this->columnPickerUiEnabled,
            $provider,
        );
    }

    /**
     * @return (callable(): mixed)|null
     */
    public function recordsProvider(): ?callable
    {
        return is_callable($this->recordsCallback) ? $this->recordsCallback : null;
    }

    /**
     * @return static
     */
    public function withFilters(TableFilter ...$filters): self
    {
        return new self($this->columns, array_values($filters), $this->actions, $this->emptyMessage, $this->columnPickerUiEnabled, $this->recordsCallback);
    }

    /**
     * Replace default index row actions (e.g. only edit, or an empty list for no actions column).
     *
     * @return static
     */
    public function withActions(TableRowAction ...$actions): self
    {
        return new self($this->columns, $this->filters, array_values($actions), $this->emptyMessage, $this->columnPickerUiEnabled, $this->recordsCallback);
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

        return new self($this->columns, [...$this->filters, $filter], $this->actions, $this->emptyMessage, $this->columnPickerUiEnabled, $this->recordsCallback);
    }

    /**
     * Copy shown above the table when there are zero rows.
     *
     * @return static
     */
    public function withEmptyMessage(string $message): self
    {
        return new self($this->columns, $this->filters, $this->actions, $message, $this->columnPickerUiEnabled, $this->recordsCallback);
    }

    /**
     * Disable the index “Columns” visibility UI entirely.
     *
     * @return static
     */
    public function withoutColumnPicker(): self
    {
        return new self($this->columns, $this->filters, $this->actions, $this->emptyMessage, false, $this->recordsCallback);
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
