<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Codegen\AdminPageConfigMerger;

final class AdminPageConfigMergerTest extends TestCase
{
    public function testMergeIntoEmptyPagesArray(): void
    {
        $before = "<?php\n\nreturn [\n    'pages' => [],\n];\n";
        $block = "        [\n            'id' => 'reports',\n        ],\n";
        $after = AdminPageConfigMerger::merge($before, $block);
        self::assertNotNull($after);
        self::assertStringContainsString("'id' => 'reports'", $after);
        self::assertNotSame($before, $after);
    }

    public function testMergeAppendsWhenPagesNonEmpty(): void
    {
        $before = <<<'PHP'
<?php

return [
    'pages' => [
        [
            'id' => 'first',
        ],
    ],
];
PHP;
        $block = "        [\n            'id' => 'second',\n        ],\n";
        $after = AdminPageConfigMerger::merge($before, $block);
        self::assertNotNull($after);
        self::assertStringContainsString("'id' => 'first'", $after);
        self::assertStringContainsString("'id' => 'second'", $after);
    }

    public function testMergeAddsPagesKeyWhenMissing(): void
    {
        $before = <<<'PHP'
<?php

declare(strict_types=1);

return [
    'discover' => true,
];
PHP;
        $block = "        [\n            'id' => 'x',\n        ],\n";
        $after = AdminPageConfigMerger::merge($before, $block);
        self::assertNotNull($after);
        self::assertStringContainsString("'pages' =>", $after);
        self::assertStringContainsString("'discover' => true", $after);
        self::assertStringContainsString("'id' => 'x'", $after);
    }

    public function testMergeIntoEmptyReturnArray(): void
    {
        $before = "<?php\nreturn [];\n";
        $block = "        [\n            'id' => 'z',\n        ],\n";
        $after = AdminPageConfigMerger::merge($before, $block);
        self::assertNotNull($after);
        self::assertStringContainsString("'pages'", $after);
        self::assertStringContainsString("'id' => 'z'", $after);
    }
}
