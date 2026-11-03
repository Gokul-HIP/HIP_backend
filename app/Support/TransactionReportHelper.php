<?php

namespace App\Support;

use App\Enums\HmsPaymentType;
use App\Models\HmisTransaction;
use App\Models\HmsIntegration;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Persons;
use App\Models\Transactions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionReportHelper
{
    /**
     * @return array<string, string>
     */
    public static function serviceTypeOptions(): array
    {
        return [
            'all' => 'All Services',
            'doctor_consultation' => 'Doctor Consultation',
            'second_opinion' => 'Second Opinion',
            'diagnostic_package' => 'Diagnostic Package',
            'procedure' => 'Procedure',
            'labTest' => 'Lab Test',
            'package' => 'Package',
            'pharmacy' => 'Pharmacy',
        ];
    }

    public static function serviceTypeLabel(string $type): string
    {
        $normalized = strtolower(str_replace('-', '_', trim($type)));

        return match ($normalized) {
            'doctor_consultation', 'doctor_consult', 'appointment', 'appointments' => 'Doctor Consultation',
            'second_opinion' => 'Second Opinion',
            'diagnostic_package' => 'Diagnostic Package',
            'procedure' => 'Procedure',
            'labtest' => 'Lab Test',
            'package' => 'Package',
            'pharmacy' => 'Pharmacy',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    /**
     * @return list<string>
     */
    public static function invoiceEagerLoads(): array
    {
        return [
            'invoice.primaryPerson.hipUser.hospital',
            'invoice.person.hipUser.hospital',
            'invoice.doctorBooking.hospital',
            'invoice.doctorBooking.branch',
            'invoice.secondOpinion.branch',
            'invoice.diagnosticTestBooking.branch',
        ];
    }

    public static function beginReportQuery(): Builder
    {
        return Transactions::query()
            ->select([
                'transactions.*',
                DB::raw('COALESCE(transactions.created_at, invoices.created_at) as transaction_sort_at'),
            ])
            ->distinct();
    }

    public static function applyDefaultOrdering(Builder $query): Builder
    {
        return $query->orderByDesc('transaction_sort_at');
    }

    public static function applyDateRangeFilter(Builder $query, string $fromDate, string $toDate): void
    {
        if ($fromDate !== '') {
            $query->whereDate(DB::raw('COALESCE(transactions.created_at, invoices.created_at)'), '>=', $fromDate);
        }

        if ($toDate !== '') {
            $query->whereDate(DB::raw('COALESCE(transactions.created_at, invoices.created_at)'), '<=', $toDate);
        }
    }

    public static function applyBookingJoins(Builder $query): Builder
    {
        return $query
            ->leftJoin('invoices', 'invoices.id', '=', 'transactions.invoice_id')
            ->leftJoin('doctor_bookings', 'doctor_bookings.id', '=', 'invoices.doctor_booking_id')
            ->leftJoin('second_opinions', 'second_opinions.id', '=', 'invoices.second_opinion_id')
            ->leftJoin('diagnostic_test_bookings', 'diagnostic_test_bookings.id', '=', 'invoices.diagnostic_test_booking_id')
            ->leftJoin('healthinpocket_users as creators', 'creators.id', '=', 'invoices.created_by')
            ->leftJoin('healthinpocket_users as transaction_creators', 'transaction_creators.id', '=', 'transactions.created_by')
            ->leftJoin('hospitals as cashier_hospitals', 'cashier_hospitals.id', '=', 'creators.hospital_id')
            ->leftJoin('hospitals as transaction_hospitals', 'transaction_hospitals.id', '=', 'transaction_creators.hospital_id')
            ->leftJoin('hospitals as doctor_hospitals', 'doctor_hospitals.id', '=', 'doctor_bookings.hospital_id')
            ->leftJoin('hospitals as doctor_branches', 'doctor_branches.id', '=', 'doctor_bookings.branch_id')
            ->leftJoin('hospitals as second_opinion_hospitals', 'second_opinion_hospitals.id', '=', 'second_opinions.branch_id')
            ->leftJoin('hospitals as diagnostic_hospitals', 'diagnostic_hospitals.id', '=', 'diagnostic_test_bookings.branch_id');
    }

    /**
     * @param  list<int>  $hospitalIds
     */
    public static function applyHospitalIdsScope(Builder $query, array $hospitalIds): void
    {
        if ($hospitalIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $builder) use ($hospitalIds) {
            $builder->whereIn('cashier_hospitals.id', $hospitalIds)
                ->orWhereIn('transaction_hospitals.id', $hospitalIds)
                ->orWhereIn('doctor_hospitals.id', $hospitalIds)
                ->orWhereIn('doctor_branches.id', $hospitalIds)
                ->orWhereIn('second_opinion_hospitals.id', $hospitalIds)
                ->orWhereIn('diagnostic_hospitals.id', $hospitalIds);
        });
    }

    public static function applyServiceTypeFilter(Builder $query, string $filter): void
    {
        if ($filter === 'all') {
            return;
        }

        $query->where(function (Builder $builder) use ($filter) {
            match ($filter) {
                'doctor_consultation' => $builder
                    ->whereJsonContains('transactions.service_types', 'doctor_consultation')
                    ->orWhereJsonContains('transactions.service_types', 'doctor_consult')
                    ->orWhereNotNull('invoices.doctor_booking_id'),
                'second_opinion' => $builder
                    ->whereJsonContains('transactions.service_types', 'second_opinion')
                    ->orWhereJsonContains('transactions.service_types', 'Second_opinion')
                    ->orWhereNotNull('invoices.second_opinion_id'),
                'diagnostic_package' => $builder
                    ->whereJsonContains('transactions.service_types', 'diagnostic_package')
                    ->orWhereNotNull('invoices.diagnostic_test_booking_id'),
                default => $builder->whereJsonContains('transactions.service_types', $filter),
            };
        });
    }

    public static function resolveHospital(?Invoice $invoice, ?Hospital $creatorHospital = null): ?Hospital
    {
        if ($invoice?->doctorBooking) {
            return $invoice->doctorBooking->hospital ?? $invoice->doctorBooking->branch;
        }

        if ($invoice?->secondOpinion) {
            return $invoice->secondOpinion->branch;
        }

        if ($invoice?->diagnosticTestBooking) {
            return $invoice->diagnosticTestBooking->branch;
        }

        return $creatorHospital
            ?? $invoice?->primaryPerson?->hipUser?->hospital
            ?? $invoice?->person?->hipUser?->hospital;
    }

    public static function resolveHospitalName(?Invoice $invoice, ?Hospital $creatorHospital = null): string
    {
        return self::resolveHospital($invoice, $creatorHospital)?->name ?: '-';
    }

    public static function transactionAmount(Transactions $transaction): float
    {
        return (float) ($transaction->total_amount ?? $transaction->transaction_amount ?? 0);
    }

    /**
     * Dashboard revenue bucket for a single service type value.
     */
    public static function dashboardCategoryFromType(string $type): string
    {
        $normalized = strtolower(str_replace('-', '_', trim($type)));

        if (in_array($normalized, [
            'doctor_consultation',
            'doctor_consult',
            'appointment',
            'appointments',
            'second_opinion',
            'procedure',
        ], true)) {
            return 'hospital_services';
        }

        if ($normalized === 'pharmacy') {
            return 'pharmacy';
        }

        if (in_array($normalized, ['diagnostic_package', 'labtest', 'lab_test', 'package'], true)) {
            return 'diagnostics';
        }

        return 'other';
    }

    /**
     * @return list<string>
     */
    public static function resolveDashboardCategories(Transactions $transaction): array
    {
        $types = collect((array) $transaction->service_types)
            ->filter()
            ->map(fn ($type) => self::dashboardCategoryFromType((string) $type))
            ->filter(fn ($category) => $category !== 'other')
            ->unique()
            ->values()
            ->all();

        if ($types !== []) {
            return $types;
        }

        $invoice = $transaction->invoice;
        if ($invoice?->doctor_booking_id || $invoice?->second_opinion_id) {
            return ['hospital_services'];
        }

        if ($invoice?->diagnostic_test_booking_id) {
            return ['diagnostics'];
        }

        return [];
    }

    public static function dashboardCategoryLabel(string $category): string
    {
        return match ($category) {
            'hospital_services' => 'Hospital Services',
            'pharmacy' => 'Pharmacy Orders',
            'diagnostics' => 'Diagnostics',
            default => ucfirst(str_replace('_', ' ', $category)),
        };
    }

    /**
     * @return array<string, float>
     */
    public static function emptyDashboardRevenueBuckets(): array
    {
        return [
            'hospital_services' => 0.0,
            'pharmacy' => 0.0,
            'diagnostics' => 0.0,
        ];
    }

    public static function isUsingHms(): bool
    {
        return (bool) config('hms.transaction_report_use_hms', false);
    }

    /**
     * @return array<string, string>
     */
    public static function hmsServiceTypeOptions(): array
    {
        return HmsPaymentType::options();
    }

    public static function hmsServiceTypeLabel(?string $type): string
    {
        if ($type === null || $type === '') {
            return '-';
        }

        if ($enum = HmsPaymentType::tryFrom($type)) {
            return $enum->label();
        }

        if ($type === 'membership') {
            return 'Membership';
        }

        return ucwords(str_replace('_', ' ', $type));
    }

    /**
     * @return array<string, string>
     */
    public static function hmsStatusOptions(): array
    {
        return [
            'all' => 'All Status',
            'recorded' => 'Successful',
            'pending' => 'Pending',
            'refunded' => 'Refunded',
            'ignored_ineligible' => 'Recorded (not eligible)',
            'unmatched' => 'Unmatched',
        ];
    }

    public static function hmsStatusLabel(?string $status): string
    {
        return match ($status) {
            'recorded' => 'Successful',
            'pending' => 'Pending',
            'refunded' => 'Refunded',
            'ignored_ineligible' => 'Recorded (not eligible)',
            'unmatched' => 'Unmatched',
            default => ucfirst(str_replace('_', ' ', (string) ($status ?: 'pending'))),
        };
    }

    public static function beginHmsReportQuery(): Builder
    {
        return HmisTransaction::query()
            ->where('is_refund', false)
            ->with(['person.hipUser'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public static function applyHmsDateRangeFilter(Builder $query, string $fromDate, string $toDate): void
    {
        $tz = config('app.timezone') ?: 'Asia/Kolkata';

        $fromTimestamp = null;
        if (trim($fromDate) !== '') {
            try {
                $fromTimestamp = \Illuminate\Support\Carbon::parse(trim($fromDate), $tz)->startOfDay()->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                $fromTimestamp = null;
            }
        }

        $toTimestamp = null;
        if (trim($toDate) !== '') {
            try {
                $toTimestamp = \Illuminate\Support\Carbon::parse(trim($toDate), $tz)->endOfDay()->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                $toTimestamp = null;
            }
        }

        if ($fromTimestamp !== null && $toTimestamp !== null) {
            $query->where(function (Builder $builder) use ($fromTimestamp, $toTimestamp) {
                $builder->where(DB::raw('COALESCE(paid_at, created_at)'), '>=', $fromTimestamp)
                    ->where(DB::raw('COALESCE(paid_at, created_at)'), '<=', $toTimestamp);
            });
        } elseif ($fromTimestamp !== null) {
            $query->where(DB::raw('COALESCE(paid_at, created_at)'), '>=', $fromTimestamp);
        } elseif ($toTimestamp !== null) {
            $query->where(DB::raw('COALESCE(paid_at, created_at)'), '<=', $toTimestamp);
        }
    }

    public static function applyHmsStatusFilter(Builder $query, string $statusFilter): void
    {
        if ($statusFilter !== 'all' && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }
    }

    public static function applyHmsServiceTypeFilter(Builder $query, string $serviceTypeFilter): void
    {
        if ($serviceTypeFilter !== 'all' && $serviceTypeFilter !== '') {
            $query->where('payment_type', $serviceTypeFilter);
        }
    }

    public static function applyHmsHospitalAndOrganizationScope(Builder $query, string $orgFilter, string $hospitalFilter): void
    {
        if ($hospitalFilter !== 'all' && $hospitalFilter !== '') {
            $targetHospital = Hospital::query()->find((int) $hospitalFilter);
            $targetCode = $targetHospital?->code ? strtoupper(trim((string) $targetHospital->code)) : null;

            $isIntegrationHospital = HmsIntegration::query()
                ->get(['hospital_ids'])
                ->contains(function (HmsIntegration $integ) use ($hospitalFilter) {
                    $ids = array_values(array_filter(array_map('intval', $integ->hospital_ids ?? [])));
                    return in_array((int) $hospitalFilter, $ids, true);
                });

            $query->where(function (Builder $builder) use ($targetCode, $isIntegrationHospital) {
                if ($targetCode !== null) {
                    if ($isIntegrationHospital) {
                        $builder->where(function (Builder $inner) use ($targetCode) {
                            $inner->whereRaw('UPPER(hospital_code) = ?', [$targetCode])
                                ->orWhereNull('hospital_code');
                        });
                    } else {
                        $builder->whereRaw('UPPER(hospital_code) = ?', [$targetCode]);
                    }
                } elseif ($isIntegrationHospital) {
                    $builder->whereNull('hospital_code');
                } else {
                    $builder->whereRaw('1 = 0');
                }
            });

            return;
        }

        if ($orgFilter !== 'all' && $orgFilter !== '') {
            $orgId = (int) $orgFilter;
            $hospitalsInOrg = Hospital::query()->where('organization_id', $orgId)->get(['id', 'code']);
            $orgHospitalCodes = $hospitalsInOrg->pluck('code')->filter()->map(fn ($code) => strtoupper(trim((string) $code)))->values()->all();
            $orgHospitalIds = $hospitalsInOrg->pluck('id')->map(fn ($id) => (int) $id)->all();

            $hasIntegrationInOrg = HmsIntegration::query()
                ->get(['organization_id', 'hospital_ids'])
                ->contains(function (HmsIntegration $integ) use ($orgId, $orgHospitalIds) {
                    if ((int) $integ->organization_id === $orgId) {
                        return true;
                    }
                    $ids = array_values(array_filter(array_map('intval', $integ->hospital_ids ?? [])));
                    return !empty(array_intersect($ids, $orgHospitalIds));
                });

            $query->where(function (Builder $builder) use ($orgHospitalCodes, $hasIntegrationInOrg) {
                $matched = false;
                if (!empty($orgHospitalCodes)) {
                    $builder->whereIn(DB::raw('UPPER(hospital_code)'), $orgHospitalCodes);
                    $matched = true;
                }
                if ($hasIntegrationInOrg) {
                    if ($matched) {
                        $builder->orWhereNull('hospital_code');
                    } else {
                        $builder->whereNull('hospital_code');
                    }
                    $matched = true;
                }
                if (!$matched) {
                    $builder->whereRaw('1 = 0');
                }
            });
        }
    }

    public static function applyHmsSearch(Builder $query, string $search): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $matchingHospitalCodes = Hospital::query()
            ->where('name', 'like', "%{$search}%")
            ->pluck('code')
            ->filter()
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->values()
            ->all();

        $matchingOrgIds = Organization::query()
            ->where('name', 'like', "%{$search}%")
            ->pluck('id')
            ->all();

        if (!empty($matchingOrgIds)) {
            $codesForOrgs = Hospital::query()
                ->whereIn('organization_id', $matchingOrgIds)
                ->pluck('code')
                ->filter()
                ->map(fn ($c) => strtoupper(trim((string) $c)))
                ->values()
                ->all();
            $matchingHospitalCodes = array_values(array_unique(array_merge($matchingHospitalCodes, $codesForOrgs)));
        }

        $query->where(function (Builder $builder) use ($search, $matchingHospitalCodes) {
            $builder->where('transaction_id', 'like', "%{$search}%")
                ->orWhere('mrn_id', 'like', "%{$search}%")
                ->orWhere('doctor_name', 'like', "%{$search}%")
                ->orWhere('lab_test_name', 'like', "%{$search}%")
                ->orWhere('raw_payload->patient_name', 'like', "%{$search}%")
                ->orWhereHas('person', function (Builder $pq) use ($search) {
                    $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) like ?", ["%{$search}%"])
                        ->orWhereHas('hipUser', function (Builder $hu) use ($search) {
                            $hu->where('hip_id', 'like', "%{$search}%");
                        });
                });

            if (!empty($matchingHospitalCodes)) {
                $builder->orWhereIn(DB::raw('UPPER(hospital_code)'), $matchingHospitalCodes);
            }
        });
    }

    /**
     * @return array{
     *     hospitals: Collection<int, Hospital>,
     *     hospitalsByCode: Collection<string, Hospital>,
     *     organizations: Collection<int, Organization>,
     *     integrations: Collection<int, HmsIntegration>,
     *     defaultIntegrationHospital: ?Hospital
     * }
     */
    public static function resolveHmsContext(): array
    {
        $hospitals = Hospital::query()->get(['id', 'name', 'code', 'organization_id'])->keyBy('id');
        $organizations = Organization::query()->get(['id', 'name'])->keyBy('id');
        $integrations = HmsIntegration::query()->get(['id', 'name', 'hospital_ids', 'organization_id']);

        $hospitalsByCode = $hospitals
            ->filter(fn (Hospital $h) => !empty($h->code))
            ->keyBy(fn (Hospital $h) => strtoupper(trim((string) $h->code)));

        $defaultIntegrationHospital = null;
        foreach ($integrations as $integ) {
            $ids = array_values(array_filter(array_map('intval', $integ->hospital_ids ?? [])));
            if (count($ids) === 1 && $hospitals->has($ids[0])) {
                $defaultIntegrationHospital = $hospitals->get($ids[0]);
                break;
            }
        }

        return [
            'hospitals' => $hospitals,
            'hospitalsByCode' => $hospitalsByCode,
            'organizations' => $organizations,
            'integrations' => $integrations,
            'defaultIntegrationHospital' => $defaultIntegrationHospital,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function mapHmisTransactionToRow(HmisTransaction $tx, array $context): array
    {
        $hospitals = $context['hospitals'];
        $hospitalsByCode = $context['hospitalsByCode'];
        $organizations = $context['organizations'];
        $defaultHospital = $context['defaultIntegrationHospital'];

        $hospital = null;
        if (!empty($tx->hospital_code)) {
            $codeKey = strtoupper(trim((string) $tx->hospital_code));
            $hospital = $hospitalsByCode->get($codeKey);
        }

        if (!$hospital) {
            $hospital = $defaultHospital;
        }

        if (!$hospital && $tx->person?->hospital_id) {
            $hospital = $hospitals->get((int) $tx->person->hospital_id);
        }

        $organization = $hospital?->organization_id ? $organizations->get((int) $hospital->organization_id) : null;

        $person = $tx->person;
        $personName = $person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : '';

        if ($personName !== '') {
            $memberName = $personName;
            $memberId = $person->hipUser?->hip_id ?: ($tx->mrn_id ?: '-');
            $memberMobile = $person->mobile ?: '-';
            $memberImage = $person->image ? asset('storage/users/' . $person->image) : null;
        } else {
            $rawPatientName = trim((string) ($tx->raw_payload['patient_name'] ?? ''));
            if ($rawPatientName !== '') {
                $memberName = $rawPatientName;
            } else {
                $memberName = $tx->mrn_id ?: '-';
            }
            $memberId = $tx->mrn_id ?: '-';
            $memberMobile = '-';
            $memberImage = null;
        }

        $initials = collect(preg_split('/\s+/', $memberName))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
        $initials = $initials !== '' ? $initials : '?';

        $serviceLabel = self::hmsServiceTypeLabel($tx->payment_type);

        $method = $tx->raw_payload['payment_method']
            ?? $tx->other_data['payment_method']
            ?? null;
        $paymentMethod = ($method !== null && trim((string) $method) !== '') ? (string) $method : '-';

        $tz = config('app.timezone') ?: 'Asia/Kolkata';
        $dateObj = $tx->paid_at ?? $tx->created_at;
        $dateFormatted = $dateObj ? \Illuminate\Support\Carbon::parse($dateObj)->setTimezone($tz)->format('d M, Y h:i A') : '-';

        return [
            'id' => $tx->id,
            'payment_id' => (string) $tx->transaction_id,
            'member_name' => $memberName,
            'member_id' => $memberId,
            'member_mobile' => $memberMobile,
            'member_image' => $memberImage,
            'initials' => $initials,
            'services' => [$serviceLabel],
            'service_summary' => $serviceLabel,
            'organization_name' => $organization?->name ?: '-',
            'hospital_name' => $hospital?->name ?: '-',
            'amount' => (float) $tx->paid_amount,
            'payment_method' => $paymentMethod,
            'status' => strtolower((string) ($tx->status ?? 'pending')),
            'status_label' => self::hmsStatusLabel($tx->status),
            'created_at' => $dateFormatted,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function mapHmisTransactionToExportRow(HmisTransaction $tx, array $context): array
    {
        $row = self::mapHmisTransactionToRow($tx, $context);
        $tz = config('app.timezone') ?: 'Asia/Kolkata';
        $dateObj = $tx->paid_at ?? $tx->created_at;

        return [
            'payment_id' => $row['payment_id'],
            'member_name' => $row['member_name'],
            'member_id' => $row['member_id'],
            'service_type' => $row['service_summary'],
            'organization_name' => $row['organization_name'],
            'hospital_name' => $row['hospital_name'],
            'amount' => number_format((float) $tx->paid_amount, 2, '.', ''),
            'payment_method' => $row['payment_method'],
            'status' => $row['status_label'],
            'date' => $dateObj ? \Illuminate\Support\Carbon::parse($dateObj)->setTimezone($tz)->format('Y-m-d H:i:s') : '',
        ];
    }
}
