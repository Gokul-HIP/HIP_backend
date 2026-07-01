@props(['booking'])

@php
    $paymentLabel = $booking->payment_mode_label;
    $paymentClass = match ($paymentLabel) {
        'Paid by online' => 'bg-blue-100 text-blue-700',
        'Pay by online' => 'bg-amber-100 text-amber-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<div class="flex flex-col gap-1 items-start">
    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $paymentClass }}">
        {{ $paymentLabel }}
    </span>

    @if($booking->is_online_payment)
        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $booking->online_payment_status_badge_class }}">
            {{ $booking->online_payment_status_label }}
        </span>
    @endif
</div>
