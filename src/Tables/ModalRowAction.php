<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/**
 * Row action that opens the shared admin modal. Body is driven by {@see resolve()} {@code content}:
 * {@code form} (POST + fields or trusted fragment), {@code html} (trusted markup only), or {@code include} (Twig partial for anything else).
 *
 * For {@code form}, register an app route that accepts {@code routeParams} and reads POST fields.
 *
 * @phpstan-type FieldSpec array{
 *     type: 'hidden'|'text'|'email'|'number'|'textarea'|'select',
 *     name: string,
 *     label?: string,
 *     value?: string|int|float|null,
 *     placeholder?: string,
 *     required?: bool,
 *     rows?: int,
 *     options?: array<string, string>
 * }
 */
final class ModalRowAction extends TableRowAction
{
    /**
     * @param array<string, mixed> $config
     */
    private function __construct(
        string $label,
        private readonly string $title,
        private readonly bool $danger,
        private readonly array $config,
    ) {
        parent::__construct($label);
    }

    /**
     * @param callable(string $slug, array<string, mixed> $row): array<string, string|int|float>|null $routeParams
     * @param callable(string $slug, array<string, mixed> $row): list<array<string, mixed>>|null $fields
     */
    public static function form(
        string $label,
        string $title,
        string $formRoute,
        callable $routeParams,
        callable $fields,
        string $submitLabel = 'Submit',
        bool $danger = false,
    ): self {
        return new self($label, $title, $danger, [
            'mode' => 'form',
            'formRoute' => $formRoute,
            'routeParams' => $routeParams,
            'fields' => $fields,
            'formBody' => null,
            'submitLabel' => $submitLabel,
        ]);
    }

    /**
     * POST form whose inner markup is trusted HTML from {@see $trustedInnerHtml} (no field builder).
     *
     * @param callable(string $slug, array<string, mixed> $row): array<string, string|int|float>|null $routeParams
     * @param callable(string $slug, array<string, mixed> $row): string|null $trustedInnerHtml
     */
    public static function formHtml(
        string $label,
        string $title,
        string $formRoute,
        callable $routeParams,
        callable $trustedInnerHtml,
        string $submitLabel = 'Submit',
        bool $danger = false,
    ): self {
        return new self($label, $title, $danger, [
            'mode' => 'form',
            'formRoute' => $formRoute,
            'routeParams' => $routeParams,
            'fields' => null,
            'formBody' => $trustedInnerHtml,
            'submitLabel' => $submitLabel,
        ]);
    }

    /**
     * Modal with no form wrapper: trusted HTML only. Optional default “Close” row unless {@see $showCloseFooter} is false.
     *
     * @param callable(string $slug, array<string, mixed> $row): string|null $body
     */
    public static function html(
        string $label,
        string $title,
        callable $body,
        bool $danger = false,
        bool $showCloseFooter = true,
    ): self {
        return new self($label, $title, $danger, [
            'mode' => 'html',
            'body' => $body,
            'showCloseFooter' => $showCloseFooter,
        ]);
    }

    /**
     * Render any Twig template (under a registered view path) with {@code with} context. Use for previews, custom layouts, or forms you define entirely in Twig.
     *
     * @param callable(string $slug, array<string, mixed> $row): array<string, mixed>|null $with Returns null to hide the action for this row
     */
    public static function include(
        string $label,
        string $title,
        string $template,
        callable $with,
        bool $danger = false,
    ): self {
        if (str_contains($template, '..')) {
            throw new \InvalidArgumentException('Modal template path must not contain "..".');
        }
        if (! str_ends_with($template, '.twig')) {
            throw new \InvalidArgumentException('Modal template must end with .twig');
        }

        return new self($label, $title, $danger, [
            'mode' => 'include',
            'template' => $template,
            'with' => $with,
        ]);
    }

    public function resolve(string $slug, array $row): ?array
    {
        $c = $this->config;
        if ($c['mode'] === 'form') {
            /** @var callable(string, array): (array<string, string|int|float>|null) $routeParams */
            $routeParams = $c['routeParams'];
            $params = $routeParams($slug, $row);
            if ($params === null) {
                return null;
            }
            if ($c['formBody'] !== null) {
                $body = ($c['formBody'])($slug, $row);
                if (is_string($body) && $body !== '') {
                    return $this->shell([
                        'type' => 'form',
                        'route' => $c['formRoute'],
                        'routeParams' => $params,
                        'submitLabel' => $c['submitLabel'],
                        'body' => $body,
                    ]);
                }
            }
            if ($c['fields'] !== null) {
                $fieldList = ($c['fields'])($slug, $row);
                if (is_array($fieldList) && $fieldList !== []) {
                    return $this->shell([
                        'type' => 'form',
                        'route' => $c['formRoute'],
                        'routeParams' => $params,
                        'submitLabel' => $c['submitLabel'],
                        'fields' => $fieldList,
                    ]);
                }
            }

            return null;
        }
        if ($c['mode'] === 'html') {
            $body = ($c['body'])($slug, $row);
            if ($body === null || $body === '') {
                return null;
            }

            return $this->shell([
                'type' => 'html',
                'body' => $body,
                'showCloseFooter' => $c['showCloseFooter'],
            ]);
        }
        if ($c['mode'] === 'include') {
            $with = ($c['with'])($slug, $row);
            if ($with === null) {
                return null;
            }

            return $this->shell([
                'type' => 'include',
                'template' => $c['template'],
                'with' => $with,
            ]);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    private function shell(array $content): array
    {
        $content['danger'] = $this->danger;

        return [
            'kind' => 'modal',
            'label' => $this->label,
            'title' => $this->title,
            'danger' => $this->danger,
            'content' => $content,
        ];
    }
}
