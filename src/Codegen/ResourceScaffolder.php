<?php

declare(strict_types=1);

namespace Vortex\Admin\Codegen;

/**
 * Builds {@see Resource} PHP source from a {@see ModelInspector} description.
 */
final class ResourceScaffolder
{
    private const LONG_TEXT_NAMES = ['body', 'content', 'description', 'text', 'bio', 'notes', 'summary', 'message'];

    /**
     * @param class-string<\Vortex\Database\Model> $modelFqcn
     * @return array{contents: string, uses: list<string>}
     */
    public static function generate(string $modelFqcn, string $resourceClassBase, string $slug): array
    {
        $info = ModelInspector::describe($modelFqcn);
        $fillable = $info['fillable'];
        $casts = $info['casts'];

        $uses = [
            'Vortex\Admin\Forms\Form',
            'Vortex\Admin\Resource',
            'Vortex\Admin\Tables\Table',
            $modelFqcn,
        ];

        $tableLines = [];
        $prependedId = false;
        if (! in_array('id', $fillable, true)) {
            $tableLines[] = '            TextColumn::make(\'id\')->sortable(),';
            $uses[] = 'Vortex\Admin\Tables\Columns\TextColumn';
            $prependedId = true;
        }

        $formLines = [];

        foreach ($fillable as $attr) {
            $kind = self::kindForAttribute($attr, $casts[$attr] ?? null);
            if ($kind['table'] === null) {
                if ($kind['form'] !== null) {
                    $uses = [...$uses, ...$kind['formUses']];
                    $formLines[] = '            ' . $kind['form'] . ',';
                }

                continue;
            }

            $uses = [...$uses, ...$kind['tableUses']];
            $tableLines[] = '            ' . $kind['table'] . ',';

            if ($kind['form'] !== null) {
                $uses = [...$uses, ...$kind['formUses']];
                $formLines[] = '            ' . $kind['form'] . ',';
            }
        }

        $uses = array_values(array_unique($uses));
        sort($uses);
        $useBlock = implode("\n", array_map(static fn (string $u): string => 'use ' . $u . ';', $uses));

        $tableInner = $tableLines !== [] ? implode("\n", $tableLines) . "\n" : '';
        $formInner = $formLines !== [] ? implode("\n", $formLines) . "\n" : '            // Add FormField::make(...) entries' . "\n";

        $sortBlock = '';
        if ($prependedId || in_array('id', $fillable, true)) {
            $sortBlock = <<<'PHP'

    public static function defaultTableSort(): ?array
    {
        return ['column' => 'id', 'direction' => 'desc'];
    }
PHP;
        }

        $contents = AdminStub::render('admin_resource', [
            'NAMESPACE' => 'App\\Admin\\Resources',
            'RESOURCE_CLASS' => $resourceClassBase,
            'MODEL_CLASS_SHORT' => self::shortClass($modelFqcn),
            'MODEL_FQCN' => $modelFqcn,
            'SLUG' => $slug,
            'USE_STATEMENTS' => $useBlock,
            'TABLE_COLUMNS' => rtrim($tableInner),
            'FORM_FIELDS' => rtrim($formInner),
            'DEFAULT_SORT' => $sortBlock,
        ]);

        return ['contents' => $contents, 'uses' => $uses];
    }

