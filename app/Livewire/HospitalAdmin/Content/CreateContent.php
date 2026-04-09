<?php

namespace App\Livewire\HospitalAdmin\Content;

use App\Models\ContentModeration;
use App\Models\ContentTargetArea;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\LocationMaster;
use App\Models\SpecialitiesMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateContent extends Component
{
    use WithFileUploads;

    public $organization_id;

    public $title;

    public $description;

    public $media_file;

    public $media_file_preview;

    public $category;

    public $speciality_id;

    public $area_ids = [];

    public $hospital_id;

    public $doctor_id;

    public $status = 'draft';

    public $schedule_date;

    public $schedule_time;

    public $is_published = false;

    public $categories = ['new', 'patient guide'];

    public $specialities = [];

    public $areas = [];

    public $hospitals = [];

    public $doctors = [];

    public function mount()
    {
        $this->organization_id = Auth::guard('filament')->user()?->organization_id;
        $this->loadSpecialities();
        $this->loadAreas();
        $this->loadHospitals();
    }

    public function loadSpecialities()
    {
        $this->specialities = SpecialitiesMaster::where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function loadAreas()
    {
        $this->areas = LocationMaster::orderBy('city')
            ->orderBy('area')
            ->get();
    }

    public function loadHospitals()
    {
        if (! $this->organization_id) {
            $this->hospitals = collect();

            return;
        }

        $this->hospitals = Hospital::where('status', 'active')
            ->where('organization_id', $this->organization_id)
            ->orderBy('name')
            ->get();
    }

    public function updatedHospitalId($value)
    {
        $this->doctor_id = null;

        if ($value) {
            $this->doctors = Doctor::where('status', 'active')
                ->whereJsonContains('hospital_ids', (int) $value)
                ->orderBy('name')
                ->get();
        } else {
            $this->doctors = [];
        }
    }

    public function removeMediaFile()
    {
        if ($this->media_file_preview) {
            $this->media_file_preview = null;
        }
        $this->media_file = null;
    }

    public function removeArea($areaId)
    {
        $this->area_ids = array_values(array_filter($this->area_ids, function ($id) use ($areaId) {
            return $id != $areaId;
        }));
    }

    protected function hospitalBelongsToOrgRule()
    {
        if (! $this->organization_id) {
            return Rule::in([]);
        }

        return Rule::exists('hospitals', 'id')->where('organization_id', $this->organization_id);
    }

    public function store()
    {
        if (! $this->organization_id) {
            $this->dispatch('toast', type: 'error', message: 'No organization is assigned to this account.');

            return;
        }

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'media_file' => [
                'nullable',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $extension = strtolower($value->getClientOriginalExtension());
                        $mimeType = $value->getMimeType();

                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];
                        $allowedMimeTypes = [
                            'image/jpeg', 'image/png', 'image/gif',
                            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv',
                            'application/octet-stream',
                        ];

                        $isValidExtension = in_array($extension, $allowedExtensions);
                        $isValidMimeType = in_array($mimeType, $allowedMimeTypes);

                        if (! $isValidExtension && ! $isValidMimeType) {
                            $fail('The media file must be a file of type: jpg, jpeg, png, gif, mp4, mov, avi.');
                        }
                    }
                },
            ],
            'category' => 'required|in:new,patient guide',
            'speciality_id' => 'nullable|exists:specialities_masters,id',
            'area_ids' => 'nullable|array',
            'area_ids.*' => 'exists:location_masters,id',
            'hospital_id' => ['nullable', $this->hospitalBelongsToOrgRule()],
            'doctor_id' => 'nullable|exists:doctors,id',
            'status' => 'required|in:active,inactive',
            'schedule_date' => 'nullable|date',
            'schedule_time' => 'nullable|date_format:H:i',
            'is_published' => 'nullable|boolean',
        ]);

        $mediaFileName = null;
        if ($this->media_file) {
            $mediaFileName = $this->media_file->store('content', 'public');
        }

        $scheduleTimeData = null;
        if ($this->schedule_date && $this->schedule_time) {
            $scheduleTimeData = [
                'date' => $this->schedule_date,
                'time' => $this->schedule_time,
            ];
        }

        $content = ContentModeration::create([
            'title' => $this->title,
            'description' => $this->description,
            'media_file' => $mediaFileName,
            'category' => $this->category,
            'speciality_id' => $this->speciality_id,
            'hospital_id' => $this->hospital_id,
            'organization_id' => $this->organization_id,
            'doctor_id' => $this->doctor_id,
            'status' => $this->status,
            'is_published' => $this->is_published ?? false,
            'schedule_time_data' => $scheduleTimeData,
            'created_by' => Auth::id(),
        ]);

        if (! empty($this->area_ids)) {
            foreach ($this->area_ids as $locationId) {
                ContentTargetArea::create([
                    'content_id' => $content->id,
                    'location_master_id' => $locationId,
                ]);
            }
        }

        $this->dispatch('toast', type: 'success', message: 'Content created successfully!');
        $this->resetForm();

        return redirect()->route('healthcare.content.index');
    }

    public function saveAsDraft()
    {
        if (! $this->organization_id) {
            $this->dispatch('toast', type: 'error', message: 'No organization is assigned to this account.');

            return;
        }

        $this->status = 'draft';

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'media_file' => [
                'nullable',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $extension = strtolower($value->getClientOriginalExtension());
                        $mimeType = $value->getMimeType();

                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];
                        $allowedMimeTypes = [
                            'image/jpeg', 'image/png', 'image/gif',
                            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv',
                            'application/octet-stream',
                        ];

                        $isValidExtension = in_array($extension, $allowedExtensions);
                        $isValidMimeType = in_array($mimeType, $allowedMimeTypes);

                        if (! $isValidExtension && ! $isValidMimeType) {
                            $fail('The media file must be a file of type: jpg, jpeg, png, gif, mp4, mov, avi.');
                        }
                    }
                },
            ],
            'category' => 'required|in:new,patient guide',
            'speciality_id' => 'nullable|exists:specialities_masters,id',
            'area_ids' => 'nullable|array',
            'area_ids.*' => 'exists:location_masters,id',
            'hospital_id' => ['nullable', $this->hospitalBelongsToOrgRule()],
            'doctor_id' => 'nullable|exists:doctors,id',
        ]);

        $mediaFileName = null;
        if ($this->media_file) {
            $mediaFileName = $this->media_file->store('content', 'public');
        }

        $content = ContentModeration::create([
            'title' => $this->title,
            'description' => $this->description,
            'media_file' => $mediaFileName,
            'category' => $this->category,
            'speciality_id' => $this->speciality_id,
            'hospital_id' => $this->hospital_id,
            'organization_id' => $this->organization_id,
            'doctor_id' => $this->doctor_id,
            'status' => 'draft',
            'is_published' => false,
            'created_by' => Auth::id(),
        ]);

        if (! empty($this->area_ids)) {
            foreach ($this->area_ids as $locationId) {
                ContentTargetArea::create([
                    'content_id' => $content->id,
                    'location_master_id' => $locationId,
                ]);
            }
        }

        $this->dispatch('toast', type: 'success', message: 'Content saved as draft successfully!');
        $this->resetForm();

        return redirect()->route('healthcare.content.index');
    }

    public function resetForm()
    {
        $this->title = '';
        $this->description = '';
        $this->media_file = null;
        $this->media_file_preview = null;
        $this->category = '';
        $this->speciality_id = null;
        $this->area_ids = [];
        $this->hospital_id = null;
        $this->doctor_id = null;
        $this->status = 'draft';
        $this->schedule_date = null;
        $this->schedule_time = null;
        $this->is_published = false;
        $this->doctors = [];
    }

    public function render()
    {
        return view('livewire.hospital-admin.content.create-content');
    }
}
