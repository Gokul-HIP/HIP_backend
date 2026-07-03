<?php

namespace App\Livewire\TechnicianAdmin\Payments;

use App\Models\HIPUser;
use App\Models\Invoice;
use App\Support\TechnicianDiagnosticInvoiceHelper;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

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

        $serviceLabels = TechnicianDiagnosticInvoiceHelper::serviceLabels($invoice);
        $itemized = TechnicianDiagnosticInvoiceHelper::itemizedAmounts($invoice);
        $total = TechnicianDiagnosticInvoiceHelper::diagnosticAmount($invoice);

        if ($itemized === [] && $total > 0) {
            $itemized = [$total];
        }

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
        $payments = TechnicianDiagnosticInvoiceHelper::baseQuery($this->hospitalId())
            ->with(['creator', 'primaryPerson.hipUser', 'person.hipUser'])
            ->latest()
            ->paginate(10)
            ->withPath(route('technician.payments.index'))
            ->through(fn (Invoice $invoice) => $this->mapInvoiceToRow($invoice));

        return view('livewire.technician-admin.payments.index', [
            'payments' => $payments,
        ]);
    }

    public function resendRequest(int $invoiceId): void
    {
        Log::info('Technician resendRequest invoked', ['invoice_id' => $invoiceId]);

        $invoice = TechnicianDiagnosticInvoiceHelper::baseQuery($this->hospitalId())
            ->whereKey($invoiceId)
            ->first();

        if (! $invoice) {
            Log::warning('Technician resendRequest invoice not found', ['invoice_id' => $invoiceId]);
            $this->dispatch('toast', type: 'error', message: 'Invoice not found.');
            return;
        }

        try {
            $service = app(\App\Services\Api\PaymentApiService::class);
            $sent = $service->sendInvoiceNotification($invoice, false);
            Log::info('Technician resendRequest send result', [
                'invoice_id' => $invoiceId,
                'sent' => $sent,
            ]);

            if ($sent) {
                $this->dispatch('toast', type: 'success', message: 'Payment request notification resent.');
            } else {
                $this->dispatch('toast', type: 'warning', message: 'No active device found, notification not sent.');
            }
        } catch (\Throwable $e) {
            Log::error('Technician resendRequest failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Failed to resend notification: ' . $e->getMessage());
        }
    }
}
