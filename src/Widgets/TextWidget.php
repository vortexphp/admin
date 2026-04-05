<?php

declare(strict_types=1);

namespace Vortex\Admin\Widgets;

/**
 * Escaped body copy (line breaks preserved). Optional title.
 */
final class TextWidget implements Widget
{
    public function __construct(
        private readonly ?string $title,
        private readonly string $body,
    ) {
    }

    public function toViewArray(): array
    {
        return [
            'kind' => 'text',
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
