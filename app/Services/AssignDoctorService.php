<?php

namespace App\Services;

use App\Models\DoctorAssignment;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\Hospital;
use Carbon\Carbon;

class AssignDoctorService
{
    public function findAssignment($id)
    {
        return DoctorAssignment::findOrFail($id);
    }

    public function getAssignmentsByDoctorAndHospital($doctorId, $hospitalId, $excludeId = null)
    {
        $query = DoctorAssignment::where('doctor_id', $doctorId)
            ->where('hospital_id', $hospitalId)
            ->where('status', 'active');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    public function getAllAssignmentsByDoctorAndHospital($doctorId, $hospitalId)
    {
        return DoctorAssignment::where('doctor_id', $doctorId)
            ->where('hospital_id', $hospitalId)
            ->where('status', 'active')
            ->orderBy('date')
            ->get();
    }

    public function assignmentExists($doctorId, $hospitalId, $date)
    {
        return DoctorAssignment::where([
            'doctor_id' => $doctorId,
            'hospital_id' => $hospitalId,
            'date' => $date,
        ])->exists();
    }

    public function createAssignment(array $data)
    {
        return DoctorAssignment::create($data);
    }

    public function updateAssignment($id, array $data)
    {
        $assignment = DoctorAssignment::findOrFail($id);
        $assignment->update($data);
        return $assignment->fresh();
    }

    public function deleteAssignmentsByDoctorAndHospital($doctorId, $hospitalId)
    {
        return DoctorAssignment::where('doctor_id', $doctorId)
            ->where('hospital_id', $hospitalId)
            ->where('status', 'active')
            ->delete();
    }

    public function getAssignedDoctorIds($hospitalId)
    {
        return DoctorAssignment::where('hospital_id', $hospitalId)
            ->pluck('doctor_id')
            ->unique()
            ->toArray();
    }

    public function getDoctorsForAssignment($hospitalId, $search = '', $excludeAssigned = true)
    {
        $query = Doctor::query();

        if ($excludeAssigned) {
            $assignedDoctorIds = $this->getAssignedDoctorIds($hospitalId);
            $query->whereNotIn('id', $assignedDoctorIds);
        }

        $query->whereJsonContains('hospital_ids', $hospitalId);

        if ($search) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhereRaw(
                        "LOWER(JSON_UNQUOTE(JSON_EXTRACT(speciality, '$'))) LIKE ?",
                        ['%' . strtolower($search) . '%']
                    )
                    ->orWhereRaw(
                        "LOWER(JSON_UNQUOTE(JSON_EXTRACT(qualifications, '$'))) LIKE ?",
                        ['%' . strtolower($search) . '%']
                    );
            });
        }

        return $query->orderBy('id')->paginate(10);
    }

    public function getProceduresByHospital($hospitalId, $search = '')
    {
        $query = Procedure::where('hospital_id', $hospitalId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('procedure_name', 'like', "%{$search}%")
                    ->orWhere('procedure_code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('procedure_name')->paginate(10);
    }

    public function getProceduresByIds(array $procedureIds)
    {
        return Procedure::whereIn('id', $procedureIds)->get();
    }

    public function getSpecialityIdsFromProcedures(array $procedureIds)
    {
        return Procedure::whereIn('id', $procedureIds)
            ->pluck('speciality_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    public function updateDoctorAssignments($doctorId, array $procedureIds, $hospitalId, $organizationId = null)
    {
        $doctor = Doctor::findOrFail($doctorId);

        $existingProcedures = collect($doctor->assigned_procedure ?? [])
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $newProcedures = collect($procedureIds)
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $finalProcedures = array_values(array_unique(
            array_merge($existingProcedures, $newProcedures)
        ));

        $specialityIdsFromProcedures = $this->getSpecialityIdsFromProcedures($finalProcedures);

        $existingSpecialities = collect($doctor->assigned_speciality ?? [])
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $finalSpecialities = array_values(array_unique(
            array_merge($existingSpecialities, $specialityIdsFromProcedures)
        ));

        $existingHospitals = collect($doctor->assigned_hospital ?? [])
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $finalHospitals = array_values(array_unique(
            array_merge($existingHospitals, [(int) $hospitalId])
        ));

        $doctor->update([
            'assigned_speciality' => $finalSpecialities,
            'assigned_procedure' => $finalProcedures,
            'assigned_hospital' => $finalHospitals,
            'assigned_organization' => $organizationId ?? $doctor->organization_id,
        ]);

        return $doctor->fresh();
    }

    public function updateDoctorAssignmentsFromAllAssignments($doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);

        $remainingAssignments = DoctorAssignment::with('hospital')
            ->where('doctor_id', $doctorId)
            ->where('status', 'active')
            ->get();

        if ($remainingAssignments->isEmpty()) {
            $doctor->update([
                'assigned_hospital' => [],
                'assigned_procedure' => [],
                'assigned_speciality' => [],
                'assigned_organization' => null,
            ]);

            return $doctor->fresh();
        }

        // Hospitals
        $hospitalIds = $remainingAssignments->pluck('hospital_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        // Procedures
        $procedureIds = $remainingAssignments->pluck('procedure_ids')
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        // Specialities
        $specialityIds = $this->getSpecialityIdsFromProcedures($procedureIds);

        // Organization (safe)
        $organizationId = Hospital::whereIn('id', $hospitalIds)
            ->value('organization_id');

        $doctor->update([
            'assigned_hospital' => $hospitalIds,
            'assigned_procedure' => $procedureIds,
            'assigned_speciality' => $specialityIds,
            'assigned_organization' => $organizationId,
        ]);

        return $doctor->fresh();
    }

    public function getDateForDay(string $day): string
    {
        $today = Carbon::today();
        $targetDow = Carbon::parse($day)->dayOfWeek;

        $nextWeek = $today->copy()->addWeek();

        $diff = ($targetDow - $nextWeek->dayOfWeek + 7) % 7;

        return $nextWeek->addDays($diff)->toDateString();
    }

    public function getAssignmentDetails($assignment)
    {
        $procedures = $this->getProceduresByIds($assignment->procedure_ids ?? []);

        return [
            'day' => $assignment->day,
            'date' => $assignment->date->format('M d, Y'),
            'procedures' => $procedures->pluck('procedure_name')->toArray(),
            'time_slots' => $assignment->time_slots,
            'status' => $assignment->status,
        ];
    }

    public function getDoctorsWithAssignments($hospitalId, array $filters = [])
    {
        $query = Doctor::whereHas('assignments', function ($q) use ($hospitalId) {
            $q->where('hospital_id', $hospitalId)
                ->where('status', 'active');
        });

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('mobile_number', 'like', $search);
            });
        }

        if (!empty($filters['sort'])) {
            match ($filters['sort']) {
                'latest' => $query->latest(),
                default => $query->orderBy('name'),
            };
        } else {
            $query->orderBy('name');
        }

        return $query->get();
    }

    public function getAllProcedureIdsForDoctor($doctorId)
    {
        return DoctorAssignment::where('doctor_id', $doctorId)
            ->where('status', 'active')
            ->pluck('procedure_ids')
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }
}