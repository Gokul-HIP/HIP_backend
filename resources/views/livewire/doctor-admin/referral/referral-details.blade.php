<div class="flex-1 overflow-y-auto p-8">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-1.5 text-sm text-slate-400">
                <a href="{{ $this->listRoute }}" class="transition-colors hover:text-[#29ABE2]">{{ $canEdit ? 'Sent Referrals' : 'Received Referrals' }}</a>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-600">Referral Details</span>
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Referral Details</h1>
            <p class="mt-1 text-slate-500">Review the details of the medical referral for {{ $this->memberFullName }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button"
                onclick="window.print()"
                class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm text-slate-600 transition-colors"
                style="border:1px solid #d1d5db;"
                onmouseover="this.style.background='#f9fafb';" onmouseout="this.style.background='';">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Referral
            </button>
            @if($canEdit)
                <a href="{{ route('doctor.referral.send.edit', $referral->id) }}"
                    class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm text-yellow-600 transition-colors"
                    style="border:1px solid #d1d5db;"
                    onmouseover="this.style.background='#f9fafb';" onmouseout="this.style.background='';">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            @endif
            @if($canUpdateStatus)
                <div x-data="{ open: false }" class="relative">
                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex items-center justify-center rounded-xl bg-[#0DA2E7] px-4 py-2 text-sm font-bold text-white shadow transition hover:bg-[#0b8ac5]"
                    >
                        {{ $this->statusLabel }}
                        <svg class="ms-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition
                        @click.outside="open = false"
                        class="absolute right-0 z-50 mt-2 w-40 rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                    >
                        <ul class="space-y-1 text-sm font-medium">
                            @foreach($this->statusOptions as $value => $label)
                                <li>
                                    <button
                                        type="button"
                                        wire:click="updateStatusInstant('{{ $value }}')"
                                        @click="open = false"
                                        class="w-full rounded-lg px-3 py-2 text-left transition hover:bg-slate-100 {{ $referral->status === $value ? 'bg-blue-50 text-[#0DA2E7]' : 'text-slate-800' }}"
                                    >
                                        {{ $label }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            <button type="button"
                wire:click="cancelReferral"
                class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm text-red-600 transition-colors"
                style="background:#fef2f2; border:1px solid #fee2e2;"
                onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='#fef2f2';">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Cancel Referral
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm" style="border:1px solid #f3f4f6;">
                <div class="flex items-center justify-between p-6" style="background:rgba(249,250,251,0.5); border-bottom:1px solid #f9fafb;">
                    <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color:#29ABE2;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Member Information
                    </h3>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold" style="background:#dbeafe; color:#1d4ed8;">Active Member</span>
                </div>
                <div class="flex flex-col gap-8 p-8 md:flex-row">
                    <div class="flex h-32 w-32 flex-shrink-0 items-center justify-center rounded-2xl" style="background:#f3f4f6;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="grid flex-1 grid-cols-1 gap-x-12 gap-y-6 md:grid-cols-2">
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-slate-400">Full Name</p>
                            <p class="text-lg font-bold text-slate-900">{{ $this->memberFullName }}</p>
                        </div>
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-slate-400">Member ID</p>
                            <p class="text-lg font-medium" style="color:#29ABE2;">{{ $this->memberHipId }}</p>
                        </div>
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-slate-400">Mobile Number</p>
                            <p class="font-medium text-slate-700">{{ $this->memberPhone }}</p>
                        </div>
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-slate-400">Gender / Age</p>
                            <p class="font-medium text-slate-700">{{ $this->memberGender }}{{ $this->memberAge === '-' ? '' : ', ' . $this->memberAge . ' Years' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white shadow-sm" style="border:1px solid #f3f4f6;">
                <div class="flex items-center gap-2 p-6" style="border-bottom:1px solid #f9fafb;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color:#29ABE2;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="font-bold text-slate-900">Referral Notes &amp; Observations</h3>
                </div>
                <div class="p-6">
                    <div class="rounded-lg border border-[#f3f4f6] bg-[#f9fafb] p-4 text-sm italic leading-relaxed text-slate-600">
                        "{{ $referral->medical_notes ?: 'No medical notes added.' }}"
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white shadow-sm" style="border:1px solid #f3f4f6;">
                <div class="p-6" style="border-bottom:1px solid #f9fafb;">
                    <h3 class="flex items-center gap-2 font-bold text-slate-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color:#29ABE2;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Referral Progress
                    </h3>
                </div>
                <div class="p-8">
                    <div class="relative">
                        <div class="absolute left-0 right-0 top-4 h-1 rounded" style="background:#e5e7eb;"></div>
                        <div class="absolute left-0 top-4 h-1 rounded transition-all duration-300" style="width:{{ $this->progressWidth }}; background:#29ABE2;"></div>

                        <div class="relative flex justify-between">
                            <div class="flex flex-col items-center text-center">
                                <div class="relative z-10 flex h-9 w-9 items-center justify-center rounded-full font-bold text-white" style="background:#29ABE2; border:4px solid white;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="mt-4">
                                    <p class="text-sm font-bold text-slate-800">Created</p>
                                    <p class="text-xs text-slate-400">{{ optional($referral->created_at)->format('M d, Y') ?? '-' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col items-center text-center">
                                <div class="relative z-10 flex h-9 w-9 items-center justify-center rounded-full font-bold text-sm {{ $this->progressStep >= 2 ? 'text-white' : 'text-slate-500' }}" style="background:{{ $this->progressStep >= 2 ? '#29ABE2' : '#e5e7eb' }}; border:4px solid white;">
                                    2
                                </div>
                                <div class="mt-4">
                                    <p class="text-sm font-bold {{ $this->progressStep >= 2 ? 'text-slate-800' : 'text-slate-400' }}">Confirmed</p>
                                    <p class="text-xs text-slate-400">{{ in_array(strtolower((string) $referral->status), ['accepted', 'progress'], true) ? 'In Progress' : 'Pending' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col items-center text-center">
                                <div class="relative z-10 flex h-9 w-9 items-center justify-center rounded-full font-bold text-sm {{ $this->progressStep >= 3 ? 'text-white' : 'text-slate-500' }}" style="background:{{ $this->progressStep >= 3 ? '#29ABE2' : '#e5e7eb' }}; border:4px solid white;">
                                    3
                                </div>
                                <div class="mt-4">
                                    <p class="text-sm font-bold {{ $this->progressStep >= 3 ? 'text-slate-800' : 'text-slate-400' }}">Completed</p>
                                    <p class="text-xs text-slate-400">{{ strtolower((string) $referral->status) === 'completed' ? 'Done' : 'Pending' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm" style="border:1px solid #f3f4f6;">
                <div class="space-y-6 p-6">
                    <div>
                        <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-slate-400">Referred By</p>
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full" style="background:#eff6ff;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" style="color:#29ABE2;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900">{{ $referral->referredByDoctor?->name ?? '-' }}</p>
                                <p class="text-xs font-medium" style="color:#29ABE2;">Doctor ID: #{{ $referral->referred_by_doctor_id ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6" style="border-top:1px solid #f9fafb;">
                        <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-slate-400">Target Hospital &amp; Doctor</p>
                        <div class="space-y-4">
                            <div class="flex gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ $referral->hospital?->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">Selected destination hospital</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ $referral->referredToDoctor?->name ?? '-' }}</p>
                                    <p class="text-xs italic text-slate-500">Specialist assigned</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm" style="border:1px solid #f3f4f6;">
                <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-slate-400">Key Dates</p>
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Referral Date</span>
                        <span class="font-semibold text-slate-800">{{ optional($referral->referral_date)->format('d-m-Y') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Created At</span>
                        <span class="font-semibold text-slate-800">{{ optional($referral->created_at)->format('d-m-Y') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Last Updated</span>
                        <span class="font-semibold text-slate-800">{{ optional($referral->updated_at)->diffForHumans() ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center rounded-xl p-6 text-center text-white shadow-md" style="background:linear-gradient(135deg, #29ABE2, #1a7fc4);">
                <div class="mb-4 rounded-lg bg-white p-2">
                    <div class="flex h-32 w-32 items-center justify-center" style="background:#f3f4f6;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm font-bold">HIP-REF-{{ str_pad((string) $referral->id, 5, '0', STR_PAD_LEFT) }}</p>
                <p class="mt-1 text-xs uppercase tracking-widest" style="opacity:0.8;">Scan for Digital Verification</p>
            </div>
        </div>
    </div>
</div>
