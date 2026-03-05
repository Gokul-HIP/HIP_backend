<div class="space-y-4">
    <h2 class="text-lg font-semibold">Manage Transactions</h2>

    <div class="overflow-x-auto rounded-lg border bg-white shadow-md">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-100 border-b">
                <tr class="text-left text-gray-700 font-semibold">
                    <th class="px-4 py-3">Transaction ID</th>
                    <th class="px-4 py-3">Invoice ID</th>
                    <th class="px-4 py-3">Service Type</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Payment Method</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">TRX{{ str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">INV{{ str_pad((string) $transaction->invoice_id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">
                            {{ collect($transaction->service_types ?? [])->map(fn ($type) => ucfirst((string) $type))->implode(', ') ?: '-' }}
                        </td>
                        <td class="px-4 py-3">Rs. {{ number_format((float) ($transaction->total_amount ?? 0), 2) }}</td>
                        <td class="px-4 py-3">{{ $transaction->payment_method ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($transaction->status ?? 'pending') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ optional($transaction->created_at)->format('d M Y, h:i A') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            No transactions found for this member.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
