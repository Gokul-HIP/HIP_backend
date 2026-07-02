{{-- In-patient billing toggle (review step) — include after pharmacy / prescription section --}}
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
    <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
            <i class="fas fa-bed-pulse" style="color: var(--button-color);"></i>
            In-Patient Billing
        </h3>
    </div>

    <div class="p-5 space-y-4" wire:key="in-patient-billing-option">
        <label class="flex items-start gap-3 cursor-pointer group">
            <input type="checkbox"
                   wire:model.boolean.live="isInPatient"
                   class="mt-0.5 h-4 w-4 rounded border-slate-300" style="color: var(--button-color); focus:ring: var(--button-hover);">
            <div>
                <p class="text-sm font-semibold text-slate-900 group-hover:text-sky-600 transition">
                    Mark this invoice as in-patient
                </p>
                <p class="text-xs text-slate-500 mt-1">
                    Check only if the member is currently admitted and staying in the hospital.
                </p>
            </div>
        </label>

        @if($isInPatient)
            <div class="rounded-lg border border-sky-200 bg-sky-50/70 p-4 space-y-2">
                <p class="text-sm font-semibold text-sky-900 flex items-center gap-2">
                    <i class="fas fa-circle-info text-sky-500"></i>
                    What happens when this is checked
                </p>
                <ul class="text-xs text-slate-700 space-y-1.5 list-none pl-0">
                    <li class="flex gap-2">
                        <i class="fas fa-check text-emerald-500 mt-0.5 flex-shrink-0"></i>
                        <span>Charges are linked to the patient’s <strong>in-patient stay</strong> (ward/bed billing), not walk-in out-patient billing.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="fas fa-check text-emerald-500 mt-0.5 flex-shrink-0"></i>
                        <span>The invoice is saved as <strong>pending</strong> and <strong>no immediate payment request</strong> is pushed to the member’s mobile app.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="fas fa-check text-emerald-500 mt-0.5 flex-shrink-0"></i>
                        <span>Prescription medicines can be fulfilled for ward delivery; payment is usually collected at <strong>discharge or final settlement</strong>.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="fas fa-check text-emerald-500 mt-0.5 flex-shrink-0"></i>
                        <span>Staff can send a payment reminder or collect payment later from the Payments list when the patient is ready to pay.</span>
                    </li>
                </ul>
            </div>
        @else
            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                <strong class="text-slate-700">Default (unchecked):</strong>
                Out-patient billing — the member receives a payment request in the app and can pay this invoice immediately after it is created.
            </div>
        @endif
    </div>
</div>
