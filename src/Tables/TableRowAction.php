<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/**
 * One row control on the resource index (link or POST form). Resolved in the controller for Twig.
 */
abstract class TableRowAction
{
    public function __construct(
        protected readonly string $label,
    ) {
    }

    public function label(): string
    {
        return $this->label;
    }

    /**
     * {@code link} — GET anchor; {@code post} — destructive/other POST with CSRF.
     *
     * @param array<string, mixed> $row Same shape as index row payload (includes {@code id} when present).
     * @return array{
     *     kind: 'link'|'post',
     *     label: string,
     *     route: string,
     *     routeParams: array<string, string|int|float>
     * }|null
     */
    abstract public function resolve(string $slug, array $row): ?array;
}
