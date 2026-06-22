<?php

namespace App\Support;

use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Transactions;
use Illuminate\Database\Eloquent\Builder;
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
}
