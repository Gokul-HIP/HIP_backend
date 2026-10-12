<?php

namespace App\Livewire\PharmacistAdmin\PrescriptionOrders;

use App\Enums\PharmacyOrderStatus;
use App\Enums\PharmacyPaymentStatus;
use App\Livewire\PharmacistAdmin\PrescriptionOrders\Concerns\HandlesStatusChange;
use App\Models\Document;
use App\Models\HIPUser;
use App\Models\MedicineMaster;
use App\Models\PharmacyOrder;
use App\Models\PharmacyOrderItem;
use App\Models\PharmacyProducts;
use App\Policies\PharmacyOrderPolicy;
use App\Services\Pharmacy\PharmacyBillCalculator;
use App\Services\Pharmacy\PharmacyOrderService;
use App\Services\PharmacyProductService;
use App\Support\PharmacyMedicineOptions;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    use HandlesStatusChange;

    #[Locked]
    public int $orderId = 0;

    public string $medicineSearch = '';

    public string $masterSearch = '';

    public bool $showQuickAdd = false;

    /** @var list<array<string, mixed>> */
    public array $billLines = [];

    public string $discount = '0';

    public string $deliveryFee = '0';

    public string $billUpdatedAt = '';

    public bool $billDirty = false;

    public bool $confirmPayment = false;

    public string $billError = '';

    public string $quickError = '';

    public string $quickNotice = '';

    public string $quickName = '';

    public string $quickStrength = '';

    public string $quickDosageForm = '';

    public string $quickBrand = '';

    public string $quickCategory = '';

    public string $quickSellingPrice = '';

    public string $quickMrp = '';

    public string $quickStock = '';

    public ?int $quickMedicineMasterId = null;

    public string $commentBody = '';

    public string $commentError = '';

    public int $knownCommentCount = 0;

    public function mount(mixed $order): void
    {
        $model = $this->resolve($order);
        $this->guard($model);
        $user = $this->pharmacist();
        $status = $model->status instanceof PharmacyOrderStatus
            ? $model->status
            : PharmacyOrderStatus::tryFrom((string) $model->status);

        if (in_array($status, [PharmacyOrderStatus::Requested, PharmacyOrderStatus::InReview], true)) {
            $model = app(PharmacyOrderService::class)->openForReview($model, $user);
        }

        $this->orderId = (int) $model->id;
        app(PharmacyOrderService::class)->markPatientCommentsRead($model);
        $this->loadBill($model->fresh(['items.pharmacyProduct']));
    }

    public function hydrate(): void
    {
        if ($this->orderId > 0) {
            $this->authorizedOrder();
        }
    }

    public function updated(string $name): void
    {
        if ($name === 'quickName') {
            $this->quickMedicineMasterId = null;
        }

        if (str_starts_with($name, 'billLines') || in_array($name, ['discount', 'deliveryFee'], true)) {
            $this->billDirty = true;
            $this->billError = '';
        }
    }

    public function addCatalogProduct(int $productId): void
    {
        $order = $this->authorizedOrder();
        if (! $this->billIsEditable($order)) {
            return;
        }

        $product = PharmacyProducts::query()
            ->where('pharmacy_id', $order->pharmacy_id)
            ->where('product_status', 'active')
            ->find($productId);

        if (! $product) {
            $this->billError = 'That medicine is not in this pharmacy catalogue.';

            return;
        }

        $this->billLines[] = $this->lineFromProduct($product);
        $this->billDirty = true;
        $this->medicineSearch = '';
    }

    public function addCustomLine(): void
    {
        $order = $this->authorizedOrder();
        if (! $this->billIsEditable($order)) {
            return;
        }

        $this->billLines[] = $this->blankLine();
        $this->billDirty = true;
        $this->showQuickAdd = false;
    }

    public function removeLine(int $index): void
    {
        $order = $this->authorizedOrder();
        if (! $this->billIsEditable($order) || ! array_key_exists($index, $this->billLines)) {
            return;
        }

        unset($this->billLines[$index]);
        $this->billLines = array_values($this->billLines);
        $this->billDirty = true;
    }

    public function toggleWhen(int $index, string $token): void
    {
        $order = $this->authorizedOrder();
        if (! $this->billIsEditable($order) || ! isset($this->billLines[$index])) {
            return;
        }

        if (! in_array($token, PharmacyMedicineOptions::WHEN_TO_TAKE, true)) {
            return;
        }

        $current = array_values($this->billLines[$index]['when_to_take'] ?? []);
        if (in_array($token, $current, true)) {
            $current = array_values(array_filter($current, fn ($value) => $value !== $token));
        } else {
            $current[] = $token;
        }

        $this->billLines[$index]['when_to_take'] = $current;
        $this->billDirty = true;
    }

    public function openQuickAdd(): void
    {
        $order = $this->authorizedOrder();
        if (! $this->billIsEditable($order)) {
            return;
        }

        $this->resetQuickForm();
        $this->showQuickAdd = true;
    }

    public function closeQuickAdd(): void
    {
        $this->authorizedOrder();
        $this->showQuickAdd = false;
        $this->quickError = '';
    }

    public function prefillFromMaster(int $id): void
    {
        $this->authorizedOrder();
        $master = MedicineMaster::query()->find($id);
        if (! $master) {
            return;
        }

        $this->quickName = (string) $master->name;
        $this->quickStrength = (string) ($master->strength ?? '');
        $this->quickDosageForm = (string) ($master->dosage_form ?? '');
        $this->quickBrand = (string) ($master->brand_name ?? '');
        $this->quickCategory = (string) ($master->category ?? '');
        $this->quickSellingPrice = (string) ($master->selling_price ?? '');
        $this->quickMrp = (string) ($master->mrp ?? '');
        $this->quickMedicineMasterId = (int) $master->id;
        $this->masterSearch = '';
    }

    public function saveQuickAdd(): void
    {
        $order = $this->authorizedOrder();
        if (! $this->billIsEditable($order)) {
            $this->quickError = 'The bill can only be edited while the order is in review.';

            return;
        }

        try {
            $result = app(PharmacyProductService::class)->quickAddProduct((int) $order->pharmacy_id, [
                'name' => $this->quickName,
                'strength' => $this->quickStrength,
                'dosage_form' => $this->quickDosageForm,
                'brand_name' => $this->quickBrand,
                'category' => $this->quickCategory,
                'selling_price' => $this->quickSellingPrice,
                'mrp' => $this->quickMrp,
                'stock_quantity' => $this->quickStock,
                'medicine_master_id' => $this->quickMedicineMasterId,
            ], $this->pharmacist());
        } catch (ValidationException $exception) {
            $this->quickError = collect($exception->errors())->flatten()->implode(' ');

            return;
        }

        $this->billLines[] = $this->lineFromProduct($result['product']);
        $this->billDirty = true;
        $this->quickNotice = $result['reused']
            ? 'An active medicine with this name and strength is already in the catalogue. That product was added to the bill.'
            : '';
        $this->showQuickAdd = false;
        $this->resetQuickForm();
    }

    public function saveBill(): void
    {
        $order = $this->authorizedOrder();

        try {
            $saved = app(PharmacyOrderService::class)->saveBill(
                $order,
                $this->billPayload(),
                (float) $this->discount,
                (float) $this->deliveryFee,
                $this->pharmacist(),
                $this->billUpdatedAt
            );
        } catch (DomainException $exception) {
            $this->billError = $exception->getMessage();

            return;
        } catch (ValidationException $exception) {
            $this->billError = collect($exception->errors())->flatten()->implode(' ');

            return;
        }

        $this->billError = '';
        $this->notice = 'Bill saved.';
        $this->loadBill($saved->fresh(['items.pharmacyProduct']));
    }

    public function openPaymentConfirm(): void
    {
        $this->authorizedOrder();
        $this->confirmPayment = true;
    }

    public function closePaymentConfirm(): void
    {
        $this->confirmPayment = false;
    }

    public function sendPaymentRequest(): void
    {
        $order = $this->authorizedOrder();

        try {
            $saved = app(PharmacyOrderService::class)->sendPaymentRequest(
                $order,
                $this->pharmacist(),
                ! $this->billDirty
            );
        } catch (DomainException $exception) {
            $this->billError = $exception->getMessage();
            $this->confirmPayment = false;

            return;
        }

        $this->confirmPayment = false;
        $this->billError = '';
        $this->notice = 'Payment request sent.';
        $this->loadBill($saved->fresh(['items.pharmacyProduct']));
    }

    public function reviseBill(): void
    {
        $order = $this->authorizedOrder();

        try {
            $saved = app(PharmacyOrderService::class)->reviseBill($order, $this->pharmacist());
        } catch (DomainException $exception) {
            $this->billError = $exception->getMessage();

            return;
        }

        $this->billError = '';
        $this->notice = 'Payment request cancelled. Save the bill, then send a new payment request.';
        $this->loadBill($saved->fresh(['items.pharmacyProduct']));
    }

    public function sendComment(): void
    {
        $order = $this->authorizedOrder();

        try {
            app(PharmacyOrderService::class)->addPharmacistComment($order, $this->pharmacist(), $this->commentBody);
        } catch (ValidationException $exception) {
            $this->commentError = collect($exception->errors())->flatten()->first() ?: 'The message could not be sent.';

            return;
        }

        $this->commentBody = '';
        $this->commentError = '';
        $this->knownCommentCount = $order->comments()->count();
        $this->dispatch('po-chat-jump');
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        if ($this->orderId <= 0) {
            return [];
        }

        $channel = 'pharmacy-order.'.$this->orderId;

        return [
            "echo-private:{$channel},.PharmacyOrderUpdated" => 'refreshFromBroadcast',
            "echo-private:{$channel},.PharmacyOrderCommentAdded" => 'appendCommentFromBroadcast',
        ];
    }

    public function refreshFromBroadcast(): void
    {
        $order = $this->authorizedOrder()->fresh(['items.pharmacyProduct']);
        if (! $this->billDirty) {
            $this->loadBill($order);
        }
    }

    public function appendCommentFromBroadcast(): void
    {
        $this->refreshComments();
    }

    public function refreshComments(): void
    {
        $order = $this->authorizedOrder();
        $before = $this->knownCommentCount;
        app(PharmacyOrderService::class)->markPatientCommentsRead($order);
        $count = $order->comments()->count();
        $added = max(0, $count - $before);
        $this->knownCommentCount = $count;

        if ($added > 0) {
            $this->dispatch('po-chat-arrived', count: $added);
        }
    }

    public function render()
    {
        $order = $this->authorizedOrder();
        $order->load([
            'person',
            'hipUser',
            'pharmacy',
            'hospital',
            'items.pharmacyProduct',
            'comments',
            'statusLogs',
            'prescriptionDocument',
            'invoice',
        ]);

        $people = $this->peopleNames($order);
        $status = $order->status instanceof PharmacyOrderStatus
            ? $order->status
            : PharmacyOrderStatus::tryFrom((string) $order->status);
        $payment = $order->payment_status instanceof PharmacyPaymentStatus
            ? $order->payment_status
            : PharmacyPaymentStatus::tryFrom((string) $order->payment_status);
        $invoice = $order->invoice;
        $activeInvoice = $invoice && in_array((string) $invoice->status, ['pending', 'completed'], true);
        $editable = $status === PharmacyOrderStatus::InReview
            || ($status === PharmacyOrderStatus::PendingPayment && ! $activeInvoice);
        $preview = $this->previewBill();
        $canRequestPayment = $editable
            && ! $this->billDirty
            && $this->billLines !== []
            && (float) $preview['total'] > 0;
        $document = $order->prescriptionDocument;
        $hasFile = $this->prescriptionExists($document);

        return view('livewire.pharmacist-admin.prescription-orders.show', [
            'order' => $order,
            'patient' => $this->patientCard($order),
            'status' => $status,
            'payment' => $payment,
            'statusColour' => $this->pillColour($status?->colour()),
            'paymentColour' => $this->pillColour($payment?->colour()),
            'editable' => $editable,
            'activeInvoice' => $activeInvoice,
            'invoice' => $invoice,
            'invoicePdfUrl' => $invoice && (string) $invoice->status === 'completed'
                ? URL::temporarySignedRoute(
                    'hospital.pay-bill-invoice-download',
                    now()->addDay(),
                    ['invoice_id' => $invoice->id]
                )
                : null,
            'canRequestPayment' => $canRequestPayment,
            'preview' => $preview,
            'discountTooHigh' => (float) $this->discount > $preview['subtotal'],
            'productMatches' => $this->productMatches($order),
            'masterMatches' => $this->showQuickAdd ? $this->masterMatches() : collect(),
            'comments' => $order->comments->sortBy([['created_at', 'asc'], ['id', 'asc']])->values()->map(function ($comment) use ($people) {
                return [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author_type' => $comment->author_type,
                    'author' => $people[(string) $comment->author_id] ?? $this->authorFallback((string) $comment->author_type),
                    'time' => $comment->created_at?->format('d M Y h:i A'),
                ];
            }),
            'timeline' => $order->statusLogs->sortBy([['created_at', 'asc'], ['id', 'asc']])->values()->map(function ($log) use ($people) {
                $to = PharmacyOrderStatus::tryFrom((string) $log->to_status);

                return [
                    'id' => $log->id,
                    'label' => $to?->label() ?? (string) $log->to_status,
                    'colour' => $this->pillColour($to?->colour()),
                    'actor' => $log->actor_id
                        ? ($people[(string) $log->actor_id] ?? 'Unknown')
                        : 'System',
                    'time' => $log->created_at?->format('d M Y h:i A'),
                    'note' => $log->note,
                ];
            }),
            'hasFile' => $hasFile,
            'fileUrl' => $hasFile ? route('pharmacist.prescription-orders.file', $order) : null,
            'prescriptionKind' => $hasFile ? $this->prescriptionKind($document) : null,
            'dosages' => PharmacyMedicineOptions::DOSAGES,
            'frequencies' => PharmacyMedicineOptions::FREQUENCIES,
            'durations' => PharmacyMedicineOptions::DURATIONS,
            'whenOptions' => PharmacyMedicineOptions::WHEN_TO_TAKE,
            'chatClosed' => in_array($status, [
                PharmacyOrderStatus::Delivered,
                PharmacyOrderStatus::Cancelled,
                PharmacyOrderStatus::Rejected,
            ], true),
        ]);
    }

    protected function afterStatusChanged(): void
    {
        $order = $this->authorizedOrder();
        $this->billUpdatedAt = $order->updated_at?->format('Y-m-d H:i:s') ?? '';
    }

    protected function findScoped(int $id): PharmacyOrder
    {
        if ($id !== $this->orderId) {
            abort(404);
        }

        return $this->authorizedOrder();
    }

    protected function pharmacist(): HIPUser
    {
        $user = auth()->user();

        if (! $user instanceof HIPUser) {
            abort(403);
        }

        return $user;
    }

    private function authorizedOrder(): PharmacyOrder
    {
        $order = PharmacyOrder::query()->find($this->orderId);

        if (! $order) {
            abort(404);
        }

        $this->guard($order);

        return $order;
    }

    private function resolve(mixed $order): PharmacyOrder
    {
        if ($order instanceof PharmacyOrder) {
            return $order;
        }

        $model = PharmacyOrder::query()->find($order);

        if (! $model) {
            abort(404);
        }

        return $model;
    }

    private function guard(PharmacyOrder $order): void
    {
        $user = auth()->user();

        if (! $user instanceof HIPUser || ! app(PharmacyOrderPolicy::class)->pharmacistCanManage($user, $order)) {
            abort(404);
        }
    }

    private function loadBill(PharmacyOrder $order): void
    {
        $order->load('items.pharmacyProduct');
        $this->billLines = $order->items->map(fn (PharmacyOrderItem $item) => [
            'key' => (string) $item->id,
            'pharmacy_product_id' => $item->pharmacy_product_id,
            'medicine_master_id' => $item->medicine_master_id,
            'is_custom' => (bool) $item->is_custom,
            'name' => (string) ($item->name ?? ''),
            'strength' => (string) ($item->strength ?? ''),
            'dosage_form' => (string) ($item->dosage_form ?? ''),
            'dosage' => (string) ($item->dosage ?? ''),
            'frequency' => (string) ($item->frequency ?? ''),
            'duration' => (string) ($item->duration ?? ''),
            'when_to_take' => $item->when_to_take
                ? array_values(array_filter(array_map('trim', explode(',', (string) $item->when_to_take))))
                : [],
            'qty' => (int) $item->qty,
            'unit_price' => (string) $item->unit_price,
            'special_instruction' => (string) ($item->special_instruction ?? ''),
            'stock' => $item->pharmacyProduct?->stock_quantity,
        ])->all();
        $this->discount = (string) ($order->discount ?? '0');
        $this->deliveryFee = (string) ($order->delivery_fee ?? '0');
        $this->billUpdatedAt = $order->updated_at?->format('Y-m-d H:i:s') ?? '';
        $this->knownCommentCount = $order->comments()->count();
        $this->billDirty = false;
    }

    /**
     * @return array<string, float>
     */
    private function previewBill(): array
    {
        $subtotal = 0.0;
        foreach ($this->billLines as $line) {
            $qty = max(0, (int) ($line['qty'] ?? 0));
            $price = max(0, (float) ($line['unit_price'] ?? 0));
            $subtotal += round($qty * $price, 2);
        }

        return app(PharmacyBillCalculator::class)->calculate(
            round($subtotal, 2),
            max(0, (float) $this->discount),
            max(0, (float) $this->deliveryFee)
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function billPayload(): array
    {
        return array_map(function (array $line) {
            return [
                'is_custom' => (bool) ($line['is_custom'] ?? false),
                'pharmacy_product_id' => $line['pharmacy_product_id'] ?? null,
                'medicine_master_id' => $line['medicine_master_id'] ?? null,
                'name' => $line['name'] ?? null,
                'dosage_form' => $line['dosage_form'] ?? null,
                'strength' => $line['strength'] ?? null,
                'dosage' => $line['dosage'] ?? null,
                'frequency' => $line['frequency'] ?? null,
                'duration' => $line['duration'] ?? null,
                'when_to_take' => $line['when_to_take'] ?? [],
                'special_instruction' => $line['special_instruction'] ?? null,
                'qty' => $line['qty'] ?? 0,
                'unit_price' => $line['unit_price'] ?? null,
            ];
        }, $this->billLines);
    }

    /**
     * @return array<string, mixed>
     */
    private function lineFromProduct(PharmacyProducts $product): array
    {
        $line = $this->blankLine();
        $line['pharmacy_product_id'] = (int) $product->id;
        $line['medicine_master_id'] = $product->medicine_master_id;
        $line['is_custom'] = false;
        $line['name'] = (string) $product->product_name;
        $line['strength'] = (string) ($product->strength ?? '');
        $line['dosage_form'] = (string) ($product->dosage_form ?? '');
        $line['unit_price'] = (string) ($product->selling_price ?? '0');
        $line['stock'] = $product->stock_quantity;

        return $line;
    }

    /**
     * @return array<string, mixed>
     */
    private function blankLine(): array
    {
        return [
            'key' => (string) Str::uuid(),
            'pharmacy_product_id' => null,
            'medicine_master_id' => null,
            'is_custom' => true,
            'name' => '',
            'strength' => '',
            'dosage_form' => '',
            'dosage' => '',
            'frequency' => '',
            'duration' => '',
            'when_to_take' => [],
            'qty' => 1,
            'unit_price' => '',
            'special_instruction' => '',
            'stock' => null,
        ];
    }

    private function billIsEditable(PharmacyOrder $order): bool
    {
        $status = $order->status instanceof PharmacyOrderStatus
            ? $order->status
            : PharmacyOrderStatus::tryFrom((string) $order->status);

        return $status === PharmacyOrderStatus::InReview;
    }

    private function productMatches(PharmacyOrder $order)
    {
        $term = trim($this->medicineSearch);
        if ($term === '' || ! $this->billIsEditable($order)) {
            return collect();
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return PharmacyProducts::query()
            ->where('pharmacy_id', $order->pharmacy_id)
            ->where('product_status', 'active')
            ->where(function ($query) use ($like) {
                $query->where('product_name', 'like', $like)
                    ->orWhere('product_code', 'like', $like)
                    ->orWhere('brand_name', 'like', $like)
                    ->orWhere('strength', 'like', $like);
            })
            ->orderBy('product_name')
            ->limit(8)
            ->get();
    }

    private function masterMatches()
    {
        $term = trim($this->masterSearch);
        if ($term === '') {
            return collect();
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return MedicineMaster::query()
            ->where('status', 'active')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('brand_name', 'like', $like)
                    ->orWhere('strength', 'like', $like);
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function patientCard(PharmacyOrder $order): array
    {
        $person = $order->person;
        $name = trim((($person->first_name ?? '').' '.($person->last_name ?? '')));
        if ($name === '') {
            $name = trim((string) $order->delivery_name);
        }
        $relationship = trim((string) ($person->relationship ?? ''));
        if (strcasecmp($relationship, 'Other') === 0 && filled($person->relationship_other ?? null)) {
            $relationship = (string) $person->relationship_other;
        }

        return [
            'name' => $name !== '' ? $name : 'Unknown patient',
            'hip_id' => trim((string) ($order->hipUser->hip_id ?? '')),
            'mrn' => trim((string) ($person->mrn ?? '')),
            'relationship' => $relationship,
            'mobile' => trim((string) ($person->mobile ?? '')),
            'address' => trim((string) ($person->address ?? '')),
            'delivery_name' => trim((string) ($order->delivery_name ?? '')),
            'delivery_phone' => trim((string) ($order->delivery_phone ?? '')),
            'delivery_address' => trim((string) ($order->delivery_address ?? '')),
            'hospital' => (string) ($order->hospital->name ?? ''),
            'pharmacy' => (string) ($order->pharmacy->name ?? ''),
            'order_number' => (string) $order->order_number,
            'submitted' => $order->created_at?->format('d M Y h:i A') ?? '',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function peopleNames(PharmacyOrder $order): array
    {
        $ids = $order->comments->pluck('author_id')
            ->merge($order->statusLogs->pluck('actor_id'))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return HIPUser::query()
            ->whereIn('id', $ids->all())
            ->get()
            ->mapWithKeys(fn (HIPUser $user) => [(string) $user->id => $user->full_name])
            ->all();
    }

    private function authorFallback(string $type): string
    {
        return match ($type) {
            'patient' => 'Patient',
            'pharmacist' => 'Pharmacist',
            default => 'System',
        };
    }

    private function prescriptionExists(?Document $document): bool
    {
        $path = $document?->document_path;

        return is_string($path)
            && $path !== ''
            && ! str_contains($path, '..')
            && Storage::disk('local')->exists($path);
    }

    private function prescriptionKind(?Document $document): string
    {
        $extension = strtolower(pathinfo((string) ($document?->document_name ?: $document?->document_path), PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return 'image';
        }

        if ($extension === 'pdf') {
            return 'pdf';
        }

        return 'file';
    }

    private function pillColour(?string $colour): string
    {
        $allowed = ['amber', 'violet', 'orange', 'blue', 'indigo', 'cyan', 'sky', 'green', 'slate', 'red'];

        return in_array($colour, $allowed, true) ? $colour : 'slate';
    }

    private function resetQuickForm(): void
    {
        $this->quickError = '';
        $this->quickName = '';
        $this->quickStrength = '';
        $this->quickDosageForm = '';
        $this->quickBrand = '';
        $this->quickCategory = '';
        $this->quickSellingPrice = '';
        $this->quickMrp = '';
        $this->quickStock = '';
        $this->quickMedicineMasterId = null;
        $this->masterSearch = '';
    }
}
