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

final class TableSortResolutionTest extends TestCase
{
    public function testExplicitSortUsesColumnDefinition(): void
    {
        $m = new ReflectionMethod(ResourceController::class, 'resolveTableSort');
        $table = Table::make(TextColumn::make('title')->sortable());
        /** @var array{apply: array{0: string, 1: string}|null, uiKey: string|null, dir: string, persist: bool} $r */
        $r = $m->invoke(null, PlainSortResource::class, $table, ['sort' => 'title', 'sort_dir' => 'desc']);
        self::assertSame(['title', 'DESC'], $r['apply']);
        self::assertSame('title', $r['uiKey']);
        self::assertTrue($r['persist']);
    }

    public function testFallsBackToResourceDefault(): void
    {
        $m = new ReflectionMethod(ResourceController::class, 'resolveTableSort');
        $table = Table::make(
            TextColumn::make('id')->sortable(),
            TextColumn::make('title'),
        );
        /** @var array{apply: array{0: string, 1: string}|null, uiKey: string|null, dir: string, persist: bool} $r */
        $r = $m->invoke(null, DefaultSortResource::class, $table, []);
        self::assertSame(['id', 'DESC'], $r['apply']);
        self::assertSame('id', $r['uiKey']);
        self::assertFalse($r['persist']);
    }
}

final class PlainSortModel extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['title'];
}

final class PlainSortResource extends Resource
{
    public static function model(): string
    {
        return PlainSortModel::class;
    }

    public static function slug(): string
    {
        return 'plain-sort';
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

final class DefaultSortResource extends Resource
{
    public static function model(): string
    {
        return PlainSortModel::class;
    }

    public static function slug(): string
    {
        return 'default-sort';
    }

    public static function table(): Table
    {
        return Table::make(TextColumn::make('id'));
    }

    public static function form(): Form
    {
        return Form::make(TextField::make('title'));
    }

    public static function defaultTableSort(): ?array
    {
        return ['column' => 'id', 'direction' => 'desc'];
    }
}
