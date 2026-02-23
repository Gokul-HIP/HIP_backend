<?php

namespace App\Livewire\Admin\HospitalReview;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\HospitalReview;
use Flux\Flux;

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
        $review = HospitalReview::findOrFail($id);
        
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
        $review = HospitalReview::findOrFail($this->delete_id);
        $review->delete();
        
        Flux::modal('delete-review')->close();
        $this->dispatch('toast', type: 'success', message: 'Review deleted successfully!');
        $this->resetPage();
    }
    public function render()
    {
        $query = HospitalReview::with(['member', 'hospital'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('member', function ($memberQuery) {
                        $memberQuery->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('hospital', function ($hospitalQuery) {
                        $hospitalQuery->where('name', 'like', '%' . $this->search . '%');
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
        return view('livewire.admin.hospital-review.index', [
            'reviews' => $reviews,
        ]);
    }
}
