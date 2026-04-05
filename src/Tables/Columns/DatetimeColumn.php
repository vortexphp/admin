<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use DateTimeInterface;
use Vortex\Admin\Tables\TableColumn;

final class DatetimeColumn extends TableColumn
{
    public function __construct(
        string $name,
        string $label,
        private readonly string $format = 'Y-m-d H:i',
        private readonly string $emptyLabel = '—',
        ?string $sortDatabaseColumn = null,
        bool $togglingEnabled = true,
        bool $startsCollapsed = false,
    ) {
        parent::__construct($name, $label, $sortDatabaseColumn, $togglingEnabled, $startsCollapsed);
    }

    public static function make(string $name, ?string $label = null, string $format = 'Y-m-d H:i'): self
    {
        return new self($name, $label ?? self::defaultLabel($name), $format);
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->format, $this->emptyLabel, $this->sortDatabaseColumn(), $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function sortable(?string $databaseColumn = null): self
    {
        $col = $databaseColumn ?? $this->name;

        return new self($this->name, $this->label, $this->format, $this->emptyLabel, $col, $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function alwaysVisible(): self
    {
        return new self($this->name, $this->label, $this->format, $this->emptyLabel, $this->sortDatabaseColumn(), false, false);
    }

    public function collapsedByDefault(): self
    {
        return new self($this->name, $this->label, $this->format, $this->emptyLabel, $this->sortDatabaseColumn(), true, true);
    }

    public function displayKind(): string
    {
        return 'datetime';
    }

    public function formatCellValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $this->emptyLabel;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format($this->format);
        }
        if (is_string($value)) {
            $t = strtotime($value);

            return $t !== false ? date($this->format, $t) : $value;
        }

        return (string) $value;
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + ['emptyLabel' => $this->emptyLabel];
    }
}
