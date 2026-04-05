<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Resource;
use Vortex\Database\Model;

final class ResourceColumnsTest extends TestCase
{
    public function testDefaultsUseFillable(): void
    {
        self::assertSame(['id', 'title', 'body'], DemoResource::tableColumns());
        self::assertSame(['title', 'body'], DemoResource::formAttributes());
    }

    public function testExcludesSensitiveFromForm(): void
    {
        self::assertSame(['name'], UserLikeResource::formAttributes());
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
}
