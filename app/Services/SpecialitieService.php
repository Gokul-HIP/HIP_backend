<?php

namespace App\Services;

use App\Models\Speciality;

class SpecialitieService
{
    public function getSpecialitiesByHospital($hospitalId, $perPage = 10)
    {
        return Speciality::where('hospital_id', $hospitalId)
            ->with(['hospital', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    public function getAllSpecialitiesByHospital($hospitalId)
    {
        return Speciality::where('hospital_id', $hospitalId)
            ->with(['hospital', 'organization'])
            ->latest()
            ->get();
    }

    public function createSpeciality(array $data)
    {
        return Speciality::create($data);
    }

    public function updateSpeciality($id, array $data)
    {
        $speciality = Speciality::findOrFail($id);
        $speciality->update($data);

        return $speciality;
    }

    public function deleteSpeciality($id)
    {
        return Speciality::findOrFail($id)->delete();
    }

    public function findSpeciality($id)
    {
        return Speciality::with(['hospital', 'organization'])->findOrFail($id);
    }

    public function searchSpecialities($hospitalId, array $filters = [], $perPage = 10)
    {
        $query = Speciality::where('hospital_id', $hospitalId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('speciality_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('speciality_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('department_category', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['department_category'])) {
            $query->where('department_category', $filters['department_category']);
        }

        return $query->latest()->paginate($perPage);
    }
}