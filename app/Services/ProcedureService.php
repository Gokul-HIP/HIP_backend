<?php

namespace App\Services;

use App\Models\Procedure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProcedureService
{
    /**
     * Get paginated procedures for a hospital
     */
    public function getProceduresByHospital($hospitalId, $perPage = 10)
    {
        return Procedure::where('hospital_id', $hospitalId)
            ->with(['hospital', 'organization', 'speciality'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all procedures for a hospital
     */
    public function getAllProceduresByHospital($hospitalId)
    {
        return Procedure::where('hospital_id', $hospitalId)
            ->with(['hospital', 'organization', 'speciality'])
            ->latest()
            ->get();
    }

    public function createBulkProcedure(array $data, $imageFile = null)
    {
        if (is_string($imageFile)) {
            $data['image'] = basename($imageFile);
        }

        if ($imageFile instanceof \Illuminate\Http\UploadedFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;

            $imageFile->storeAs('procedures', $imageName, 'public');
            $data['image'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        return Procedure::create($data);
    }

    /** 
     * Create procedure
     */
    public function createProcedure(array $data, $imageFile = null)
    {
        // Handle image upload
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('procedures', $imageName, 'public');
            $data['image'] = $imageName;
        }

        // Handle status conversion if needed
        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        // Format recovery_time if individual components are provided
        if (isset($data['recovery_from']) && isset($data['recovery_to']) && isset($data['recovery_unit'])) {
            $data['recovery_time'] = $data['recovery_from'] . ' ' . $data['recovery_unit'] . ' to ' . $data['recovery_to'] . ' ' . $data['recovery_unit'];
            unset($data['recovery_from'], $data['recovery_to'], $data['recovery_unit']);
        }

        return Procedure::create($data);
    }

    /**
     * Update procedure
     */
    public function updateProcedure($id, array $data, $imageFile = null, $removeImage = false)
    {
        $procedure = Procedure::findOrFail($id);
        $oldPath = 'procedures/' . $procedure->image;

        // Handle image removal
        if ($removeImage && !$imageFile) {
            if ($procedure->image && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $data['image'] = null;
        } elseif ($imageFile) {
            // Delete old image if exists
            if ($procedure->image && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            // Upload new image
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('procedures', $imageName, 'public');
            $data['image'] = $imageName;
        }
        // If no new image and not removing, keep existing image (don't modify it)

        // Handle status conversion
        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        // Format recovery_time if individual components are provided
        if (isset($data['recovery_from']) && isset($data['recovery_to']) && isset($data['recovery_unit'])) {
            $data['recovery_time'] = $data['recovery_from'] . ' ' . $data['recovery_unit'] . ' to ' . $data['recovery_to'] . ' ' . $data['recovery_unit'];
            unset($data['recovery_from'], $data['recovery_to'], $data['recovery_unit']);
        }

        $procedure->update($data);

        return $procedure->fresh();
    }

    /**
     * Delete procedure
     */
    public function deleteProcedure($id)
    {
        $procedure = Procedure::findOrFail($id);

        // Delete associated image if exists
        if ($procedure->image) {
            $imagePath = 'procedures/' . $procedure->image;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $procedure->delete();
    }

    /**
     * Find procedure
     */
    public function findProcedure($id)
    {
        return Procedure::with(['hospital', 'organization', 'speciality'])
            ->findOrFail($id);
    }

    /**
     * Search procedures (FK-BASED SEARCH)
     */
    public function searchProcedures($hospitalId, array $filters = [], $perPage = 10)
    {
        $query = Procedure::where('hospital_id', $hospitalId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('procedure_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('procedure_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('speciality', function ($sq) use ($filters) {
                      $sq->where('speciality_name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['speciality_id'])) {
            $query->where('speciality_id', $filters['speciality_id']);
        }

        return $query
            ->with(['hospital', 'organization', 'speciality'])
            ->latest()
            ->paginate($perPage);
    }
}