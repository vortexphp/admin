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
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->maxLabelLength);
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
