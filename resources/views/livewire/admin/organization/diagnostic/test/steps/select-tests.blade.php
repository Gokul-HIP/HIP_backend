<div class="bg-white rounded-lg p-6 shadow-sm">
    
    <h3 class="text-lg font-semibold mb-4">Select tests to add to the system</h3>

    <!-- SEARCH BAR -->
    <div class="relative mb-6">
        <i class="fas fa-search absolute left-3 top-3 text-black"></i>
        <input 
            type="text" 
            wire:model.live.debounce.300ms="testSearch"
            placeholder="Search test name..." 
            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none"
        />
    </div>

    <!-- SELECT ALL CHECKBOX -->
    <div class="mb-4 pb-4 border-b border-gray-200">
        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-3 rounded-lg transition-colors">
            <input
                type="checkbox"
                wire:key="select-all-{{ $tests->currentPage() }}"
                wire:click="toggleSelectAll"
                @checked($this->isAllSelectedOnPage)
                class="w-5 h-5 text-blue-600 border-gray-300 rounded"
            />
            <span class="ml-3 text-sm font-semibold text-gray-700">Select all tests</span>
        </label>
    </div>

    <!-- TESTS LIST -->
    <div class="space-y-2 max-h-96 overflow-y-auto">
        @forelse($tests as $test)
                <label wire:key="test-{{ $test->id }}" class="flex items-start p-4 border border-gray-200 rounded-lg transition-all duration-200 hover:bg-blue-50 cursor-pointer
                {{ in_array($test->id, $selectedTests) ? 'bg-blue-50 border-blue-400' : '' }}">

                    <input
                        type="checkbox"
                        wire:key="test-checkbox-{{ $test->id }}"
                        wire:click="toggleTest({{ $test->id }})"
                        @checked(in_array($test->id, $selectedTests))
                        class="w-5 h-5 text-blue-600 rounded mt-1"
                    />
                    <div class="ml-3 flex-1">
                    <p class="font-medium text-gray-900">{{ $test->test_name }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ $test->test_description }}</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                        <span><i class="fas fa-hashtag mr-1 text-black"></i>{{ $test->test_code }}</span>
                        <span><i class="fas fa-tag mr-1 text-black"></i>{{ $test->test_category }}</span>
                        <span><i class="fas fa-toggle-on mr-1 text-black"></i>{{ ucfirst($test->test_status) }}</span>
                    </div>
                </div>
            </label>
        @empty
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
                <p class="text-lg font-medium">No tests found</p>
                <p class="text-sm">Try adjusting your search</p>
            </div>
        @endforelse
    </div>

    <!-- PAGINATION -->
    @if($tests->hasPages())
        <div class="mt-4 pt-4 border-t border-gray-200">
            {{ $tests->links() }}
        </div>
    @endif

    <!-- SELECTED TESTS SUMMARY -->
    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h4 class="font-semibold text-gray-900 mb-2">
            <i class="fas fa-check-circle text-blue-600 mr-2"></i>
            Selected Tests ({{ count($selectedTests) }})
        </h4>
        @if(count($selectedTests) > 0)
            <p class="text-sm text-gray-600">
                {{ count($selectedTests) }} test(s) selected and ready to add
            </p>
        @else
            <p class="text-sm text-gray-500">No tests selected</p>
        @endif
    </div>

    @error('selectedTests')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

</div>

