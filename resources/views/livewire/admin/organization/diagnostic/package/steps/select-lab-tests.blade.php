<div class="bg-white rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Select Lab Tests</h3>
    <p class="text-sm text-gray-600 mb-4">You can select up to 4 lab tests for this package.</p>

    @if(count($selected_lab_test_ids) > 0)
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-gray-700">
                <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                {{ count($selected_lab_test_ids) }} test(s) selected (Maximum: 4)
            </p>
        </div>
    @endif

    <div class="space-y-2 max-h-96 overflow-y-auto">
        @forelse($labTests as $labTest)
            <label class="flex items-start p-4 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 cursor-pointer transition-all duration-200"
                wire:key="lab-test-{{ $labTest->id }}">
                <input
                    type="checkbox"
                    wire:key="lab-test-checkbox-{{ $labTest->id }}"
                    wire:click="toggleLabTest({{ $labTest->id }})"
                    @checked(in_array($labTest->id, $selected_lab_test_ids))
                    @disabled(count($selected_lab_test_ids) >= 4 && !in_array($labTest->id, $selected_lab_test_ids))
                    class="w-5 h-5 text-blue-600 border-gray-300 rounded mt-0.5 {{ count($selected_lab_test_ids) >= 4 && !in_array($labTest->id, $selected_lab_test_ids) ? 'opacity-50 cursor-not-allowed' : '' }}"
                />
                <div class="ml-3 flex-1">
                    <p class="font-medium text-gray-900">{{ $labTest->test_name }}</p>
                    @if($labTest->test_category)
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-folder mr-1 text-black"></i>{{ $labTest->test_category }}
                        </p>
                    @endif
                    @if($labTest->test_price)
                        <p class="text-sm text-green-600 mt-1">
                            <i class="fas fa-rupee-sign mr-1 text-black"></i>₹{{ number_format($labTest->test_price, 2) }}
                        </p>
                    @endif
                </div>
            </label>
        @empty
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-flask text-4xl mb-3 text-gray-300"></i>
                <p class="text-lg font-medium">No lab tests found</p>
                <p class="text-sm">Please add lab tests first</p>
            </div>
        @endforelse
    </div>

    @error('selected_lab_test_ids')
        <p class="mt-4 text-sm text-red-600 flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            {{ $message }}
        </p>
    @enderror

    @if(count($selected_lab_test_ids) >= 4)
        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-info-circle mr-2"></i>
                Maximum limit reached. You have selected 4 lab tests.
            </p>
        </div>
    @endif
</div>

