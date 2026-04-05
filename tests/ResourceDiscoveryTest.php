<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\ResourceRegistry;
use Vortex\Config\Repository;

final class ResourceDiscoveryTest extends TestCase
{
    public function testDiscoverTrueRegistersResourcesUnderAppAdminResources(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admdisc_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        mkdir($base . '/app/Admin/Resources', 0777, true);

        file_put_contents($base . '/composer.json', json_encode([
            'autoload' => ['psr-4' => ['App\\' => 'app/']],
        ], JSON_THROW_ON_ERROR));

        file_put_contents($base . '/config/admin.php', "<?php\nreturn ['resources' => [], 'discover' => true];\n");

        file_put_contents(
            $base . '/app/Admin/Resources/DiscoveredCrudResource.php',
            <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Admin\Resources;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Database\Model;
final class DiscoveredCrudModel extends Model { protected static array $fillable = ['title']; }
final class DiscoveredCrudResource extends Resource
{
    public static function model(): string { return DiscoveredCrudModel::class; }
    public static function slug(): string { return 'discovered-cruds'; }
    public static function table(): Table { return Table::make(TextColumn::make('id'), TextColumn::make('title')); }
    public static function form(): Form { return Form::make(TextField::make('title')); }
}
PHP,
        );

        Repository::setInstance(new Repository($base . '/config'));
        ResourceRegistry::forget();
        try {
            require_once $base . '/app/Admin/Resources/DiscoveredCrudResource.php';

            $map = ResourceRegistry::slugToClass();
            self::assertSame(
                \App\Admin\Resources\DiscoveredCrudResource::class,
                $map['discovered-cruds'] ?? null,
            );
        } finally {
            Repository::forgetInstance();
            ResourceRegistry::forget();
            $this->deleteTree($base);
        }
    }

    public function testExplicitResourceWinsOverDiscoveredClassWithSameSlug(): void
    {
        $base = sys_get_temp_dir() . '/vortex_admdisc_' . bin2hex(random_bytes(3));
        mkdir($base . '/config', 0777, true);
        mkdir($base . '/app/Admin/Resources', 0777, true);

        file_put_contents($base . '/composer.json', json_encode([
            'autoload' => ['psr-4' => ['App\\' => 'app/']],
        ], JSON_THROW_ON_ERROR));

        file_put_contents(
            $base . '/config/admin.php',
            "<?php\nreturn ['resources' => [\\Vortex\\Admin\\Tests\\StubNoteResource::class], 'discover' => true];\n",
        );

        file_put_contents(
            $base . '/app/Admin/Resources/AlsoStubResource.php',
            <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Admin\Resources;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tests\StubNoteResource as Stub;
use Vortex\Database\Model;
final class AlsoStubModel extends Model { protected static array $fillable = ['title']; }
/** Same slug as StubNoteResource (stub-notes); registration must keep the explicit class. */
final class AlsoStubResource extends Resource
{
    public static function model(): string { return AlsoStubModel::class; }
    public static function slug(): string { return Stub::slug(); }
    public static function table(): Table { return Stub::table(); }
    public static function form(): Form { return Form::make(TextField::make('title')); }
}
PHP,
        );

        Repository::setInstance(new Repository($base . '/config'));
        ResourceRegistry::forget();
        try {
            require_once $base . '/app/Admin/Resources/AlsoStubResource.php';

            $map = ResourceRegistry::slugToClass();
            self::assertSame(StubNoteResource::class, $map['stub-notes']);
        } finally {
            Repository::forgetInstance();
            ResourceRegistry::forget();
            $this->deleteTree($base);
        }
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            $path = $file->getPathname();
            $file->isDir() ? @rmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
