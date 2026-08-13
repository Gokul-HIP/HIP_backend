@section('title', 'Communication Logs')
@section('breadcrumb', 'Dashboard / Automation / Logs')

<div class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900">Communication Logs</h1>
        <p class="text-sm text-gray-500 mt-1">Email / SMS / WhatsApp / Push messages sent by workflows.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex flex-col lg:flex-row gap-3 mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search recipient or message…"
                   class="w-full max-w-md border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            <select wire:model.live="channelFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                <option value="all">All channels</option>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="push">Push</option>
            </select>
            <select wire:model.live="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                <option value="all">All statuses</option>
                <option value="queued">Queued</option>
                <option value="sent">Sent</option>
                <option value="delivered">Delivered</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 pr-4 font-semibold">ID</th>
                        <th class="py-3 pr-4 font-semibold">Channel</th>
                        <th class="py-3 pr-4 font-semibold">Recipient</th>
                        <th class="py-3 pr-4 font-semibold">Status</th>
                        <th class="py-3 pr-4 font-semibold">Message</th>
                        <th class="py-3 pr-4 font-semibold">Sent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-gray-100" wire:key="log-{{ $log->id }}">
                            <td class="py-3 pr-4">#{{ $log->id }}</td>
                            <td class="py-3 pr-4">{{ $log->channel }}</td>
                            <td class="py-3 pr-4">{{ $log->recipient ?: '—' }}</td>
                            <td class="py-3 pr-4">{{ $log->status }}</td>
                            <td class="py-3 pr-4 max-w-xs truncate" title="{{ $log->message }}">{{ \Illuminate\Support\Str::limit($log->message, 80) }}</td>
                            <td class="py-3 pr-4">{{ optional($log->sent_at ?? $log->created_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-gray-500">No communication logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
