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

    public function pay(Request $request, int $invoice_id, PaymentApiService $paymentApiService): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'member_id' => ['required', 'integer', 'exists:persons,id'],
            'primary_person_id' => ['required', 'integer', 'exists:persons,id'],
            'payment_method' => ['required', 'string', Rule::in(['upi', 'card', 'net_banking', 'wallet', 'cash', 'emi'])],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'original_amount' => ['required', 'numeric', 'min:0'],
            'coins_applied' => ['nullable', 'integer', 'min:0'],
            'coins_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'upi_id' => ['nullable', 'string', 'max:255'],
            'service_types' => ['nullable'],
            'status' => ['required', Rule::in(['completed', 'pending', 'failed', 'refunded', 'cancelled'])],
            'token' => ['nullable', 'string'],
        ]);

        abort_if((int) $validated['invoice_id'] !== $invoice_id, 422, 'Route invoice id mismatch.');
        $token = $validated['token'] ?? $request->query('token');
        abort_if(empty($token), 422, 'token is required.');
        $this->validateSignedToken($invoice_id, (string) $token);
        $validated['token'] = (string) $token;

        $result = $paymentApiService->completeInvoicePayment($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment completed successfully.',
            'data' => $result,
        ]);
    }
}
