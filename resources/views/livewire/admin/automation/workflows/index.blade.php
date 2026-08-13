@section('title', 'Workflows')
@section('breadcrumb', 'Dashboard / Automation / Workflows')

<div class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Workflows</h1>
            <p class="text-sm text-gray-500 mt-1">Hospital automation workflows (Builder Connect).</p>
        </div>
        <a href="{{ route('admin.automation.workflows.create') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-semibold"
           style="background: var(--button-color, #0da2e7)">
            <i class="fas fa-plus"></i>
            Create Workflow
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex flex-col lg:flex-row gap-3 mb-6">
            <div class="relative flex-1 max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search workflows…"
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <select wire:model.live="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                <option value="all">All statuses</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="archived">Archived</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 pr-4 font-semibold">Name</th>
                        <th class="py-3 pr-4 font-semibold">Trigger</th>
                        <th class="py-3 pr-4 font-semibold">Status</th>
                        <th class="py-3 pr-4 font-semibold">Updated</th>
                        <th class="py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workflows as $workflow)
                        <tr class="border-b border-gray-100 hover:bg-gray-50" wire:key="wf-{{ $workflow->id }}">
                            <td class="py-3 pr-4">
                                <div class="font-semibold text-gray-900">{{ $workflow->name }}</div>
                                <div class="text-xs text-gray-400">#{{ $workflow->id }}</div>
                            </td>
                            <td class="py-3 pr-4">{{ $workflow->trigger_type ?: '—' }}</td>
                            <td class="py-3 pr-4">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ ucfirst($workflow->status) }}
                                </span>
                            </td>
                            <td class="py-3 pr-4">{{ optional($workflow->updated_at)->format('d M Y H:i') }}</td>
                            <td class="py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="publish({{ $workflow->id }})"
                                            class="w-8 h-8 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-blue-50"
                                            title="Publish"><i class="fas fa-upload"></i></button>
                                    <a href="{{ route('admin.automation.workflows.view', $workflow->id) }}"
                                       class="w-8 h-8 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-blue-50"
                                       title="View"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('admin.automation.workflows.edit', $workflow->id) }}"
                                       class="w-8 h-8 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-blue-50"
                                       title="Edit"><i class="fas fa-pen"></i></a>
                                    <button type="button"
                                            wire:click="confirmDelete({{ $workflow->id }}, @js($workflow->name))"
                                            class="w-8 h-8 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-red-50 hover:text-red-600"
                                            title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-gray-500">No workflows found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $workflows->links() }}</div>
    </div>

    <flux:modal name="delete-workflow" class="md:w-96">
        <div class="space-y-4 p-1">
            <h2 class="text-lg font-bold text-gray-900">Delete Workflow?</h2>
            <p class="text-sm text-gray-600">
                @if ($confirmDeleteName)
                    <span class="font-semibold">{{ $confirmDeleteName }}</span> will be deleted.
                @endif
            </p>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeDelete">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteWorkflow">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
