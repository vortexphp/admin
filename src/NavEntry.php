<?php

declare(strict_types=1);

namespace Vortex\Admin;

/**
 * Root-level item for {@see Navigation} (a link or a labeled group of links).
 */
interface NavEntry
{
    /**
     * @return array<string, mixed>
     */
    public function toViewArray(): array;
}
