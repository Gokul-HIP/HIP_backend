<?php

namespace App\Livewire\HospitalAdmin\Ads;

use App\Models\Ad;
use App\Models\Hospital;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class AdManagement extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';
    public string $hospitalFilter = '';
    public string $statusFilter = '';
    public string $dateFilter = '';
    public string $priorityFilter = '';

    public int $updateStatusAdId = 0;
    public string $updateStatusNewStatus = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingHospitalFilter(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingDateFilter(): void { $this->resetPage(); }
    public function updatingPriorityFilter(): void { $this->resetPage(); }

    public static function priorityLabel(int $priority): string
    {
        if ($priority >= 67) return 'High';
        if ($priority >= 34) return 'Medium';
        return 'Low';
    }

    public static function formatCount(int $count): string
    {
        return $count >= 1000 ? number_format($count / 1000, 1, '.', '') . 'K' : (string) $count;
    }

    public function stopAd(int $id): void
    {
        $ad = Ad::findOrFail($id);
        $ad->update(['status' => 'stopped']);
        $this->dispatch('toast', type: 'success', message: 'Ad stopped.');
    }

    public function openUpdateStatusModal(int $adId): void
    {
        $ad = Ad::find($adId);
        if (! $ad) return;
        $this->updateStatusAdId = $adId;
        $this->updateStatusNewStatus = $ad->status ?? 'draft';
        Flux::modal('update-ad-status')->show();
    }

    public function closeUpdateStatusModal(): void
    {
        $this->updateStatusAdId = 0;
        $this->updateStatusNewStatus = '';
        Flux::modal('update-ad-status')->close();
    }

    public function updateAdStatus(): void
    {
        if ($this->updateStatusAdId <= 0) {
            $this->closeUpdateStatusModal();
            return;
        }

        $ad = Ad::find($this->updateStatusAdId);
        if (! $ad) {
            $this->closeUpdateStatusModal();
            return;
        }

        if (! in_array($this->updateStatusNewStatus, ['active', 'pending', 'draft', 'stopped', 'completed'], true)) {
            $this->closeUpdateStatusModal();
            return;
        }

        $ad->update(['status' => $this->updateStatusNewStatus]);
        $this->closeUpdateStatusModal();
        $this->dispatch('toast', type: 'success', message: 'Ad status updated.');
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->hospitalFilter = '';
        $this->statusFilter = '';
        $this->dateFilter = '';
        $this->priorityFilter = '';
        $this->resetPage();
    }

    public function render()
    {
        $ads = Ad::with('hospital')
            ->withCount(['impressions', 'clicks'])
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->hospitalFilter !== '', fn ($q) => $q->where('hospital_id', $this->hospitalFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('start_date', '<=', $this->dateFilter)->whereDate('end_date', '>=', $this->dateFilter))
            ->when($this->priorityFilter !== '', function ($q) {
                if ($this->priorityFilter === 'high') $q->where('priority', '>=', 67);
                elseif ($this->priorityFilter === 'medium') $q->whereBetween('priority', [34, 66]);
                elseif ($this->priorityFilter === 'low') $q->where('priority', '<=', 33);
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        $hospitalIds = Ad::whereNotNull('hospital_id')->distinct()->pluck('hospital_id');
        $hospitals = Hospital::whereIn('id', $hospitalIds)->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('livewire.hospital-admin.ads.ad-management', [
            'ads' => $ads,
            'hospitals' => $hospitals,
        ]);
    }
}
