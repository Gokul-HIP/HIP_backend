<?php

namespace App\Livewire\Admin\DoctorBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\DoctorBooking;
use Livewire\Attributes\On;
use App\Models\DoctorBookingStatus;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class UpdateStatus extends Component
{
    public $id;
    public $status;
    public $note;
    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->status = DoctorBooking::find($this->id)->status;
        $this->note = '';
        Flux::modal('update-status')->show();
    }

    public function updateStatus()
    {
        $doctorBooking = DoctorBooking::find($this->id);

        $oldStatus = $doctorBooking->status;

        $doctorBooking->status = $this->status;
        $doctorBooking->save();

        if ($this->status !== $oldStatus) {
            DoctorBookingStatus::create([
                'doctor_booking_id' => $doctorBooking->id,
                'from_status' => $oldStatus,
                'to_status' => $this->status,
                'changed_by' => Auth::id(),
            ]);

            if ($this->status === 'completed') {
                $this->sendReviewNotification($doctorBooking);
            }

            if($this->status === 'confirmed'){
                $this->sendConfirmationNotification($doctorBooking);
            }

            if($this->status === 'cancelled'){
                $this->sendCancellationNotification($doctorBooking);
            }
        }

        if ($this->note) {
            DoctorBookingStatus::create([
                'doctor_booking_id' => $doctorBooking->id,
                'notes' => $this->note,
                'notes_by' => Auth::id(),
            ]);
        }
        
        $this->dispatch('refreshDoctorBookings');
        $this->closeModal();
        
        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$doctorBooking->name.' successfully!');
    }

    protected function sendReviewNotification(DoctorBooking $booking): void
    {
        if (!$booking->member_id || !$booking->doctor_id) {
            return;
        }

        $notificationService = app(NotificationService::class);

        $title = 'How was your appointment?';
        $body = 'Please review the doctor ('.$booking->doctor->name.') for your recent appointment.';

        $data = [
            'type' => 'review_popup',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'appointment',
            'booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ];

        $notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }

    protected function sendConfirmationNotification(DoctorBooking $booking): void
    {
        if (!$booking->member_id || !$booking->doctor_id) {
            return;
        }

        $notificationService = app(NotificationService::class);

        $appointmentDate = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'your scheduled date';

        $appointmentTime = null;
        if (is_array($booking->required_time_slots) && count($booking->required_time_slots) > 0) {
            $appointmentTime = $booking->required_time_slots[0];
        }

        $timeText = $appointmentTime ? ' at '.$appointmentTime : '';

        $title = 'Your appointment is confirmed!';
        $body = 'Your appointment with the doctor ('.$booking->doctor->name.') has been confirmed for '.$appointmentDate.$timeText.'.';

        $data = [
            'type' => 'appointment_confirmation',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'appointment',
            'booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ];

        $notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }

    protected function sendCancellationNotification(DoctorBooking $booking): void{

        if (!$booking->member_id || !$booking->doctor_id) {
            return;
        }

        $notificationService = app(NotificationService::class);

        $appointmentDate = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'your scheduled date';

        $appointmentTime = null;
        if (is_array($booking->required_time_slots) && count($booking->required_time_slots) > 0) {
            $appointmentTime = $booking->required_time_slots[0];
        }

        $timeText = $appointmentTime ? ' at '.$appointmentTime : '';

        $title = 'Your appointment is cancelled!';
        $body = 'Your appointment with the doctor ('.$booking->doctor->name.') has been cancelled for '.$appointmentDate.$timeText.'.';

        $data = [
            'type' => 'appointment_cancellation',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'appointment',
            'booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ];

        $notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);

    }

    public function closeModal()
    {
        $this->status = '';
        $this->id = null;
        $this->reset(['status','id']);
        Flux::modal('update-status')->close();
    }

    public function render()
    {
        return view('livewire.admin.doctor-booking.update-status');
    }
}