    /**
     * @return array{
     *     table: ?string,
     *     form: ?string,
     *     tableUses: list<string>,
     *     formUses: list<string>,
     * }
     */
    private static function kindForAttribute(string $name, ?string $cast): array
    {
        $n = strtolower($name);
        $c = $cast ?? '';

        $tableUses = [];
        $formUses = [];

        if ($n === 'password' || $n === 'secret' || $c === 'hash' || str_ends_with($n, '_secret')) {
            return [
                'table' => null,
                'form' => 'PasswordField::make(' . self::q($name) . ')',
                'tableUses' => [],
                'formUses' => ['Vortex\Admin\Forms\PasswordField'],
            ];
        }

        if ($c === 'bool' || $c === 'boolean') {
            return [
                'table' => 'BooleanColumn::make(' . self::q($name) . ')->sortable()',
                'form' => 'ToggleField::make(' . self::q($name) . ')',
                'tableUses' => ['Vortex\Admin\Tables\Columns\BooleanColumn'],
                'formUses' => ['Vortex\Admin\Forms\ToggleField'],
            ];
        }

        if ($c === 'int' || $c === 'integer') {
            return [
                'table' => 'NumericColumn::make(' . self::q($name) . ', null, 0)->sortable()',
                'form' => 'NumberField::make(' . self::q($name) . ')',
                'tableUses' => ['Vortex\Admin\Tables\Columns\NumericColumn'],
                'formUses' => ['Vortex\Admin\Forms\NumberField'],
            ];
        }

        if ($c === 'float' || $c === 'double' || $c === 'decimal') {
            return [
                'table' => 'NumericColumn::make(' . self::q($name) . ', null, 2)->sortable()',
                'form' => 'NumberField::make(' . self::q($name) . ')',
                'tableUses' => ['Vortex\Admin\Tables\Columns\NumericColumn'],
                'formUses' => ['Vortex\Admin\Forms\NumberField'],
            ];
        }

        if ($c === 'datetime' || $c === 'date') {
            return [
                'table' => 'DatetimeColumn::make(' . self::q($name) . ')->sortable()',
                'form' => 'TextField::make(' . self::q($name) . ')',
                'tableUses' => ['Vortex\Admin\Tables\Columns\DatetimeColumn'],
                'formUses' => ['Vortex\Admin\Forms\TextField'],
            ];
        }

        if ($c === 'json' || $c === 'array') {
            return [
                'table' => 'TextColumn::make(' . self::q($name) . ')',
                'form' => 'TextareaField::make(' . self::q($name) . ')',
                'tableUses' => ['Vortex\Admin\Tables\Columns\TextColumn'],
                'formUses' => ['Vortex\Admin\Forms\TextareaField'],
            ];
        }

        if (str_contains($n, 'email')) {
            return [
                'table' => 'EmailColumn::make(' . self::q($name) . ')->sortable()',
                'form' => 'EmailField::make(' . self::q($name) . ')',
                'tableUses' => ['Vortex\Admin\Tables\Columns\EmailColumn'],
                'formUses' => ['Vortex\Admin\Forms\EmailField'],
            ];
        }

        if (self::isLongText($n)) {
            return [
                'table' => 'TextColumn::make(' . self::q($name) . ')',
                'form' => 'TextareaField::make(' . self::q($name) . ')',
                'tableUses' => ['Vortex\Admin\Tables\Columns\TextColumn'],
                'formUses' => ['Vortex\Admin\Forms\TextareaField'],
            ];
        }

        if (str_ends_with($n, '_id') && $n !== 'id') {
            return [
                'table' => 'NumericColumn::make(' . self::q($name) . ', null, 0)->sortable()',
                'form' => 'NumberField::make(' . self::q($name) . ')',
                'tableUses' => ['Vortex\Admin\Tables\Columns\NumericColumn'],
                'formUses' => ['Vortex\Admin\Forms\NumberField'],
            ];
        }

        return [
            'table' => 'TextColumn::make(' . self::q($name) . ')->sortable()',
            'form' => 'TextField::make(' . self::q($name) . ')',
            'tableUses' => ['Vortex\Admin\Tables\Columns\TextColumn'],
            'formUses' => ['Vortex\Admin\Forms\TextField'],
        ];
    }

    private static function isLongText(string $n): bool
    {
        if (in_array($n, self::LONG_TEXT_NAMES, true)) {
            return true;
        }
        if (str_ends_with($n, '_html')) {
            return true;
        }

        return str_contains($n, 'description') || str_contains($n, 'content');
    }

    private static function q(string $s): string
    {
        return "'" . addcslashes($s, "\\'") . "'";
    }

    private static function shortClass(string $fqcn): string
    {
        $i = strrpos($fqcn, '\\');

        return $i === false ? $fqcn : substr($fqcn, $i + 1);
    }
}
