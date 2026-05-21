<?php

namespace App\Traits;

use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sppg;
use App\Models\StockItem;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait ProcurementHelpers
{
    private function validatePurchaseOrder(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'created_by' => ['required', 'string', 'max:120'],
            'sppg_code' => ['required', 'exists:sppgs,code'],
            'droping_date' => ['nullable', 'date'],
            'droping_time' => ['nullable', 'date_format:H:i'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.stock_item_id' => ['nullable', 'exists:stock_items,id'],
            'items.*.name' => ['nullable', 'string', 'max:120'],
            'items.*.grade' => ['required', 'string', 'max:30'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.supplier' => ['nullable', 'exists:suppliers,name'],
            'items.*.request' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function syncPurchaseOrderItems(PurchaseOrder $order, array $items): void
    {
        foreach ($items as $itemData) {
            // Skip baris yang benar-benar kosong: tidak ada stock_item_id maupun nama barang
            $hasStockItem = ! empty($itemData['stock_item_id']);
            $hasName = ($itemData['name'] ?? '') !== '';

            if (! $hasStockItem && ! $hasName) {
                continue;
            }

            $stockItem = ! empty($itemData['stock_item_id']) ? StockItem::query()->find($itemData['stock_item_id']) : null;
            $supplier = ! empty($itemData['supplier']) ? Supplier::query()->where('name', $itemData['supplier'])->first() : null;
            $order->items()->create([
                'stock_item_id' => $stockItem?->id,
                'supplier_id' => $supplier?->id,
                'name' => $stockItem?->name ?? $itemData['name'],
                'grade' => $itemData['grade'],
                'qty' => $itemData['qty'],
                'unit' => strtoupper($itemData['unit'] ?? $stockItem?->unit ?? 'KG'),
                'price' => $itemData['price'],
                'request_note' => $itemData['request'] ?? null,
                'is_invoiced' => false,
            ]);
        }
    }

    private function publishOrResplitPurchaseOrder(PurchaseOrder $order): int
    {
        $items = $order->items()->with('supplier')->orderBy('id')->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $hasPublishedNumber = filled($order->number);
        $hasUnassignedSupplier = $items->contains(fn (PurchaseOrderItem $item): bool => $item->supplier_id === null);

        if ($hasPublishedNumber && $hasUnassignedSupplier) {
            throw ValidationException::withMessages([
                'suppliers' => 'Semua item pada PO yang sudah terbit wajib memiliki supplier.',
            ]);
        }

        if (! $hasPublishedNumber && $hasUnassignedSupplier) {
            $order->update([
                'number' => null,
                'status' => 'VALID',
            ]);

            return 0;
        }

        $numberParts = $this->purchaseOrderNumberParts($order);
        $supplierGroups = $items->groupBy('supplier_id')->values();
        $createdOrders = 0;
        $usesSourceOrder = false;

        foreach ($supplierGroups as $supplierItems) {
            /** @var Collection<int, PurchaseOrderItem> $supplierItems */
            $supplierName = $supplierItems->first()->supplier->name;
            $number = $this->purchaseOrderNumberForSupplier($numberParts, $supplierName);
            $existingOrder = PurchaseOrder::query()
                ->where('number', $number)
                ->first();

            if ($existingOrder !== null && ! $existingOrder->is($order)) {
                $targetOrder = $existingOrder;
            } elseif (! $usesSourceOrder) {
                $targetOrder = $order;
                $targetOrder->update([
                    'number' => $number,
                    'status' => 'PROCESSING',
                ]);
                $usesSourceOrder = true;
            } else {
                $targetOrder = PurchaseOrder::query()->create([
                    'number' => $number,
                    'date' => $order->date,
                    'created_by' => $order->created_by,
                    'sppg_id' => $order->sppg_id,
                    'droping_date' => $order->droping_date,
                    'droping_time' => $order->droping_time,
                    'status' => 'PROCESSING',
                ]);

                $createdOrders++;
            }

            $supplierItems->each(fn (PurchaseOrderItem $item): bool => $item->update([
                'purchase_order_id' => $targetOrder->id,
            ]));
        }

        if (! $usesSourceOrder && $order->items()->doesntExist()) {
            $order->delete();
        }

        return $createdOrders;
    }

    private function paginateCollection(Collection $items, Request $request, int $perPage = 10): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url()],
        ))->withQueryString();
    }

    /**
     * @return array{base: string, date: string, year: string}
     */
    private function purchaseOrderNumberParts(PurchaseOrder $order): array
    {
        if (is_string($order->number) && preg_match('/^([^\/]+)\/PO\/([^\/]+)\/[^\/]+\/([^\/]+)$/', $order->number, $matches) === 1) {
            return [
                'base' => $matches[1],
                'date' => $matches[2],
                'year' => $matches[3],
            ];
        }

        return [
            'base' => (string) $this->nextPurchaseOrderSequence((int) now()->format('Y')),
            'date' => now()->format('dmY'),
            'year' => now()->format('Y'),
        ];
    }

    /**
     * @param  array{base: string, date: string, year: string}  $numberParts
     */
    private function purchaseOrderNumberForSupplier(array $numberParts, string $supplierName): string
    {
        return "{$numberParts['base']}/PO/{$numberParts['date']}/{$this->supplierAbbreviation($supplierName)}/{$numberParts['year']}";
    }

    private function nextPurchaseOrderSequence(int $year): int
    {
        DB::table('po_sequences')->insertOrIgnore([
            'year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('po_sequences')
            ->where('year', $year)
            ->lockForUpdate()
            ->firstOrFail();

        $nextNumber = (int) $sequence->last_number + 1;

        DB::table('po_sequences')
            ->where('year', $year)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return $nextNumber;
    }

    private function requireAuth(): ?RedirectResponse
    {
        if (! session()->has('auth_user')) {
            return redirect()->route('login');
        }

        return null;
    }

    private function authorizeAdmin(): void
    {
        abort_unless(($this->currentUser()['role'] ?? null) === 'ADMIN', 403);
    }

    private function currentUser(): array
    {
        return session('auth_user', []);
    }

    private function visibleOrdersQuery(): Builder
    {
        $query = PurchaseOrder::query()->with(['sppg', 'items.supplier', 'deliveryNote', 'invoices.items', 'invoices.supplier']);

        if (($this->currentUser()['role'] ?? null) === 'SPPG') {
            $query->whereHas('sppg', fn (Builder $sppg): Builder => $sppg->where('code', $this->currentUser()['id']));
        }

        return $query;
    }

    private function visibleOrders(): Collection
    {
        return $this->visibleOrdersQuery()->latest('id')->get()->map(fn (PurchaseOrder $order): array => $this->orderToArray($order));
    }

    private function findOrderModel(string $id): PurchaseOrder
    {
        return $this->visibleOrdersQuery()->whereKey($id)->firstOrFail();
    }

    private function findOrderArray(string $id): array
    {
        return $this->orderToArray($this->findOrderModel($id));
    }

    private function orderToArray(PurchaseOrder $order): array
    {
        $order->loadMissing(['sppg', 'items.supplier', 'deliveryNote', 'invoices.items', 'invoices.supplier']);

        return [
            'id' => (string) $order->id,
            'number' => $order->number,
            'date' => $order->date->format('Y-m-d'),
            'created_by' => $order->created_by,
            'sppg' => $order->sppg->name,
            'sppg_code' => $order->sppg->code,
            'sppg_location' => $order->sppg->location,
            'sppg_pic_name' => $order->sppg->pic_name,
            'sppg_whatsapp' => $order->sppg->whatsapp,
            'droping_date' => $order->droping_date?->format('Y-m-d'),
            'droping_time' => $order->droping_time ? substr((string) $order->droping_time, 0, 5) : null,
            'status' => $order->status,
            'items' => $order->items->map(fn (PurchaseOrderItem $item): array => $this->itemToArray($item))->values()->all(),
            'delivery_suggested_number' => $this->deliveryNoteNumberForPurchaseOrder($order->number),
            'delivery' => $order->deliveryNote ? $this->deliveryToArray($order->deliveryNote) : null,
            'invoices' => $order->invoices->map(fn (Invoice $invoice): array => $this->invoiceToArray($invoice))->values()->all(),
        ];
    }

    private function itemToArray(PurchaseOrderItem $item): array
    {
        return [
            'id' => (string) $item->id,
            'stock_item_id' => $item->stock_item_id,
            'name' => $item->name,
            'qty' => (float) $item->qty,
            'unit' => $item->unit,
            'grade' => $item->grade,
            'price' => $item->price,
            'supplier' => $item->supplier?->name ?? '-',
            'invoiced' => $item->is_invoiced,
            'request' => $item->request_note,
        ];
    }

    private function deliveryToArray(DeliveryNote $delivery): array
    {
        return [
            'number' => $delivery->number,
            'date' => $delivery->date->format('Y-m-d'),
            'time' => $delivery->time ? substr((string) $delivery->time, 0, 5) : null,
            'driver' => $delivery->driver,
            'notes' => $delivery->notes,
            'kepada' => $delivery->kepada,
            'kd_sppg' => $delivery->kd_sppg,
            'nama_sppg' => $delivery->nama_sppg,
            'pj_sppg' => $delivery->pj_sppg,
            'whatsapp' => $delivery->whatsapp,
            'has_photo' => $delivery->has_photo,
            'proof_photo' => $delivery->proof_photo,
            'item_photos' => $delivery->item_photos ?? [],
        ];
    }

    private function invoiceToArray(Invoice $invoice): array
    {
        return [
            'number' => $invoice->number,
            'date' => $invoice->date->format('Y-m-d'),
            'supplier' => $invoice->supplier_name,
            'status' => $invoice->status,
            'total_amount' => $invoice->total_amount,
            'items' => $invoice->items->map(fn ($item): array => [
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'name' => $item->name,
                'qty' => (float) $item->qty,
                'unit' => $item->unit,
                'price' => $item->price,
            ])->all(),
        ];
    }

    private function stockItems(): array
    {
        return StockItem::query()->orderBy('id')->get()->map(fn (StockItem $item): array => $this->stockItemToArray($item))->all();
    }

    private function stockItemToArray(?StockItem $item): ?array
    {
        if ($item === null) {
            return null;
        }

        return [
            'id' => (string) $item->id,
            'name' => $item->name,
            'unit' => $item->unit,
            'category' => $item->category,
            'image' => $item->image,
            'qty' => (float) $item->qty,
            'het' => (int) $item->het,
            'status' => $item->status,
        ];
    }

    private function orderTotal(array $order): int
    {
        return (int) collect($order['items'])->sum(fn (array $item): float|int => $item['qty'] * $item['price']);
    }

    private function poStats(Collection $orders): array
    {
        return [
            'total_value' => $orders->sum(fn (array $order): int => $this->orderTotal($order)),
            'active' => $orders->where('status', 'PROCESSING')->count(),
            'valid' => $orders->where('status', 'VALID')->count(),
            'completed' => $orders->where('status', 'COMPLETED')->count(),
        ];
    }

    private function suppliers(): array
    {
        return Supplier::query()->orderBy('name')->pluck('name')->all();
    }

    private function supplierDetails(?string $supplier): array
    {
        $record = Supplier::query()->where('name', $supplier)->first() ?? Supplier::query()->first();
        $bankAccounts = $this->supplierBankAccounts($record?->name);

        return [
            'name' => $record?->name ?? 'SUPPLIER',
            'address' => $record?->address ?? '-',
            'logo' => $record?->logo_path ?? 'logo-duniabumbu.jpeg',
            'stamp' => $record?->stamp_path ?? 'stamp-duniabumbu.jpeg',
            'theme' => $record?->theme_color ?? '#2563eb',
            'bank_name' => $bankAccounts[0]['bank'],
            'bank_account_name' => $bankAccounts[0]['account_name'],
            'bank_account_number' => $bankAccounts[0]['number'],
            'bank_accounts' => $bankAccounts,
            'managing_director_name' => $this->supplierManagingDirectorName($record),
        ];
    }

    private function supplierManagingDirectorName(?Supplier $supplier): string
    {
        return match ($supplier?->name) {
            'DUNIA BUMBU MOJOKERTO' => 'Arif Rakhman Hadi',
            'NUTRIVA FOODS' => 'Dessy Istuning Tiyas',
            'VIALA PANGAN' => 'Dwi Silvia Anggraini',
            default => $supplier?->bank_account_name ?? 'Arif Rakhman Hadi',
        };
    }

    /**
     * @return array<int, array{bank: string, account_name: string, number: string}>
     */
    private function supplierBankAccounts(?string $supplier): array
    {
        return match ($supplier) {
            'VIALA PANGAN' => [
                ['bank' => 'MANDIRI', 'account_name' => 'Dwi Silvia Anggraini', 'number' => '1420026949999'],
            ],
            'NUTRIVA FOODS' => [
                ['bank' => 'MANDIRI', 'account_name' => 'Dessy Istuning Tiyas', 'number' => '1420026949973'],
            ],
            default => [
                ['bank' => 'MANDIRI', 'account_name' => 'Arif Rakhman Hadi', 'number' => '1420015180150'],
            ],
        };
    }

    private function invoiceNumberFor(?string $supplier, string $poNumber): string
    {
        $prefix = match ($supplier) {
            'NUTRIVA FOODS' => 'NUTRIVA',
            'VIALA PANGAN' => 'VIALA',
            default => 'DUNIA',
        };

        return 'INV/'.$prefix.'/'.substr(preg_replace('/\D+/', '', $poNumber), -6).random_int(10, 99);
    }

    private function deliveryNoteNumberForPurchaseOrder(?string $poNumber): string
    {
        if (is_string($poNumber) && str_contains($poNumber, '/PO/')) {
            return str_replace('/PO/', '/SJ/', $poNumber);
        }

        return 'SJ/'.now()->format('YmdHis');
    }

    private function nextPurchaseOrderNumber(string $prefix): string
    {
        $count = PurchaseOrder::query()->count() + 1;

        return $count.'/PO/'.now()->format('dmY').'/'.$prefix.'/'.now()->format('Y');
    }

    /**
     * Buat singkatan supplier untuk digunakan sebagai prefix nomor PO.
     * Contoh: "DUNIA BUMBU MOJOKERTO" → "DBM", "NUTRIVA FOODS" → "NF"
     */
    private function supplierAbbreviation(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));

        return strtoupper(implode('', array_map(fn (string $w): string => $w[0] ?? '', $words)));
    }

    private function sppgForRequest(?string $code): Sppg
    {
        if (($this->currentUser()['role'] ?? null) === 'SPPG') {
            return Sppg::query()->where('code', $this->currentUser()['id'])->firstOrFail();
        }

        return Sppg::query()->where('code', $code)->firstOrFail();
    }
}
