@section('title', 'Executions')
@section('breadcrumb', 'Dashboard / Automation / Executions')

<div class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900">Workflow Executions</h1>
        <p class="text-sm text-gray-500 mt-1">Runtime history from the workflow engine.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex flex-col lg:flex-row gap-3 mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by id or workflow…"
                   class="w-full max-w-md border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            <select wire:model.live="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                <option value="all">All statuses</option>
                <option value="running">Running</option>
                <option value="waiting">Waiting</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 pr-4 font-semibold">ID</th>
                        <th class="py-3 pr-4 font-semibold">Workflow</th>
                        <th class="py-3 pr-4 font-semibold">Version</th>
                        <th class="py-3 pr-4 font-semibold">Status</th>
                        <th class="py-3 pr-4 font-semibold">Started</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($executions as $execution)
                        <tr class="border-b border-gray-100" wire:key="ex-{{ $execution->id }}">
                            <td class="py-3 pr-4">#{{ $execution->id }}</td>
                            <td class="py-3 pr-4">{{ $execution->workflow?->name ?: '—' }}</td>
                            <td class="py-3 pr-4">{{ $execution->version?->version_number ?? '—' }}</td>
                            <td class="py-3 pr-4">{{ $execution->status }}</td>
                            <td class="py-3 pr-4">{{ optional($execution->started_at ?? $execution->created_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-gray-500">No executions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $executions->links() }}</div>
    </div>
</div>
