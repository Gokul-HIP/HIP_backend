<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Pharmacy\CancelPharmacyOrderRequest;
use App\Http\Requests\Api\Pharmacy\ListPharmaciesRequest;
use App\Http\Requests\Api\Pharmacy\ListPharmacyOrdersRequest;
use App\Http\Requests\Api\Pharmacy\StorePharmacyOrderCommentRequest;
use App\Http\Requests\Api\Pharmacy\StorePharmacyOrderRequest;
use App\Http\Resources\Pharmacy\PharmacyOrderCommentResource;
use App\Http\Resources\Pharmacy\PharmacyOrderListResource;
use App\Http\Resources\Pharmacy\PharmacyOrderResource;
use App\Http\Resources\Pharmacy\PharmacySummaryResource;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Pharmacy;
use App\Models\PharmacyOrder;
use App\Policies\PharmacyOrderPolicy;
use App\Services\Pharmacy\PharmacyOrderService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PharmacyOrderController extends Controller
{
    public function __construct(
        private readonly PharmacyOrderService $orders,
        private readonly PharmacyOrderPolicy $policy
    ) {}

    public function pharmacies(ListPharmaciesRequest $request): JsonResponse
    {
        $hospital = Hospital::query()->findOrFail($request->integer('hospital_id'));
        $ids = collect($hospital->pharmacy_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $rows = $ids->isEmpty()
            ? collect()
            : Pharmacy::query()
                ->whereIn('id', $ids->all())
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

        return $this->ok('Pharmacies fetched.', PharmacySummaryResource::collection($rows));
    }

    public function store(StorePharmacyOrderRequest $request): JsonResponse
    {
        $user = $this->patient($request);

        try {
            $person = $this->orders->resolveOrderPerson($user, $request->input('patient_id'));
        } catch (AuthorizationException $exception) {
            return $this->fail(403, $exception->getMessage());
        }

        $pharmacyId = $this->orders->resolveActivePharmacyId(
            (int) $request->input('hospital_id'),
            $request->filled('pharmacy_id') ? (int) $request->input('pharmacy_id') : null
        );
        [$deliveryName, $deliveryPhone, $deliveryAddress] = $this->orders->resolveDeliveryDetails(
            $person,
            $request->input('delivery_name'),
            $request->input('delivery_phone'),
            $request->input('delivery_address')
        );

        $order = $this->orders->submitOrder(
            $user,
            (int) $request->input('hospital_id'),
            $pharmacyId,
            (string) $person->id,
            $request->input('note'),
            $deliveryName,
            $deliveryPhone,
            $deliveryAddress,
            $request->file('prescription')
        );

        $order->load($this->detailRelations());

        return $this->ok('Prescription order submitted.', new PharmacyOrderResource($order), 201);
    }

    public function index(ListPharmacyOrdersRequest $request): JsonResponse
    {
        $user = $this->patient($request);
        $personIds = $this->orders->familyPersonIds($user);
        $personFilter = $request->input('person_id');

        if (is_string($personFilter) && $personFilter !== '' && ! in_array($personFilter, $personIds, true)) {
            $personFilter = '__none__';
        }

        $orders = PharmacyOrder::query()
            ->with([
                'person:id,first_name,last_name,hip_user_id',
                'person.hipUser:id,hip_id',
                'pharmacy:id,name',
                'hospital:id,name',
            ])
            ->withCount([
                'comments as unread_comments_count' => function ($query) {
                    $query->where('author_type', 'pharmacist')->whereNull('read_at');
                },
            ])
            ->visibleToPatient($user, $personIds)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($personFilter, fn ($query) => $query->where('person_id', $personFilter))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json([
            'status' => 200,
            'message' => 'Prescription orders fetched.',
            'data' => PharmacyOrderListResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(int $order): JsonResponse
    {
        $record = $this->visibleOrder(request(), $order);
        $record->load($this->detailRelations());

        return $this->ok('Prescription order fetched.', new PharmacyOrderResource($record));
    }

    public function storeComment(StorePharmacyOrderCommentRequest $request, int $order): JsonResponse
    {
        $record = $this->visibleOrder($request, $order);

        try {
            $comment = $this->orders->addComment($record, 'patient', $this->patient($request)->id, (string) $request->input('body'));
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return $this->ok('Comment added.', new PharmacyOrderCommentResource($comment), 201);
    }

    public function markCommentsRead(int $order): JsonResponse
    {
        $record = $this->visibleOrder(request(), $order);
        $updated = $record->comments()
            ->where('author_type', 'pharmacist')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->ok('Comments marked as read.', ['updated' => $updated]);
    }

    public function cancel(CancelPharmacyOrderRequest $request, int $order): JsonResponse
    {
        $user = $this->patient($request);
        $record = $this->visibleOrder($request, $order);

        if (! $this->policy->patientCanCancel($user, $record)) {
            return $this->fail(422, 'This order can no longer be cancelled.');
        }

        try {
            $cancelled = $this->orders->cancelByPatient($record, $user, $request->input('reason'));
        } catch (DomainException $exception) {
            return $this->fail(422, $exception->getMessage());
        }

        $cancelled->load($this->detailRelations());

        return $this->ok('Prescription order cancelled.', new PharmacyOrderResource($cancelled));
    }

    public function prescription(int $order)
    {
        $record = PharmacyOrder::query()->with('prescriptionDocument')->find($order);
        $document = $record?->prescriptionDocument;
        $path = $document?->document_path;

        if (! $record || ! $path || ! Storage::disk('local')->exists($path)) {
            return $this->fail(404, 'Prescription not found.');
        }

        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';
        $name = $this->downloadName($document->document_name, $path);

        return Storage::disk('local')->response($path, $name, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$name.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function visibleOrder($request, int $orderId): PharmacyOrder
    {
        $user = $this->patient($request);
        $record = PharmacyOrder::query()->find($orderId);

        if (! $record || ! $this->policy->patientCanView($user, $record)) {
            abort(response()->json([
                'status' => 404,
                'message' => 'Order not found.',
                'data' => [],
            ], 404));
        }

        return $record;
    }

    private function patient($request): HIPUser
    {
        $user = $request->user();

        if (! $user instanceof HIPUser) {
            abort(response()->json([
                'status' => 401,
                'message' => 'Unauthenticated. Please login and send a valid Bearer token.',
                'data' => [],
            ], 401));
        }

        return $user;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function detailRelations(): array
    {
        return [
            'person:id,first_name,last_name,hip_user_id',
            'person.hipUser:id,hip_id',
            'pharmacy:id,name',
            'hospital:id,name',
            'items',
            'prescriptionDocument',
            'comments' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
            'statusLogs' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
        ];
    }

    private function downloadName(?string $original, string $path): string
    {
        $name = trim((string) $original);
        if ($name === '') {
            $name = basename($path);
        }

        $name = str_replace(['"', '\\', "\r", "\n"], '', $name);

        return $name !== '' ? $name : 'prescription';
    }

    private function ok(string $message, mixed $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function fail(int $status, string $message): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => [],
        ], $status);
    }
}
