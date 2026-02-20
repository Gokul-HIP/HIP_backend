<?php

namespace App\Livewire\Admin\DoctorReview;

use Livewire\Component;
use App\Models\DoctorReview;
use Livewire\WithPagination;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $status = 'all';
    public $dateFilter = '';
    public $ratingFilter = 'all';
    public $delete_id;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function updatingRatingFilter()
    {
        $this->resetPage();
    }

    public function clearDateFilter()
    {
        $this->dateFilter = '';
        $this->resetPage();
    }

    public function toggleStatus($id)
    {
        $review = DoctorReview::findOrFail($id);
        
        if ($review->status === 'active') {
            $review->status = 'inactive';
            $review->save();
            $this->dispatch('toast', type: 'success', message: 'Review status updated to inactive!');
        } elseif ($review->status === 'inactive') {
            $review->status = 'active';
            $review->save();
            $this->dispatch('toast', type: 'success', message: 'Review status updated to active!');
        }
        
        $this->resetPage();
    }

    public function openDeleteReviewModal($id)
    {
        $this->delete_id = $id;
        Flux::modal('delete-review')->show();
    }

    public function closeDeleteReviewModal()
    {
        $this->delete_id = null;
        Flux::modal('delete-review')->close();
    }

    public function deleteReview()
    {
        $review = DoctorReview::findOrFail($this->delete_id);
        $review->delete();
        
        Flux::modal('delete-review')->close();
        $this->dispatch('toast', type: 'success', message: 'Review deleted successfully!');
        $this->resetPage();
    }

    public function render()
    {
        $query = DoctorReview::with(['member', 'doctor'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('member', function ($memberQuery) {
                        $memberQuery->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('doctor', function ($doctorQuery) {
                        $doctorQuery->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('review', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status !== 'all', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->dateFilter, function ($q) {
                $q->whereDate('created_at', $this->dateFilter);
            })
            ->when($this->ratingFilter !== 'all', function ($q) {
                $q->where('rating', $this->ratingFilter);
            })
            ->orderBy('created_at', 'desc');

        $reviews = $query->paginate(10);

        return view('livewire.admin.doctor-review.index', [
            'reviews' => $reviews,
        ]);
    }
}
