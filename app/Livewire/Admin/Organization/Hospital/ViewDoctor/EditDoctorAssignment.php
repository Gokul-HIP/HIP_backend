<?php

namespace App\Livewire\Admin\Organization\Hospital\ViewDoctor;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Flux\Flux;
use App\Services\AssignDoctorService;
use App\Models\Procedure;
use App\Models\Doctor;

class EditDoctorAssignment extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public int $step = 1;
    public ?int $hospitalId = null;
    public ?int $assignmentId = null;
    public ?int $selectedDoctorId = null;

    public string $doctorSearch = '';
    public string $procedureSearch = '';

    public array $selectedProcedures = [];
    public string $notes = '';

    protected $assignDoctorService;

    public function boot(AssignDoctorService $assignDoctorService)
    {
        $this->assignDoctorService = $assignDoctorService;
    }

    /**
     * schedules = [
     *   [
     *     'day' => 'Monday',
     *     'slots' => [
     *        ['start' => '09:00', 'end' => '17:00']
     *     ]
     *   ]
     * ]
     */
    public array $schedules = [];

    public array $days = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    public function resetInput()
    {
        $this->reset([
            'step',
            'assignmentId',
            'selectedDoctorId',
            'doctorSearch',
            'procedureSearch',
            'selectedProcedures',
            'notes',
        ]);

        $this->resetErrorBag();

        $this->step = 1;

        $this->schedules = [
            [
                'day' => null,
                'slots' => [
                    ['start' => '09:00', 'end' => '17:00'],
                ],
            ],
        ];
    }

    #[On('open-edit-assignment')]
    public function open(int $assignmentId)
    {
        $this->resetInput();
        $this->resetPage();

        $assignment = $this->assignDoctorService->findAssignment($assignmentId);

        $this->assignmentId      = $assignmentId;
        $this->hospitalId        = $assignment->hospital_id;
        $this->selectedDoctorId  = $assignment->doctor_id;

        // With the new single-row-per-doctor+hospital design, procedures
        // live on this single assignment record.
        $this->selectedProcedures = collect($assignment->procedure_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $this->notes = $assignment->notes ?? '';

        // Rebuild the schedules UI structure from the flattened time_slots JSON:
        // time_slots = [
        //   ['day' => 'Monday', 'start' => '09:00:00', 'end' => '10:00:00'],
        //   ['day' => 'Tuesday', 'start' => '13:00:00', 'end' => '14:00:00'],
        // ]
        $grouped = collect($assignment->time_slots ?? [])
            ->groupBy('day')
            ->map(function ($slots, $day) use ($assignment) {
                return [
                    'day'          => $day,
                    'slots'        => $slots->map(function ($slot) {
                        return [
                            'start' => $slot['start'] ?? '09:00',
                            'end'   => $slot['end'] ?? '17:00',
                        ];
                    })->values()->toArray(),
                    'assignment_id' => $assignment->id,
                ];
            })
            ->values()
            ->toArray();

        $this->schedules = !empty($grouped)
            ? $grouped
            : [
                [
                    'day'   => null,
                    'slots' => [
                        ['start' => '09:00', 'end' => '17:00'],
                    ],
                    'assignment_id' => $assignment->id,
                ],
            ];

        Flux::modal('edit-assignment')->show();
    }

    public function addScheduleDay()
    {
        foreach ($this->schedules as $schedule) {
            if (empty($schedule['day'])) {
                $this->addError('schedules', 'Please select day before adding another');
                return;
            }
        }

        $this->schedules[] = [
            'day' => null,
            'slots' => [
                ['start' => '09:00', 'end' => '17:00'],
            ],
        ];
    }

    public function addSlot(int $scheduleIndex)
    {
        $this->schedules[$scheduleIndex]['slots'][] = [
            'start' => '09:00',
            'end'   => '17:00',
        ];
    }

    public function removeSlot(int $scheduleIndex, int $slotIndex)
    {
        if (count($this->schedules[$scheduleIndex]['slots']) > 1) {
            unset($this->schedules[$scheduleIndex]['slots'][$slotIndex]);
            $this->schedules[$scheduleIndex]['slots'] =
                array_values($this->schedules[$scheduleIndex]['slots']);
        }
    }

    private function getDateForDay(string $day): string
    {
        return $this->assignDoctorService->getDateForDay($day);
    }

    public function removeScheduleDay(int $index): void
    {
        if (count($this->schedules) <= 1) {
            return;
        }

        unset($this->schedules[$index]);
        $this->schedules = array_values($this->schedules);
    }

    public function updatedDoctorSearch()
    {
        $this->resetPage();
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-assignment')->close();
    }

    public function next()
    {
        if ($this->step === 1 && !$this->selectedDoctorId) {
            $this->addError('selectedDoctorId', 'Please select a doctor');
            return;
        }

        if ($this->step === 2) {
            foreach ($this->schedules as $schedule) {
                if (empty($schedule['day'])) {
                    $this->addError('schedules', 'Please select all days');
                    return;
                }

                if (empty($schedule['slots'])) {
                    $this->addError('schedules', 'Each day must have time slots');
                    return;
                }
            }
        }

        if ($this->step === 3) {
            $this->validate([
                'selectedProcedures' => 'required|array|min:1',
            ]);
        }

        if ($this->step < 4) {
            $this->step++;
        }
    }

    public function back()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function save()
    {
        $this->validate([
            'selectedDoctorId'   => 'required|exists:doctors,id',
            'selectedProcedures' => 'required|array|min:1',
        ]);

        // Flatten all schedules into the time_slots JSON for this single assignment row.
        $allSlots = collect($this->schedules)
            ->filter(fn ($schedule) => !empty($schedule['day']))
            ->flatMap(function ($schedule) {
                $day = $schedule['day'];

                return collect($schedule['slots'] ?? [])
                    ->map(function (array $slot) use ($day) {
                        $normalize = function (?string $time): string {
                            $time = $time ?: '09:00';
                            return strlen($time) === 5 ? $time . ':00' : $time;
                        };

                        return [
                            'day'   => $day,
                            'start' => $normalize($slot['start'] ?? null),
                            'end'   => $normalize($slot['end'] ?? null),
                        ];
                    });
            })
            ->values()
            ->all();

        if (empty($allSlots)) {
            $this->addError('schedules', 'Please configure at least one day and time slot.');
            return;
        }

        // DB columns 'day' and 'date' are NOT NULL, so persist a
        // representative day/date (from the first slot) while the
        // detailed schedule still lives inside time_slots JSON.
        $representativeDay = collect($allSlots)->pluck('day')->first() ?? 'Monday';
        $representativeDate = $this->getDateForDay($representativeDay);

        $this->assignDoctorService->updateAssignment($this->assignmentId, [
            'day'           => $representativeDay,
            'date'          => $representativeDate,
            'time_slots'    => $allSlots,
            'procedure_ids' => $this->selectedProcedures,
            'notes'         => $this->notes,
        ]);

        $allProcedureIds = $this->assignDoctorService->getAllProcedureIdsForDoctor($this->selectedDoctorId);
        $finalSpecialities = $this->assignDoctorService->getSpecialityIdsFromProcedures($allProcedureIds);
    
        $doctor = Doctor::findOrFail($this->selectedDoctorId);
        $doctor->update([
            'assigned_procedure'  => $allProcedureIds,
            'assigned_speciality' => $finalSpecialities,
        ]);
    
        Flux::modal('edit-assignment')->close();
        $this->dispatch('assignment');
    
        $this->dispatch('toast', type: 'success', message: 'Assignment updated for doctor '.$doctor->name.' successfully!');
    }
    

    public function getDoctorAssignments()
    {
        if (!$this->selectedDoctorId) {
            return collect();
        }

        $assignments = $this->assignDoctorService->getAssignmentsByDoctorAndHospital(
            $this->selectedDoctorId,
            $this->hospitalId,
            $this->assignmentId
        );

        return $assignments->map(function ($assignment) {
            return $this->assignDoctorService->getAssignmentDetails($assignment);
        });
    }

    public function getCurrentAssignment()
    {
        if (empty($this->schedules) || empty($this->selectedProcedures)) {
            return collect();
        }

        $procedures = $this->assignDoctorService->getProceduresByIds($this->selectedProcedures);
        $procedureNames = $procedures->pluck('procedure_name')->toArray();

        $allSlots = collect($this->schedules)
            ->filter(fn ($schedule) => !empty($schedule['day']))
            ->flatMap(function ($schedule) {
                $day = $schedule['day'];

                return collect($schedule['slots'] ?? [])
                    ->map(function (array $slot) use ($day) {
                        return [
                            'day'   => $day,
                            'start' => $slot['start'] ?? '09:00',
                            'end'   => $slot['end'] ?? '17:00',
                        ];
                    });
            })
            ->values()
            ->all();

        return collect([
            [
                'day'        => collect($allSlots)->pluck('day')->unique()->implode(', '),
                'date'       => null,
                'time_slots' => $allSlots,
                'procedures' => $procedureNames,
                'is_new'     => false,
                'is_edited'  => true,
            ],
        ]);
    }

    public function render()
    {
        if (!$this->hospitalId) {
            return view(
                'livewire.admin.organization.hospital.view-doctor.edit-assignment',
                [
                    'doctors'            => collect()->paginate(10),
                    'procedures'         => collect(),
                    'doctorAssignments'  => collect(),
                    'currentAssignment'  => collect(),
                ]
            );
        }

        $doctors = $this->assignDoctorService->getDoctorsForAssignment(
            $this->hospitalId,
            $this->doctorSearch,
            false
        );

        $procedures = $this->assignDoctorService->getProceduresByHospital(
            $this->hospitalId,
            $this->procedureSearch
        );

        return view(
            'livewire.admin.organization.hospital.view-doctor.edit-assignment',
            [
                'doctors'           => $doctors,
                'procedures'        => $procedures,
                'doctorAssignments' => $this->getDoctorAssignments(),
                'currentAssignment' => $this->getCurrentAssignment(),
            ]
        );
    }
}

