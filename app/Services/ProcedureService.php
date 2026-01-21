<?php

namespace App\Services;

use App\Models\Procedure;

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

    /**
     * Create procedure
     */
    public function createProcedure(array $data)
    {
        return Procedure::create($data);
    }

    /**
     * Update procedure
     */
    public function updateProcedure($id, array $data)
    {
        $procedure = Procedure::findOrFail($id);
        $procedure->update($data);

        return $procedure;
    }

    /**
     * Delete procedure
     */
    public function deleteProcedure($id)
    {
        return Procedure::findOrFail($id)->delete();
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
     * Search procedures (CORRECT FK-BASED SEARCH)
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
