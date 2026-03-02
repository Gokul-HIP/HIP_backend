{{-- ============================================================
   Step 3: Select Lab Tests & Packages
   Path: resources/views/livewire/cashier/payment/steps/lab.blade.php
   Uses: Font Awesome + Tailwind only (no Material Icons)
   ============================================================ --}}

   <div class="space-y-6">

    {{-- ══════════════════════════════
         SEARCH
    ══════════════════════════════ --}}
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <label class="block text-sm font-semibold text-slate-700 mb-3">
            Search Lab Tests &amp; Packages
        </label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-search text-slate-400 text-sm"></i>
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="labSearch"
                placeholder="Search by name, category or package..."
                class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder:text-slate-400 outline-none focus:ring-2 focus:ring-sky-400/30 focus:border-sky-400 transition-all"
            >
        </div>
    </div>


    {{-- ══════════════════════════════
         SELECTED LAB TESTS & PACKAGES
    ══════════════════════════════ --}}
    @if(count($selectedLabTests ?? []) > 0)
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-flask text-sky-500 text-base"></i>
                Selected Lab Tests &amp; Packages
            </h3>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full">
                {{ count($selectedLabTests) }} {{ count($selectedLabTests) === 1 ? 'Item' : 'Items' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            @foreach($selectedLabTests as $item)
            @php
                $name = $item->type === 'test' ? $item->model->test_name : $item->model->name;
                $price = $item->type === 'test' ? $item->model->test_price : $item->model->price;
                $id = $item->model->id;
            @endphp
            <div class="bg-white border border-sky-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-{{ $item->type === 'package' ? 'box-open' : 'vial' }}"></i>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 text-sm leading-tight">{{ $name }}</p>
                        <p class="text-sky-500 font-bold text-sm">₹{{ number_format((float) $price, 2) }}</p>
                    </div>
                </div>
                @if($item->type === 'test')
                <button type="button" wire:click="removeLabTest({{ $id }})" class="w-7 h-7 rounded-full flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 transition-all flex-shrink-0 ml-2">
                    <i class="fas fa-times text-xs"></i>
                </button>
                @else
                <button type="button" wire:click="removeLabPackage({{ $id }})" class="w-7 h-7 rounded-full flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 transition-all flex-shrink-0 ml-2">
                    <i class="fas fa-times text-xs"></i>
                </button>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════
         AVAILABLE OPTIONS
    ══════════════════════════════ --}}
    <div class="pb-10">

        {{-- Header + Toggle --}}
        <div class="flex items-center gap-5 mb-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 flex-shrink-0">
                <i class="fas fa-th-list text-sky-500 text-base"></i>
                Available Options
            </h3>

            <div class="flex bg-slate-100 rounded-lg p-0.5 gap-0.5">
                <button
                    wire:click="$set('labTab', 'tests')"
                    class="px-4 py-1.5 text-xs font-bold rounded-md transition-all
                        {{ ($labTab ?? 'tests') === 'tests'
                            ? 'bg-white text-sky-500 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700' }}"
                >
                    Tests
                </button>
                <button
                    wire:click="$set('labTab', 'packages')"
                    class="px-4 py-1.5 text-xs font-semibold rounded-md transition-all
                        {{ ($labTab ?? 'tests') === 'packages'
                            ? 'bg-white text-sky-500 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700' }}"
                >
                    Packages
                </button>
            </div>
        </div>

        {{-- Items list: Tests or Packages for this hospital's diagnostic center --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm divide-y divide-slate-100">
            @if(($labTab ?? 'tests') === 'tests')
                @forelse($availableLabTests ?? [] as $test)
                <div class="px-4 py-3.5 flex items-center justify-between hover:bg-slate-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-vial text-sm"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">{{ $test->test_name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $test->category->category_name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 flex-shrink-0">
                        <div class="text-right">
                            <span class="block text-sm font-bold text-slate-900">₹{{ number_format((float) $test->test_price, 2) }}</span>
                            @if($test->test_discount)
                                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide">Save {{ $test->test_discount }}%</span>
                            @endif
                        </div>
                        <button type="button" wire:click="addLabTest({{ $test->id }})" class="inline-flex items-center gap-1.5 text-sky-500 hover:bg-sky-50 border border-sky-200 hover:border-sky-400 px-3 py-1.5 rounded-lg font-bold text-xs transition-all whitespace-nowrap">
                            <i class="fas fa-plus text-[10px]"></i> Add
                        </button>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-slate-500">
                    @if(trim($labSearch ?? '') !== '')
                        <p class="text-sm">No lab tests match your search for this diagnostic center.</p>
                    @else
                        <p class="text-sm">No lab tests available for this diagnostic center.</p>
                    @endif
                </div>
                @endforelse
            @else
                @forelse($availableLabTests ?? [] as $pkg)
                <div class="px-4 py-3.5 flex items-center justify-between hover:bg-slate-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-box-open text-sm"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">{{ $pkg->name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $pkg->code ?? \Illuminate\Support\Str::limit($pkg->description ?? '', 40) ?: '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 flex-shrink-0">
                        <div class="text-right">
                            <span class="block text-sm font-bold text-slate-900">₹{{ number_format((float) $pkg->price, 2) }}</span>
                            @if($pkg->discount)
                                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide">Save {{ $pkg->discount }}%</span>
                            @endif
                        </div>
                        <button type="button" wire:click="addLabPackage({{ $pkg->id }})" class="inline-flex items-center gap-1.5 text-sky-500 hover:bg-sky-50 border border-sky-200 hover:border-sky-400 px-3 py-1.5 rounded-lg font-bold text-xs transition-all whitespace-nowrap">
                            <i class="fas fa-plus text-[10px]"></i> Add
                        </button>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-slate-500">
                    @if(trim($labSearch ?? '') !== '')
                        <p class="text-sm">No packages match your search for this diagnostic center.</p>
                    @else
                        <p class="text-sm">No packages available for this diagnostic center.</p>
                    @endif
                </div>
                @endforelse
            @endif
        </div>

        @if(isset($availableLabTests) && method_exists($availableLabTests, 'links'))
        <div class="mt-3 flex justify-end">
            {{ $availableLabTests->links() }}
        </div>
        @endif
    </div>

</div>