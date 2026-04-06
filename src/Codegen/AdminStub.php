<?php

declare(strict_types=1);

namespace Vortex\Admin\Codegen;

use RuntimeException;

/** Renders {@code resources/stubs/*.stub} in this package with {@code {{NAME}}} placeholders. */
final class AdminStub
{
    /**
     * @param array<string, string> $replacements
     */
    public static function render(string $stubBasename, array $replacements): string
    {
        $path = dirname(__DIR__, 2) . '/resources/stubs/' . $stubBasename . '.stub';
        if (! is_file($path)) {
            throw new RuntimeException('Stub not found: ' . $stubBasename);
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Cannot read stub: ' . $path);
        }
        foreach ($replacements as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }
}
