<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;
use App\Models\ContentModeration;
use App\Models\ContentTargetArea;
use App\Models\SpecialitiesMaster;
use App\Models\LocationMaster;
use App\Models\Hospital;
use App\Models\Doctor;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class EditContent extends Component
{
    use WithFileUploads;

    public $content_id;
    public $title;
    public $description;
    public $media_file;
    public $media_file_preview;
    public $existing_media_file;
    public $category;
    public $speciality_id;
    public $area_ids = [];
    public $hospital_id;
    public $doctor_id;
    public $status = 'draft';
    public $schedule_date;
    public $schedule_time;
    public $is_published = false;
    
    // Options
    public $categories = ['new', 'patient guide'];
    public $specialities = [];
    public $areas = [];
    public $hospitals = [];
    public $doctors = [];

    public function mount($id)
    {
        $this->content_id = $id;
        $this->loadSpecialities();
        $this->loadAreas();
        $this->loadHospitals();
        $this->loadContent();
    }

    public function loadContent()
    {
        $content = ContentModeration::findOrFail($this->content_id);
        
        $this->title = $content->title;
        $this->description = $content->description;
        $this->existing_media_file = $content->media_file;
        $this->category = $content->category;
        $this->speciality_id = $content->speciality_id;
        // $this->area_ids = is_array($content->area_ids) ? $content->area_ids : [];
        $this->area_ids = $content->targetAreas->pluck('location_master_id')->toArray();
        $this->hospital_id = $content->hospital_id;
        $this->doctor_id = $content->doctor_id;
        $this->status = $content->status;
        $this->is_published = $content->is_published ?? false;
        
        // Load schedule data
        if ($content->schedule_time_data) {
            $this->schedule_date = $content->schedule_time_data['date'] ?? null;
            $this->schedule_time = $content->schedule_time_data['time'] ?? null;
        }
        
        // Load doctors if hospital is set
        if ($this->hospital_id) {
            $this->updatedHospitalId($this->hospital_id);
        }
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
        $this->hospitals = Hospital::where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function updatedHospitalId($value)
    {
        if ($value) {
            // Load doctors assigned to this hospital
            $this->doctors = Doctor::where('status', 'active')
                ->whereJsonContains('hospital_ids', (int)$value)
                ->orderBy('name')
                ->get();
            
            // If current doctor is not in the new hospital's doctors, clear it
            $doctorExists = false;
            foreach ($this->doctors as $doctor) {
                if ($doctor->id == $this->doctor_id) {
                    $doctorExists = true;
                    break;
                }
            }
            if ($this->doctor_id && !$doctorExists) {
                $this->doctor_id = null;
            }
        } else {
            $this->doctors = [];
            $this->doctor_id = null;
        }
    }

    public function removeMediaFile()
    {
        if ($this->media_file_preview) {
            $this->media_file_preview = null;
        }
        $this->media_file = null;
        $this->existing_media_file = null;
    }

    public function removeArea($areaId)
    {
        $this->area_ids = array_values(array_filter($this->area_ids, function($id) use ($areaId) {
            return $id != $areaId;
        }));
    }
    public function update()
    {
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
                        $mimeType  = $value->getMimeType();

                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];
                        $allowedMimeTypes  = [
                            'image/jpeg', 'image/png', 'image/gif',
                            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv',
                            'application/octet-stream',
                        ];

                        if (! in_array($extension, $allowedExtensions) && ! in_array($mimeType, $allowedMimeTypes)) {
                            $fail('The media file must be a file of type: jpg, jpeg, png, gif, mp4, mov, avi.');
                        }
                    }
                },
            ],
            'category'        => 'required|in:new,patient guide',
            'speciality_id'   => 'nullable|exists:specialities_masters,id',
            'area_ids'        => 'nullable|array',
            'area_ids.*'      => 'exists:location_masters,id',
            'hospital_id'     => 'nullable|exists:hospitals,id',
            'doctor_id'       => 'nullable|exists:doctors,id',
            'status'          => 'required|in:active,inactive',
            'schedule_date'   => 'nullable|date',
            'schedule_time'   => 'nullable|date_format:H:i',
            'is_published'    => 'nullable|boolean',
        ]);

        $content = ContentModeration::findOrFail($this->content_id);

        DB::transaction(function () use ($content) {
            // Media handling (keep / replace / remove)
            $mediaFileName = $content->media_file;
            if ($this->media_file) {
                if ($content->media_file && Storage::disk('public')->exists($content->media_file)) {
                    Storage::disk('public')->delete($content->media_file);
                }
                $mediaFileName = $this->media_file->store('content', 'public');
            } elseif ($this->existing_media_file === null) {
                if ($content->media_file && Storage::disk('public')->exists($content->media_file)) {
                    Storage::disk('public')->delete($content->media_file);
                }
                $mediaFileName = null;
            }

            // Organization from hospital
            $organization_id = null;
            if ($this->hospital_id) {
                $hospital = Hospital::find($this->hospital_id);
                if ($hospital) {
                    $organization_id = $hospital->organization_id;
                }
            }

            // Schedule data
            $scheduleTimeData = null;
            if ($this->schedule_date && $this->schedule_time) {
                $scheduleTimeData = [
                    'date' => $this->schedule_date,
                    'time' => $this->schedule_time,
                ];
            }

            // Update main content row
            $content->update([
                'title'              => $this->title,
                'description'        => $this->description,
                'media_file'         => $mediaFileName,
                'category'           => $this->category,
                'speciality_id'      => $this->speciality_id,
                'hospital_id'        => $this->hospital_id,
                'organization_id'    => $organization_id,
                'doctor_id'          => $this->doctor_id,
                'status'             => $this->status,
                'is_published'       => $this->is_published ?? false,
                'schedule_time_data' => $scheduleTimeData,
                'updated_by'         => Auth::id(),
            ]);

            // SYNC target areas via pivot table (delete removed, keep existing, add new)
            $selectedIds = collect($this->area_ids ?? [])
                ->filter()       // remove null/empty
                ->unique()
                ->values();

            if ($selectedIds->isEmpty()) {
                // No areas selected: remove all existing pivots
                $content->targetAreas()->delete();
            } else {
                // Remove pivots that are no longer selected
                $content->targetAreas()
                    ->whereNotIn('location_master_id', $selectedIds->all())
                    ->delete();

                // Ensure all selected areas exist (no duplicates)
                foreach ($selectedIds as $locationId) {
                    $content->targetAreas()->firstOrCreate([
                        'location_master_id' => $locationId,
                    ]);
                }
            }
        });

        $this->dispatch('toast', type: 'success', message: 'Content updated successfully!');
        return redirect()->route('admin.content-moderation.index');
    }

    public function saveAsDraft()
    {
        $this->status = 'draft';

        $this->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'media_file'  => [
                'nullable',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $extension = strtolower($value->getClientOriginalExtension());
                        $mimeType  = $value->getMimeType();

                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];
                        $allowedMimeTypes  = [
                            'image/jpeg', 'image/png', 'image/gif',
                            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv',
                            'application/octet-stream',
                        ];

                        if (! in_array($extension, $allowedExtensions) && ! in_array($mimeType, $allowedMimeTypes)) {
                            $fail('The media file must be a file of type: jpg, jpeg, png, gif, mp4, mov, avi.');
                        }
                    }
                },
            ],
            'category'      => 'required|in:new,patient guide',
            'speciality_id' => 'nullable|exists:specialities_masters,id',
            'area_ids'      => 'nullable|array',
            'area_ids.*'    => 'exists:location_masters,id',
            'hospital_id'   => 'nullable|exists:hospitals,id',
            'doctor_id'     => 'nullable|exists:doctors,id',
        ]);

        $content = ContentModeration::findOrFail($this->content_id);

        DB::transaction(function () use ($content) {
            // Media handling (same as update)
            $mediaFileName = $content->media_file;
            if ($this->media_file) {
                if ($content->media_file && Storage::disk('public')->exists($content->media_file)) {
                    Storage::disk('public')->delete($content->media_file);
                }
                $mediaFileName = $this->media_file->store('content', 'public');
            } elseif ($this->existing_media_file === null) {
                if ($content->media_file && Storage::disk('public')->exists($content->media_file)) {
                    Storage::disk('public')->delete($content->media_file);
                }
                $mediaFileName = null;
            }

            $organization_id = null;
            if ($this->hospital_id) {
                $hospital = Hospital::find($this->hospital_id);
                if ($hospital) {
                    $organization_id = $hospital->organization_id;
                }
            }

            $scheduleTimeData = null;
            if ($this->schedule_date && $this->schedule_time) {
                $scheduleTimeData = [
                    'date' => $this->schedule_date,
                    'time' => $this->schedule_time,
                ];
            }

            // IMPORTANT: update existing row, do NOT create a new one
            $content->update([
                'title'              => $this->title,
                'description'        => $this->description,
                'media_file'         => $mediaFileName,
                'category'           => $this->category,
                'speciality_id'      => $this->speciality_id,
                'hospital_id'        => $this->hospital_id,
                'organization_id'    => $organization_id,
                'doctor_id'          => $this->doctor_id,
                'status'             => 'draft',
                'is_published'       => false,
                'schedule_time_data' => $scheduleTimeData,
                'updated_by'         => Auth::id(),
            ]);

            // Reuse the same SYNC logic for target areas as update()
            $selectedIds = collect($this->area_ids ?? [])
                ->filter()
                ->unique()
                ->values();

            if ($selectedIds->isEmpty()) {
                $content->targetAreas()->delete();
            } else {
                $content->targetAreas()
                    ->whereNotIn('location_master_id', $selectedIds->all())
                    ->delete();

                foreach ($selectedIds as $locationId) {
                    $content->targetAreas()->firstOrCreate([
                        'location_master_id' => $locationId,
                    ]);
                }
            }
        });

        $this->dispatch('toast', type: 'success', message: 'Content saved as draft successfully!');
        return redirect()->route('admin.content-moderation.index');
    }

    public function render()
    {
        return view('livewire.admin.content.edit-content');
    }
}

