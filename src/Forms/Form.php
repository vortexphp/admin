<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

/**
 * Create/edit form: ordered {@see FormField} instances.
 */
final class Form
{
    /**
     * @param list<FormField> $fields
     */
    public function __construct(
        private readonly array $fields,
    ) {
    }

    public static function make(FormField ...$fields): self
    {
        return new self(array_values($fields));
    }

    /**
     * @return list<FormField>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * @return list<string> Model attribute names in form order.
     */
    public function fieldNames(): array
    {
        return array_map(static fn (FormField $f): string => $f->name, $this->fields);
    }

    public function requiresMultipart(): bool
    {
        foreach ($this->fields as $f) {
            if ($f instanceof UploadField) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{markdown: bool, html: bool, tags: bool}
     */
    public function richEditorAssets(): array
    {
        $markdown = $html = $tags = false;
        foreach ($this->fields as $f) {
            match ($f->inputKind()) {
                'markdown' => $markdown = true,
                'html' => $html = true,
                'tags' => $tags = true,
                default => null,
            };
        }

        return ['markdown' => $markdown, 'html' => $html, 'tags' => $tags];
    }
}
