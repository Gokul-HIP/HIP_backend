<div class="border border-dashed border-gray-300 rounded-xl p-4 space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900">Product Benefits</h3>
        <button type="button"
                wire:click="addBenefitRow"
                class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50">
            <i class="fa-solid fa-plus mr-1"></i> Add Benefit
        </button>
    </div>

    @foreach($benefits as $index => $benefit)
        @php
            $iconInputId = 'benefitIconInput'.$index;
            $storedIcon = $benefit['icon'] ?? '';
            $isFaIcon = filled($storedIcon) && str_contains($storedIcon, 'fa-');
            $isImageIcon = filled($storedIcon) && ! $isFaIcon;
            $hasUpload = ! empty($benefit['icon_upload']);
            $existingIconUrl = $isImageIcon ? \App\Services\CatalogProductService::benefitIconUrl($storedIcon) : null;
        @endphp

        <div class="bg-gray-50 rounded-xl p-4 space-y-3 border border-gray-200"
             wire:key="benefit-row-{{ $index }}-{{ $benefit['id'] ?? 'new' }}">

            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Benefit #{{ $index + 1 }}</span>
                @if(count($benefits) > 1)
                    <button type="button"
                            wire:click="removeBenefitRow({{ $index }})"
                            class="text-red-500 hover:text-red-700 text-sm inline-flex items-center gap-1">
                        <i class="fa-regular fa-trash-can"></i> Remove
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                <div class="md:col-span-5">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                    <input type="text"
                           wire:model="benefits.{{ $index }}.title"
                           class="glass-input w-full px-3 py-2 rounded-lg"
                           placeholder="Benefit title">
                    @error('benefits.'.$index.'.title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Display Order</label>
                    <input type="number"
                           min="0"
                           wire:model="benefits.{{ $index }}.display_order"
                           class="glass-input w-full px-3 py-2 rounded-lg">
                    @error('benefits.'.$index.'.display_order') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-5">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Icon Class (optional)</label>
                    <input type="text"
                           wire:model.live="benefits.{{ $index }}.icon"
                           class="glass-input w-full px-3 py-2 rounded-lg"
                           placeholder="fa-solid fa-leaf">
                    @error('benefits.'.$index.'.icon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    @if($isFaIcon)
                        <div class="mt-2 inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg">
                            <i class="{{ $storedIcon }} text-lg text-[#0da2e7]"></i>
                            <span class="text-xs text-gray-500">Class preview</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Icon image upload + preview --}}
            <div x-data="{
                    previewUrl: null,
                    showExisting: {{ ($isImageIcon && ! ($benefit['remove_icon'] ?? false)) ? 'true' : 'false' }},
                    existingUrl: @js($existingIconUrl),
                    handleFileChange(event) {
                        const file = event.target.files[0];
                        if (!file) return;
                        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = URL.createObjectURL(file);
                        this.showExisting = false;
                    },
                    clearPreview() {
                        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = null;
                        this.showExisting = false;
                        const input = document.getElementById('{{ $iconInputId }}');
                        if (input) input.value = '';
                        $wire.clearBenefitIcon({{ $index }});
                    },
                    init() {
                        this.$watch('$wire.benefits[{{ $index }}].icon_upload', (value) => {
                            if (value) {
                                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                                this.previewUrl = null;
                                this.showExisting = false;
                            }
                        });
                    }
                 }"
                 @reset-benefit-icons.window="
                    if (previewUrl) URL.revokeObjectURL(previewUrl);
                    previewUrl = null;
                    showExisting = false;
                 ">

                <label class="block text-xs font-medium text-gray-600 mb-2">Icon Image</label>

                <div class="flex flex-wrap items-start gap-4">
                    {{-- Existing stored icon (edit) --}}
                    <div x-show="showExisting && existingUrl && !previewUrl && !$wire.benefits[{{ $index }}].icon_upload"
                         class="benefit-icon-preview-wrap relative">
                        <img :src="existingUrl" alt="Benefit icon" class="benefit-icon-preview-img">
                        <button type="button"
                                @click.stop="clearPreview()"
                                class="absolute -top-2 -right-2 bg-red-600 text-white w-7 h-7 flex items-center justify-center rounded-full text-xs shadow hover:bg-red-700 transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- Livewire uploaded preview --}}
                    @if($hasUpload && is_object($benefit['icon_upload']))
                        <div class="benefit-icon-preview-wrap relative">
                            <img src="{{ $benefit['icon_upload']->temporaryUrl() }}"
                                 alt="Uploaded benefit icon"
                                 class="benefit-icon-preview-img">
                            <button type="button"
                                    wire:click="clearBenefitIcon({{ $index }})"
                                    class="absolute -top-2 -right-2 bg-red-600 text-white w-7 h-7 flex items-center justify-center rounded-full text-xs shadow hover:bg-red-700 transition">
                                <i class="fas fa-times"></i>
                            </button>
                            <div wire:loading wire:target="benefits.{{ $index }}.icon_upload"
                                 class="absolute inset-0 bg-black/40 rounded-lg flex items-center justify-center">
                                <i class="fas fa-spinner fa-spin text-white"></i>
                            </div>
                        </div>
                    @endif

                    {{-- Alpine instant preview --}}
                    <div x-show="previewUrl && !$wire.benefits[{{ $index }}].icon_upload"
                         x-cloak
                         class="benefit-icon-preview-wrap relative">
                        <img :src="previewUrl" alt="Icon preview" class="benefit-icon-preview-img">
                        <button type="button"
                                @click.stop="clearPreview()"
                                class="absolute -top-2 -right-2 bg-red-600 text-white w-7 h-7 flex items-center justify-center rounded-full text-xs shadow hover:bg-red-700 transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- Upload box --}}
                    <div @click="document.getElementById('{{ $iconInputId }}').click()"
                         class="benefit-icon-upload-box border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-[#0da2e7] hover:bg-blue-50/30 transition-colors bg-white"
                         x-show="!previewUrl && !showExisting && !$wire.benefits[{{ $index }}].icon_upload">
                        <i class="fas fa-image text-xl text-gray-400 mb-1"></i>
                        <span class="text-xs text-gray-600 font-medium">Upload icon</span>
                        <span class="text-[10px] text-gray-400 mt-0.5">PNG, JPG</span>
                        <input type="file"
                               id="{{ $iconInputId }}"
                               wire:model="benefits.{{ $index }}.icon_upload"
                               class="hidden"
                               accept="image/*"
                               @change="handleFileChange($event)">
                    </div>
                </div>

                @error('benefits.'.$index.'.icon_upload') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
            </div>
        </div>
    @endforeach
</div>

<style>
    .benefit-icon-preview-wrap {
        width: 88px;
        height: 88px;
        flex-shrink: 0;
    }
    .benefit-icon-preview-img {
        width: 88px;
        height: 88px;
        object-fit: contain;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #fff;
        padding: 8px;
        display: block;
    }
    .benefit-icon-upload-box {
        width: 88px;
        height: 88px;
        flex-shrink: 0;
    }
</style>
