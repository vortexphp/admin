<?php

declare(strict_types=1);

namespace Vortex\Admin\Widgets;

enum NoticeTone: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Danger = 'danger';
}
