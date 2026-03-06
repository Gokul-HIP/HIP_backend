<?php

namespace App\Livewire\Admin\DoctorReview;

use Livewire\Component;
use App\Models\DoctorReview;

class ViewReview extends Component
{
    public $reviewId;
    public $review;
    public $isActive = true;
    public $reviewActive = true;
    public $showDeleteModal = false;

    public function mount($reviewId)
    {
        $this->reviewId = $reviewId;
        $this->loadReview();
    }

    public function loadReview()
    {
        $this->review = DoctorReview::with(['member', 'doctor'])
            ->findOrFail($this->reviewId);
        
        $this->isActive = $this->review->status === 'active';
        $this->reviewActive = $this->review->status === 'active';
    }

    public function updateStatus()
    {
        $this->review->update([
            'status' => $this->isActive ? 'active' : 'inactive'
        ]);
        $this->reviewActive = $this->isActive;
    }

    public function openDeleteModal()
    {
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
    }

    public function confirmDelete()
    {
        $this->review->delete();
        $this->closeDeleteModal();
        $this->dispatch('reviewDeleted');
        // redirect back to list after deletion
        return redirect()->route('admin.doctor-review.index');
    }

    public function updatedIsActive()
    {
        $this->updateStatus();
    }

    public function updatedReviewActive()
    {
        $this->review->update([
            'status' => $this->reviewActive ? 'active' : 'inactive'
        ]);
        $this->isActive = $this->reviewActive;
    }

    public function render()
    {
        return view('livewire.admin.doctor-review.view-review', [
            'review' => $this->review,
        ]);
    }
}
