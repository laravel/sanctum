<?php

namespace Laravel\Sanctum\Diagnostics;

use Laravel\Sanctum\HasApiTokens;

trait FindsApiTokenModels
{
    /**
     * Get the authentication provider models that issue Sanctum API tokens.
     *
     * @return list<class-string>
     */
    protected function apiTokenModels(): array
    {
        $providers = config('auth.providers');

        if (! is_array($providers)) {
            return [];
        }

        return collect($providers)
            ->filter(fn ($provider): bool => is_array($provider) && ($provider['driver'] ?? null) === 'eloquent')
            ->map(fn (array $provider) => $provider['model'] ?? null)
            ->filter(fn ($model): bool => is_string($model) && class_exists($model))
            ->filter(fn (string $model): bool => in_array(HasApiTokens::class, class_uses_recursive($model), true))
            ->unique()
            ->values()
            ->all();
    }
}
