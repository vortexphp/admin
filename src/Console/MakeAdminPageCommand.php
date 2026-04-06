<?php

declare(strict_types=1);

namespace Vortex\Admin\Console;

use Vortex\Admin\Codegen\AdminStub;
use Vortex\Console\Command;
use Vortex\Console\Input;

final class MakeAdminPageCommand extends Command
{
    private const PAGE_NS = 'App\\Admin\\Pages';

    public function description(): string
    {
        return 'Scaffold App\\Admin\\Pages\\{Name}Page + Twig view (autoloaded when admin.page_discover is true). Example: make:admin-page Reports [--slug=reports]';
    }

    protected function shouldBootApplication(): bool
    {
        return true;
    }

    protected function execute(Input $input): int
    {
        $args = $input->arguments();
        if ($args === []) {
            $this->error('Usage: make:admin-page <Name> [options]');
            $this->line('Options: --slug= --label= --description= --icon= --hidden --no-view --force');
            $this->line('Creates app/Admin/Pages/{Name}Page.php and resources/views/admin/pages/{slug}.twig');

            return 1;
        }

        $raw = trim(implode(' ', $args));
        if (str_contains($raw, '\\')) {
            $this->error('Pass a short name only (e.g. Reports), not a class name.');

            return 1;
        }

        $pascal = $this->toPascalCase($raw);
        if ($pascal === '') {
            $this->error('Name must contain letters or numbers.');

            return 1;
        }

        $pageBase = str_ends_with($pascal, 'Page') ? $pascal : $pascal . 'Page';

        $slugOpt = $input->option('slug', '');
        $slug = is_string($slugOpt) && trim($slugOpt) !== ''
            ? $this->sanitizeSlug(trim($slugOpt))
            : $this->defaultKebabSlug($pascal);
        if ($slug === null) {
            $this->error('Invalid --slug (lowercase letters, numbers, hyphens between segments).');

            return 1;
        }

        $viewName = 'admin.pages.' . $slug;
        $viewRel = 'resources/views/admin/pages/' . $slug . '.twig';

        $basePath = $this->basePath();
        $pageDir = $basePath . '/app/Admin/Pages';
        if (! is_dir($pageDir) && ! mkdir($pageDir, 0775, true) && ! is_dir($pageDir)) {
            $this->error('Cannot create directory: ' . $pageDir);

            return 1;
        }

        $pageFile = $pageDir . '/' . $pageBase . '.php';
        if (is_file($pageFile) && ! $input->flag('force')) {
            $this->error('File already exists: ' . $pageFile . ' (use --force).');

            return 1;
        }

        $optional = $this->buildOptionalMethods($input, $pascal);
        $pageSrc = AdminStub::render('admin_page', [
            'NAMESPACE' => self::PAGE_NS,
            'PAGE_CLASS' => $pageBase,
            'SLUG' => $slug,
            'VIEW_NAME' => $viewName,
            'OPTIONAL_BLOCKS' => $optional,
        ]);

        if (file_put_contents($pageFile, $pageSrc) === false) {
            $this->error('Could not write: ' . $pageFile);

            return 1;
        }
        $this->info('Created ' . $pageFile);

        if (! $input->flag('no-view')) {
            $viewPath = $basePath . '/' . $viewRel;
            $viewDir = dirname($viewPath);
            if (! is_dir($viewDir) && ! mkdir($viewDir, 0775, true) && ! is_dir($viewDir)) {
                $this->error('Cannot create directory: ' . $viewDir);

                return 1;
            }
            if (is_file($viewPath) && ! $input->flag('force')) {
                $this->error('View already exists: ' . $viewPath . ' (use --force).');

                return 1;
            }
            $hint = 'Edit this template: ' . $viewRel;
            $twigSrc = AdminStub::render('admin_page_view.twig', [
                'BODY_HINT' => $hint,
            ]);
            if (file_put_contents($viewPath, $twigSrc) === false) {
                $this->error('Could not write: ' . $viewPath);

                return 1;
            }
            $this->info('Created ' . $viewPath);
        }

        $fqcn = self::PAGE_NS . '\\' . $pageBase;
        $this->line('Route: GET /admin/' . $slug . ' · name: admin.pages.' . str_replace('-', '_', $slug));
        $this->line('If admin.page_discover is false, add ' . $fqcn . '::class to config/admin.php `pages`.');

        return 0;
    }

    private function buildOptionalMethods(Input $input, string $pascal): string
    {
        $blocks = [];
        $labelOpt = $input->option('label', '');
        if (is_string($labelOpt) && trim($labelOpt) !== '') {
            $blocks[] = $this->methodBlock(
                'title',
                'string',
                'return ' . var_export(trim($labelOpt), true) . ';',
            );
        }

        $descOpt = $input->option('description', '');
        if (is_string($descOpt) && trim($descOpt) !== '') {
            $blocks[] = $this->methodBlock(
                'description',
                'string',
                'return ' . var_export(trim($descOpt), true) . ';',
            );
        }

        $iconOpt = $input->option('icon', '');
        if (is_string($iconOpt) && trim($iconOpt) !== '') {
            $blocks[] = $this->methodBlock(
                'navigationIcon',
                '?string',
                'return ' . var_export(trim($iconOpt), true) . ';',
            );
        }

        if ($input->flag('hidden')) {
            $blocks[] = $this->methodBlock(
                'showInNavigation',
                'bool',
                'return false;',
            );
        }

        return $blocks === [] ? '' : "\n" . implode("\n", $blocks);
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-string $returnType
     * @param non-empty-string $bodyReturn single statement body
     */
    private function methodBlock(string $name, string $returnType, string $bodyReturn): string
    {
        return <<<PHP
    public static function {$name}(): {$returnType}
    {
        {$bodyReturn}
    }
PHP;
    }

    private function defaultKebabSlug(string $pascal): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $pascal));
    }

    private function sanitizeSlug(string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return null;
        }

        return $slug;
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
}
