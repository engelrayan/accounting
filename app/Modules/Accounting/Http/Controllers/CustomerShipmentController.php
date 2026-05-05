<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\Governorate;
use App\Modules\Accounting\Models\Invoice;
use App\Modules\Accounting\Models\ShipmentBatch;
use App\Modules\Accounting\Models\ShipmentEntry;
use App\Modules\Accounting\Services\InvoiceService;
use App\Modules\Accounting\Services\PriceResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $batches = ShipmentBatch::query()
            ->forCompany($companyId)
            ->with('creator:id,name')
            ->withCount('entries')
            ->withSum('entries as total_quantity', 'quantity')
            ->withSum('entries as total_sales', 'line_total')
            ->orderByDesc('shipment_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('accounting.customer-shipments.index', compact('batches'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;
        $customers = Customer::forCompany($companyId)->orderBy('name')->get(['id', 'name', 'phone']);
        $governorates = Governorate::active()->ordered()->get(['id', 'name_ar']);

        return view('accounting.customer-shipments.create', compact('customers', 'governorates'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;
        $validated = $this->validatePayload($request, $companyId);

        $invoiceCount = 0;
        $batch = DB::transaction(function () use ($validated, $companyId, $request, &$invoiceCount) {
            $batch = ShipmentBatch::create([
                'company_id' => $companyId,
                'shipment_date' => $validated['shipment_date'],
                'notes' => $validated['batch_notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $nextCode = $this->nextEntryCodeSeed($companyId, $validated['shipment_date']);
            foreach ($validated['entries'] as $entry) {
                $resolved = $this->resolvePriceRow($companyId, (int) $entry['customer_id'], (int) $entry['governorate_id'], $entry['shipment_type']);
                ShipmentEntry::create([
                    'shipment_batch_id' => $batch->id,
                    'company_id' => $companyId,
                    'shipment_date' => $validated['shipment_date'],
                    'customer_id' => (int) $entry['customer_id'],
                    'governorate_id' => (int) $entry['governorate_id'],
                    'price_list_id' => $resolved['price_list_id'],
                    'price_source' => $resolved['source'],
                    'shipment_type' => $entry['shipment_type'],
                    'quantity' => $entry['quantity'],
                    'unit_price' => $resolved['unit_price'],
                    'line_total' => round(((float) $entry['quantity']) * $resolved['unit_price'], 2),
                    'entry_code' => str_pad((string) $nextCode, 6, '0', STR_PAD_LEFT),
                    'notes' => $entry['notes'] ?? null,
                ]);
                $nextCode++;
            }

            $invoiceCount = $this->syncBatchInvoices($batch, $companyId);

            return $batch;
        });

        return redirect()
            ->route('accounting.customer-shipments.show', $batch)
            ->with('success', "تم تسجيل الشحنات اليومية بنجاح وإنشاء {$invoiceCount} فاتورة للعملاء.");
    }

    public function show(Request $request, ShipmentBatch $customerShipment): View
    {
        $this->authorizeBatch($request, $customerShipment);

        $customerShipment->load([
            'entries.customer:id,name',
            'entries.governorate:id,name_ar',
            'entries.priceList:id,name',
        ]);

        $totals = $this->batchTotals($customerShipment);

        return view('accounting.customer-shipments.show', [
            'batch' => $customerShipment,
            'totals' => $totals,
        ]);
    }

    public function edit(Request $request, ShipmentBatch $customerShipment): View
    {
        Gate::authorize('can-write');
        $this->authorizeBatch($request, $customerShipment);

        $companyId = $request->user()->company_id;
        $customerShipment->load('entries');
        $customers = Customer::forCompany($companyId)->orderBy('name')->get(['id', 'name', 'phone']);
        $governorates = Governorate::active()->ordered()->get(['id', 'name_ar']);

        return view('accounting.customer-shipments.edit', [
            'batch' => $customerShipment,
            'customers' => $customers,
            'governorates' => $governorates,
        ]);
    }

    public function update(Request $request, ShipmentBatch $customerShipment): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeBatch($request, $customerShipment);

        $companyId = $request->user()->company_id;
        $validated = $this->validatePayload($request, $companyId, true);
        $originalDate = $customerShipment->shipment_date?->toDateString();

        $invoiceCount = 0;
        DB::transaction(function () use ($validated, $companyId, $customerShipment, $originalDate, &$invoiceCount) {
            $customerShipment->update([
                'shipment_date' => $validated['shipment_date'],
                'notes' => $validated['batch_notes'] ?? null,
            ]);

            $existingEntries = $customerShipment->entries()->get()->keyBy('id');
            $nextCode = $this->nextEntryCodeSeed($companyId, $validated['shipment_date']);
            $usedCodes = [];

            foreach ($validated['entries'] as $entry) {
                $resolved = $this->resolvePriceRow($companyId, (int) $entry['customer_id'], (int) $entry['governorate_id'], $entry['shipment_type']);
                $existing = !empty($entry['id']) ? $existingEntries->get((int) $entry['id']) : null;

                $code = $existing?->entry_code;
                if ($validated['shipment_date'] !== $originalDate || !$code || isset($usedCodes[$code])) {
                    while (isset($usedCodes[str_pad((string) $nextCode, 6, '0', STR_PAD_LEFT)])) {
                        $nextCode++;
                    }
                    $code = str_pad((string) $nextCode, 6, '0', STR_PAD_LEFT);
                    $nextCode++;
                }

                $payload = [
                    'shipment_batch_id' => $customerShipment->id,
                    'company_id' => $companyId,
                    'shipment_date' => $validated['shipment_date'],
                    'customer_id' => (int) $entry['customer_id'],
                    'governorate_id' => (int) $entry['governorate_id'],
                    'price_list_id' => $resolved['price_list_id'],
                    'price_source' => $resolved['source'],
                    'shipment_type' => $entry['shipment_type'],
                    'quantity' => $entry['quantity'],
                    'unit_price' => $resolved['unit_price'],
                    'line_total' => round(((float) $entry['quantity']) * $resolved['unit_price'], 2),
                    'entry_code' => $code,
                    'notes' => $entry['notes'] ?? null,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $usedCodes[$code] = true;
                    $existingEntries->forget($existing->id);
                } else {
                    ShipmentEntry::create($payload);
                    $usedCodes[$code] = true;
                }
            }

            if ($existingEntries->isNotEmpty()) {
                ShipmentEntry::query()->whereIn('id', $existingEntries->keys()->all())->delete();
            }

            $invoiceCount = $this->syncBatchInvoices($customerShipment, $companyId);
        });

        return redirect()
            ->route('accounting.customer-shipments.show', $customerShipment)
            ->with('success', "تم تحديث الشحنات اليومية بنجاح وتحديث {$invoiceCount} فاتورة للعملاء.");
    }

    public function resolvePrice(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'shipment_type' => ['required', 'in:delivery,return'],
        ]);

        Customer::forCompany($companyId)->whereKey($data['customer_id'])->firstOrFail();

        $resolved = $this->resolvePriceRow($companyId, (int) $data['customer_id'], (int) $data['governorate_id'], $data['shipment_type']);

        return response()->json([
            'unit_price' => number_format($resolved['unit_price'], 2, '.', ''),
            'price_list_id' => $resolved['price_list_id'],
            'price_list_name' => $resolved['price_list_name'],
            'source' => $resolved['source'],
        ]);
    }

    private function validatePayload(Request $request, int $companyId, bool $isUpdate = false): array
    {
        $validated = $request->validate([
            'shipment_date' => ['required', 'date'],
            'batch_notes' => ['nullable', 'string', 'max:2000'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.id' => ['nullable', 'integer'],
            'entries.*.customer_id' => ['required', 'integer', 'exists:customers,id'],
            'entries.*.governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'entries.*.notes' => ['nullable', 'string', 'max:1000'],
            'entries.*.shipment_type' => ['required', 'in:delivery,return'],
            'entries.*.quantity' => [
                'required',
                'numeric',
                'min:0.25',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $scaled = round(((float) $value) * 4, 6);
                    if (abs($scaled - round($scaled)) > 0.00001) {
                        $fail('عدد الأشولة يجب أن يكون صحيحًا أو بربع/نصف/ثلاثة أرباع.');
                    }
                },
            ],
        ], [
            'shipment_date.required' => 'تاريخ الشحنات مطلوب.',
            'entries.required' => 'أضف شحنة واحدة على الأقل.',
            'entries.*.customer_id.required' => 'اختر العميل لكل سطر.',
            'entries.*.governorate_id.required' => 'اختر المحافظة لكل سطر.',
            'entries.*.shipment_type.required' => 'نوع الشحنة مطلوب.',
            'entries.*.quantity.required' => 'عدد الأشولة مطلوب.',
        ]);

        $customerIds = collect($validated['entries'])->pluck('customer_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $validCustomerIds = Customer::forCompany($companyId)->whereIn('id', $customerIds)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count($validCustomerIds) !== $customerIds->count()) {
            throw ValidationException::withMessages([
                'entries' => 'يوجد عميل غير تابع لهذه الشركة.',
            ]);
        }

        foreach ($validated['entries'] as $index => $entry) {
            $this->resolvePriceRow(
                $companyId,
                (int) $entry['customer_id'],
                (int) $entry['governorate_id'],
                $entry['shipment_type'],
                "entries.{$index}.governorate_id"
            );
        }

        return $validated;
    }

    private function resolvePriceRow(
        int $companyId,
        int $customerId,
        int $governorateId,
        string $shipmentType,
        ?string $errorKey = null
    ): array {
        /** @var PriceResolverService $resolver */
        $resolver = app(PriceResolverService::class);
        $resolved = $resolver->resolve($companyId, $customerId, $governorateId);

        $price = $shipmentType === 'return'
            ? $resolved['return']
            : $resolved['delivery'];

        if ($price === null) {
            throw ValidationException::withMessages([
                $errorKey ?? 'entries' => $shipmentType === 'return'
                    ? 'لا يوجد سعر مرتجع لهذا العميل/المحافظة في قوائم الأسعار.'
                    : 'لا يوجد سعر تسليم لهذا العميل/المحافظة في قوائم الأسعار.',
            ]);
        }

        return [
            'unit_price' => (float) $price,
            'source' => $resolved['source'],
            'price_list_id' => $resolved['price_list_id'],
            'price_list_name' => $resolved['price_list_name'],
        ];
    }

    private function nextEntryCodeSeed(int $companyId, string $shipmentDate): int
    {
        $lastCode = ShipmentEntry::query()
            ->forCompany($companyId)
            ->whereDate('shipment_date', $shipmentDate)
            ->max('entry_code');

        return $lastCode ? ((int) $lastCode + 1) : 1;
    }

    private function batchTotals(ShipmentBatch $batch): array
    {
        $entries = $batch->entries;

        return [
            'quantity' => (float) $entries->sum('quantity'),
            'sales' => (float) $entries->sum('line_total'),
            'delivery_quantity' => (float) $entries->where('shipment_type', 'delivery')->sum('quantity'),
            'return_quantity' => (float) $entries->where('shipment_type', 'return')->sum('quantity'),
        ];
    }

    private function authorizeBatch(Request $request, ShipmentBatch $batch): void
    {
        abort_if($batch->company_id !== $request->user()->company_id, 403);
    }

    private function syncBatchInvoices(ShipmentBatch $batch, int $companyId): int
    {
        $batch->loadMissing('entries.customer');
        $entries = $batch->entries;
        if ($entries->isEmpty()) {
            return 0;
        }

        $batchTag = "shipment_batch:{$batch->id}";

        // Rebuild invoices for this batch on every save/update to keep totals consistent.
        Invoice::query()
            ->where('company_id', $companyId)
            ->where('source', 'customer_shipment')
            ->where('notes', 'like', "%{$batchTag}%")
            ->delete();

        /** @var InvoiceService $invoiceService */
        $invoiceService = app(InvoiceService::class);
        $createdCount = 0;

        foreach ($entries->groupBy('customer_id') as $customerEntries) {
            $customer = $customerEntries->first()?->customer;
            if (!$customer) {
                continue;
            }

            $items = $customerEntries
                ->groupBy(fn (ShipmentEntry $entry) => number_format((float) $entry->unit_price, 2, '.', '') . '|' . $entry->shipment_type)
                ->map(function ($group): array {
                    $quantity = (float) $group->sum('quantity');
                    $unitPrice = (float) $group->first()->unit_price;

                    return [
                        'description' => 'شوال',
                        'quantity' => round($quantity, 3),
                        'unit_price' => round($unitPrice, 2),
                    ];
                })
                ->values()
                ->all();

            if (empty($items)) {
                continue;
            }

            $invoiceService->create([
                'customer_id' => (int) $customer->id,
                'issue_date' => $batch->shipment_date->toDateString(),
                'due_date' => $batch->shipment_date->toDateString(),
                'payment_method' => 'other',
                'tax_rate' => 0,
                'source' => 'customer_shipment',
                'notes' => "فاتورة مولدة تلقائيًا من الشحنات اليومية ({$batchTag}).",
                'items' => $items,
            ], $companyId);

            $createdCount++;
        }

        return $createdCount;
    }
}
