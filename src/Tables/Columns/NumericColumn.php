<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Tables\TableColumn;

final class NumericColumn extends TableColumn
{
    public function __construct(
        string $name,
        string $label,
        private readonly int $decimals = 0,
        private readonly string $decPoint = '.',
        private readonly string $thousandsSep = '',
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null, int $decimals = 0): self
    {
        return new self($name, $label ?? self::defaultLabel($name), $decimals);
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->decimals, $this->decPoint, $this->thousandsSep);
    }

    public function withThousandsSeparator(string $sep = ','): self
    {
        return new self($this->name, $this->label, $this->decimals, $this->decPoint, $sep);
    }

    public function displayKind(): string
    {
        return 'numeric';
    }

    public function formatCellValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, $this->decimals, $this->decPoint, $this->thousandsSep);
    }
}
