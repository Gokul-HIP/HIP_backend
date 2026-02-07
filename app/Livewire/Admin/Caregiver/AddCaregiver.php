<?php

namespace App\Livewire\Admin\Caregiver;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\CareGiver;
use App\Models\WellnessCenters;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\MasterQualification;

class AddCaregiver extends Component
{
    use WithFileUploads;

    // Form fields
    public $name;
    public $category;
    public $gender;
    public $qualification = [];
    public $working_since;
    public $about;
    public $wellness_center_id;
    public $mobile_number;
    public $whatsapp_number;
    public $email;
    public $address_line_1;
    public $address_line_2;
    public $city;
    public $is_active = false;

    // File uploads
    public $profile_photo;
    public $gallery_photos = [];
    
    // Preview URLs
    public $profile_photo_preview;
    public $gallery_previews = [];
    
    // Temporary qualification input
    public $newQualification;

    // Categories and Gender options
    public $categories = ['Elder Care', 'Nurse', 'Both'];
    public $genders = ['Male', 'Female', 'Other'];

    // Wellness centers
    public $wellness_centers = [];


    protected $messages = [
        'qualification.required' => 'Please add at least one qualification',
        'qualification.min' => 'Please add at least one qualification',
        'profile_photo.required' => 'Profile photo is required',
        'profile_photo.max' => 'Profile photo must not exceed 5MB',
        'gallery_photos.*.max' => 'Each gallery photo must not exceed 5MB',
    ];

    public function mount()
    {
        $this->wellness_centers = WellnessCenters::select('id', 'centre_name')->orderBy('centre_name')->get();
    }

    public function updatedProfilePhoto()
    {
        $this->validate([
            'profile_photo' => 'image|max:5120',
        ]);

        $this->profile_photo_preview = $this->profile_photo->temporaryUrl();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'gender' => 'required|string',
            'qualification' => 'required|array|min:1',
            'working_since' => 'required|digits:4|integer|min:1950|max:' . date('Y'),
            'about' => 'nullable|string|max:1000',
            'wellness_center_id' => 'required|exists:wellness_centres,id',
            'mobile_number' => 'required|string|max:15',
            'whatsapp_number' => 'nullable|string|max:15',
            'email' => 'required|email|unique:care_givers,email',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'profile_photo' => 'required|image|max:5120',
            'gallery_photos.*' => 'nullable|image|max:5120',
        ];
    }

    public function updatedGalleryPhotos()
    {
        $this->validate([
            'gallery_photos.*' => 'image|max:5120',
        ]);

        $this->gallery_previews = [];
        foreach ($this->gallery_photos as $photo) {
            $this->gallery_previews[] = $photo->temporaryUrl();
        }
    }

    public function removeGalleryPhoto($index)
    {
        array_splice($this->gallery_photos, $index, 1);
        array_splice($this->gallery_previews, $index, 1);
    }

    public function removeProfilePhoto()
    {
        $this->profile_photo = null;
        $this->profile_photo_preview = null;
    }

    public function addQualification($value)
    {
        if (!empty($value) && trim($value) !== '') {
            $this->qualification[] = trim($value);
            $this->newQualification = '';
        }
    }

    public function removeQualification($index)
    {
        array_splice($this->qualification, $index, 1);
    }

    public function save()
    {
        $this->validate();

        try {
            // Upload profile photo
            $extension = $this->profile_photo->getClientOriginalExtension();
            $filename = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $profilePhoto = Storage::disk('public')->putFileAs('caregivers/profile', $this->profile_photo, $filename);

            // Upload gallery photos
            $galleryPaths = [];
            if (!empty($this->gallery_photos)) {
                foreach ($this->gallery_photos as $photo) {
                    $extension = $photo->getClientOriginalExtension();
                    $filename = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
                    $galleryPhoto = Storage::disk('public')->putFileAs('caregivers/gallery', $photo, $filename);
                    $galleryPaths[] = $galleryPhoto;
                }
            }

            CareGiver::create([
                'name' => $this->name,
                'category' => $this->category,
                'gender' => $this->gender,
                'qualification' => $this->qualification,
                'working_since' => $this->working_since,
                'about' => $this->about,
                'wellness_center_id' => $this->wellness_center_id,
                'mobile_number' => $this->mobile_number,
                'whatsapp_number' => $this->whatsapp_number,
                'email' => $this->email,
                'image' => $profilePhoto,
                'gallery' => $galleryPaths,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'city' => $this->city,
                'is_active' => $this->is_active,
            ]);

            $this->dispatch('toast', type: 'success', message: 'Caregiver added successfully!');
            $this->dispatch('caregiver-added');
            
            return redirect()->route('admin.caregiver.index');

        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Failed to add caregiver. Please try again.');
        }
    }

    public function cancel()
    {
        return redirect()->route('admin.caregiver.index');
    }

    public function render()
    {
        $masterQualifications = MasterQualification::select('id', 'name')->orderBy('name')->get();

        return view('livewire.admin.caregiver.add-caregiver', compact('masterQualifications'));
    }
}