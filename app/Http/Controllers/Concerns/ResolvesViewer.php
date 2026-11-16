<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesViewer
{
    /**
     * Resolve the authenticated user's id for routes that are intentionally
     * public (no `auth:sanctum` middleware).
     *
     * `$request->user()` uses the default `web`/session guard, so it stays null
     * on public routes even when the SPA sends a valid Sanctum bearer token.
     * That silently wiped viewer state (`joined`, `userLiked`, `userReacted`,
     * `isYou`) on every refresh. Resolving the `sanctum` guard explicitly fixes
     * it while leaving the route readable by guests.
     */
    protected function viewerId(Request $request): ?int
    {
        return ($request->user() ?? auth('sanctum')->user())?->id;
    }
}
