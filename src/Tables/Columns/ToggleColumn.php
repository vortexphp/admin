<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Tables\TableColumn;

/**
 * Renders a non-interactive switch reflecting a boolean (and optional empty state for null / unknown).
 */
final class ToggleColumn extends TableColumn
{
    public function __construct(
        string $name,
        string $label,
        private readonly string $trueLabel = 'On',
        private readonly string $falseLabel = 'Off',
        private readonly string $emptyLabel = '—',
        ?string $sortDatabaseColumn = null,
        bool $togglingEnabled = true,
        bool $startsCollapsed = false,
    ) {
        parent::__construct($name, $label, $sortDatabaseColumn, $togglingEnabled, $startsCollapsed);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->trueLabel, $this->falseLabel, $this->emptyLabel, $this->sortDatabaseColumn(), $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function labels(string $true, string $false, string $empty = '—'): self
    {
        return new self($this->name, $this->label, $true, $false, $empty, $this->sortDatabaseColumn(), $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function sortable(?string $databaseColumn = null): self
    {
        $col = $databaseColumn ?? $this->name;

        return new self($this->name, $this->label, $this->trueLabel, $this->falseLabel, $this->emptyLabel, $col, $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function alwaysVisible(): self
    {
        return new self($this->name, $this->label, $this->trueLabel, $this->falseLabel, $this->emptyLabel, $this->sortDatabaseColumn(), false, false);
    }

    public function collapsedByDefault(): self
    {
        return new self($this->name, $this->label, $this->trueLabel, $this->falseLabel, $this->emptyLabel, $this->sortDatabaseColumn(), true, true);
    }

    public function displayKind(): string
    {
        return 'toggle';
    }

    /**
     * @return true|false|null Null means show the empty state (null, '', or non-boolean values).
     */
    public function formatCellValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
            return true;
        }
        if ($value === false || $value === 0 || $value === '0' || $value === 'false') {
            return false;
        }

        return null;
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + [
            'trueLabel' => $this->trueLabel,
            'falseLabel' => $this->falseLabel,
            'emptyLabel' => $this->emptyLabel,
        ];
    }
}
