<?php

namespace App\Livewire\Admin\Caregiver;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\CareGiver;
use App\Models\WellnessCenters;
use App\Models\MasterQualification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditCaregiver extends Component
{
    use WithFileUploads;

    public $caregiver;
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
    public $is_active;
    public $profile_photo;
    public $gallery_photos;
    public $gallery_previews = [];
    public $existing_gallery = [];
    public $remove_profile_photo = false;

    public $categories = ['Elder Care', 'Nurse', 'Both'];
    public $genders = ['Male', 'Female', 'Other'];
    public $wellness_centers = [];

    protected $messages = [
        'qualification.required' => 'Please add at least one qualification',
        'qualification.min' => 'Please add at least one qualification',
        'profile_photo.max' => 'Profile photo must not exceed 5MB',
        'gallery_photos.*.max' => 'Each gallery photo must not exceed 5MB',
    ];

    public function mount($id)
    {
        $this->caregiver = CareGiver::where('id', $id)->first();
        if (!$this->caregiver) {
            abort(404);
        }
        $this->wellness_centers = WellnessCenters::select('id', 'centre_name')->orderBy('centre_name')->get();
        $this->name = $this->caregiver->name;
        $this->category = $this->caregiver->category;
        $this->gender = $this->caregiver->gender;
        $this->qualification = $this->caregiver->qualification ?? [];
        $this->working_since = $this->caregiver->working_since;
        $this->about = $this->caregiver->about;
        $this->wellness_center_id = $this->caregiver->wellness_center_id;
        $this->mobile_number = $this->caregiver->mobile_number;
        $this->whatsapp_number = $this->caregiver->whatsapp_number;
        $this->email = $this->caregiver->email;
        $this->address_line_1 = $this->caregiver->address_line_1;
        $this->address_line_2 = $this->caregiver->address_line_2;
        $this->city = $this->caregiver->city;
        $this->is_active = $this->caregiver->is_active;
        $this->profile_photo = $this->caregiver->image;
        $this->existing_gallery = $this->caregiver->gallery ?? [];
        $this->gallery_photos = [];
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
            'email' => 'required|email|unique:care_givers,email,' . $this->caregiver->id,
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'profile_photo' => 'nullable',
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
        if (is_array($this->gallery_previews)) {
            array_splice($this->gallery_previews, $index, 1);
        }
        if (is_array($this->gallery_photos)) {
            array_splice($this->gallery_photos, $index, 1);
        }
    }

    public function removeProfilePhoto()
    {
        $this->profile_photo = null;
        $this->remove_profile_photo = true;
    }

    public function restoreProfilePhoto()
    {
        $this->remove_profile_photo = false;
        $this->profile_photo = $this->caregiver->image;
    }

    public function removeExistingGallery($index)
    {
        if (is_array($this->existing_gallery)) {
            array_splice($this->existing_gallery, $index, 1);
        }
    }

    public function save()
    {
        $rules = $this->rules();

        if ($this->profile_photo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $this->validate([
                'profile_photo' => 'image|max:5120',
            ]);
        } else {
            unset($rules['profile_photo']);
        }

        $this->validate($rules);

        try {
            $profilePhoto = $this->caregiver->image;
            if ($this->remove_profile_photo) {
                $profilePhoto = null;
            }

            if ($this->profile_photo && $this->profile_photo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $extension = $this->profile_photo->getClientOriginalExtension();
                $filename = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
                $profilePhoto = Storage::disk('public')->putFileAs('caregivers/profile', $this->profile_photo, $filename);
                $this->remove_profile_photo = false;
            }

            $galleryPaths = $this->existing_gallery ?? [];
            if (!empty($this->gallery_photos)) {
                foreach ($this->gallery_photos as $photo) {
                    if (!$photo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        continue;
                    }
                    $extension = $photo->getClientOriginalExtension();
                    $filename = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
                    $galleryPhoto = Storage::disk('public')->putFileAs('caregivers/gallery', $photo, $filename);
                    $galleryPaths[] = $galleryPhoto;
                }
            }

            $this->caregiver->update([
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

            $this->dispatch('toast', type: 'success', message: 'Caregiver updated successfully!');
            $this->dispatch('caregiver-updated');

            return redirect()->route('admin.caregiver.index');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Failed to update caregiver. Please try again.');
        }
    }

    public function cancel()
    {
        return redirect()->route('admin.caregiver.index');
    }

    public function render()
    {
        $qualificationOptions = MasterQualification::select('id', 'name')->orderBy('name')->get();

        return view('livewire.admin.caregiver.edit-caregiver', compact('qualificationOptions'));
    }
}
