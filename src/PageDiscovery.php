<?php

declare(strict_types=1);

namespace Vortex\Admin;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Vortex\Config\Repository;

/**
 * Finds {@see AdminPage} classes under {@code admin.page_discover} paths via project {@code composer.json} PSR-4 autoload.
 */
final class PageDiscovery
{
    /**
     * @return list<class-string<AdminPage>>
     */
    public static function classes(): array
    {
        $dirs = self::directories();
        if ($dirs === []) {
            return [];
        }

        $projectBase = Repository::basePath();
        $psr4 = self::psr4Autoload($projectBase);
        if ($psr4 === []) {
            return [];
        }

        $seen = [];
        $found = [];
        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            );
            foreach ($it as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $path = $file->getPathname();
                if (! str_ends_with(strtolower($path), '.php')) {
                    continue;
                }

                $class = self::classFromPath($path, $projectBase, $psr4);
                if ($class === null || isset($seen[$class])) {
                    continue;
                }
                if (! class_exists($class)) {
                    continue;
                }
                if (! is_subclass_of($class, AdminPage::class)) {
                    continue;
                }
                $ref = new ReflectionClass($class);
                if ($ref->isAbstract()) {
                    continue;
                }

                $seen[$class] = true;
                $found[] = $class;
            }
        }

        usort(
            $found,
            static fn (string $a, string $b): int => strcmp($a::slug(), $b::slug()),
        );

        return $found;
    }

    /**
     * @return list<string> absolute directory paths
     */
    private static function directories(): array
    {
        /** @var mixed $raw */
        $raw = Repository::get('admin.page_discover', true);
        if ($raw === false) {
            return [];
        }
        if ($raw === true) {
            $default = Repository::basePath() . '/app/Admin/Pages';

            return is_dir($default) ? [$default] : [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $base = Repository::basePath();
        $out = [];
        foreach ($raw as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            $out[] = self::isAbsolutePath($path)
                ? rtrim(str_replace('\\', '/', $path), '/')
                : $base . '/' . trim(str_replace('\\', '/', $path), '/');
        }

        return $out;
    }

    private static function isAbsolutePath(string $path): bool
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return true;
        }

        return strlen($path) > 2
            && ctype_alpha($path[0])
            && $path[1] === ':'
            && ($path[2] === '\\' || $path[2] === '/');
    }

    /**
     * @return array<string, string>
     */
    private static function psr4Autoload(string $projectBase): array
    {
        $composer = $projectBase . '/composer.json';
        if (! is_file($composer)) {
            return [];
        }
        /** @var mixed $json */
        $json = json_decode((string) file_get_contents($composer), true);
        if (! is_array($json)) {
            return [];
        }
        /** @var mixed $autoload */
        $autoload = $json['autoload'] ?? [];
        if (! is_array($autoload)) {
            return [];
        }
        /** @var mixed $psr4 */
        $psr4 = $autoload['psr-4'] ?? [];
        if (! is_array($psr4)) {
            return [];
        }

        $out = [];
        foreach ($psr4 as $nsPrefix => $relative) {
            if (is_string($nsPrefix) && is_string($relative) && $relative !== '') {
                $out[$nsPrefix] = $relative;
            }
        }

        return $out;
    }

    /**
     * @param array<string, string> $psr4
     * @return class-string|null
     */
    private static function classFromPath(string $absoluteFile, string $projectBase, array $psr4): ?string
    {
        $fileReal = realpath($absoluteFile);
        if ($fileReal === false) {
            return null;
        }
        $normFile = str_replace('\\', '/', $fileReal);

        $projectReal = realpath($projectBase);
        if ($projectReal === false) {
            return null;
        }
        $normBase = str_replace('\\', '/', $projectReal);

        foreach ($psr4 as $namespacePrefix => $relativeRoot) {
            $root = realpath($projectBase . '/' . trim(str_replace('\\', '/', $relativeRoot), '/'));
            if ($root === false) {
                continue;
            }
            $normRoot = str_replace('\\', '/', $root);
            if (! str_starts_with($normFile, $normRoot . '/')) {
                continue;
            }
            $rel = substr($normFile, strlen($normRoot) + 1);
            if (! str_ends_with($rel, '.php')) {
                return null;
            }
            $logical = substr($rel, 0, -4);
            $prefix = rtrim($namespacePrefix, '\\');
            $fqn = $prefix . '\\' . str_replace('/', '\\', $logical);

            return $fqn;
        }

        return null;
    }
}
