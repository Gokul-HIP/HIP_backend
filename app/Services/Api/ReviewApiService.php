<?php

namespace App\Services\Api;

use App\Models\DoctorReview;
use App\Models\HospitalReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReviewApiService
{
    /**
     * Store a doctor review.
     */
    public function createDoctorReview(
        string $memberId,
        string $doctorId,
        ?string $review,
        int $rating,
        ?string $quickTags = null
    ): void {
        $quickTags = filled($quickTags) ? trim($quickTags) : null;

        DoctorReview::create([
            'member_id'  => $memberId,
            'doctor_id'  => $doctorId,
            'review'     => $review,
            'rating'     => $rating,
            'quick_tags' => $quickTags,
            'status'     => 'inactive',
        ]);
    }

    /**
     * Store a hospital review.
     */
    public function createHospitalReview(int $memberId, int $hospitalId, ?string $review, int $rating): void
    {
        HospitalReview::create([
            'member_id'   => $memberId,
            'hospital_id' => $hospitalId,
            'review'      => $review,
            'rating'      => $rating,
        ]);
    }

    /**
     * Fetch paginated reviews and presentation data for API.
     *
     * @return array{message:string, reviews:LengthAwarePaginator, average_rating:float}
     */
    public function getReviews(string $type, int $id): array
    {
        if ($type === 'hospital') {
            $reviews = HospitalReview::where('hospital_id', $id)
                ->where('status', 'active')
                ->with('member')
                ->paginate(10);

            $message = 'Hospital reviews fetched successfully';
        } elseif ($type === 'doctor') {
            $reviews = DoctorReview::where('doctor_id', $id)
                ->where('status', 'active')
                ->with('member')
                ->paginate(10);

            $message = 'Doctor reviews fetched successfully';
        } else {
            throw new \InvalidArgumentException('Invalid type');
        }

        $averageRating = round((float) $reviews->avg('rating'), 1);

        return [
            'message'        => $message,
            'reviews'        => $reviews,
            'average_rating' => $averageRating,
        ];
    }
}

