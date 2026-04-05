<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/** GET link to a named application route (same shape as {@see EditRowAction}, different target). */
final class LinkRowAction extends TableRowAction
{
    /**
     * @param array<string, string|int> $routeParams
     */
    private function __construct(
        string $label,
        private readonly string $routeName,
        private readonly array $routeParams,
    ) {
        parent::__construct($label);
    }

    /**
     * @param array<string, string|int> $routeParams
     */
    public static function toRoute(string $label, string $routeName, array $routeParams = []): self
    {
        return new self($label, $routeName, $routeParams);
    }

    public function resolve(string $slug, array $row): ?array
    {
        return [
            'kind' => 'link',
            'label' => $this->label,
            'route' => $this->routeName,
            'routeParams' => $this->routeParams,
        ];
    }
}
