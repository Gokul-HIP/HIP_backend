<?php

namespace App\Livewire\CashierAdmin\Payments;

use App\Models\HIPUser;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

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

        $createdInfo = $invoice->createdInfo();

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
            'source_type'    => $createdInfo['type'],
            'source_label'   => $createdInfo['label'],
            'created_by'     => $createdInfo['creator'] ?? ($createdInfo['type'] === 'app' ? 'App Invoice' : '—'),
            'created_at'     => $createdAt,
        ];
    }

    protected function hospitalId(): ?string
    {
        $user = auth()->user();

        return $user instanceof HIPUser ? $user->hospital_id : null;
    }

    public function render()
    {
        $payments = Invoice::with(['creator', 'primaryPerson.hipUser', 'person.hipUser'])
            ->forHospital($this->hospitalId())
            ->latest()
            ->paginate(10)
            ->withPath(route('cashier.payments.index'))
            ->through(fn (Invoice $invoice) => $this->mapInvoiceToRow($invoice));

        return view('livewire.cashier-admin.payments.index', [
            'payments' => $payments,
        ]);
    }

    /**
     * Resend the payment request notification for a given invoice.
     *
     * This method is triggered by the "Resend Request" button in the
     * index table. It simply calls the API service helper which already
     * handles sending the FCM push message. The service will mark the
     * invoice as notified if the delivery succeeds.
     *
     * @param int $invoiceId
     */
    public function resendRequest(int $invoiceId): void
    {
        Log::info('Cashier resendRequest invoked', ['invoice_id' => $invoiceId]);

        $invoice = Invoice::forHospital($this->hospitalId())->find($invoiceId);
        if (! $invoice) {
            Log::warning('Cashier resendRequest invoice not found', ['invoice_id' => $invoiceId]);
            $this->dispatch('toast', type: 'error', message: 'Invoice not found.');
            return;
        }

        try {
            $service = app(\App\Services\Api\PaymentApiService::class);
            $sent = $service->sendInvoiceNotification($invoice, false);
            Log::info('Cashier resendRequest send result', [
                'invoice_id' => $invoiceId,
                'sent' => $sent,
            ]);

            if ($sent) {
                $this->dispatch('toast', type: 'success', message: 'Payment request notification resent.');
            } else {
                $this->dispatch('toast', type: 'warning', message: 'No active device found, notification not sent.');
            }
        } catch (\Throwable $e) {
            Log::error('Cashier resendRequest failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Failed to resend notification: ' . $e->getMessage());
        }
    }
}

