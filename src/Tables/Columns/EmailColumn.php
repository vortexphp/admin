<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Tables\TableColumn;

final class EmailColumn extends TableColumn
{
    public function __construct(
        string $name,
        string $label,
        private readonly int $maxLabelLength = 48,
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
        return new self($this->name, $label, $this->maxLabelLength, $this->sortDatabaseColumn(), $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function sortable(?string $databaseColumn = null): self
    {
        $col = $databaseColumn ?? $this->name;

        return new self($this->name, $this->label, $this->maxLabelLength, $col, $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function alwaysVisible(): self
    {
        return new self($this->name, $this->label, $this->maxLabelLength, $this->sortDatabaseColumn(), false, false);
    }

    public function collapsedByDefault(): self
    {
        return new self($this->name, $this->label, $this->maxLabelLength, $this->sortDatabaseColumn(), true, true);
    }

    public function displayKind(): string
    {
        return 'email';
    }

    /**
     * Full address for {@code mailto:}; Twig truncates visible label via {@see toViewArray} {@code maxLabelLength}.
     */
    public function formatCellValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) $value;
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + ['maxLabelLength' => $this->maxLabelLength];
    }
}
