<?php

namespace App\Http\Controllers\DesktopApi;

use App\Http\Controllers\Controller;
use App\Models\Coins;
use App\Models\HIPCard;
use App\Models\HIPUser;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Persons;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DesktopController extends Controller
{
    
    public function assignHIPCard(Request $request){
        $validated = $request->validate([
            'patient_id' => 'nullable|string',
            'hip_card_id' => 'required|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'gender' => 'nullable|string',
            'phone_number' => [
                'nullable',
                'string',
                Rule::unique('h_i_p_cards', 'phone_number')->ignore(
                    HIPCard::where('hip_card_id', $request->hip_card_id)->value('id')
                ),
            ],
        ]);

        try{
            $patientId = $validated['patient_id'] ?? null;
            $hipCardId = $validated['hip_card_id'];
            $firstName = $validated['first_name'] ?? null;
            $lastName = $validated['last_name'] ?? null;
            $phoneNumber = $validated['phone_number'] ?? null;
            $gender = $validated['gender'] ?? null;

            $hasAssignmentPayload = ! empty($firstName) && ! empty($phoneNumber);
            $existingCard = HIPCard::where('hip_card_id', $hipCardId)->first();

            if (! $hasAssignmentPayload) {
                if ($existingCard && $this->isCardAssigned($existingCard)) {
                    return response()->json([
                        'status' => 409,
                        'message' => 'Card is already assigned',
                        'data' => [
                            'hip_card_id' => $existingCard->hip_card_id,
                            'patient_id' => $existingCard->patient_id,
                            'first_name' => $existingCard->first_name,
                            'last_name' => $existingCard->last_name,
                            'phone_number' => $existingCard->phone_number,
                            'gender' => $existingCard->gender,
                        ],
                    ], 409);
                }

                if (! $existingCard) {
                    // $existingCard = HIPCard::create([
                    //     'hip_card_id' => $hipCardId,
                    // ]);

                    return response()->json([
                        'status' => 404,
                        'message' => 'New card number is not assigned yet',
                    ], 404);

                }

                return response()->json([
                    'status' => 200,
                    'message' => 'Card is not assigned',
                    'data' => [
                        'hip_card_id' => $existingCard->hip_card_id,
                        'patient_id' => $existingCard->patient_id,
                        'first_name' => $existingCard->first_name,
                        'last_name' => $existingCard->last_name,
                        'phone_number' => $existingCard->phone_number,
                        'gender' => $existingCard->gender,
                    ],
                ], 200);
            }

            if ($existingCard && $this->isCardAssigned($existingCard)) {
                return response()->json([
                    'status' => 409,
                    'message' => 'Card is already assigned',
                    'data' => [
                        'hip_card_id' => $existingCard->hip_card_id,
                        'patient_id' => $existingCard->patient_id,
                        'first_name' => $existingCard->first_name,
                        'last_name' => $existingCard->last_name,
                        'phone_number' => $existingCard->phone_number,
                        'gender' => $existingCard->gender,
                    ],
                ], 409);
            }

            $person = Persons::where('mobile', $phoneNumber)->first();
            $hipUser = HIPUser::where('mobile_num', $phoneNumber)->first();

            if (! $person) {
                if (!empty($patientId)) {
                    $person = new Persons();
                    $person->id = (string) $patientId;
                    $person->first_name = $firstName;
                    $person->last_name = $lastName;
                    $person->mobile = $phoneNumber;
                    $person->gender = $gender;
                    $person->hip_user_id = $hipUser?->id;
                    $person->is_primary = true;
                    $person->parent_id = (string) $patientId;
                    $person->save();
                } else {
                    $person = Persons::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'mobile' => $phoneNumber,
                        'gender' => $gender,
                        'hip_user_id' => $hipUser?->id,
                        'is_primary' => true,
                    ]);

                    $person->update([
                        'parent_id' => $person->id,
                    ]);
                }
            } elseif (! $person->hip_user_id && $hipUser) {
                $person->update([
                    'hip_user_id' => $hipUser->id,
                ]);
            }

            if ($gender !== null && $gender !== '') {
                $person->update([
                    'gender' => $gender,
                ]);
            }

            $resolvedPatientId = (string) $person->id;

            if($existingCard){
                $hipCard = $existingCard;
                $hipCard->update([
                    'patient_id' => $resolvedPatientId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone_number' => $phoneNumber,
                    'gender' => $gender,
                ]);
            }else{
                $hipCard = HIPCard::create([
                    'patient_id' => $resolvedPatientId,
                    'hip_card_id' => $hipCardId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone_number' => $phoneNumber,
                    'gender' => $gender,
                ]);
            }
            return response()->json([
                'status' => 200,
                'message' => 'HIP card assigned successfully',
                'data' => [
                    'hip_card_id' => $hipCard->hip_card_id,
                ],
            ], 200);

        }catch(\Throwable $e){
            Log::error('HIP card assignment failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }

    }

    private function isCardAssigned(HIPCard $hipCard): bool
    {
        return ! empty($hipCard->patient_id)
            || ! empty($hipCard->phone_number)
            || ! empty($hipCard->first_name)
            || ! empty($hipCard->gender);
    }

    public function nfcLogin(Request $request){

        $request->validate([
            'hip_card_id' => 'required|string',
        ]);

        try{
            $hipCard = HIPCard::where('hip_card_id', $request->hip_card_id)->first();

            if(!$hipCard){
                return response()->json([
                    'status' => 404,
                    'message' => 'HIP card not found',
                ], 404);
            }

            if(is_null($hipCard->phone_number) || is_null($hipCard->patient_id)){
                return response()->json([
                    'status' => 404,
                    'message' => 'HIP card is not assigned',
                ], 404);
            }

            $hipCard->update([
                'nfc_login_time' => now(),
            ]);
            
            return response()->json([
                'status' => 200,
                'message' => 'NFC login successful',
                'data' => [
                    'hip_card_id' => $hipCard->hip_card_id,
                ],
            ], 200);
        }catch(\Throwable $e){
            Log::error('NFC login failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }

    }
    
    public function updateHIPPoints(Request $request)
    {
        $request->validate([
            'hip_card_id' => 'nullable|string',
            'patient_id' => 'nullable|string',
            'points' => 'required|integer|min:1',
        ]);

        try {
            $patientId = $request->patient_id ?? null;
            $hipCardId = $request->hip_card_id ?? null;
            $points = (int) $request->points;

            if (! $hipCardId && ! $patientId) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Patient ID or HIP card ID is required',
                ], 400);
            }

            $hipCard = null;
            $person = null;

            if ($hipCardId) {
                $hipCard = HIPCard::where('hip_card_id', $hipCardId)->first();
                if (! $hipCard) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'HIP card not found',
                    ], 404);
                }

                if (! empty($hipCard->patient_id)) {
                    $person = Persons::where('id', $hipCard->patient_id)->first();
                }

                if (! $person) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Person not found for this HIP card',
                    ], 404);
                }
            } else {
                $person = Persons::where('id', $patientId)->first();

                if (! $person) {
                    // Keep backward compatibility: sometimes patient_id input is HIP user id.
                    $hipUserId = HIPUser::where('id', $patientId)->value('id');
                    if ($hipUserId) {
                        $person = Persons::where('hip_user_id', $hipUserId)->first();
                    }
                }

                if (! $person) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Person not found',
                    ], 404);
                }

                $hipCard = HIPCard::where('patient_id', $person->id)->first();
            }

            DB::transaction(function () use ($hipCard, $person, $points) {
                if ($hipCard) {
                    $hipCard->update([
                        'hip_points' => ((int) ($hipCard->hip_points ?? 0)) + $points,
                    ]);
                }

                $walletService = app(\App\Services\CoinsWalletService::class);
                $coins = Coins::where('person_id', $person->id)->first();

                if ($coins) {
                    $walletService->credit($coins, $points);

                    return;
                }

                $organizationId = $person->hipUser?->organization_id
                    ?? Organization::query()->orderBy('id', 'asc')->value('id');

                Coins::create([
                    'person_id' => $person->id,
                    'organization_id' => $organizationId ? (int) $organizationId : null,
                    'coins' => $points,
                ]);
            });

            return response()->json([
                'status' => 200,
                'message' => 'HIP points updated successfully',
                'data' => [
                    'hip_card_id' => $hipCard?->hip_card_id,
                    'patient_id' => $person->id,
                    'points_added' => $points,
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('HIP points update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function showCoins(Request $request){

        $request->validate([
            'patient_id'=> 'nullable|string',
            'hip_card_id'=> 'nullable|string',
        ]);

        try{
            $patientId = $request->patient_id ?? null;
            $hipCardId = $request->hip_card_id ?? null;

            if($patientId){

                $person = Persons::where('id', $patientId)->first();

                if(!$person){

                   $HIPUserId = HIPUser::where('id', $patientId)->value('id');

                   $person = Persons::where('hip_user_id', $HIPUserId)->first();

                   if(!$person){
                    return response()->json([
                        'status' => 404,
                        'message' => 'Person not found',
                    ], 404);
                   }

                   $coins = Coins::where('person_id', $person->id)->first();

                   if(!$coins){
                    return response()->json([
                        'status' => 404,
                        'message' => 'Coins not found',
                    ], 404);
                   }

                   return response()->json([
                        'status' => 200,
                        'message' => 'Coins fetched successfully',
                        'data' => [
                            'patient_name' => $person->first_name . ' ' . $person->last_name,
                            'phone_number' => $person->mobile,
                            'patient_id' => $request->patient_id,
                            'coins' => $coins->coins,
                        ],
                    ], 200);

                }else{
                    $coins = Coins::where('person_id', $person->id)->first();

                    if(!$coins){
                        return response()->json([
                            'status' => 404,
                            'message' => 'Coins not found',
                        ], 404);
                    }

                    return response()->json([
                        'status' => 200,
                        'message' => 'Coins fetched successfully',
                        'data' => [
                            'patient_name' => $person->first_name . ' ' . $person->last_name,
                            'phone_number' => $person->mobile,
                            'patient_id' => $request->patient_id,
                            'coins' => $coins->coins,
                        ],
                    ], 200);
                }

            }elseif($hipCardId){

                $hipCard = HIPCard::where('hip_card_id', $hipCardId)->first();

                if(!$hipCard){
                    return response()->json([
                        'status' => 404,
                        'message' => 'HIP card not found',
                    ], 404);
                }

                $person = Persons::where('id', $hipCard->patient_id)->first();

                if(!$person){
                    return response()->json([
                        'status' => 404,
                        'message' => 'Person not found',
                    ], 404);
                }

                $coins = Coins::where('person_id', $person->id)->first();

                if(!$coins){
                    return response()->json([
                        'status' => 404,
                        'message' => 'Coins not found',
                    ], 404);
                }

                return response()->json([
                    'status' => 200,
                    'message' => 'Coins fetched successfully',
                    'data' => [
                        'patient_name' => $person->first_name . ' ' . $person->last_name,
                        'phone_number' => $person->mobile,
                        'patient_id' => $hipCard->hip_card_id,
                        'coins' => $coins->coins,
                    ],
                ], 200);

            }
        }catch(\Throwable $e){
            Log::error('Coins fetch failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }

    }

    public function transactionHistory(Request $request){

            $validated = $request->validate([
                'patient_id'=> 'nullable|string', // accepts person uuid / hip user id / hip card id
                'mobile_number' => 'nullable|string',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'sort_by' => ['nullable', Rule::in(['newest', 'oldest'])],
            ]);

            try{
            $identifier = trim((string) ($validated['patient_id'] ?? ''));

                $hipCard = HIPCard::where('hip_card_id', $identifier)->first();
                $person = null;

            if ($identifier !== '') {
                if ($hipCard) {
                    $person = Persons::where('id', $hipCard->patient_id)->first();
                } else {
                    $person = Persons::where('id', $identifier)->first();

                    if (! $person) {
                        $hipUserId = HIPUser::where('id', $identifier)->value('id');
                        if ($hipUserId) {
                            $person = Persons::where('hip_user_id', $hipUserId)->first();
                        }
                        }

                    if ($person) {
                        $hipCard = HIPCard::where('patient_id', $person->id)->first();
                    }
                    }
                if (! $person) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Person not found',
                    ], 404);
                }
                }

            if ($person) {
                $invoiceIds = Invoice::where('person_id', $person->id)
                    ->orWhere('primary_person_id', $person->id)
                    ->pluck('id');
            } else {
                $invoiceIds = Invoice::query()->pluck('id');
            }

                if ($invoiceIds->isEmpty()) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Transaction history fetched successfully',
                        'data' => [],
                    ], 200);
                }

            $query = Transactions::with('invoice')->whereIn('invoice_id', $invoiceIds);

            if (! empty($validated['mobile_number'])) {
                $mobile = trim((string) $validated['mobile_number']);

                if ($person) {
                    $personMobile = trim((string) ($person->mobile ?? ''));
                    if ($personMobile === '' || stripos($personMobile, $mobile) === false) {
                        return response()->json([
                            'status' => 200,
                            'message' => 'Transaction history fetched successfully',
                            'data' => [],
                        ], 200);
                    }
                } else {
                    $query->whereHas('invoice.person', function ($q) use ($mobile) {
                        $q->where('mobile', 'like', '%' . $mobile . '%');
                    });
                }
            }

                if (! empty($validated['from_date'])) {
                    $query->whereDate('created_at', '>=', $validated['from_date']);
                }
                if (! empty($validated['to_date'])) {
                    $query->whereDate('created_at', '<=', $validated['to_date']);
                }

                $sortBy = $validated['sort_by'] ?? 'newest';
                $query->orderBy('created_at', $sortBy === 'oldest' ? 'asc' : 'desc');

                $transactions = $query->get();

            $data = $transactions->map(function($transaction) use ($person){
                    $invoice = $transaction->invoice;
                $txnPerson = $person ?: ($invoice?->person ?: ($invoice?->primaryPerson));
                    $resolvedHipCardId = $txnPerson?->id
                        ? HIPCard::where('patient_id', $txnPerson->id)->value('hip_card_id')
                        : null;
                    $coinsEarned = (int) ($invoice?->coins_earned ?? 0);
                    $coinsUsed = (int) ($invoice?->coins_applied ?? 0);

                    // UI row should represent a single action per transaction line.
                    if ($coinsUsed > 0) {
                        $txnLabel = 'Used';
                        $coinsDelta = -$coinsUsed;
                    } else {
                        $txnLabel = 'Credited';
                        $coinsDelta = $coinsEarned;
                    }

                    return [
                        // 'date_time' => optional($transaction->created_at)->format('M d, Y h:i A'),
                        'date' => optional($transaction->created_at)->format('M d, Y'),
                        'time' => optional($transaction->created_at)->format('h:i A'),
                        'patient_id' => $txnPerson?->id,
                        'card_id' => $resolvedHipCardId,
                        'mobile_number' => $txnPerson?->mobile,
                        'invoice_id' => 'INV-' . str_pad((string) $transaction->invoice_id, 4, '0', STR_PAD_LEFT),
                        // 'transaction' => sprintf('%+d Coins', $coinsDelta),
                        'transaction_type' => $txnLabel,
                        'coins_credited' => sprintf('%+d Coins', $coinsEarned),
                        'coins_used' => sprintf('%+d Coins', -$coinsUsed),
                        // 'coins_delta' => $coinsDelta,
                        'status' => $transaction->status,
                        'payment_method' => $transaction->payment_method,
                    ];
                })->values();

                return response()->json([
                    'status' => 200,
                    'message' => 'Transaction history fetched successfully',
                    'data' => $data,
                ], 200);

            }catch(\Throwable $e){
                Log::error('Transaction history fetch failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json([
                    'status' => 500,
                    'message' => 'Something went wrong',
                ], 500);
            }

        }

    public function useCoins(Request $request){

            $request->validate([
                'patient_id'=> 'required|string',
                'coins'=> 'required|integer|min:1',
            ]);

            try{
                $identifier = trim((string) $request->patient_id);
                $coinsToUse = (int) $request->coins;

                $hipCard = HIPCard::where('hip_card_id', $identifier)->first();
                $person = null;

                if ($hipCard) {
                    $person = Persons::where('id', $hipCard->patient_id)->first();
                } else {
                    $person = Persons::where('id', $identifier)->first();

                    if (! $person) {
                        $hipUserId = HIPUser::where('id', $identifier)->value('id');
                        if ($hipUserId) {
                            $person = Persons::where('hip_user_id', $hipUserId)->first();
                        }
                    }

                    if ($person) {
                        $hipCard = HIPCard::where('patient_id', $person->id)->first();
                    }
                }

                if (! $person) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Person not found',
                    ], 404);
                }

                if (! $hipCard) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'HIP card not found',
                    ], 404);
                }

                $coins = Coins::where('person_id', $person->id)->first();
                if (! $coins) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Coins not found',
                    ], 404);
                }

                $availableCoins = (int) ($coins->coins ?? 0);
                $availableHipPoints = (int) ($hipCard->hip_points ?? 0);

                if ($availableCoins < $coinsToUse || $availableHipPoints < $coinsToUse) {
                    return response()->json([
                        'status' => 422,
                        'message' => 'Insufficient balance',
                        'data' => [
                            'available_coins' => $availableCoins,
                            'available_hip_points' => $availableHipPoints,
                            'requested' => $coinsToUse,
                        ],
                    ], 422);
                }

                DB::transaction(function () use ($hipCard, $coins, $coinsToUse): void {
                    $hipCard->update([
                        'hip_points_used' => ((int) ($hipCard->hip_points_used ?? 0)) + $coinsToUse,
                        'hip_points' => ((int) ($hipCard->hip_points ?? 0)) - $coinsToUse,
                    ]);

                    $coins->update([
                        'coins' => ((int) ($coins->coins ?? 0)) - $coinsToUse,
                    ]);
                });

                return response()->json([
                    'status' => 200,
                    'message' => 'Coins used successfully',
                    'data' => [
                        'patient_name' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                        'phone_number' => $person->mobile,
                        'patient_id' => $person->id,
                        'hip_card_id' => $hipCard->hip_card_id,
                        'coins' => (int) ($coins->fresh()->coins ?? $coins->coins),
                    ],
                ], 200);

            }catch(\Throwable $e){
                Log::error('Coins use failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json([
                    'status' => 500,
                    'message' => 'Something went wrong',
                ], 500);
            }
        }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $hipUser = HIPUser::where('email', $request->email)->first();

            if (! $hipUser) {
                return response()->json([
                    'status' => 404,
                    'message' => 'HIP user not found',
                ], 404);
            }

            if (! $hipUser->hasRole('desk-admin', 'filament')) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access denied. Desk admin role required.',
                ], 403);
            }

            if (! $hipUser->password || ! Hash::check($request->password, $hipUser->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $plainTextToken = $hipUser->createToken(
                'desk_admin_token',
                ['*'],
                Carbon::now()->addMonths(6)
            )->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'Login successful',
                'token' => $plainTextToken,
                'user' => [
                    'id' => $hipUser->id,
                    'email' => $hipUser->email,
                    'first_name' => $hipUser->first_name,
                    'last_name' => $hipUser->last_name,
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Desktop login failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function nfcAssignedList(Request $request){

        try{
            $validated = $request->validate([
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'mobile_number' => 'nullable|string',
                'hip_card_id' => 'nullable|string',
                'sort_by' => 'nullable|string|in:newest,oldest,card_id_asc,card_id_desc,name_asc,name_desc',
            ]);

            $query = HIPCard::query()
                ->where(function ($builder) {
                    $builder->whereNotNull('patient_id')
                        ->orWhereNotNull('phone_number')
                        ->orWhereNotNull('first_name');
                });

            if (! empty($validated['from_date'])) {
                $query->whereDate('created_at', '>=', $validated['from_date']);
            }

            if (! empty($validated['to_date'])) {
                $query->whereDate('created_at', '<=', $validated['to_date']);
            }

            if (! empty($validated['mobile_number'])) {
                $query->where('phone_number', 'like', '%' . trim($validated['mobile_number']) . '%');
            }

            if (! empty($validated['hip_card_id'])) {
                $query->where('hip_card_id', 'like', '%' . trim($validated['hip_card_id']) . '%');
            }

            $sortBy = $validated['sort_by'] ?? 'newest';

            match ($sortBy) {
                'oldest' => $query->orderBy('created_at', 'asc'),
                'card_id_asc' => $query->orderBy('hip_card_id', 'asc'),
                'card_id_desc' => $query->orderBy('hip_card_id', 'desc'),
                'name_asc' => $query->orderBy('first_name', 'asc'),
                'name_desc' => $query->orderBy('first_name', 'desc'),
                default => $query->orderBy('created_at', 'desc'),
            };

            $nfcAssignedList = $query->paginate(10);

            if($nfcAssignedList->isEmpty()){
                return response()->json([
                    'status' => 404,
                    'message' => 'NFC assigned list not found',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'NFC assigned list fetched successfully',
                'data' => $nfcAssignedList->map(function($item){
                    return[
                        'hip_card_id' => $item->hip_card_id,
                        'patient_id' => $item->patient_id,
                        'phone_number' => $item->phone_number,
                        'name' => $item->first_name . ' ' . $item->last_name,
                        'gender' => $item->gender,
                        'assigned_at' => Carbon::parse($item->created_at)->format('M d,Y'),
                    ];
                }),
                'current_page' => $nfcAssignedList->currentPage(),
                'total_pages' => $nfcAssignedList->lastPage(),
                'total_items' => $nfcAssignedList->total(),
                'per_page' => $nfcAssignedList->perPage(),
            ], 200);

        }catch(\Throwable $e){
            Log::error('NFC assigned list fetch failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function nfcLoginHistory(Request $request){

        try{

            $validated = $request->validate([

                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'patient_id' => 'nullable|string',
                'mobile_number' => 'nullable|string',
                'sort_by' => 'nullable|string|in:newest,oldest,number_asc,number_desc',

            ]);

            $query = HIPCard::query()
                ->whereNotNull('nfc_login_time');

            if (! empty($validated['from_date'])) {
                $query->whereDate('nfc_login_time','>=', $validated['from_date']);
            }

            if(!empty($validated['to_date'])){
                $query->whereDate('nfc_login_time', '<=',$validated['to_date']);
            }

            if(!empty($validated['patient_id'])){
                $query->where('patient_id','like', '%'.trim($validated['patient_id']).'%');
            }

            if(!empty($validated['mobile_number'])){
                $query->where('phone_number','like', '%'.trim($validated['mobile_number']).'%');
            }

            $sortBy = $validated['sort_by'] ?? 'newest';

            match ($sortBy) {
                'oldest' => $query->orderBy('nfc_login_time', 'asc'),
                'number_asc' => $query->orderBy('phone_number', 'asc'),
                'number_desc' => $query->orderBy('phone_number', 'desc'),
                default => $query->orderBy('nfc_login_time', 'desc'),
            };

            $nfcLoginHistory = $query->paginate(10);

            if($nfcLoginHistory->isEmpty()){
                return response()->json([
                    'status' => 404,
                    'message' => 'NFC login history not found',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'NFC login history fetched successfully',
                'data' => $nfcLoginHistory->map(function($item){
                    return[
                        'patient_id' => $item->patient_id,
                        'mobile_number' => $item->phone_number,
                        'login_time' => Carbon::parse($item->nfc_login_time)->format('M d,Y h:i A')
                    ];
                }),
                'current_page' => $nfcLoginHistory->currentPage(),
                'total_pages' => $nfcLoginHistory->lastPage(),
                'total_items' => $nfcLoginHistory->total(),
                'per_page' => $nfcLoginHistory->perPage(),
            ], 200);

        }catch(\Throwable $e){
            Log::error('NFC login history fetch failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }

    }

}