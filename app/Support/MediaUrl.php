<?php

namespace App\Support;

/**
 * Resolve a stored file path into a URL the SPA can <img src>.
 *
 * - http/https            → unchanged
 * - "/assets/..."          → unchanged (frontend's own public assets, served by Vite)
 * - "/storage/..." or key  → prefixed with the API origin so it resolves on the
 *                            backend (http://127.0.0.1:8001), not the Vite dev
 *                            server (:5173) — which would 404.
 */
class MediaUrl
{
    public static function make(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '/assets/')) {
            return $path; // frontend public asset
        }

        $base = rtrim(config('app.url'), '/');
        if (str_starts_with($path, '/storage/')) {
            return $base . $path; // already a storage URL — just needs the host
        }

        $clean = preg_replace('#^(?:public/|storage/)#', '', ltrim($path, '/'));
        return $base . '/storage/' . $clean;
    }
}
