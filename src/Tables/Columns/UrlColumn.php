<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Tables\TableColumn;

final class UrlColumn extends TableColumn
{
    public function __construct(
        string $name,
        string $label,
        private readonly int $maxDisplayLength = 40,
        private readonly bool $external = true,
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->maxDisplayLength, $this->external);
    }

    public function displayKind(): string
    {
        return 'url';
    }

    /** Full href; Twig shortens anchor text using {@see maxDisplayLength}. */
    public function formatCellValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) $value;
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + [
            'external' => $this->external,
            'maxLabelLength' => $this->maxDisplayLength,
        ];
    }
}
