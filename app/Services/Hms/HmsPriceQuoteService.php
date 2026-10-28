<?php

namespace App\Services\Hms;

use App\Exceptions\HmsClientException;
use App\Models\DiagnosticPackage;
use App\Models\Doctor;
use App\Models\HmsExternalIdMap;
use App\Models\HmsIntegration;
use App\Models\Hospital;
use App\Services\BookingPricingPreviewService;
use Illuminate\Support\Str;

/** Prices a booking the same way the app preview does, without persisting a quote. */
class HmsPriceQuoteService
{
    public const TTL_MINUTES = 15;

    /** Passed to the read-only preview when the mobile has no HIP account. */
    public const ANONYMOUS_HIP_USER_ID = '00000000-0000-0000-0000-000000000000';

    public function __construct(private readonly BookingPricingPreviewService $pricing) {}

    /**
     * @return array<string, mixed>
     */
    public function quote(
        string $hipUserId,
        string $bookingType,
        Hospital $hospital,
        ?Doctor $doctor,
        ?DiagnosticPackage $package,
        ?float $originalFee,
        bool $patientExists = true,
    ): array {
        if ($bookingType === 'package') {
            if (! $package) {
                throw new HmsClientException('not_found', 'Package was not found.', 404);
            }
            if (! $this->packageAvailableAt($package, $hospital)) {
                throw new HmsClientException('not_found', 'Package was not found at this hospital.', 404);
            }

            $pricedPackage = $package;
            if ($originalFee !== null) {
                $pricedPackage = clone $package;
                $pricedPackage->setAttribute('price', $originalFee);
                $pricedPackage->setAttribute('discount', null);
            }

            $preview = $this->pricing->package($hipUserId, $pricedPackage, 'diagnostic', 0, 0);
        } else {
            if (! $doctor) {
                throw new HmsClientException('not_found', 'Doctor was not found.', 404);
            }
            if (! $this->doctorAvailableAt($doctor, (int) $hospital->getKey())) {
                throw new HmsClientException('not_found', 'Doctor was not found at this hospital.', 404);
            }

            $fee = $originalFee ?? round((float) ($doctor->consultation_fee ?? 0), 2);
            $preview = $bookingType === 'second_opinion'
                ? $this->pricing->secondOpinion($hipUserId, $fee, 0, 0)
                : $this->pricing->doctor($hipUserId, $fee, 0, 0, (int) $hospital->getKey(), [], true);
        }

        $pricing = $preview['pricing'];

        return [
            'quote_id' => (string) Str::uuid(),
            'patient_exists' => $patientExists,
            'payable_amount' => $pricing['total_payable'],
            'currency' => 'INR',
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->toIso8601String(),
            'pricing' => $pricing,
        ];
    }

    public function resolveDoctor(HmsIntegration $integration, string $doctorId): ?Doctor
    {
        $doctorId = trim($doctorId);
        if ($doctorId === '') {
            return null;
        }

        $doctor = Str::isUuid($doctorId) ? Doctor::query()->find($doctorId) : null;
        if ($doctor) {
            return $doctor;
        }

        $mapped = HmsExternalIdMap::query()
            ->where('integration_id', $integration->getKey())
            ->where('entity_type', HmsExternalIdMap::TYPE_DOCTOR)
            ->where('external_id', $doctorId)
            ->first();

        return $mapped ? Doctor::query()->find($mapped->local_id) : null;
    }

    public function resolvePackage(HmsIntegration $integration, string $packageCode): ?DiagnosticPackage
    {
        $packageCode = trim($packageCode);
        if ($packageCode === '') {
            return null;
        }

        $mapped = HmsExternalIdMap::query()
            ->where('integration_id', $integration->getKey())
            ->where('entity_type', HmsExternalIdMap::TYPE_PACKAGE)
            ->where('external_id', $packageCode)
            ->first();

        if ($mapped) {
            return DiagnosticPackage::query()->find($mapped->local_id);
        }

        return DiagnosticPackage::query()->where('code', $packageCode)->first();
    }

    private function doctorAvailableAt(Doctor $doctor, int $hospitalId): bool
    {
        $hospitalIds = array_map('intval', $doctor->hospital_ids ?? []);

        return $hospitalIds === [] || in_array($hospitalId, $hospitalIds, true);
    }

    private function packageAvailableAt(DiagnosticPackage $package, Hospital $hospital): bool
    {
        if (! $hospital->diagnostic_center_id || $package->status !== 'active') {
            return false;
        }

        return (int) $package->diagnostic_id === (int) $hospital->diagnostic_center_id;
    }
}
