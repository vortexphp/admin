<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Tables\TableColumn;

final class TextColumn extends TableColumn
{
    public function __construct(
        string $name,
        string $label,
        private readonly int $maxDisplayLength = 80,
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null, int $maxDisplayLength = 80): self
    {
        return new self($name, $label ?? self::defaultLabel($name), $maxDisplayLength);
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->maxDisplayLength);
    }

    public function displayKind(): string
    {
        return 'text';
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
}
