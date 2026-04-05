<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

/**
 * One field on a resource create/edit {@see Form}. Each {@see inputKind()} maps to
 * {@code resources/views/admin/resource/fields/{kind}.twig}.
 */
abstract class FormField
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
    ) {
    }

    abstract public function inputKind(): string;

    /**
     * @return array<string, mixed>
     */
    public function toViewArray(): array
    {
        return [
            'inputKind' => $this->inputKind(),
            'name' => $this->name,
            'label' => $this->label,
        ];
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null) {
            return '';
        }

        return is_string($raw) ? trim($raw) : $raw;
    }

    /**
     * @return static
     */
    public function label(string $label): static
    {
        return new static($this->name, $label);
    }

    protected static function defaultLabel(string $name): string
    {
        $name = str_replace('_', ' ', $name);

        return ucwords($name);
    }
}
