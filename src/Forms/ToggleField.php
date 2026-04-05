<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

/**
 * Boolean switch (styled checkbox); same semantics as {@see CheckboxField}.
 */
final class ToggleField extends FormField
{
    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function inputKind(): string
    {
        return 'toggle';
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '' || $raw === false || $raw === '0') {
            return false;
        }

        return $raw === true || $raw === 1 || $raw === '1' || $raw === 'on';
    }
}
