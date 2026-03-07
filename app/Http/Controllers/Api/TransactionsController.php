<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coins;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Transactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    public function getTransactions(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $personIds = Persons::where('hip_user_id', $user->id)->pluck('id')->toArray();

        if (empty($personIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Payment history fetched successfully.',
                'data' => []
            ]);
        }

        $invoiceIds = Invoice::whereIn('primary_person_id', $personIds)
            ->orWhereIn('person_id', $personIds)
            ->pluck('id')
            ->toArray();

        if (empty($invoiceIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Payment history fetched successfully.',
                'data' => []
            ]);
        }

        $transactions = Transactions::with('invoice')
            ->whereIn('invoice_id', $invoiceIds)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $data = $transactions->getCollection()->map(function ($transaction) {
            $invoice = $transaction->invoice;

            $hospital = null;
            if ($invoice && $invoice->created_by) {
                $creator = HIPUser::find($invoice->created_by);
                if ($creator && $creator->hospital_id) {
                    $hospital = Hospital::find($creator->hospital_id);
                }
            }

            return [
                'transaction_id'      => 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT),
                'hospital_name'       => $hospital?->name ?? 'Hospital',
                'hospital_logo'       => $hospital?->logo
                                            ? asset('storage/hospital/' . $hospital->logo)
                                            : null,
                'created_at'          => optional($transaction->created_at)->format('d M Y \a\t g:ia'),
                'status'              => (string) $transaction->status,
                'payment_method'      => (string) ($transaction->payment_method ?? ''),
                'total_amount'        => (float) ($transaction->transaction_amount ?? 0),
                'actual_amount'       => (float) ($transaction->total_amount ?? 0),
                'coins_applied'       => (int) ($invoice?->coins_applied ?? 0),
                'coin_discount_amount'=> (float) ($transaction->discount_amount ?? 0),
            ];
        });

        return response()->json([
            'success'      => true,
            'message'      => 'Payment history fetched successfully.',
            'data'         => $data,
            'current_page' => $transactions->currentPage(),
            'per_page'     => $transactions->perPage(),
            'count'        => $transactions->count(),
            'total'        => $transactions->total(),
            'last_page'    => $transactions->lastPage(),
        ]);
    }

    public function getCoinsHistory(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $personIds = Persons::where('hip_user_id', $user->id)->pluck('id')->toArray();

        if (empty($personIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Coins history fetched successfully.',
                'coins_balance' => 0,
                'current_page' => 1, 'per_page' => 10,
                'count' => 0, 'total' => 0, 'last_page' => 1,
                'data' => []
            ]);
        }

        $primaryPerson = Persons::where('hip_user_id', $user->id)
            ->where('is_primary', 1)
            ->first();
        
        $coinsBalance = 0;
        if ($primaryPerson) {
            $wallet = Coins::where('person_id', $primaryPerson->id)->latest('id')->first();
            $coinsBalance = (int) ($wallet?->coins ?? 0);
        }

        $invoiceIds = Invoice::whereIn('primary_person_id', $personIds)
            ->orWhereIn('person_id', $personIds)
            ->pluck('id')
            ->toArray();

        if (empty($invoiceIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Coins history fetched successfully.',
                'coins_balance' => $coinsBalance,
                'current_page' => 1, 'per_page' => 10,
                'count' => 0, 'total' => 0, 'last_page' => 1,
                'data' => []
            ]);
        }

        $transactions = Transactions::with('invoice')
            ->whereIn('invoice_id', $invoiceIds)
            ->where(function ($q) {
                $q->where('discount_amount', '>', 0)  // coins were used
                ->orWhereHas('invoice', function ($q2) {
                    $q2->where('coins_earned', '>', 0); // coins were earned
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $data = $transactions->getCollection()->map(function ($transaction) {
            $invoice = $transaction->invoice;

            $hospital = null;
            if ($invoice?->created_by) {
                $creator = HIPUser::find($invoice->created_by);
                if ($creator?->hospital_id) {
                    $hospital = Hospital::find($creator->hospital_id);
                }
            }

            $coinsEarned = (int) ($invoice?->coins_earned ?? 0);
            $coinsUsed   = 0;

            $amountForOneCoin = (float) env('AMOUNT_FOR_ONE_COIN', env('AMOUNT_For_ONE_COIN', 0.10));
            if ($transaction->discount_amount > 0 && $amountForOneCoin > 0) {
                $coinsUsed = (int) round($transaction->discount_amount / $amountForOneCoin);
            }

            return [
                'transaction_id'   => 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT),
                'hospital_name'    => $hospital?->name ?? 'Hospital',
                'hospital_logo'    => $hospital?->logo
                                        ? asset('storage/hospital/' . $hospital->logo)
                                        : null,
                'created_at'       => optional($transaction->created_at)->format('d M Y \a\t g:ia'),
                'coins_earned'     => $coinsEarned,   // coins added to wallet
                'coins_used'       => $coinsUsed,     // coins deducted from wallet
                // net = earned - used (positive = gained, negative = spent)
                'coins_available'        => $coinsEarned - $coinsUsed,
            ];
        });

        return response()->json([
            'success'      => true,
            'message'      => 'Coins history fetched successfully.',
            'coins_balance'=> $coinsBalance,
            'data'         => $data,
            'current_page' => $transactions->currentPage(),
            'per_page'     => $transactions->perPage(),
            'count'        => $transactions->count(),
            'total'        => $transactions->total(),
            'last_page'    => $transactions->lastPage(),
        ]);
    }

}