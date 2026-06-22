<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">User Subscriptions</h2>
            <p class="text-sm text-gray-500 mt-1">View family package subscriptions and usage</p>
        </div>
        <a href="{{ route('healthcare.settings.membership-packages.index') }}"
            class="text-sm text-[#0da2e7] hover:underline">← Back to Packages</a>
    </div>

    <div class="bg-white border rounded-xl p-4 flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, email, package..."
            class="w-full sm:w-72 border rounded-lg px-3 py-2">
        <select wire:model.live="statusFilter" class="border rounded-lg px-3 py-2">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="expired">Expired</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white border rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold">User Name</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Package</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Start Date</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">End Date</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Consultations</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Lab Tests</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Amount Paid</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($subscriptions as $subscription)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">{{ trim(($subscription->member?->first_name ?? '').' '.($subscription->member?->last_name ?? '')) ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $subscription->familyPackage?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm">{{ optional($subscription->start_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-sm">{{ optional($subscription->end_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $subscription->status === 'active' ? 'bg-green-100 text-green-700' : ($subscription->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $subscription->consultations_used ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm">{{ $subscription->lab_tests_used ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm">₹{{ number_format((float) $subscription->amount_paid, 2) }}</td>
                            <td class="px-4 py-3">
                                <button type="button" wire:click="viewDetails({{ $subscription->id }})"
                                    class="text-sm text-[#0da2e7] hover:underline">View Details</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-gray-500">No subscriptions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $subscriptions->links() }}</div>
        </div>

        <div class="bg-white border rounded-xl p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Usage Log Timeline</h3>
            @if($selectedSubscription)
                <div class="mb-4 text-sm text-gray-600">
                    <p><strong>User:</strong> {{ trim(($selectedSubscription->member?->first_name ?? '').' '.($selectedSubscription->member?->last_name ?? '')) }}</p>
                    <p><strong>Package:</strong> {{ $selectedSubscription->familyPackage?->name }}</p>
                </div>
                <div class="space-y-3 max-h-[600px] overflow-y-auto">
                    @forelse($usageLogs as $log)
                        <div class="border rounded-lg p-3">
                            <p class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $log->usage_type)) }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ optional($log->used_at)->format('d M Y, h:i A') }}</p>
                            @if($log->booking_type)
                                <p class="text-xs text-gray-500">{{ $log->booking_type }} #{{ $log->booking_id }}</p>
                            @endif
                            @if($log->notes)
                                <p class="text-xs text-gray-600 mt-1">{{ $log->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No usage logged yet.</p>
                    @endforelse
                </div>
                <button type="button" wire:click="closeDetails" class="mt-4 text-sm text-gray-500 hover:text-gray-700">Close panel</button>
            @else
                <p class="text-sm text-gray-500">Select a subscription to view usage details.</p>
            @endif
        </div>
    </div>
</div>
