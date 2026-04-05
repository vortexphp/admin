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
use Vortex\Config\Repository;
use Vortex\Database\Model;

final class ResourceRegistryTest extends TestCase
{
    public function testSlugToClassReadsConfig(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admreg_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['resources' => [\\Vortex\\Admin\\Tests\\StubNoteResource::class]];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        ResourceRegistry::forget();
        try {
            $map = ResourceRegistry::slugToClass();
            self::assertSame(['stub-notes' => StubNoteResource::class], $map);
            self::assertSame(StubNoteResource::class, ResourceRegistry::classForSlug('stub-notes'));
            $nav = ResourceRegistry::navigationSidebarEntries();
            self::assertCount(1, $nav);
            self::assertSame('stub-notes', $nav[0]['slug']);
            self::assertSame(StubNoteResource::pluralLabel(), $nav[0]['label']);
            self::assertSame('folder', $nav[0]['navIcon']);
        } finally {
            Repository::forgetInstance();
            ResourceRegistry::forget();
        }
    }

    public function testNavigationSidebarOmitsResourcesThatHideFromNav(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admnav_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['resources' => [\\Vortex\\Admin\\Tests\\StubNoteResource::class, \\Vortex\\Admin\\Tests\\StubHiddenNavResource::class]];\n",
        );
        Repository::setInstance(new Repository($base . '/config'));
        ResourceRegistry::forget();
        try {
            self::assertArrayHasKey('stub-hidden', ResourceRegistry::slugToClass());
            $nav = ResourceRegistry::navigationSidebarEntries();
            self::assertCount(1, $nav);
            self::assertSame('stub-notes', $nav[0]['slug']);
        } finally {
            Repository::forgetInstance();
            ResourceRegistry::forget();
        }
    }
}

final class StubModel extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['title'];
}

final class StubNoteResource extends Resource
{
    public static function model(): string
    {
        return StubModel::class;
    }

    public static function slug(): string
    {
        return 'stub-notes';
    }

    public static function table(): Table
    {
        return Table::make(
            TextColumn::make('id'),
            TextColumn::make('title'),
        );
    }

    public static function form(): Form
    {
        return Form::make(TextField::make('title'));
    }

    public static function navigationIcon(): ?string
    {
        return 'folder';
    }
}

final class StubHiddenNavResource extends Resource
{
    public static function model(): string
    {
        return StubModel::class;
    }

    public static function slug(): string
    {
        return 'stub-hidden';
    }

    public static function showInNavigation(): bool
    {
        return false;
    }

    public static function table(): Table
    {
        return Table::make(TextColumn::make('id'));
    }

    public static function form(): Form
    {
        return Form::make(TextField::make('title'));
    }
}
