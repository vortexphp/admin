<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/** POST form to the resource destroy route (CSRF + confirm in the index template). */
final class DeleteRowAction extends TableRowAction
{
    public static function make(?string $label = null): self
    {
        return new self($label ?? 'Delete');
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
            'kind' => 'post',
            'label' => $this->label,
            'route' => 'admin.resource.destroy',
            'routeParams' => ['slug' => $slug, 'id' => (string) $id],
        ];
    }
}
