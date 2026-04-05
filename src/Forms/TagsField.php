<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

/**
 * Tags via Tagify (CDN). Normalizes to CSV or JSON string for persistence.
 */
final class TagsField extends FormField
{
    public function __construct(
        string $name,
        string $label,
        private readonly string $delimiter = ',',
        private readonly bool $asJson = false,
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function asJson(): self
    {
        return new self($this->name, $this->label, $this->delimiter, true);
    }

    public function delimiter(string $d): self
    {
        return new self($this->name, $this->label, $d, $this->asJson);
    }

    public function label(string $label): static
    {
        return new self($this->name, $label, $this->delimiter, $this->asJson);
    }

    public function inputKind(): string
    {
        return 'tags';
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + [
            'delimiter' => $this->delimiter,
            'asJson' => $this->asJson,
        ];
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return $this->asJson ? '[]' : '';
        }
        $s = is_string($raw) ? trim($raw) : (string) $raw;
        if ($s !== '' && ($s[0] === '[' || $s[0] === '{')) {
            $d = json_decode($s, true);
            if (is_array($d)) {
                $tags = [];
                foreach ($d as $v) {
                    if (is_string($v)) {
                        $t = trim($v);
                        if ($t !== '') {
                            $tags[] = $t;
                        }
                    } elseif (is_array($v) && isset($v['value']) && is_string($v['value'])) {
                        $t = trim($v['value']);
                        if ($t !== '') {
                            $tags[] = $t;
                        }
                    }
                }

                return $this->asJson ? json_encode(array_values(array_unique($tags))) : implode($this->delimiter, array_unique($tags));
            }
        }
        $parts = preg_split('/[,;\n\r]+/', $s) ?: [];
        $tags = [];
        foreach ($parts as $p) {
            $t = trim((string) $p);
            if ($t !== '') {
                $tags[] = $t;
            }
        }
        $tags = array_values(array_unique($tags));

        return $this->asJson ? json_encode($tags) : implode($this->delimiter, $tags);
    }
}
