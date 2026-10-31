<div class="bg-white border rounded-xl p-5 space-y-4">
    <div>
        <h3 class="font-semibold text-gray-900">Payment discounts</h3>
        <p class="text-xs text-gray-500 mt-1">Percentage off each payment type, from 0 to 100. Use 0 when this plan does not discount that type.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach(\App\Enums\MembershipPaymentType::cases() as $paymentType)
            <div>
                <label class="block text-sm font-medium mb-1">{{ $paymentType->label() }}</label>
                <input type="number" min="0" max="100" step="0.01"
                    wire:model="payment_discounts.{{ $paymentType->value }}"
                    class="w-full border rounded-lg px-3 py-2">
                @error('payment_discounts.'.$paymentType->value)
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>
</div>
