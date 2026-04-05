<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

/**
 * HTML date input ({@code Y-m-d}). Values normalize to that string or empty.
 */
final class DateField extends FormField
{
    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function inputKind(): string
    {
        return 'date';
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return '';
        }
        $s = is_string($raw) ? trim($raw) : (string) $raw;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) === 1) {
            return $s;
        }

        $t = strtotime($s);

        return $t !== false ? date('Y-m-d', $t) : '';
    }
}
