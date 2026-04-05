<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Tables\TableColumn;
use Vortex\Database\Model;

/**
 * Shows a label from an ORM relation (e.g. {@code category.name}). Requires the same relation name/path in
 * {@see Model::eagerRelations()} and typically a public relation method on the model so {@code with()} can load it.
 */
final class BelongsToColumn extends TableColumn
{
    public function __construct(
        string $relationPath,
        string $label,
        private readonly string $displayAttribute = 'name',
        private readonly int $maxDisplayLength = 80,
        private readonly ?string $foreignKeyFallback = null,
        ?string $sortDatabaseColumn = null,
        bool $togglingEnabled = true,
        bool $startsCollapsed = false,
    ) {
        parent::__construct($relationPath, $label, $sortDatabaseColumn, $togglingEnabled, $startsCollapsed);
    }

    /**
     * @param string $relationPath Dot path segments match eager-load keys (e.g. {@code category} or {@code author.country})
     */
    public static function make(
        string $relationPath,
        ?string $label = null,
        string $displayAttribute = 'name',
        int $maxDisplayLength = 80,
        ?string $foreignKeyFallback = null,
    ): self {
        return new self(
            $relationPath,
            $label ?? self::defaultLabel($relationPath),
            $displayAttribute,
            $maxDisplayLength,
            $foreignKeyFallback,
        );
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->displayAttribute, $this->maxDisplayLength, $this->foreignKeyFallback, $this->sortDatabaseColumn(), $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function sortable(?string $databaseColumn = null): self
    {
        $fk = $databaseColumn ?? $this->foreignKeyFallback ?? self::defaultForeignKeyForPath($this->name);

        return new self($this->name, $this->label, $this->displayAttribute, $this->maxDisplayLength, $this->foreignKeyFallback, $fk, $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function alwaysVisible(): self
    {
        return new self($this->name, $this->label, $this->displayAttribute, $this->maxDisplayLength, $this->foreignKeyFallback, $this->sortDatabaseColumn(), false, false);
    }

    public function collapsedByDefault(): self
    {
        return new self($this->name, $this->label, $this->displayAttribute, $this->maxDisplayLength, $this->foreignKeyFallback, $this->sortDatabaseColumn(), true, true);
    }

    public function displayKind(): string
    {
        return 'text';
    }

    public function eagerRelationPaths(): array
    {
        return [$this->name];
    }

    public function resolveRowValue(Model|array $row): mixed
    {
        if (is_array($row)) {
            return $row[$this->name] ?? null;
        }

        $related = $this->terminalRelatedModel($row);
        if ($related !== null) {
            return $related->{$this->displayAttribute} ?? null;
        }

        $fk = $this->foreignKeyFallback ?? self::defaultForeignKeyForPath($this->name);

        return $row->{$fk} ?? null;
    }

    public function formatCellValue(mixed $value): mixed
    {
        if ($value === null) {
            return '';
        }
        $s = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
        if ($s !== '' && strlen($s) > $this->maxDisplayLength) {
            return substr($s, 0, max(0, $this->maxDisplayLength - 1)) . '…';
        }

        return $s;
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + ['maxLength' => $this->maxDisplayLength];
    }

    private function terminalRelatedModel(Model $row): ?Model
    {
        $current = $row;
        foreach (explode('.', $this->name) as $segment) {
            $next = $current->{$segment} ?? null;
            if (! $next instanceof Model) {
                return null;
            }
            $current = $next;
        }

        return $current === $row ? null : $current;
    }

    public static function defaultForeignKeyForPath(string $relationPath): string
    {
        $parts = explode('.', $relationPath);
        $leaf = $parts[count($parts) - 1];

        return $leaf . '_id';
    }
}
