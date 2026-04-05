<?php

declare(strict_types=1);

namespace Vortex\Admin\Widgets;

/**
 * Dashboard tile: {@see toViewArray()} must include {@code kind} (Twig partial name under admin/widgets/).
 */
interface Widget
{
    /**
     * @return array{kind: string, ...}
     */
    public function toViewArray(): array;
}
