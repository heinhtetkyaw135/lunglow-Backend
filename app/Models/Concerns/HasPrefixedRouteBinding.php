<?php

namespace App\Models\Concerns;

trait HasPrefixedRouteBinding
{
    /**
     * Accept the SPA's prefixed ids in route-model binding.
     *
     * The API serialises records with a type prefix ("post-3", "ch-2", "u-4",
     * "pcom-6") to match the frontend's mock-data contract, while the database
     * primary key is a plain integer. Without this, implicit binding would run
     * `where id = 'post-3'`, throw a 404, and the SPA — which swallows failed
     * mutations — would show an optimistic success that never persisted.
     *
     * Plain integer ids still bind normally; anything that matches neither
     * shape falls through and 404s as before.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where(
            $field ?? $this->getRouteKeyName(),
            $this->stripRouteKeyPrefix($value)
        )->firstOrFail();
    }

    protected function stripRouteKeyPrefix(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[a-z]+-(\d+)$/i', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }
}
