<?php

namespace App\Livewire\Admin\Ads;

use App\Models\Ad;
use App\Models\AdPlacement;
use App\Models\AdTargetArea;
use App\Models\Hospital;
use App\Models\LocationMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditAd extends Component
{
    use WithFileUploads;

    public int $id;
    public ?Ad $ad = null;

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
            // 'home_banner' => 'Home Page Banner',
            // 'mid_scroll' => 'Mid Scroll Banner',
            // 'overlay' => 'Overlay Ad',
            // 'content_break' => 'Content Break Ad',
            // 'post_action' => 'Post Action Ad',
            // 'bottom_banner' => 'Bottom Banner',
            'slider' => 'Slider',
        ];
    }

    private function toDatabasePlacementType(string $placementType): string
    {
        return $placementType === 'slider' ? 'home_banner' : $placementType;
    }

    private function toUiPlacementType(string $placementType): string
    {
        if ($placementType === 'home_banner' && array_key_exists('slider', self::placementTypes())) {
            return 'slider';
        }

        return $placementType;
    }

    public function mount(int $id): void
    {
        $this->id = $id;
        $this->ad = Ad::with(['hospital', 'placements', 'targetAreas'])->find($id);
        if (! $this->ad) {
            return;
        }

        $this->hospitals = Hospital::where('status', 'active')->orderBy('name')->get();
        $this->locations = LocationMaster::orderBy('city')->orderBy('area')->get();

        $this->title = $this->ad->title ?? '';
        $this->description = $this->ad->description;
        $this->media_type = $this->ad->media_type ?? 'image';
        $this->hospital_id = $this->ad->hospital_id;
        $this->redirect_type = $this->ad->redirect_type ?? 'internal';
        $this->redirect_url = $this->ad->redirect_url ?? '';
        $this->start_date = $this->ad->start_date ? $this->ad->start_date->format('Y-m-d') : '';
        $this->end_date = $this->ad->end_date ? $this->ad->end_date->format('Y-m-d') : '';
        $this->priority_type = $this->ad->priority_type ?? 'medium';
        $this->priority = (int) ($this->ad->priority ?? 50);
        $this->placements = $this->ad->placements
            ->pluck('placement_type')
            ->map(fn ($value) => $this->toUiPlacementType((string) $value))
            ->unique()
            ->values()
            ->all();
        $this->location_master_ids = $this->ad->targetAreas->pluck('location_master_id')->values()->all();
    }

    public function removeMediaFile(): void
    {
        $this->media_file = null;
    }

    private function validateInput(bool $requireMediaFile): bool
    {
        if ($this->hospital_id === '') {
            $this->hospital_id = null;
        }

        $this->placements = array_values(array_filter($this->placements, function ($value) {
            return $value !== null && $value !== '' && $value !== '__rm__';
        }));

        $this->location_master_ids = array_values(array_filter($this->location_master_ids, function ($value) {
            return $value !== null && $value !== '' && $value !== '__rm__';
        }));

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'media_type' => 'required|in:image,video',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'redirect_type' => 'required|in:internal,external',
            'redirect_url' => 'required|string|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'priority_type' => 'required|in:high,medium,low',
            'priority' => 'nullable|integer|min:1|max:100',
            'placements' => 'required|array|min:1',
            'placements.*' => ['string', Rule::in(array_keys(self::placementTypes()))],
            'location_master_ids' => 'nullable|array',
            'location_master_ids.*' => 'exists:location_masters,id',
        ];
        if ($requireMediaFile) {
            $rules['media_file'] = 'required|file|max:51200';
        } else {
            $rules['media_file'] = 'nullable|file|max:51200';
        }
        $this->validate($rules);

        if ($this->redirect_type === 'external' && ! $this->isValidUrl($this->redirect_url)) {
            $this->addError('redirect_url', 'External redirect must be a valid http/https URL.');
            return false;
        }
        return true;
    }

    private function isValidUrl(string $value): bool
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        $lower = strtolower($value);
        return str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://');
    }

    public function update(): void
    {
        if (! $this->validateInput(false)) {
            return;
        }

        DB::transaction(function () {
            $path = $this->ad->media_url;
            if ($this->media_file) {
                if ($this->ad->media_url && Storage::disk('public')->exists($this->ad->media_url)) {
                    Storage::disk('public')->delete($this->ad->media_url);
                }
                $path = $this->media_file->store('ads', 'public');
            }

            $numericPriority = match ($this->priority_type) {
                'high' => 75,
                'medium' => 50,
                'low' => 25,
                default => 50,
            };
            if ($this->priority >= 1 && $this->priority <= 100) {
                $numericPriority = $this->priority;
            }

            $this->ad->update([
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
            ]);

            $this->ad->placements()->delete();
            foreach (array_unique($this->placements) as $placementType) {
                AdPlacement::create([
                    'ad_id' => $this->ad->id,
                    'placement_type' => $this->toDatabasePlacementType((string) $placementType),
                ]);
            }

            $this->ad->targetAreas()->delete();
            foreach (array_unique($this->location_master_ids) as $locId) {
                AdTargetArea::create(['ad_id' => $this->ad->id, 'location_master_id' => $locId]);
            }
        });

        $this->dispatch('toast', type: 'success', message: 'Ad updated successfully.');
        $this->redirect(route('admin.ads.ad-management.index'), navigate: true);
    }

    public function updateAsDraft(): void
    {
        if (! $this->validateInput(false)) {
            return;
        }

        DB::transaction(function () {
            $path = $this->ad->media_url;
            if ($this->media_file) {
                if ($this->ad->media_url && Storage::disk('public')->exists($this->ad->media_url)) {
                    Storage::disk('public')->delete($this->ad->media_url);
                }
                $path = $this->media_file->store('ads', 'public');
            }

            $numericPriority = match ($this->priority_type) {
                'high' => 75,
                'medium' => 50,
                'low' => 25,
                default => 50,
            };
            if ($this->priority >= 1 && $this->priority <= 100) {
                $numericPriority = $this->priority;
            }

            $this->ad->update([
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

            $this->ad->placements()->delete();
            foreach (array_unique($this->placements) as $placementType) {
                AdPlacement::create([
                    'ad_id' => $this->ad->id,
                    'placement_type' => $this->toDatabasePlacementType((string) $placementType),
                ]);
            }

            $this->ad->targetAreas()->delete();
            foreach (array_unique($this->location_master_ids) as $locId) {
                AdTargetArea::create(['ad_id' => $this->ad->id, 'location_master_id' => $locId]);
            }
        });

        $this->dispatch('toast', type: 'success', message: 'Ad saved as draft.');
        $this->redirect(route('admin.ads.ad-management.index'), navigate: true);
    }

    public function render()
    {
        if (! $this->ad) {
            return view('livewire.admin.ads.edit-ad-not-found');
        }

        $placementOptions = collect(self::placementTypes())->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values()->all();
        $priorityTypeOptions = self::priorityTypeOptions();
        $existingMediaUrl = $this->ad->media_url ? asset('storage/' . $this->ad->media_url) : null;
        $currentHospitalName = $this->ad->hospital?->name ?? 'All Hospitals';

        return view('livewire.admin.ads.edit-ad', [
            'placementOptions' => $placementOptions,
            'priorityTypeOptions' => $priorityTypeOptions,
            'existingMediaUrl' => $existingMediaUrl,
            'currentHospitalName' => $currentHospitalName,
        ]);
    }
}
