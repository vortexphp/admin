<?php

declare(strict_types=1);

namespace Vortex\Admin;

use Closure;

/**
 * Mutable admin nav. The container holds one shared instance (see {@see AdminPackage::register}).
 * From your app package boot (after routes are registered), call e.g.
 * `$container->make(Navigation::class)->link('Notes', route('admin.resource.index', ['slug' => 'notes']));`
 */
final class Navigation
{
    /** @var list<NavEntry> */
    private array $entries = [];

    public static function make(): self
    {
        return new self();
    }

    public function link(string $label, string $href, ?string $icon = null, ?string $iconClass = null): self
    {
        $this->entries[] = new NavLink($label, $href, $icon, $iconClass);

        return $this;
    }

    /**
     * @param Closure(NavGroup): void $callback
     */
    public function group(string $label, Closure $callback): self
    {
        $g = new NavGroup($label);
        $callback($g);
        $this->entries[] = $g;

        return $this;
    }

    public function add(NavEntry $entry): self
    {
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * @return list<NavEntry>
     */
    public function all(): array
    {
        return $this->entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toViewArray(): array
    {
        return array_map(
            static fn (NavEntry $e): array => $e->toViewArray(),
            $this->entries,
        );
    }
}
