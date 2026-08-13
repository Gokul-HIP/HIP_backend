@section('title', 'Workflow Templates')
@section('breadcrumb', 'Dashboard / Automation / Workflow Templates')

<div class="space-y-6"
     x-data
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <!-- HEADER -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-start gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <i data-lucide="workflow" class="w-5 h-5"></i>
            </span>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Workflow Templates</h1>
                <p class="text-sm text-gray-500 mt-1 max-w-xl">
                    Reusable React Flow blueprints. Templates are never executed — copy them into a workflow when ready.
                </p>
            </div>
        </div>

        <a href="{{ route('admin.automation.workflow-templates.create') }}"
           class="inline-flex items-center justify-center gap-2 self-start px-4 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm transition hover:shadow-md hover:brightness-105 active:scale-[0.98]"
           style="background: var(--button-color, #0da2e7)">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Create Template
        </a>
    </div>

    <!-- MAIN CARD -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">

        <!-- FILTER BAR -->
        <div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-6">
            <div class="relative flex-1 max-w-md">
                <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search templates…"
                       class="w-full h-11 pl-10 pr-4 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none" />
            </div>

            <div class="relative w-full sm:w-56 shrink-0">
                <select wire:model.live="moduleFilter"
                        class="w-full h-11 appearance-none border border-gray-200 rounded-lg pl-3.5 pr-9 text-sm font-medium text-gray-700 bg-white transition hover:border-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none">
                    <option value="all">All modules</option>
                    @foreach ($modules as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
            </div>

            <div class="relative w-full sm:w-48 shrink-0">
                <select wire:model.live="statusFilter"
                        class="w-full h-11 appearance-none border border-gray-200 rounded-lg pl-3.5 pr-9 text-sm font-medium text-gray-700 bg-white transition hover:border-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none">
                    <option value="all">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
                <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
            </div>
        </div>

        <!-- TABLE -->
        <div class="overflow-x-auto rounded-lg border border-gray-100">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr class="text-left text-xs font-bold uppercase tracking-wide text-gray-500 bg-gray-50 border-b border-gray-200">
                        <th class="py-3 px-4">Name</th>
                        <th class="py-3 px-4">Module</th>
                        <th class="py-3 px-4">Trigger</th>
                        <th class="py-3 px-4">Nodes</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Created</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($templates as $template)
                        @php
                            $nodeCount = is_array($template->definition['nodes'] ?? null)
                                ? count($template->definition['nodes'])
                                : ($template->node_count ?? 0);
                            $status = $template->status instanceof \BackedEnum
                                ? $template->status->value
                                : $template->status;

                            $statusStyles = match ($status) {
                                'active' => 'bg-emerald-50 text-emerald-700',
                                'draft' => 'bg-amber-50 text-amber-700',
                                'archived' => 'bg-gray-100 text-gray-500',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors" wire:key="tpl-{{ $template->id }}">
                            <td class="py-3.5 px-4 align-top">
                                <div class="font-semibold text-gray-900">{{ $template->name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">#{{ $template->id }} · {{ $template->slug }}</div>
                            </td>
                            <td class="py-3.5 px-4 align-top text-gray-600">{{ $template->module ?: '—' }}</td>
                            <td class="py-3.5 px-4 align-top text-gray-600">{{ $template->trigger_label ?: ($template->trigger_type ?: '—') }}</td>
                            <td class="py-3.5 px-4 align-top text-gray-600">{{ $nodeCount }}</td>
                            <td class="py-3.5 px-4 align-top">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusStyles }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 align-top text-gray-600">
                                {{ optional($template->created_at)->format('d M Y') ?: '—' }}
                            </td>
                            <td class="py-3.5 px-4 align-top">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.automation.workflow-templates.preview', $template->id) }}"
                                       class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                                       title="Preview"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                    <a href="{{ route('admin.automation.workflow-templates.edit', $template->id) }}"
                                       class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                                       title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                                    <button type="button"
                                            wire:click="duplicate({{ $template->id }})"
                                            class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-600"
                                            title="Duplicate"><i data-lucide="copy" class="w-4 h-4"></i></button>
                                    <button type="button"
                                            wire:click="confirmDelete({{ $template->id }}, @js($template->name))"
                                            class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                            title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-50 text-gray-300">
                                        <i data-lucide="workflow" class="w-6 h-6"></i>
                                    </span>
                                    <p class="text-gray-500">No workflow templates yet.</p>
                                    <a href="{{ route('admin.automation.workflow-templates.create') }}"
                                       class="inline-flex items-center gap-1.5 text-blue-600 font-semibold text-sm hover:text-blue-700">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        Create one
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $templates->links() }}
        </div>
    </div>

    <!-- DELETE MODAL -->
    <flux:modal name="delete-workflow-template" class="md:w-96" id="delete-org">
        <div class="space-y-4 p-1">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-500">
                <i data-lucide="triangle-alert" class="w-5 h-5"></i>
            </span>

            <h2 class="text-lg font-bold text-gray-900">Delete Template?</h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                This soft-deletes the blueprint. Existing workflows are not affected.
                @if ($confirmDeleteName)
                    <span class="font-semibold text-gray-900">{{ $confirmDeleteName }}</span> will be removed.
                @endif
            </p>
            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="closeDelete">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteTemplate" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow"><span style="color: white !important;">Delete</span></flux:button>
            </div>
        </div>
    </flux:modal>
</div> 