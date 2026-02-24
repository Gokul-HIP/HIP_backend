<?php

namespace App\Livewire\Admin\Ads;

use App\Models\Ad;
use App\Models\AdClick;
use App\Models\AdImpression;
use Livewire\Component;

class AdEngagement extends Component
{
    public ?int $adId = null;

    public function mount(): void
    {
        $id = request()->query('ad_id');
        $this->adId = $id !== null && $id !== '' ? (int) $id : null;
    }

    public function getTotals(): array
    {
        $impQ = AdImpression::query();
        $clickQ = AdClick::query();
        if ($this->adId !== null) {
            $impQ->where('ad_id', $this->adId);
            $clickQ->where('ad_id', $this->adId);
        }
        $impressions = $impQ->count();
        $clicks = $clickQ->count();
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;

        return ['impressions' => $impressions, 'clicks' => $clicks, 'ctr' => $ctr];
    }

    public function render()
    {
        $ads = Ad::orderBy('title')->get(['id', 'title']);
        $totals = $this->getTotals();

        return view('livewire.admin.ads.ad-engagement', [
            'ads' => $ads,
            'totals' => $totals,
        ]);
    }
}
