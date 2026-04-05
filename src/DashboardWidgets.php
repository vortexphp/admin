<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Vortex\Admin\Widgets\Widget;

/**
 * Mutable dashboard stack. Registered as a container singleton (see {@see AdminPackage::register}).
 *
 * From app {@code Package::boot()}:
 *
 * {@code $container->make(DashboardWidgets::class)->clear()->add(new StatsGridWidget(...)); }
 */
final class DashboardWidgets
{
    /** @var list<Widget> */
    private array $widgets = [];

    public static function make(): self
    {
        return new self();
    }

    public function clear(): self
    {
        $this->widgets = [];

        return $this;
    }

    public function add(Widget $widget): self
    {
        $this->widgets[] = $widget;

        return $this;
    }

    /**
     * @return list<Widget>
     */
    public function all(): array
    {
        return $this->widgets;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toViewArray(): array
    {
        return array_map(
            static fn (Widget $w): array => $w->toViewArray(),
            $this->widgets,
        );
    }
}
