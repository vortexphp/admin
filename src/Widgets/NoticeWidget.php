<?php

declare(strict_types=1);

namespace Vortex\Admin\Widgets;

/**
 * Inline callout: {@see NoticeTone} maps to zinc / amber / rose accents.
 */
final class NoticeWidget implements Widget
{
    public function __construct(
        private readonly NoticeTone $tone,
        private readonly string $message,
        private readonly ?string $title = null,
    ) {
    }

    public function toViewArray(): array
    {
        return [
            'kind' => 'notice',
            'tone' => $this->tone->value,
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
