<div class="border border-dashed border-gray-300 rounded-xl p-4 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Common Questions (FAQ)</h3>
            <p class="text-xs text-gray-500 mt-0.5">Add questions and answers shown for this procedure.</p>
        </div>
        <button type="button"
                wire:click="addCommonQuestionRow"
                class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 whitespace-nowrap">
            <i class="fa-solid fa-plus mr-1"></i> Add Question
        </button>
    </div>

    @foreach($commonQuestions as $index => $faq)
        <div class="bg-gray-50 rounded-xl p-4 space-y-3 border border-gray-200"
             wire:key="faq-row-{{ $index }}-{{ md5(($faq['question'] ?? '').($faq['answer'] ?? '')) }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Question #{{ $index + 1 }}</span>
                @if(count($commonQuestions) > 1)
                    <button type="button"
                            wire:click="removeCommonQuestionRow({{ $index }})"
                            class="text-red-500 hover:text-red-700 text-sm inline-flex items-center gap-1">
                        <i class="fa-regular fa-trash-can"></i> Remove
                    </button>
                @endif
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Question</label>
                <input type="text"
                       wire:model="commonQuestions.{{ $index }}.question"
                       class="glass-input w-full px-3 py-2 rounded-lg"
                       placeholder="e.g. How long is the recovery period?">
                @error('commonQuestions.'.$index.'.question') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Answer</label>
                <textarea wire:model="commonQuestions.{{ $index }}.answer"
                          rows="3"
                          class="glass-input w-full px-3 py-2 rounded-lg resize-none"
                          placeholder="Provide a clear answer for patients..."></textarea>
                @error('commonQuestions.'.$index.'.answer') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
    @endforeach
</div>
