<?php

declare(strict_types=1);

namespace Vortex\Admin\Console;

use Vortex\Admin\Codegen\ModelInspector;
use Vortex\Admin\Codegen\ResourceScaffolder;
use Vortex\Console\Command;
use Vortex\Console\Input;
use Vortex\Database\Model;

final class MakeAdminResourceCommand extends Command
{
    public function description(): string
    {
        return 'Scaffold an admin Resource under app/Admin/Resources from a Vortex Model (uses $fillable + $casts). Example: make:admin-resource Post [--slug=posts] [--force]';
    }

    protected function shouldBootApplication(): bool
    {
        return true;
    }

    protected function execute(Input $input): int
    {
        $args = $input->arguments();
        if ($args === []) {
            $this->error('Usage: make:admin-resource <ModelName|Fqcn> [--slug=segment] [--force]');
            $this->line('Model: short name resolves to App\\Models\\{Name}, or pass a FQCN.');

            return 1;
        }

        $raw = trim(implode(' ', $args));
        $modelClass = $this->resolveModelClass($raw);
        if ($modelClass === null) {
            $this->error('Model class not found. Use a name like `Post` (App\\Models\\Post) or a full FQCN.');

            return 1;
        }

        try {
            $info = ModelInspector::describe($modelClass);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return 1;
        }

        if ($info['fillable'] === []) {
            $this->error('Model has an empty $fillable array. Add mass-assignable attributes first, then re-run.');

            return 1;
        }

        $basePath = $this->basePath();
        $resourceDir = $basePath . '/app/Admin/Resources';
        if (! is_dir($resourceDir) && ! mkdir($resourceDir, 0775, true) && ! is_dir($resourceDir)) {
            $this->error('Cannot create directory: ' . $resourceDir);

            return 1;
        }

        $modelShort = $this->classBaseName($modelClass);
        $resourceBase = $modelShort . 'Resource';
        $target = $resourceDir . '/' . $resourceBase . '.php';

        if (is_file($target) && ! $input->flag('force')) {
            $this->error('File already exists: ' . $target . ' (use --force to overwrite).');

            return 1;
        }

        $slugOpt = $input->option('slug', '');
        $slug = is_string($slugOpt) && trim($slugOpt) !== ''
            ? $this->sanitizeSlug(trim($slugOpt))
            : $this->pluralizeSlug($modelShort);
        if ($slug === null) {
            $this->error('Invalid --slug (use lowercase letters, numbers, hyphen).');

            return 1;
        }

        $gen = ResourceScaffolder::generate($modelClass, $resourceBase, $slug);
        if (file_put_contents($target, $gen['contents']) === false) {
            $this->error('Could not write: ' . $target);

            return 1;
        }

        $this->info('Created ' . $target);
        $this->line('Register the class in config/admin.php under `resources` (and run discover if you rely on it).');

        return 0;
    }

    /**
     * @return class-string<Model>|null
     */
    private function resolveModelClass(string $arg): ?string
    {
        $arg = trim($arg);
        if ($arg === '') {
            return null;
        }
        if (str_contains($arg, '\\')) {
            return class_exists($arg) && is_subclass_of($arg, Model::class) ? $arg : null;
        }

        $pascal = $this->toPascalCase($arg);
        if ($pascal === '') {
            return null;
        }

        $candidate = 'App\\Models\\' . $pascal;

        return class_exists($candidate) && is_subclass_of($candidate, Model::class) ? $candidate : null;
    }

    private function classBaseName(string $fqcn): string
    {
        $i = strrpos($fqcn, '\\');

        return $i === false ? $fqcn : substr($fqcn, $i + 1);
    }

    private function toPascalCase(string $raw): string
    {
        $raw = preg_replace('/[^a-zA-Z0-9]+/', ' ', $raw) ?? '';
        $parts = preg_split('/\s+/', trim($raw)) ?: [];
        $out = '';
        foreach ($parts as $p) {
            if ($p === '') {
                continue;
            }
            $out .= ucfirst(strtolower($p));
        }

        return $out;
    }

    private function pluralizeSlug(string $pascal): ?string
    {
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $pascal));
        if ($snake === '') {
            return null;
        }
        if ($snake !== '' && preg_match('/[^aeiou]y$/', $snake) === 1) {
            $plural = substr($snake, 0, -1) . 'ies';
        } elseif (preg_match('/(s|x|z|ch|sh)$/', $snake) === 1) {
            $plural = $snake . 'es';
        } else {
            $plural = $snake . 's';
        }

        return $this->sanitizeSlug($plural);
    }

    private function sanitizeSlug(string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return null;
        }

        return $slug;
    }
}
