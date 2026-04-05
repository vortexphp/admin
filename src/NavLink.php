<?php

declare(strict_types=1);

namespace Vortex\Admin;

/**
 * Single link; may appear at the root of {@see Navigation} or inside a {@see NavGroup}.
 */
final readonly class NavLink implements NavEntry
{
    public function __construct(
        public string $label,
        public string $href,
        public ?string $icon = null,
        public ?string $iconClass = null,
    ) {
    }

    /**
     * @param array<string, string|int|float> $routeParams
     */
    public static function route(
        string $label,
        string $routeName,
        array $routeParams = [],
        ?string $icon = null,
        ?string $iconClass = null,
    ): self {
        return new self($label, route($routeName, $routeParams), $icon, $iconClass);
    }

    public function toViewArray(): array
    {
        return [
            'kind' => 'link',
            'label' => $this->label,
            'href' => $this->href,
            'icon' => $this->icon,
            'iconClass' => $this->iconClass,
        ];
    }
}
