<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/** GET link to the resource edit screen. */
final class EditRowAction extends TableRowAction
{
    public static function make(?string $label = null): self
    {
        return new self($label ?? 'Edit');
    }

    public function withLabel(string $label): self
    {
        return new self($label);
    }

    public function resolve(string $slug, array $row): ?array
    {
        $id = $row['id'] ?? null;
        if ($id === null || $id === '') {
            return null;
        }

        return [
            'kind' => 'link',
            'label' => $this->label,
            'route' => 'admin.resource.edit',
            'routeParams' => ['slug' => $slug, 'id' => (string) $id],
        ];
    }
}
