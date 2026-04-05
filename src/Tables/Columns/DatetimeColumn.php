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
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null, string $format = 'Y-m-d H:i'): self
    {
        return new self($name, $label ?? self::defaultLabel($name), $format);
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->format, $this->emptyLabel);
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
