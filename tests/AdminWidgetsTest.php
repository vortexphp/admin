<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\ResourceRegistry;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Widgets\AdminOverviewStatsWidget;
use Vortex\Admin\Widgets\LinkListWidget;
use Vortex\Admin\Widgets\NoticeTone;
use Vortex\Admin\Widgets\NoticeWidget;
use Vortex\Admin\Widgets\ResourceLinksWidget;
use Vortex\Admin\Widgets\StatsGridWidget;
use Vortex\Admin\Widgets\TextWidget;
use Vortex\Config\Repository;
use Vortex\Database\Model;

final class AdminWidgetsTest extends TestCase
{
    public function testTextWidgetViewArray(): void
    {
        $w = new TextWidget('About', "Line one.\nLine two.");
        self::assertSame([
            'kind' => 'text',
            'title' => 'About',
            'body' => "Line one.\nLine two.",
        ], $w->toViewArray());
    }

    public function testStatsGridWidgetViewArray(): void
    {
        $w = new StatsGridWidget('KPIs', [
            ['label' => 'Users', 'value' => '42', 'hint' => 'active'],
            ['label' => 'Posts', 'value' => '7'],
        ]);
        self::assertSame([
            'kind' => 'stats_grid',
            'title' => 'KPIs',
            'items' => [
                ['label' => 'Users', 'value' => '42', 'hint' => 'active'],
                ['label' => 'Posts', 'value' => '7'],
            ],
        ], $w->toViewArray());
    }

    public function testLinkListWidgetViewArray(): void
    {
        $w = new LinkListWidget('Shortcuts', [
            ['label' => 'Home', 'href' => '/', 'description' => 'Site'],
        ]);
        self::assertSame([
            'kind' => 'link_list',
            'title' => 'Shortcuts',
            'items' => [
                ['label' => 'Home', 'href' => '/', 'description' => 'Site'],
            ],
        ], $w->toViewArray());
    }

    public function testNoticeWidgetViewArray(): void
    {
        $w = new NoticeWidget(NoticeTone::Warning, 'Check settings.', 'Heads up');
        self::assertSame([
            'kind' => 'notice',
            'tone' => 'warning',
            'title' => 'Heads up',
            'message' => 'Check settings.',
        ], $w->toViewArray());
    }

    public function testAdminOverviewStatsWidgetUsesRegistry(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admoverview_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['resources' => [\\Vortex\\Admin\\Tests\\WidgetTestNoteResource::class]];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        ResourceRegistry::forget();
        try {
            $row = (new AdminOverviewStatsWidget())->toViewArray();
            self::assertSame('stats_grid', $row['kind']);
            self::assertSame('Overview', $row['title']);
            self::assertCount(1, $row['items']);
            self::assertSame('Resources', $row['items'][0]['label']);
            self::assertSame('1', $row['items'][0]['value']);
        } finally {
            Repository::forgetInstance();
            ResourceRegistry::forget();
        }
    }

    public function testResourceLinksWidgetUsesRegistry(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admwidget_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['resources' => [\\Vortex\\Admin\\Tests\\WidgetTestNoteResource::class]];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        ResourceRegistry::forget();
        try {
            $w = new ResourceLinksWidget('CRUD');
            $row = $w->toViewArray();
            self::assertSame('resource_links', $row['kind']);
            self::assertSame('CRUD', $row['title']);
            self::assertCount(1, $row['items']);
            self::assertSame('widget-test-notes', $row['items'][0]['slug']);
            self::assertSame(WidgetTestNoteResource::pluralLabel(), $row['items'][0]['label']);
        } finally {
            Repository::forgetInstance();
            ResourceRegistry::forget();
        }
    }
}

final class WidgetTestModel extends Model
{
    /** @var list<string> */
    protected static array $fillable = [];
}

final class WidgetTestNoteResource extends Resource
{
    public static function model(): string
    {
        return WidgetTestModel::class;
    }

    public static function slug(): string
    {
        return 'widget-test-notes';
    }

    public static function table(): Table
    {
        return Table::make(TextColumn::make('id'));
    }

    public static function form(): Form
    {
        return Form::make();
    }
}
