<div>
    <style>
        [x-cloak] { display: none !important; }

        .step-connector { height: 2px; flex: 1; margin: 0 8px; border-radius: 9999px; transition: background 0.3s; }

        .image-upload-zone {
            min-height: 160px;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            background: #f9fafb;
        }
        .image-upload-zone:hover {
            border-color: #0da2e7;
            background: #f0faff;
        }
        .image-upload-zone.drag-over {
            border-color: #0da2e7;
            background: #e0f4fd;
        }
        .preview-box {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        .preview-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .glass-input {
            background: white;
            border: 1px solid #e5e7eb;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .glass-input:focus {
            border-color: #0da2e7;
            box-shadow: 0 0 0 3px rgba(13,162,231,0.08);
            outline: none;
        }
    </style>

    <flux:modal name="add-disease-package" class="p-0 !max-w-2xl" wire:close="closeModal">
        <div x-data="{ modalReady: false }"
             x-init="
                $el.closest('dialog')?.addEventListener('click', (e) => {
                    if (e.target === e.currentTarget && modalReady) $wire.closeModal();
                });
             "
             @modal-show.window="
                if ($event.detail.name === 'add-disease-package') {
                    modalReady = false;
                    setTimeout(() => modalReady = true, 300);
                }
             ">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white rounded-t-xl">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Add Disease Package</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Complete all steps to create the package</p>
                </div>
                <button type="button" wire:click="closeModal"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            {{-- Stepper --}}
            <div class="px-6 pt-5 pb-4 bg-white border-b border-gray-100">
                <div class="flex items-center">
                    @foreach([
                        [1, 'Basic Info',    'fa-file-lines'],
                        [2, 'Lab Tests',     'fa-flask'],
                        [3, 'Pricing',       'fa-indian-rupee-sign'],
                    ] as [$s, $label, $icon])
                        <div class="flex items-center {{ $s < 3 ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-300
                                    {{ $step > $s
                                        ? 'bg-green-500 text-white shadow-sm'
                                        : ($step === $s
                                            ? 'text-white shadow-md'
                                            : 'bg-gray-100 text-gray-400') }}"
                                    style="{{ $step === $s ? 'background:#0da2e7;' : '' }}">
                                    @if($step > $s)
                                        <i class="fa-solid fa-check text-xs"></i>
                                    @else
                                        <i class="fa-solid fa-{{ $icon }} text-xs"></i>
                                    @endif
                                </div>
                                <span class="text-xs font-medium whitespace-nowrap
                                    {{ $step === $s ? 'text-[#0da2e7]' : ($step > $s ? 'text-green-500' : 'text-gray-400') }}">
                                    {{ $label }}
                                </span>
                            </div>
                            @if($s < 3)
                                <div class="step-connector mb-4 {{ $step > $s ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Step content --}}
            <div class="px-6 py-5 bg-gray-50 min-h-[360px]">
                <form wire:submit.prevent="addDiseasePackage">

                    @if($step === 1)
                        @include('livewire.admin.organization.diagnostic.disease-package.steps.basic-info')
                    @elseif($step === 2)
                        @include('livewire.admin.organization.diagnostic.package.steps.select-lab-tests')
                    @elseif($step === 3)
                        @include('livewire.admin.organization.diagnostic.package.steps.pricing-details')
                    @endif

                </form>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-6 py-4 bg-white border-t border-gray-100 rounded-b-xl">
                <button type="button" wire:click="closeModal"
                    class="text-sm text-gray-400 hover:text-red-500 transition font-medium">
                    Cancel
                </button>
                <div class="flex items-center gap-3">
                    @if($step > 1)
                        <button type="button" wire:click="back"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-arrow-left text-xs"></i> Back
                        </button>
                    @endif

                    @if($step < 3)
                        <button type="button" wire:click="next"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium text-white transition shadow-sm hover:opacity-90"
                            style="background:#0da2e7;">
                            Next <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    @else
                        <button type="button" wire:click="addDiseasePackage"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium text-white bg-green-500 hover:bg-green-600 transition shadow-sm">
                            <i class="fa-solid fa-check text-xs"></i> Save Package
                        </button>
                    @endif
                </div>
            </div>

        </div>
    </flux:modal>
</div>