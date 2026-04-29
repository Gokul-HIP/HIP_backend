<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coins;
use App\Models\HIPCard;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Transactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Api\PaymentApiService;

class TransactionsController extends Controller
{
    private function syncHipCardPointsFromWallet(Persons $walletOwner): void
    {
        $wallet = Coins::where('person_id', $walletOwner->id)->latest('id')->first();
        $coinsBalance = (int) ($wallet?->coins ?? 0);

        HIPCard::where('patient_id', (string) $walletOwner->id)
            ->update(['hip_points' => $coinsBalance]);
    }

    private function resolveWalletOwnerPersonFromUser(HIPUser $user): ?Persons
    {
        $self = Persons::where('hip_user_id', $user->id)->first();
        if (! $self) {
            return null;
        }

        if (!empty($self->parent_id)) {
            return Persons::find((string) $self->parent_id) ?: $self;
        }

        return $self;
    }

    private function resolveWalletOwnerPerson(Persons $person): Persons
    {
        if (!empty($person->parent_id)) {
            return Persons::find((string) $person->parent_id) ?: $person;
        }

        return $person;
    }

    /**
     * List family members (persons) linked to the logged-in user.
     */
    public function getFamilyMembers(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Primary person for this HIP user (self, typical case)
        $primaryPerson = Persons::where('hip_user_id', $user->id)
            ->where('is_primary', 1)
            ->first();

        if ($primaryPerson) {
            // When primary member is logged in:
            // start from all persons tied to this hip_user_id OR dependents by parent_id,
            // then explicitly exclude the primary person from the result.
            $persons = Persons::query()
                ->where('hip_user_id', $user->id)
                ->orWhere('parent_id', $primaryPerson->id)
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->get()
                ->reject(function (Persons $p) use ($primaryPerson) {
                    return (int) $p->id === (int) $primaryPerson->id;
                })
                ->values();
        } else {
            // When a dependent is logged in (no primary linked via hip_user_id):
            // find their person record and return ONLY the primary (parent) as "self"
            $self = Persons::where('hip_user_id', $user->id)->first();
            $primary = $self && $self->parent_id ? Persons::find($self->parent_id) : null;

            $persons = $primary ? collect([$primary]) : collect();
        }

        if ($persons->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No family members found.',
                'data'    => [],
            ], 200);
        }

        $data = $persons->map(function (Persons $person) {
            return [
                'id'          => (int) $person->id,
                'name'        => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                // 'is_primary'  => (bool) $person->is_primary,
                // 'relation'    => $person->is_primary ? 'Self' : 'Family member',
                'image'       => $person->image ? asset('storage/users/' . $person->image) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Family members fetched successfully.',
            'data'    => $data,
        ], 200);
    }

    public function getTransactions(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $walletOwner = $this->resolveWalletOwnerPersonFromUser($user);
        if ($walletOwner) {
            $this->syncHipCardPointsFromWallet($walletOwner);
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

        $walletOwner = $this->resolveWalletOwnerPersonFromUser($user);
        if ($walletOwner) {
            $this->syncHipCardPointsFromWallet($walletOwner);
        }
        
        $coinsBalance = 0;
        if ($walletOwner) {
            $wallet = Coins::where('person_id', $walletOwner->id)->latest('id')->first();
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

    /**
     * Coins history for a specific family member (person).
     */
    public function getMemberCoinsHistory(Request $request, int $personId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Primary person (self) for this HIP user
        $primaryPerson = Persons::where('hip_user_id', $user->id)
            ->where('is_primary', 1)
            ->first();

        // Ensure this person belongs to the logged-in user (self or dependent of primary)
        $personQuery = Persons::query()->where('id', $personId);

        if ($primaryPerson) {
            $personQuery->where(function ($q) use ($user, $primaryPerson) {
                $q->where('hip_user_id', $user->id)
                  ->orWhere('parent_id', $primaryPerson->id);
            });
        } else {
            $personQuery->where('hip_user_id', $user->id);
        }

        $person = $personQuery->first();

        if (! $person) {
            return response()->json([
                'success' => false,
                'message' => 'Family member not found.',
            ], 404);
        }

        // Shared family wallet balance (primary wallet owner + all dependents use same wallet).
        $walletOwner = $this->resolveWalletOwnerPerson($person);
        $this->syncHipCardPointsFromWallet($walletOwner);
        $wallet = Coins::where('person_id', $walletOwner->id)->latest('id')->first();
        $coinsBalance = (int) ($wallet?->coins ?? 0);

        // Invoices that belong to this specific family member (as primary or as person)
        $invoiceIds = Invoice::where('primary_person_id', $personId)
            ->orWhere('person_id', $personId)
            ->pluck('id')
            ->toArray();

        if (empty($invoiceIds)) {
            return response()->json([
                'success'       => true,
                'message'       => 'Coins history fetched successfully.',
                'coins_balance' => $coinsBalance,
                'current_page'  => 1, 'per_page' => 10,
                'count'         => 0, 'total' => 0, 'last_page' => 1,
                'data'          => [],
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

        if ($transactions->isEmpty()) {
            return response()->json([
                'success'       => false,
                'message'       => 'Coins history not found for this member.',
                'coins_balance' => $coinsBalance,
                'data'          => [],
            ], 404);
        }

        $data = $transactions->getCollection()->map(function ($transaction) use ($person) {
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
                // 'member_id'        => (int) $person->id,
                // 'member_name'      => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                'coins_earned'     => $coinsEarned,
                'coins_used'       => $coinsUsed,
                'coins_available'  => $coinsEarned - $coinsUsed,
            ];
        });

        return response()->json([
            'success'       => true,
            'message'       => 'Coins history fetched successfully.',
            'coins_balance' => $coinsBalance,
            'data'          => $data,
            'current_page'  => $transactions->currentPage(),
            'per_page'      => $transactions->perPage(),
            'count'         => $transactions->count(),
            'total'         => $transactions->total(),
            'last_page'     => $transactions->lastPage(),
        ]);
    }

    /**
     * Get full details for a single transaction (invoice + payment summary)
     * for the authenticated user.
     */
    public function getTransactionDetails(Request $request, int $transactionId, PaymentApiService $paymentApiService): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Person(s) linked to this HIP user
        $personIds = Persons::where('hip_user_id', $user->id)->pluck('id')->toArray();
        if (empty($personIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No members found for this user.',
            ], 404);
        }

        // Invoices that belong to those persons
        $invoiceIds = Invoice::whereIn('primary_person_id', $personIds)
            ->orWhereIn('person_id', $personIds)
            ->pluck('id')
            ->toArray();

        if (empty($invoiceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No invoices found for this user.',
            ], 404);
        }

        // Ensure the transaction belongs to one of the user's invoices
        $transaction = Transactions::with('invoice')
            ->where('id', $transactionId)
            ->whereIn('invoice_id', $invoiceIds)
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        $invoice = $transaction->invoice;
        if (! $invoice) {
            $invoice = Invoice::find($transaction->invoice_id);
        }

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found for this transaction.',
            ], 404);
        }

        // Reuse existing payment request mapping for the detailed invoice view
        $invoiceData = $paymentApiService->getInvoicePaymentRequestData((int) $invoice->id);

        // Return data in the same shape as PaymentApiService::getInvoicePaymentRequestData,
        // augmented with transaction-specific fields needed for history details.
        $data = array_merge($invoiceData, [
            'transaction_id'       => 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT),
            'transaction_status'   => (string) $transaction->status,
            'transaction_amount'   => (float) ($transaction->transaction_amount ?? 0),
            'transaction_discount' => (float) ($transaction->discount_amount ?? 0),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Transaction details fetched successfully.',
            'data'     => $data,
        ], 200, [], JSON_NUMERIC_CHECK);
    }


}