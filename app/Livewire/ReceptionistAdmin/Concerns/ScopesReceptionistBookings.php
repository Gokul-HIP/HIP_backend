<?php

namespace App\Livewire\ReceptionistAdmin\Concerns;

use App\Services\ReceptionistBookingScopeService;

trait ScopesReceptionistBookings
{
    protected function scopeService(): ReceptionistBookingScopeService
    {
        return app(ReceptionistBookingScopeService::class);
    }
}
