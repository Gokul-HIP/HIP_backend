<?php

namespace App\Livewire\CashierAdmin\Payments;

use App\Models\HIPUser;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Invoice;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    /**
     * Map a single invoice to a row array (for table and CSV).
     */
    protected function mapInvoiceToRow(Invoice $invoice): array
    {
        $primary = $invoice->primaryPerson;
        $person  = $invoice->person;

        $memberName = $primary
            ? trim(($primary->first_name ?? '') . ' ' . ($primary->last_name ?? ''))
            : ($person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : '-');

        $memberId = $primary
            ? '#' . str_pad((string) $primary->id, 6, '0', STR_PAD_LEFT)
            : ($person ? '#' . str_pad((string) $person->id, 6, '0', STR_PAD_LEFT) : '—');

        $personName = $person
            ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''))
            : $memberName;

        $avatarImg = $person && $person->image
            ? asset('storage/users/' . $person->image)
            : null;

        $serviceTypes = $invoice->service_types ?? [];
        $serviceLabels = collect($serviceTypes)->map(function ($type) {
            return match ($type) {
                'procedure' => 'Procedure',
                'labTest'   => 'Diagnostic',
                'package'   => 'Package',
                'pharmacy'  => 'Pharmacy',
                default     => ucfirst((string) $type),
            };
        })->values()->all();

        $details  = $invoice->invoice_details ?? [];
        $itemized = [];

        $sumItems = function ($items) {
            return collect($items)->sum(function ($it) {
                $amount   = (float) ($it['amount'] ?? 0);
                $discount = isset($it['discount_amount']) && $it['discount_amount'] !== ''
                    ? (float) $it['discount_amount']
                    : null;
                return $discount ?? $amount;
            });
        };

        if (!empty($details['procedures']) && is_array($details['procedures'])) {
            $itemized[] = $sumItems($details['procedures']);
        }
        if (!empty($details['labTest']) && is_array($details['labTest'])) {
            $itemized[] = $sumItems($details['labTest']);
        }
        if (!empty($details['package']) && is_array($details['package'])) {
            $itemized[] = $sumItems($details['package']);
        }
        if (!empty($details['pharmacy']) && is_array($details['pharmacy'])) {
            $ph = $details['pharmacy'];
            $phAmount = isset($ph['discount_amount']) && $ph['discount_amount'] !== ''
                ? (float) $ph['discount_amount']
                : (float) ($ph['amount'] ?? 0);
            $itemized[] = $phAmount;
        }

        $total = (float) ($invoice->total_amount ?? $invoice->amount ?? array_sum($itemized));

        $createdBy = '—';
        if ($invoice->created_by) {
            $creator = HIPUser::find($invoice->created_by);
            if ($creator) {
                $createdBy = trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''));
            }
        }

        $createdAt = $invoice->created_at
            ? $invoice->created_at->format('d M, h:i A')
            : '';

        return [
            'id'             => $invoice->id,
            'member_name'    => $memberName,
            'member_id'      => $memberId,
            'person_name'    => $personName,
            'avatar_color'   => 'av-blue',
            'avatar_img'     => $avatarImg,
            'services'       => $serviceLabels,
            'itemized'       => $itemized,
            'total'          => $total,
            'payment_method' => $invoice->payment_method ?? '',
            'status'         => $invoice->status ?? '',
            'coins'          => (int) ($invoice->coins_earned ?? 0),
            'created_by'     => $createdBy,
            'created_at'     => $createdAt,
        ];
    }

    public function render()
    {
        $payments = Invoice::with(['primaryPerson', 'person'])
            ->latest()
            ->paginate(10)
            ->withPath(route('cashier.payments.index'))
            ->through(fn (Invoice $invoice) => $this->mapInvoiceToRow($invoice));

        return view('livewire.cashier-admin.payments.index', [
            'payments' => $payments,
        ]);
    }
}
