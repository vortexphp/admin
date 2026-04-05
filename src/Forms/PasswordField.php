<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

final class PasswordField extends FormField
{
    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function inputKind(): string
    {
        return 'password';
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null) {
            return '';
        }

        return is_string($raw) ? $raw : (string) $raw;
    }
}
