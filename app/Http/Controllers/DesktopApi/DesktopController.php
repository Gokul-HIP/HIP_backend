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
    
    public function updateHIPPoints(Request $request){

        $request->validate([
            'hip_card_id' => 'required|string',
            'points' => 'required|integer',
        ]);
        
        try{
            $hipCard = HIPCard::where('hip_card_id', $request->hip_card_id)->first();
            if (! $hipCard) {
                return response()->json([
                    'status' => 404,
                    'message' => 'HIP card not found',
                ], 404);
            }

            $hipCard->update([
                'hip_points' => $hipCard->hip_points + $request->points,
            ]);

            $person = Persons::where('id', $hipCard->patient_id)->first();
            if (! $person) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Person not found for this HIP card',
                ], 404);
            }
            
            $coins = Coins::where('person_id', $person->id)->first();
            
            if($coins){
                $coins->update([
                    'coins' => $coins->coins + $request->points,
                ]);
            }else{
                $organizationId = Organization::query()->orderBy('id')->value('id');
                $coins = Coins::create([
                    'person_id' => $person->id,
                    'organization_id' => $organizationId ? (int) $organizationId : null,
                    'coins' => $request->points,
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'HIP points updated successfully',
                'data' => [
                    'hip_card_id' => $hipCard->hip_card_id,
                ],
            ], 200);
        }catch(\Throwable $e){
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

                    $invoices = Invoice::where('person_id', $person->id)->orWhere('primary_person_id', $person->id)->get();

                    if(!$invoices){
                        return response()->json([
                            'status' => 404,
                            'message' => 'Invoices not found',
                        ], 404);
                    }

                    $transactions = Transactions::whereIn('invoice_id', $invoices->pluck('id'))->get();

                    if(!$transactions){
                        return response()->json([
                            'status' => 404,
                            'message' => 'Transactions not found',
                        ], 404);
                    }

                    $data = $transactions->map(function($transaction){
                        return [
                            'transaction_id' => $transaction->id,
                            'invoice_id' => $transaction->invoice_id,
                            'transaction_amount' => $transaction->transaction_amount,
                            'service_charges' => $transaction->service_charges,
                            'payment_gateway_charges' => $transaction->payment_gateway_charges,
                            'discount_amount' => $transaction->discount_amount,
                            'total_gst' => $transaction->total_gst,
                            'total_amount' => $transaction->total_amount,
                            'status' => $transaction->status,
                            'payment_method' => $transaction->payment_method,
                            'created_at' => $transaction->created_at,
                            'updated_at' => $transaction->updated_at,
                        ];
                    });

                    return response()->json([
                        'status' => 200,
                        'message' => 'Transaction history fetched successfully',
                        'data' => $data,
                    ], 200);

                }else{
                    $invoices = Invoice::where('person_id', $person->id)->orWhere('primary_person_id', $person->id)->get();

                    if(!$invoices){
                        return response()->json([
                            'status' => 404,
                            'message' => 'Invoices not found',
                        ], 404);
                    }

                    $transactions = Transactions::whereIn('invoice_id', $invoices->pluck('id'))->get();

                    if(!$transactions){
                        return response()->json([
                            'status' => 404,
                            'message' => 'Transactions not found',
                        ], 404);
                    }

                    $data = $transactions->map(function($transaction){
                        return [
                            'transaction_id' => $transaction->id,
                            'invoice_id' => $transaction->invoice_id,
                            'transaction_amount' => $transaction->transaction_amount,
                            'service_charges' => $transaction->service_charges,
                            'payment_gateway_charges' => $transaction->payment_gateway_charges,
                            'discount_amount' => $transaction->discount_amount,
                            'total_gst' => $transaction->total_gst,
                            'total_amount' => $transaction->total_amount,
                            'status' => $transaction->status,
                            'payment_method' => $transaction->payment_method,
                            'created_at' => $transaction->created_at,
                            'updated_at' => $transaction->updated_at,
                        ];
                    });

                    return response()->json([
                        'status' => 200,
                        'message' => 'Transaction history fetched successfully',
                        'data' => $data,
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

                $invoices = Invoice::where('person_id', $person->id)->orWhere('primary_person_id', $person->id)->get();

                if(!$invoices){
                    return response()->json([
                        'status' => 404,
                        'message' => 'Invoices not found',
                    ], 404);
                }

                $transactions = Transactions::whereIn('invoice_id', $invoices->pluck('id'))->get();

                if(!$transactions){
                    return response()->json([
                        'status' => 404,
                        'message' => 'Transactions not found',
                    ], 404);
                }

                $data = $transactions->map(function($transaction){
                    return [
                        'transaction_id' => $transaction->id,
                        'invoice_id' => $transaction->invoice_id,
                        'transaction_amount' => $transaction->transaction_amount,
                        'service_charges' => $transaction->service_charges,
                        'payment_gateway_charges' => $transaction->payment_gateway_charges,
                        'discount_amount' => $transaction->discount_amount,
                        'total_gst' => $transaction->total_gst,
                        'total_amount' => $transaction->total_amount,
                        'status' => $transaction->status,
                        'payment_method' => $transaction->payment_method,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ];
                });

                return response()->json([
                    'status' => 200,
                    'message' => 'Transaction history fetched successfully',
                    'data' => $data,
                ], 200);
            }

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
            'patient_id'=> 'nullable|string',
            'hip_card_id'=> 'nullable|string',
            'coins'=> 'required|integer',
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

                    $hipCard = HIPCard::where('patient_id', $person->id)->first();

                    if(!$hipCard){
                        return response()->json([
                            'status' => 404,
                            'message' => 'HIP card not found',
                        ], 404);
                    }

                    $hipCard->update([
                        'hip_points_used' => $request->coins,
                        'hip_points' => $hipCard->hip_points - $request->coins,
                    ]);

                    $coins = Coins::where('person_id', $person->id)->first();

                    if(!$coins){
                        return response()->json([
                            'status' => 404,
                            'message' => 'Coins not found',
                        ], 404);
                    }

                    $coins->update([
                        'coins' => $coins->coins - $request->coins,
                    ]);

                    return response()->json([
                        'status' => 200,
                        'message' => 'Coins used successfully',
                    ], 200);

                }else{

                    $hipCard = HIPCard::where('patient_id', $person->id)->first();

                    if(!$hipCard){
                        return response()->json([
                            'status' => 404,
                            'message' => 'HIP card not found',
                        ], 404);
                    }

                    $hipCard->update([
                        'hip_points_used' => $request->coins,
                        'hip_points' => $hipCard->hip_points - $request->coins,
                    ]);

                    $coins = Coins::where('person_id', $person->id)->first();
                    
                    if(!$coins){
                        return response()->json([
                            'status' => 404,
                            'message' => 'Coins not found',
                        ], 404);
                    }

                    $coins->update([
                        'coins' => $coins->coins - $request->coins,
                    ]);

                    return response()->json([
                        'status' => 200,
                        'message' => 'Coins used successfully',
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

                $hipCard->update([
                    'hip_points_used' => $request->coins,
                    'hip_points' => $hipCard->hip_points - $request->coins,
                ]);

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

                $coins->update([
                    'coins' => $coins->coins - $request->coins,
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'Coins used successfully',
                ], 200);

            }

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

}
