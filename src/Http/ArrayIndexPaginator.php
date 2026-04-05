<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Support\UrlHelp;

/**
 * Paginator compatible with {@code admin/resource/index.twig} when rows come from {@see \Vortex\Admin\Tables\Table::records()} instead of SQL.
 */
final class ArrayIndexPaginator
{
    public bool $hasPages;

    public int $page;

    public int $last_page;

    public int $total;

    public bool $onFirstPage;

    public bool $onLastPage;

    public function __construct(
        int $total,
        int $perPage,
        int $page,
        private readonly string $listPath,
    ) {
        $this->total = max(0, $total);
        $perPage = max(1, $perPage);
        $this->last_page = max(1, (int) ceil($this->total / $perPage));
        $this->page = max(1, min($page, $this->last_page));
        $this->hasPages = $this->last_page > 1;
        $this->onFirstPage = $this->page <= 1;
        $this->onLastPage = $this->page >= $this->last_page;
    }

    public function urlForPage(int $page): string
    {
        $page = max(1, min($page, $this->last_page));

        return UrlHelp::withQuery($this->listPath, ['page' => (string) $page]);
    }
}
