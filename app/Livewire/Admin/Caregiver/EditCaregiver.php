<?php

namespace App\Livewire\Admin\Caregiver;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\CareGiver;
use App\Models\WellnessCenters;
use App\Models\MasterQualification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Log;

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
    public $existing_profile_photo;
    public $gallery_photos = [];
    public $gallery_previews = [];
    public $existing_gallery = [];
    public $remove_profile_photo = false;
    public $removed_gallery_images = []; 
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
        $this->existing_profile_photo = $this->caregiver->image;
        $this->profile_photo = null;
        $this->existing_gallery = $this->caregiver->gallery ?? [];
        $this->gallery_photos = [];
        $this->removed_gallery_images = [];
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
            'profile_photo' => 'nullable|image|max:5120',
            'gallery_photos.*' => 'nullable|image|max:5120',
        ];
    }

    public function updatedGalleryPhotos()
    {
        $this->validate([
            'gallery_photos.*' => 'image|max:5120',
        ]);
        
        if (!is_array($this->gallery_photos)) {
            $this->gallery_photos = [$this->gallery_photos];
        }
    }

    public function updatedProfilePhoto()
    {
        if ($this->profile_photo) {
            $this->validate([
                'profile_photo' => 'image|max:5120',
            ]);
            $this->remove_profile_photo = false;
        }
    }

    public function removeGalleryPhoto($index)
    {
        // This is called from Alpine.js but we don't actually need to do anything here
        // because Alpine manages the previews and Livewire will get all files on submit
        // Just keeping this method so the wire:click doesn't error
    }

    public function removeProfilePhoto()
    {
        $this->profile_photo = null;
        $this->remove_profile_photo = true;
    }

    public function restoreProfilePhoto()
    {
        $this->remove_profile_photo = false;
        $this->existing_profile_photo = $this->caregiver->image;
        $this->profile_photo = null;
    }

    public function removeExistingGallery($index)
    {
        // Track removed image for deletion
        if (isset($this->existing_gallery[$index])) {
            $this->removed_gallery_images[] = $this->existing_gallery[$index];
            unset($this->existing_gallery[$index]);
            $this->existing_gallery = array_values($this->existing_gallery);
        }
    }

    /**
     * Delete old image file from storage
     */
    private function deleteOldImage($imagePath)
    {
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $oldProfilePhoto = $this->caregiver->image;
            $oldGallery = $this->caregiver->gallery ?? [];
            
            $profilePhoto = $this->existing_profile_photo;
            
            if ($this->remove_profile_photo) {
                if ($oldProfilePhoto) {
                    $this->deleteOldImage($oldProfilePhoto);
                }
                $profilePhoto = null;
            } elseif ($this->profile_photo) {
                if ($oldProfilePhoto) {
                    $this->deleteOldImage($oldProfilePhoto);
                }
                
                $extension = $this->profile_photo->getClientOriginalExtension();
                $filename = Str::uuid() . '_' . time() . '.' . $extension;
                $profilePhoto = $this->profile_photo->storeAs('caregivers/profile', $filename, 'public');
            }

            $galleryPaths = $this->existing_gallery;

            foreach ($this->removed_gallery_images as $removedImage) {
                $this->deleteOldImage($removedImage);
            }

            // Upload new gallery photos
            // Log::info('Gallery photos before upload:', [
            //     'count' => is_array($this->gallery_photos) ? count($this->gallery_photos) : 0,
            //     'is_array' => is_array($this->gallery_photos),
            //     'type' => gettype($this->gallery_photos)
            // ]);

            if (!empty($this->gallery_photos) && is_array($this->gallery_photos)) {
                $uploadedCount = 0;
                foreach ($this->gallery_photos as $index => $photo) {
                    // Skip if not a valid upload
                    if (!$photo instanceof TemporaryUploadedFile) {
                        Log::warning("Gallery photo at index {$index} is not a valid upload");
                        continue;
                    }
                    
                    $extension = $photo->getClientOriginalExtension();
                    $filename = Str::uuid() . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
                    $galleryPhoto = $photo->storeAs('caregivers/gallery', $filename, 'public');
                    $galleryPaths[] = $galleryPhoto;
                    $uploadedCount++;
                    // Log::info("Uploaded gallery photo {$uploadedCount}: {$galleryPhoto}");
                }
                // Log::info("Total gallery photos uploaded: {$uploadedCount}");
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
                'gallery' => array_values($galleryPaths),
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'city' => $this->city,
                'is_active' => $this->is_active,
            ]);

            $this->gallery_photos = [];
            $this->removed_gallery_images = [];

            session()->flash('success', 'Caregiver updated successfully!');
            $this->dispatch('toast', type: 'success', message: 'Caregiver updated successfully!');
            $this->dispatch('caregiver-updated');

            return redirect()->route('admin.caregiver.index');
            
        } catch (\Exception $e) {
            Log::error('Caregiver update error: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to update caregiver. Please try again.');
            session()->flash('error', 'Failed to update caregiver: ' . $e->getMessage());
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