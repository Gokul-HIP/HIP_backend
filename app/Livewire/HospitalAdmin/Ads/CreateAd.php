<?php

namespace App\Livewire\HospitalAdmin\Ads;

use App\Models\Ad;
use App\Models\AdPlacement;
use App\Models\AdTargetArea;
use App\Models\Hospital;
use App\Models\LocationMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateAd extends Component
{
    use WithFileUploads;

    public string $title = '';
    public ?string $description = null;
    public string $media_type = 'image';
    public $media_file = null;
    public ?int $hospital_id = null;
    public string $redirect_type = 'internal';
    public string $redirect_url = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $priority_type = 'medium';
    public int $priority = 50;
    public array $placements = [];
    public array $location_master_ids = [];

    public $hospitals = [];
    public $locations = [];

    public static function priorityTypeOptions(): array
    {
        return [
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ];
    }

    public static function placementTypes(): array
    {
        return [
            'home_banner' => 'Home Page Banner',
            'mid_scroll' => 'Mid Scroll Banner',
            'overlay' => 'Overlay Ad',
            'content_break' => 'Content Break Ad',
            'post_action' => 'Post Action Ad',
            'bottom_banner' => 'Bottom Banner',
        ];
    }

    public function mount(): void
    {
        $this->hospitals = Hospital::where('status', 'active')->orderBy('name')->get();
        $this->locations = LocationMaster::orderBy('city')->orderBy('area')->get();
    }

    public function removeMediaFile(): void
    {
        $this->media_file = null;
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'media_type' => 'required|in:image,video',
            'media_file' => 'required|file|max:51200',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'redirect_type' => 'required|in:internal,external',
            'redirect_url' => 'required|string|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'priority_type' => 'required|in:high,medium,low',
            'priority' => 'nullable|integer|min:1|max:100',
            'placements' => 'required|array|min:1',
            'placements.*' => 'string|in:home_banner,mid_scroll,overlay,content_break,post_action,bottom_banner',
            'location_master_ids' => 'nullable|array',
            'location_master_ids.*' => 'exists:location_masters,id',
        ]);

        if ($this->redirect_type === 'external' && ! $this->isValidUrl($this->redirect_url)) {
            $this->addError('redirect_url', 'External redirect must be a valid http/https URL.');
            return;
        }

        DB::transaction(function () {
            $path = $this->media_file->store('ads', 'public');
            $numericPriority = match ($this->priority_type) {
                'high' => 75,
                'medium' => 50,
                'low' => 25,
                default => 50,
            };
            if ($this->priority >= 1 && $this->priority <= 100) {
                $numericPriority = $this->priority;
            }
            $ad = Ad::create([
                'title' => $this->title,
                'description' => $this->description,
                'media_type' => $this->media_type,
                'media_url' => $path,
                'hospital_id' => $this->hospital_id,
                'redirect_type' => $this->redirect_type,
                'redirect_url' => $this->redirect_url,
                'priority_type' => $this->priority_type,
                'start_date' => Carbon::parse($this->start_date)->startOfDay(),
                'end_date' => Carbon::parse($this->end_date)->endOfDay(),
                'priority' => $numericPriority,
                'status' => 'pending',
            ]);

            foreach (array_unique($this->placements) as $placementType) {
                AdPlacement::create(['ad_id' => $ad->id, 'placement_type' => $placementType]);
            }
            foreach (array_unique($this->location_master_ids) as $locId) {
                AdTargetArea::create(['ad_id' => $ad->id, 'location_master_id' => $locId]);
            }
        });

        $this->dispatch('toast', type: 'success', message: 'Ad created successfully.');
        $this->redirect(route('healthcare.ads.ad-management.index'), navigate: true);
    }

    public function saveAsDraft(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'media_type' => 'required|in:image,video',
            'media_file' => 'required|file|max:51200',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'redirect_type' => 'required|in:internal,external',
            'redirect_url' => 'required|string|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'priority_type' => 'required|in:high,medium,low',
            'priority' => 'nullable|integer|min:1|max:100',
            'placements' => 'required|array|min:1',
            'placements.*' => 'string|in:home_banner,mid_scroll,overlay,content_break,post_action,bottom_banner',
            'location_master_ids' => 'nullable|array',
            'location_master_ids.*' => 'exists:location_masters,id',
        ]);

        if ($this->redirect_type === 'external' && ! $this->isValidUrl($this->redirect_url)) {
            $this->addError('redirect_url', 'External redirect must be a valid http/https URL.');
            return;
        }

        DB::transaction(function () {
            $path = $this->media_file->store('ads', 'public');
            $numericPriority = match ($this->priority_type) {
                'high' => 75,
                'medium' => 50,
                'low' => 25,
                default => 50,
            };
            if ($this->priority >= 1 && $this->priority <= 100) {
                $numericPriority = $this->priority;
            }
            $ad = Ad::create([
                'title' => $this->title,
                'description' => $this->description,
                'media_type' => $this->media_type,
                'media_url' => $path,
                'hospital_id' => $this->hospital_id,
                'redirect_type' => $this->redirect_type,
                'redirect_url' => $this->redirect_url,
                'priority_type' => $this->priority_type,
                'start_date' => Carbon::parse($this->start_date)->startOfDay(),
                'end_date' => Carbon::parse($this->end_date)->endOfDay(),
                'priority' => $numericPriority,
                'status' => 'draft',
            ]);

            foreach (array_unique($this->placements) as $placementType) {
                AdPlacement::create(['ad_id' => $ad->id, 'placement_type' => $placementType]);
            }
            foreach (array_unique($this->location_master_ids) as $locId) {
                AdTargetArea::create(['ad_id' => $ad->id, 'location_master_id' => $locId]);
            }
        });

        $this->dispatch('toast', type: 'success', message: 'Ad saved as draft.');
        $this->redirect(route('healthcare.ads.ad-management.index'), navigate: true);
    }

    private function isValidUrl(string $value): bool
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        $lower = strtolower($value);
        return str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://');
    }

    public function render()
    {
        $placementOptions = collect(self::placementTypes())->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values()->all();
        $priorityTypeOptions = self::priorityTypeOptions();

        return view('livewire.hospital-admin.ads.create-ad', [
            'placementOptions' => $placementOptions,
            'priorityTypeOptions' => $priorityTypeOptions,
        ]);
    }
}
