<?php

namespace App\Livewire\Admin\Organization\Hospital\ViewDoctor;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Flux\Flux;
use App\Services\AssignDoctorService;
use App\Models\Procedure;
use App\Models\Doctor;

class AssignDoctor extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public int $step = 1;
    public ?int $hospitalId = null;
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

    #[On('open-assign-doctor')]
    public function open(int $hospitalId)
    {
        $this->resetInput();
        $this->resetPage();

        $this->hospitalId = $hospitalId;

        Flux::modal('assign-doctor')->show();
    }

    public function addScheduleDay()
    {
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

        $doctor = $this->assignDoctorService->updateDoctorAssignments(
            $this->selectedDoctorId,
            $this->selectedProcedures,
            $this->hospitalId
        );

        foreach ($this->schedules as $schedule) {
            $date = $this->getDateForDay($schedule['day']);

            if ($this->assignDoctorService->assignmentExists(
                $this->selectedDoctorId,
                $this->hospitalId,
                $date
            )) {
                $this->addError(
                    'schedules',
                    "Doctor already assigned on {$schedule['day']}"
                );
                return;
            }
            
            $doctorName = Doctor::find($this->selectedDoctorId)->first();

            $this->assignDoctorService->createAssignment([
                'doctor_id'     => $this->selectedDoctorId,
                'hospital_id'   => $this->hospitalId,
                'day'           => $schedule['day'],
                'date'          => $date,
                'time_slots'    => $schedule['slots'],
                'procedure_ids' => $this->selectedProcedures,
                'notes'         => $this->notes,
                'status'        => 'active',
            ]);
        }

        Flux::modal('assign-doctor')->close();
        $this->dispatch('assignment');

        $this->dispatch('toast', type: 'success', message: 'Doctor '.$doctorName->doctor_name.' assigned successfully!');
    }

    public function getDoctorAssignments()
    {
        if (!$this->selectedDoctorId) {
            return collect();
        }

        $assignments = $this->assignDoctorService->getAssignmentsByDoctorAndHospital(
            $this->selectedDoctorId,
            $this->hospitalId
        );

        return $assignments->map(function ($assignment) {
            return $this->assignDoctorService->getAssignmentDetails($assignment);
        });
    }

    public function getCurrentAssignment()
    {
        if (empty($this->schedules) || empty($this->selectedProcedures)) {
            return null;
        }

        $procedures = $this->assignDoctorService->getProceduresByIds($this->selectedProcedures);
        $procedureNames = $procedures->pluck('procedure_name')->toArray();

        return collect($this->schedules)->map(function ($s) use ($procedureNames) {
            return [
                'day'        => $s['day'],
                'date'       => \Carbon\Carbon::parse(
                    $this->getDateForDay($s['day'])
                )->format('M d, Y'),
                'time_slots' => $s['slots'],
                'procedures' => $procedureNames,
                'is_new'     => true,
            ];
        });
    }

    public function render()
    {
        if (!$this->hospitalId) {
            return view(
                'livewire.admin.organization.hospital.view-doctor.assign-doctor',
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
            true
        );

        $procedures = $this->assignDoctorService->getProceduresByHospital(
            $this->hospitalId,
            $this->procedureSearch
        );

        return view(
            'livewire.admin.organization.hospital.view-doctor.assign-doctor',
            [
                'doctors'           => $doctors,
                'procedures'        => $procedures,
                'doctorAssignments' => $this->getDoctorAssignments(),
                'currentAssignment' => $this->getCurrentAssignment(),
            ]
        );
    }
}
