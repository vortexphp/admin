<?php

declare(strict_types=1);

namespace Vortex\Admin\Console;

use Vortex\Admin\Codegen\AdminPageConfigMerger;
use Vortex\Admin\Codegen\AdminStub;
use Vortex\Console\Command;
use Vortex\Console\Input;

final class MakeAdminPageCommand extends Command
{
    private const CONTROLLER_NS = 'App\\Http\\Admin';

    public function description(): string
    {
        return 'Scaffold AdminHttpController + Twig view and register a config/admin.php pages entry. Example: make:admin-page Reports [--id=reports] [--no-register]';
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
            $this->line('Options: --id= --path= --route-name= --label= --icon= --no-view --no-register --force');
            $this->line('Creates app/Http/Admin/{Name}Controller.php and resources/views/admin/pages/…twig');

            return 1;
        }

        $raw = trim(implode(' ', $args));
        if (str_contains($raw, '\\')) {
            $this->error('Pass a short page name only (e.g. Reports), not a class name.');

            return 1;
        }

        $pascal = $this->toPascalCase($raw);
        if ($pascal === '') {
            $this->error('Name must contain letters or numbers.');

            return 1;
        }

        $controllerBase = str_ends_with($pascal, 'Controller') ? $pascal : $pascal . 'Controller';

        $idOpt = $input->option('id', '');
        $pageId = is_string($idOpt) && trim($idOpt) !== ''
            ? $this->sanitizePageId(trim($idOpt))
            : $this->defaultPageId($pascal);
        if ($pageId === null) {
            $this->error('Invalid --id (lowercase letters, numbers, single hyphens between segments).');

            return 1;
        }

        $urlSegment = str_replace('_', '-', $pageId);

        $pathOpt = $input->option('path', '');
        $path = is_string($pathOpt) && trim($pathOpt) !== ''
            ? trim($pathOpt)
            : '/admin/' . $urlSegment;
        if (! $this->isSafeAdminPath($path)) {
            $this->error('path must look like /admin/segment (no .., not /admin alone).');

            return 1;
        }

        $routeOpt = $input->option('route-name', '');
        $routeName = is_string($routeOpt) && trim($routeOpt) !== ''
            ? trim($routeOpt)
            : 'admin.pages.' . $urlSegment;

        $labelOpt = $input->option('label', '');
        $label = is_string($labelOpt) && trim($labelOpt) !== ''
            ? trim($labelOpt)
            : $this->humanLabel($pascal);

        $iconOpt = $input->option('icon', '');
        $icon = is_string($iconOpt) && trim($iconOpt) !== '' ? trim($iconOpt) : null;

        $viewName = 'admin.pages.' . $urlSegment;
        $viewRel = 'resources/views/admin/pages/' . $urlSegment . '.twig';

        $basePath = $this->basePath();
        $ctrlDir = $basePath . '/app/Http/Admin';
        if (! is_dir($ctrlDir) && ! mkdir($ctrlDir, 0775, true) && ! is_dir($ctrlDir)) {
            $this->error('Cannot create directory: ' . $ctrlDir);

            return 1;
        }

        $controllerFile = $ctrlDir . '/' . $controllerBase . '.php';
        if (is_file($controllerFile) && ! $input->flag('force')) {
            $this->error('File already exists: ' . $controllerFile . ' (use --force).');

            return 1;
        }

        $fqcn = self::CONTROLLER_NS . '\\' . $controllerBase;
        $titlePhp = var_export($label, true);
        $controllerSrc = AdminStub::render('admin_page_controller', [
            'NAMESPACE' => self::CONTROLLER_NS,
            'CONTROLLER_CLASS' => $controllerBase,
            'VIEW_NAME' => $viewName,
            'TITLE_PHP' => $titlePhp,
            'PAGE_ID' => $pageId,
        ]);

        if (file_put_contents($controllerFile, $controllerSrc) === false) {
            $this->error('Could not write: ' . $controllerFile);

            return 1;
        }
        $this->info('Created ' . $controllerFile);

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
            $hint = 'Replace this line with your content. Template: ' . $viewRel;
            $twigSrc = AdminStub::render('admin_page_view.twig', [
                'BODY_HINT' => $hint,
            ]);
            if (file_put_contents($viewPath, $twigSrc) === false) {
                $this->error('Could not write: ' . $viewPath);

                return 1;
            }
            $this->info('Created ' . $viewPath);
        }

        $pageBlock = $this->buildPageConfigBlock($pageId, $path, $routeName, $fqcn, $label, $icon);
        $noReg = $input->flag('no-register');

        if ($noReg) {
            $this->line('Skipped config/admin.php (--no-register). Add a pages[] row:');
            $this->line($this->pageConfigSnippet($pageId, $path, $routeName, $fqcn, $label, $icon));

            return 0;
        }

        $adminPhp = $basePath . '/config/admin.php';
        if (! is_file($adminPhp)) {
            $minimal = <<<'PHP'
<?php

declare(strict_types=1);

return [
];

PHP;
            if (file_put_contents($adminPhp, $minimal) === false) {
                $this->error('Could not create ' . $adminPhp);

                return 1;
            }
            $this->info('Created ' . $adminPhp);
        }

        $before = file_get_contents($adminPhp);
        if ($before === false) {
            $this->error('Could not read: ' . $adminPhp);

            return 1;
        }

        $merged = AdminPageConfigMerger::merge($before, $pageBlock);
        if ($merged === null || $merged === $before) {
            $this->warning('Could not merge into config/admin.php automatically.');
            $this->line('Add this to pages[] manually:');
            $this->line($this->pageConfigSnippet($pageId, $path, $routeName, $fqcn, $label, $icon));

            return 0;
        }

        if (file_put_contents($adminPhp, $merged) === false) {
            $this->error('Could not write: ' . $adminPhp);

            return 1;
        }
        $this->info('Updated config/admin.php (pages)');

        return 0;
    }

    private function buildPageConfigBlock(
        string $pageId,
        string $path,
        string $routeName,
        string $fqcn,
        string $label,
        ?string $icon,
    ): string {
        $block = sprintf(
            "        [\n            'id' => '%s',\n            'path' => '%s',\n            'name' => '%s',\n            'action' => [%s::class, 'index'],\n            'label' => %s,\n",
            addslashes($pageId),
            addslashes($path),
            addslashes($routeName),
            $fqcn,
            var_export($label, true),
        );
        if ($icon !== null && $icon !== '') {
            $block .= '            \'icon\' => ' . var_export($icon, true) . ",\n";
        }
        $block .= "        ],\n";

        return $block;
    }

    private function pageConfigSnippet(
        string $pageId,
        string $path,
        string $routeName,
        string $fqcn,
        string $label,
        ?string $icon,
    ): string {
        return trim($this->buildPageConfigBlock($pageId, $path, $routeName, $fqcn, $label, $icon));
    }

    private function isSafeAdminPath(string $path): bool
    {
        if (str_contains($path, '..')) {
            return false;
        }
        if (! str_starts_with($path, '/admin/')) {
            return false;
        }
        if ($path === '/admin/' || strlen($path) < 8) {
            return false;
        }

        return true;
    }

    private function humanLabel(string $pascal): string
    {
        $spaced = strtolower((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $pascal));

        return ucwords($spaced);
    }

    private function defaultPageId(string $pascal): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $pascal));
    }

    private function sanitizePageId(string $id): ?string
    {
        $id = strtolower(trim($id));
        if ($id === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id)) {
            return null;
        }

        return str_replace('-', '_', $id);
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
