<?php

namespace Database\Seeders;

use App\Models\HowToEarnContent;
use Illuminate\Database\Seeder;

class HowToEarnContentSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            HowToEarnContent::TYPE_BOOKING_APPOINTMENT => [
                'how_to_earn' => [
                    [
                        'title' => 'Select Redemption Amount',
                        'description' => 'Choose how many HIP coins you want to apply to this doctor consultation booking.',
                    ],
                    [
                        'title' => 'Apply and Search',
                        'description' => 'Confirm the coin amount and continue to search for available doctors and time slots.',
                    ],
                    [
                        'title' => 'Complete Booking',
                        'description' => 'Select your preferred doctor and slot, then confirm the appointment with coins applied.',
                    ],
                ],
                'terms_conditions' => [
                    ['description' => 'Discount value: 1 HIP Coin = ₹1.00.'],
                    ['description' => 'Appointments are subject to real-time doctor availability.'],
                    ['description' => 'Coins can be redeemed up to the maximum limit shown for this service.'],
                    ['description' => 'Cancelled appointments may follow the hospital refund and coin reversal policy.'],
                ],
                'is_active' => true,
            ],
            HowToEarnContent::TYPE_HEALTH_PACKAGE => [
                'how_to_earn' => [
                    [
                        'title' => 'Choose a Health Package',
                        'description' => 'Browse available full body checkups and wellness screening packages.',
                    ],
                    [
                        'title' => 'Select Coin Redemption',
                        'description' => 'Pick how many HIP coins you want to use toward the package payable amount.',
                    ],
                    [
                        'title' => 'Confirm and Pay',
                        'description' => 'Review the final amount after coin discount and complete your package booking.',
                    ],
                ],
                'terms_conditions' => [
                    ['description' => 'Health packages are valid only at participating hospital branches.'],
                    ['description' => 'Coin redemption is limited to the maximum allowed for health package bookings.'],
                    ['description' => 'Package inclusions and fasting instructions apply as per the selected plan.'],
                    ['description' => 'Rescheduling is subject to diagnostic centre availability and policy.'],
                ],
                'is_active' => true,
            ],
            HowToEarnContent::TYPE_SECOND_OPINION => [
                'how_to_earn' => [
                    [
                        'title' => 'Submit Your Case',
                        'description' => 'Upload reports and share your medical history for specialist review.',
                    ],
                    [
                        'title' => 'Apply HIP Coins',
                        'description' => 'Select the number of coins to redeem against the second opinion consultation fee.',
                    ],
                    [
                        'title' => 'Receive Specialist Review',
                        'description' => 'A specialist reviews your case and shares recommendations through the app.',
                    ],
                ],
                'terms_conditions' => [
                    ['description' => 'Second opinion responses depend on report completeness and specialist availability.'],
                    ['description' => 'Coin redemption cannot exceed the service maximum for second opinion bookings.'],
                    ['description' => 'Medical opinions are advisory and do not replace emergency or in-person care.'],
                    ['description' => 'Uploaded documents must be clear and relevant to the case under review.'],
                ],
                'is_active' => true,
            ],
            HowToEarnContent::TYPE_FAMILY_PLAN => [
                'how_to_earn' => [
                    [
                        'title' => 'Select Family Plan',
                        'description' => 'Choose a family health subscription plan that covers your selected members.',
                    ],
                    [
                        'title' => 'Redeem or Transfer Coins',
                        'description' => 'Apply HIP coins toward plan activation or transfer coins for family subscription benefits.',
                    ],
                    [
                        'title' => 'Activate Membership',
                        'description' => 'Confirm covered members and activate the family plan for the selected duration.',
                    ],
                ],
                'terms_conditions' => [
                    ['description' => 'Family plan benefits apply only to members added during subscription activation.'],
                    ['description' => 'Coin usage for family plans follows the redeem limit shown in the wallet.'],
                    ['description' => 'Plan validity, consultations, and lab benefits depend on the selected package.'],
                    ['description' => 'Renewal and member changes are subject to plan terms at the time of renewal.'],
                ],
                'is_active' => true,
            ],
        ];

        foreach ($records as $type => $data) {
            HowToEarnContent::query()->updateOrCreate(
                ['type' => $type],
                $data
            );
        }

        $this->command?->info('HowToEarnContentSeeder: seeded '.count($records).' redeem content types.');
    }
}
