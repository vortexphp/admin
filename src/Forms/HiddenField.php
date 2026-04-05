<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

final class HiddenField extends FormField
{
    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? $name);
    }

    public function inputKind(): string
    {
        return 'hidden';
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null) {
            return '';
        }

        return is_string($raw) ? $raw : (string) $raw;
    }
}
