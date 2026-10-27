<?php

namespace Tests\Unit;

use App\Livewire\Admin\Ads\CreateAd as AdminCreateAd;
use App\Livewire\Admin\Ads\EditAd as AdminEditAd;
use App\Livewire\HospitalAdmin\Ads\CreateAd as HospitalCreateAd;
use App\Livewire\HospitalAdmin\Ads\EditAd as HospitalEditAd;
use PHPUnit\Framework\TestCase;

class AdPlacementOptionsTest extends TestCase
{
    public function test_fixed_placement_is_available_in_all_ad_forms(): void
    {
        foreach ([AdminCreateAd::class, AdminEditAd::class, HospitalCreateAd::class, HospitalEditAd::class] as $form) {
            $this->assertSame('Fixed', $form::placementTypes()['fixed'] ?? null);
        }
    }
}