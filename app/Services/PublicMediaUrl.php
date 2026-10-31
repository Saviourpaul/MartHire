<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Generates and validates paths for images deliberately exposed without login.
 * Private documents must never be passed through this service.
 */
class PublicMediaUrl
{
    public function url(?string $path): ?string
    {
        $path = $this->normalizeStoredPath($path);

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return route('public-media.show', ['path' => $path]);
    }

    /**
     * Returns a canonical, allow-listed key or null. The deliberately strict
     * filename rule also blocks encoded separators, traversal and query input.
     */
    public function publicPath(?string $path): ?string
    {
        if (! is_string($path) || $path === '' || strlen($path) > 320 || preg_match('/[\x00-\x1f\x7f:%\\\\?#]/', $path)) {
            return null;
        }

        $directories = array_map(preg_quote(...), config('public-media.directories', []));
        if ($directories === []) {
            return null;
        }

        $directory = '(?:'.implode('|', $directories).')';

        return preg_match('~^'.$directory.'/[A-Za-z0-9][A-Za-z0-9._-]{0,254}$~D', $path) === 1
            ? $path
            : null;
    }

    private function normalizeStoredPath(?string $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = ltrim($path, '/');
        foreach (['storage/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        return $this->publicPath($path);
    }
}
