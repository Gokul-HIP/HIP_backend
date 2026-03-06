<?php

namespace App\Http\Controllers;

use App\Services\Api\PaymentApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class InvoicePaymentController extends Controller
{
    private function validateSignedToken(int $invoiceId, string $token): void
    {
        parse_str(parse_url($token, PHP_URL_QUERY) ?? '', $signedQuery);
        $signedInvoiceId = (int) ($signedQuery['invoice_id'] ?? 0);
        abort_if($signedInvoiceId !== $invoiceId, 403, 'Invalid token for invoice.');

        $signedRequest = Request::create($token, 'GET');
        abort_unless(URL::hasValidSignature($signedRequest), 401, 'Unauthenticated.');
    }

    public function show(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filePath = public_path('payment/invoice-payment.html');
        abort_unless(file_exists($filePath), 404, 'Payment page not found.');

        return response()->file($filePath);
    }

    public function paymentRequest(Request $request, int $invoice_id, PaymentApiService $paymentApiService): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $this->validateSignedToken($invoice_id, $validated['token']);
        $data = $paymentApiService->getInvoicePaymentRequestData($invoice_id);

        return response()->json([
            'success' => true,
            'message' => 'Payment request data fetched successfully.',
            'data' => $data,
        ]);
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
        ]);
    }

    /**
     * Create Razorpay order and initiate checkout flow.
     * Does NOT complete payment - just creates the order.
     */
    public function pay(Request $request, int $invoice_id, PaymentApiService $paymentApiService): JsonResponse
    {
        $request->merge([
            'coins_applied' => $request->input('coins_applied', $request->input('coinsApplied')),
        ]);

        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'member_id' => ['required', 'string', 'exists:persons,id'],
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

            return response()->json([
                'success' => true,
                'message' => 'Razorpay order created successfully.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment order: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify Razorpay payment and complete transaction.
     * Called after user completes payment in Razorpay checkout.
     */
    public function verifyPayment(Request $request, int $invoice_id, PaymentApiService $paymentApiService): JsonResponse
    {
        $request->merge([
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

        abort_if((int) $validated['invoice_id'] !== $invoice_id, 422, 'Route invoice id mismatch.');
        
        $token = $validated['token'] ?? $request->query('token');
        if ($token) {
            $this->validateSignedToken($invoice_id, (string) $token);
        }

        try {
            $result = $paymentApiService->completeRazorpayPayment([
                'invoice_id' => $invoice_id,
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_order_id' => $validated['razorpay_order_id'],
                'razorpay_signature' => $validated['razorpay_signature'],
                'coins_applied' => (int) ($validated['coins_applied'] ?? 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and completed successfully.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage(),
            ], 400);
        }
    }
}
