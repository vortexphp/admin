<?php

declare(strict_types=1);

namespace Vortex\Admin;

/**
 * Guards user-influenced fragments used in simple ORDER BY / WHERE column slots (single identifier, no dots).
 */
final class SqlIdentifier
{
    public static function isSafe(string $name): bool
    {
        return $name !== '' && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) === 1;
    }
}
