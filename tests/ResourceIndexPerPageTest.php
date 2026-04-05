<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Http\ResourceController;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Database\Model;
use ReflectionMethod;

final class ResourceIndexPerPageTest extends TestCase
{
    public function testResolvesPerPageFromQueryWhenWhitelisted(): void
    {
        $m = new ReflectionMethod(ResourceController::class, 'resolveTablePerPage');
        $m->setAccessible(true);
        /** @var array{perPage: int, options: list<int>} $a */
        $a = $m->invoke(null, PerPageStubResource::class, []);
        self::assertSame(8, $a['perPage']);
        self::assertSame([8, 16], $a['options']);

        /** @var array{perPage: int, options: list<int>} $b */
        $b = $m->invoke(null, PerPageStubResource::class, ['per_page' => '16']);
        self::assertSame(16, $b['perPage']);

        /** @var array{perPage: int, options: list<int>} $c */
        $c = $m->invoke(null, PerPageStubResource::class, ['per_page' => '999']);
        self::assertSame(8, $c['perPage']);
    }

    public function testMergesDefaultTablePerPageIntoOptionsWhenMissing(): void
    {
        $m = new ReflectionMethod(ResourceController::class, 'resolveTablePerPage');
        $m->setAccessible(true);
        /** @var array{perPage: int, options: list<int>} $a */
        $a = $m->invoke(null, MergeDefaultPerPageResource::class, []);
        self::assertSame(7, $a['perPage']);
        self::assertSame([7, 10, 15, 25, 50], $a['options']);
    }
}

final class PerPageDemoModel extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['title'];
}

final class PerPageStubResource extends Resource
{
    public static function model(): string
    {
        return PerPageDemoModel::class;
    }

    public static function slug(): string
    {
        return 'per-page-stub';
    }

    public static function table(): Table
    {
        return Table::make(TextColumn::make('id'));
    }

    public static function tablePerPageOptions(): array
    {
        return [8, 16];
    }

    public static function tablePerPage(): int
    {
        return 8;
    }

    public static function form(): Form
    {
        return Form::make(TextField::make('title'));
    }
}

final class MergeDefaultPerPageResource extends Resource
{
    public static function model(): string
    {
        return PerPageDemoModel::class;
    }

    public static function slug(): string
    {
        return 'merge-per-page';
    }

    public static function table(): Table
    {
        return Table::make(TextColumn::make('id'));
    }

    public static function tablePerPage(): int
    {
        return 7;
    }

    public static function form(): Form
    {
        return Form::make(TextField::make('title'));
    }
}
