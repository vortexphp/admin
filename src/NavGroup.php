<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Closure;

/**
 * Labeled section containing {@see NavLink} rows only (no nested groups).
 */
final class NavGroup implements NavEntry
{
    /** @var list<NavLink> */
    private array $links = [];

    public function __construct(
        public readonly string $label,
    ) {
    }

    public function link(string $label, string $href, ?string $icon = null, ?string $iconClass = null): self
    {
        $this->links[] = new NavLink($label, $href, $icon, $iconClass);

        return $this;
    }

    public function add(NavLink $link): self
    {
        $this->links[] = $link;

        return $this;
    }

    /**
     * @param Closure(self): void $callback
     */
    public static function make(string $label, Closure $callback): self
    {
        $g = new self($label);
        $callback($g);

        return $g;
    }

    /**
     * @return list<NavLink>
     */
    public function links(): array
    {
        return $this->links;
    }

    public function toViewArray(): array
    {
        return [
            'kind' => 'group',
            'label' => $this->label,
            'items' => array_map(
                static fn (NavLink $l): array => $l->toViewArray(),
                $this->links,
            ),
        ];
    }
}
