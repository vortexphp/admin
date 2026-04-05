<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

use Vortex\Config\Repository;
use Vortex\Http\UploadedFile;

/**
 * Stores valid uploads under {@code public/{relativeDir}/}; persists the relative path (e.g. {@code uploads/x/y.jpg}).
 */
final class UploadField extends FormField
{
    /**
     * @param list<string>|null $allowedMimes MIME types from {@see UploadedFile::mimeFromContent()}; null = any
     * @param list<string>|null $allowedExtensions lowercase, no dot (e.g. {@code ['jpg','png']}); null = any
     */
    public function __construct(
        string $name,
        string $label,
        private readonly string $relativeDir = 'uploads',
        private readonly ?int $maxKb = 5120,
        private readonly ?array $allowedMimes = null,
        private readonly ?array $allowedExtensions = null,
        private readonly bool $keepExistingOnEmpty = true,
        private readonly string $accept = '*/*',
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function to(string $relativeDir): self
    {
        return new self(
            $this->name,
            $this->label,
            $relativeDir,
            $this->maxKb,
            $this->allowedMimes,
            $this->allowedExtensions,
            $this->keepExistingOnEmpty,
            $this->accept,
        );
    }

    public function maxKb(?int $max): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->relativeDir,
            $max,
            $this->allowedMimes,
            $this->allowedExtensions,
            $this->keepExistingOnEmpty,
            $this->accept,
        );
    }

    /**
     * @param list<string>|null $mimes
     */
    public function allowedMimes(?array $mimes): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->relativeDir,
            $this->maxKb,
            $mimes,
            $this->allowedExtensions,
            $this->keepExistingOnEmpty,
            $this->accept,
        );
    }

    /**
     * @param list<string>|null $ext
     */
    public function allowedExtensions(?array $ext): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->relativeDir,
            $this->maxKb,
            $this->allowedMimes,
            $ext,
            $this->keepExistingOnEmpty,
            $this->accept,
        );
    }

    public function discardExistingWhenEmpty(): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->relativeDir,
            $this->maxKb,
            $this->allowedMimes,
            $this->allowedExtensions,
            false,
            $this->accept,
        );
    }

    public function accept(string $accept): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->relativeDir,
            $this->maxKb,
            $this->allowedMimes,
            $this->allowedExtensions,
            $this->keepExistingOnEmpty,
            $accept,
        );
    }

    public function label(string $label): static
    {
        return new self(
            $this->name,
            $label,
            $this->relativeDir,
            $this->maxKb,
            $this->allowedMimes,
            $this->allowedExtensions,
            $this->keepExistingOnEmpty,
            $this->accept,
        );
    }

    public function inputKind(): string
    {
        return 'file';
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + ['accept' => $this->accept];
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        return '';
    }

    public function normalizeUpload(?UploadedFile $file, ?string $existingRelativePath): string
    {
        if ($file === null || ! $file->hasFile() || ! $file->isValid()) {
            if ($this->keepExistingOnEmpty) {
                return $existingRelativePath ?? '';
            }

            return '';
        }
        if ($this->maxKb !== null && $file->size > $this->maxKb * 1024) {
            return $existingRelativePath ?? '';
        }
        $mime = $file->mimeFromContent();
        if ($this->allowedMimes !== null
            && ($mime === null || ! in_array($mime, $this->allowedMimes, true))) {
            return $existingRelativePath ?? '';
        }
        $ext = strtolower(pathinfo($file->originalName, PATHINFO_EXTENSION));
        if ($this->allowedExtensions !== null
            && ($ext === '' || ! in_array($ext, $this->allowedExtensions, true))) {
            return $existingRelativePath ?? '';
        }

        $safeBase = bin2hex(random_bytes(10));
        $safeName = $ext !== '' ? $safeBase . '.' . $ext : $safeBase;
        $sub = trim(str_replace('\\', '/', $this->relativeDir), '/');
        $rel = $sub === '' ? $safeName : $sub . '/' . $safeName;
        $public = Repository::basePath() . '/public';
        $dest = $public . '/' . $rel;
        $file->moveTo($dest);

        return $rel;
    }
}
