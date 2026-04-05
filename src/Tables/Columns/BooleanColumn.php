<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Tables\TableColumn;

final class BooleanColumn extends TableColumn
{
    public function __construct(
        string $name,
        string $label,
        private readonly string $trueLabel = 'Yes',
        private readonly string $falseLabel = 'No',
        private readonly string $emptyLabel = '—',
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->trueLabel, $this->falseLabel, $this->emptyLabel);
    }

    public function labels(string $true, string $false, string $empty = '—'): self
    {
        return new self($this->name, $this->label, $true, $false, $empty);
    }

    public function displayKind(): string
    {
        return 'boolean';
    }

    public function formatCellValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $this->emptyLabel;
        }
        if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
            return $this->trueLabel;
        }
        if ($value === false || $value === 0 || $value === '0' || $value === 'false') {
            return $this->falseLabel;
        }

        return (string) $value;
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
