<?php

namespace App\Livewire\Admin\Automation\Concerns;

trait BuildsAutomationEmbedUrl
{
    /**
     * @param  array<string, scalar|null>  $query
     */
    protected function automationEmbedUrl(string $path, array $query = []): string
    {
        $base = (string) config('automation.ui_url');
        $query = array_merge([
            // hip-automation detects embed via /embed path and/or embed=true|1
            'embed' => 'true',
            'parent_origin' => rtrim((string) config('app.url'), '/'),
            // Future production: pass a signed embed_token from Laravel Admin.
            // 'embed_token' => ...,
        ], $query);

        $separator = str_contains($path, '?') ? '&' : '?';

        return $base.$path.$separator.http_build_query($query);
    }

    protected function actorId(): ?string
    {
        $id = auth('filament')->id() ?? auth()->id();

        return $id !== null ? (string) $id : null;
    }
}
