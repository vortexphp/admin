<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests\Fixtures;

use Vortex\Admin\AdminPage;

/** @internal fixture for AdminPageRegistry tests */
final class SampleReportsAdminPage extends AdminPage
{
    public static function slug(): string
    {
        return 'reports';
    }

    public static function view(): string
    {
        return 'admin.pages.reports';
    }

    public static function title(): string
    {
        return 'Reports';
    }

    public static function description(): string
    {
        return 'Quarterly summaries';
    }

    public static function navigationIcon(): ?string
    {
        return 'document';
    }
}
