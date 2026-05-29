<?php

namespace App\Http\Controllers;

use App\Services\Api\PaymentApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class InvoicePaymentController extends Controller
{
    private function validateSignedToken(int $invoiceId, string $token): void
    {
        // Token is the full signed URL; decode once so %26 in query string becomes & for signature check
        $token = urldecode($token);
        parse_str(parse_url($token, PHP_URL_QUERY) ?? '', $signedQuery);
        $signedInvoiceId = (int) ($signedQuery['invoice_id'] ?? 0);
        abort_if($signedInvoiceId !== $invoiceId, 403, 'Invalid token for invoice.');

        $signedRequest = Request::create($token, 'GET');
        if (! URL::hasValidSignature($signedRequest)) {
            abort(401, 'Invalid or expired payment link. Use the link from your payment email or notification.');
        }
    }

    public function show(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filePath = public_path('payment/invoice-payment.html');
        abort_unless(file_exists($filePath), 404, 'Payment page not found.');

        return response()->file($filePath);
    }

    /**
     * Get payment request data for an invoice. Does NOT use Bearer token.
     * Pass the signed payment link URL as query param: ?token=https://api.../pay/invoice?expires=...&invoice_id=...&signature=...
     */
    public function paymentRequest(Request $request, int $invoice_id, PaymentApiService $paymentApiService): JsonResponse
    {
        $token = $request->query('token', $request->input('token'));
        $request->merge(['token' => $token]);

        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $this->validateSignedToken($invoice_id, $validated['token']);
        $data = $paymentApiService->getInvoicePaymentRequestData($invoice_id);

        return response()->json([
            'success' => true,
            'message' => 'Payment request data fetched successfully.',
            'data' => $data,
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    public function applyCoins(Request $request, int $invoice_id, PaymentApiService $paymentApiService): JsonResponse
    {
        $request->merge([
            'coins_applied' => $request->input('coins_applied', $request->input('coinsApplied')),
        ]);

        $validated = $request->validate([
            'token' => ['nullable', 'string'],
            'coins_applied' => ['required', 'integer', 'min:0'],
        ]);

        $token = $validated['token'] ?? $request->query('token');
        abort_if(empty($token), 422, 'token is required.');
        $this->validateSignedToken($invoice_id, (string) $token);

        $data = $paymentApiService->calculateCoinsApplication($invoice_id, (int) $validated['coins_applied']);

        return response()->json([
            'success' => true,
            'message' => 'Coins calculated successfully.',
            'data' => $data,
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Create Razorpay order and initiate checkout flow.
     * Does NOT complete payment - just creates the order.
     */
    public function pay(Request $request, int $invoice_id, PaymentApiService $paymentApiService): JsonResponse
    {
        // If JSON body wasn't parsed (e.g. wrong Content-Type), parse raw content so body is available
        $content = $request->getContent();
        if (! empty($content) && $request->input('member_id') === null && $request->input('invoice_id') === null) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $request->merge($decoded);
            }
        }

        // Accept snake_case, camelCase, and nested under "data"; invoice_id can come from URL
        $data = $request->input('data', []);
        $data = is_array($data) ? $data : [];
        $memberId = $request->input('member_id') ?? $request->input('memberId') ?? ($data['member_id'] ?? $data['memberId'] ?? null) ?? $request->query('member_id') ?? $request->query('memberId');
        $primaryPersonId = $request->input('primary_person_id') ?? $request->input('primaryPersonId') ?? ($data['primary_person_id'] ?? $data['primaryPersonId'] ?? null) ?? $request->query('primary_person_id') ?? $request->query('primaryPersonId');
        $request->merge([
            'invoice_id' => $request->input('invoice_id') ?? $request->input('invoiceId') ?? ($data['invoice_id'] ?? $data['invoiceId'] ?? null) ?? $invoice_id,
            'member_id' => $memberId,
            'primary_person_id' => $primaryPersonId,
            'coins_applied' => $request->input('coins_applied') ?? $request->input('coinsApplied') ?? ($data['coins_applied'] ?? $data['coinsApplied'] ?? null),
            'token' => $request->input('token') ?? $request->query('token'),
        ]);

        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            // member_id can be full hip_id (e.g. HIP00031), numeric part only (e.g. 31), or persons.id
            'member_id' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $value = trim((string) $value);
                    $existsInHip = DB::table('healthinpocket_users')->whereNotNull('hip_id')->where('hip_id', $value)->exists();
                    if ($existsInHip) {
                        return;
                    }
                    if (is_numeric($value)) {
                        $num = (int) $value;
                        if (DB::table('persons')->where('id', $num)->exists()) {
                            return;
                        }
                        $hipPadded = 'HIP' . str_pad($value, 5, '0', STR_PAD_LEFT);
                        if (DB::table('healthinpocket_users')->whereNotNull('hip_id')->where('hip_id', $hipPadded)->exists()) {
                            return;
                        }
                    }
                    $fail('The selected member id is invalid.');
                },
            ],
            'primary_person_id' => ['required', 'integer', 'exists:persons,id'],
            'coins_applied' => ['nullable', 'integer', 'min:0'],
            'token' => ['nullable', 'string'],
        ]);

        abort_if((int) $validated['invoice_id'] !== $invoice_id, 422, 'Route invoice id mismatch.');
        $token = $validated['token'] ?? $request->query('token');
        abort_if(empty($token), 422, 'token is required.');
        $this->validateSignedToken($invoice_id, (string) $token);

        try {
            $coinsApplied = (int) ($validated['coins_applied'] ?? 0);
            $result = $paymentApiService->createRazorpayOrder($invoice_id, $coinsApplied);

            // Ensure all numeric fields are int/float for Flutter (avoids "String is not a subtype of int of 'index'").
            $data = [
                'success' => (bool) ($result['success'] ?? true),
                'order_id' => (string) ($result['order_id'] ?? ''),
                'razorpay_key' => (string) ($result['razorpay_key'] ?? ''),
                'amount' => (int) ($result['amount'] ?? 0),
                'invoice_id' => (int) ($result['invoice_id'] ?? $invoice_id),
                'transaction_id' => (string) ($result['transaction_id'] ?? ''),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Razorpay order created successfully.',
                'data' => $data,
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment order: ' . $e->getMessage(),
            ], 400, [], JSON_NUMERIC_CHECK);
        }
    }

    /**
     * Verify Razorpay payment and complete transaction.
     * Called after user completes payment in Razorpay checkout.
     */
    public function verifyPayment(Request $request, PaymentApiService $paymentApiService): JsonResponse
    {
        $request->merge([
            'invoice_id' => $request->input('invoice_id', $request->input('invoiceId')),
            'coins_applied' => $request->input('coins_applied', $request->input('coinsApplied')),
        ]);

        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
            'coins_applied' => ['nullable', 'integer', 'min:0'],
            'token' => ['nullable', 'string'],
        ]);

        $invoiceId = (int) $validated['invoice_id'];

        $token = $validated['token'] ?? $request->query('token');
        if ($token) {
            $this->validateSignedToken($invoiceId, (string) $token);
        }

        try {
            $result = $paymentApiService->completeRazorpayPayment([
                'invoice_id' => $invoiceId,
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_order_id' => $validated['razorpay_order_id'],
                'razorpay_signature' => $validated['razorpay_signature'],
                'coins_applied' => (int) ($validated['coins_applied'] ?? 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and completed successfully.',
                'data' => $result,
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage(),
            ], 400, [], JSON_NUMERIC_CHECK);
        }
    }
}
