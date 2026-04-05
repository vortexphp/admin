<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

final class TextField extends FormField
{
    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function inputKind(): string
    {
        return 'text';
    }
}
