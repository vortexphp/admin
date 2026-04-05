<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextareaField;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TableColumn;
use Vortex\Database\Model;

final class ResourceColumnsTest extends TestCase
{
    public function testTableDefinesColumnsAndLabels(): void
    {
        $table = DemoResource::table();
        self::assertSame(['id', 'title'], $table->columnNames());
        self::assertSame('Title', $table->columns()[1]->label);
        $form = DemoResource::form();
        self::assertSame(['title', 'body'], $form->fieldNames());
        self::assertInstanceOf(TextareaField::class, $form->fields()[1]);
    }

    public function testExcludesSensitiveFromForm(): void
    {
        $table = UserLikeResource::table();
        self::assertSame(['id', 'name'], $table->columnNames());
        self::assertSame(['name'], UserLikeResource::form()->fieldNames());
    }
}

final class DemoModel extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['title', 'body'];
}

final class DemoResource extends Resource
{
    public static function model(): string
    {
        return DemoModel::class;
    }

    public static function slug(): string
    {
        return 'demos';
    }

    public static function table(): Table
    {
        return Table::make(
            TableColumn::make('id'),
            TableColumn::make('title', 'Title'),
        );
    }

    public static function form(): Form
    {
        return Form::make(
            TextField::make('title'),
            TextareaField::make('body'),
        );
    }
}

final class UserLikeModel extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name', 'password', 'remember_token'];
}

final class UserLikeResource extends Resource
{
    public static function model(): string
    {
        return UserLikeModel::class;
    }

    public static function slug(): string
    {
        return 'users';
    }

    public static function table(): Table
    {
        return Table::make(
            TableColumn::make('id'),
            TableColumn::make('name', 'Name'),
        );
    }

    public static function form(): Form
    {
        return Form::make(TextField::make('name'));
    }
}
