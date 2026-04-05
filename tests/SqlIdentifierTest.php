<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\SqlIdentifier;

final class SqlIdentifierTest extends TestCase
{
    public function testAllowsSimpleIdentifiers(): void
    {
        self::assertTrue(SqlIdentifier::isSafe('id'));
        self::assertTrue(SqlIdentifier::isSafe('category_id'));
        self::assertTrue(SqlIdentifier::isSafe('_x'));
    }

    public function testRejectsUnsafe(): void
    {
        self::assertFalse(SqlIdentifier::isSafe(''));
        self::assertFalse(SqlIdentifier::isSafe('x.y'));
        self::assertFalse(SqlIdentifier::isSafe('`x`'));
        self::assertFalse(SqlIdentifier::isSafe('1a'));
    }
}
