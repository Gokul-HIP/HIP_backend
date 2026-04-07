<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\SpecialitiesMaster;
use App\Models\MasterQualification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DoctorProfileService
{
    public function getDoctorsPaginated(array $filters = [], $perPage = 10)
    {
        $query = Doctor::query()->with(['organization:id,name']);

        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', (int) $filters['organization_id']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('working_since', 'like', "%{$search}%");

                $q->orWhereHas('organization', function ($org) use ($search) {
                    $org->where('name', 'like', "%{$search}%");
                });

                $specialityIds = SpecialitiesMaster::where(
                    'name',
                    'like',
                    "%{$search}%"
                )->pluck('id')->toArray();

                if (!empty($specialityIds)) {
                    $q->orWhere(function ($qq) use ($specialityIds) {
                        foreach ($specialityIds as $id) {
                            $qq->orWhere('speciality', 'like', '%"' . $id . '"%');
                        }
                    });
                }

                $qualificationIds = MasterQualification::where(
                    'name',
                    'like',
                    "%{$search}%"
                )->pluck('id')->toArray();

                if (!empty($qualificationIds)) {
                    $q->orWhere(function ($qq) use ($qualificationIds) {
                        foreach ($qualificationIds as $id) {
                            $qq->orWhere('qualifications', 'like', '%"' . $id . '"%');
                        }
                    });
                }
            });
        }

        if (!empty($filters['sort'])) {
            match ($filters['sort']) {
                'name_asc' => $query->orderBy('name', 'asc'),
                'name_desc' => $query->orderBy('name', 'desc'),
                'newest' => $query->orderBy('id', 'desc'),
                'oldest' => $query->orderBy('id', 'asc'),
                default => $query->orderBy('name', 'asc'),
            };
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function findDoctor($id)
    {
        return Doctor::findOrFail($id);
    }

    public function createDoctor(array $data, $imageFile = null)
    {
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('doctor', $imageName, 'public');
            $data['doctor_image'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        return Doctor::create($data);
    }

    public function updateDoctor($id, array $data, $imageFile = null, $removeImage = false)
    {
        $doctor = Doctor::findOrFail($id);
        $oldPath = 'doctor/' . $doctor->doctor_image;

        // Handle image removal
        if ($removeImage && !$imageFile) {
            if ($doctor->doctor_image && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $data['doctor_image'] = null;
        } elseif ($imageFile) {
            // Upload new image
            if ($doctor->doctor_image && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('doctor', $imageName, 'public');
            $data['doctor_image'] = $imageName;
        }
        // If no new image and not removing, keep existing image (don't modify it)

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        $doctor->update($data);

        return $doctor->fresh();
    }

    public function deleteDoctor($id)
    {
        $doctor = Doctor::findOrFail($id);

        if ($doctor->doctor_image) {
            $imagePath = 'doctor/' . $doctor->doctor_image;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $doctor->delete();
    }

    public function unlinkHospital($doctorId, $hospitalId)
    {
        $doctor = Doctor::findOrFail($doctorId);

        $updatedHospitalIds = collect($doctor->hospital_ids ?? [])
            ->reject(fn ($id) => (int) $id === (int) $hospitalId)
            ->values()
            ->toArray();

        $organizationId = null;
        if (count($updatedHospitalIds) > 0) {
            $organizationId = Hospital::whereIn('id', $updatedHospitalIds)
                ->value('organization_id');
        }

        $doctor->update([
            'hospital_ids' => $updatedHospitalIds,
            'organization_id' => $organizationId,
        ]);

        return $doctor->fresh();
    }

    public function getLinkedHospitals($doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);
        return $doctor->hospitals();
    }
}