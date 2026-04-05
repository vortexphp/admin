<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

final class EmailField extends FormField
{
    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function inputKind(): string
    {
        return 'email';
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return '';
        }

        return is_string($raw) ? trim($raw) : (string) $raw;
    }
}
