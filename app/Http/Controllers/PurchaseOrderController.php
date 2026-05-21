<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\Supplier;
use App\Traits\ProcurementHelpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    use ProcurementHelpers;

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($request->has('clear')) {
            $request->session()->forget('po_filters');

            return redirect()->route('purchase-orders.index');
        }

        $hasQueryParams = $request->has('search') || $request->has('status') || $request->has('po_date') || $request->has('drop_date') || $request->has('page');

        if ($hasQueryParams) {
            $filters = [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'po_date' => $request->string('po_date')->toString(),
                'drop_date' => $request->string('drop_date')->toString(),
                'page' => $request->string('page')->toString(),
            ];
            $request->session()->put('po_filters', $filters);
        } else {
            if ($request->session()->has('po_filters')) {
                return redirect()->route('purchase-orders.index', $request->session()->get('po_filters'));
            }
        }

        $query = $this->visibleOrdersQuery();
        $search = strtolower($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        $dropFrom = $request->string('drop_from')->toString();
        $dropTo = $request->string('drop_to')->toString();

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->whereRaw('LOWER(number) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(created_by) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('sppg', fn (Builder $sppg): Builder => $sppg->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
                    ->orWhereHas('items', fn (Builder $item): Builder => $item->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]));
            });
        }

        if ($status !== '' && $status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($dateFrom !== '') {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('date', '<=', $dateTo);
        }

        if ($dropFrom !== '') {
            $query->whereDate('droping_date', '>=', $dropFrom);
        }

        if ($dropTo !== '') {
            $query->whereDate('droping_date', '<=', $dropTo);
        }

        $orders = $query
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (PurchaseOrder $order): array => $this->orderToArray($order));

        return view('purchase-orders.index', [
            'currentUser' => $this->currentUser(),
            'orders' => $orders,
            'stats' => $this->poStats($this->visibleOrders()),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $status ?: 'ALL',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'drop_from' => $dropFrom,
                'drop_to' => $dropTo,
            ],
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        return view('purchase-orders.create', [
            'currentUser' => $this->currentUser(),
            'stockItems' => $this->stockItems(),
            'suppliers' => $this->suppliers(),
            'sppgs' => Sppg::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $validated = $this->validatePurchaseOrder($request);

        $order = DB::transaction(function () use ($validated): PurchaseOrder {
            $sppg = $this->sppgForRequest($validated['sppg_code'] ?? null);

            // Nomor PO dibiarkan null dulu — akan diterbitkan setelah admin menentukan supplier.
            $order = PurchaseOrder::query()->create([
                'number' => null,
                'date' => $validated['date'],
                'created_by' => $validated['created_by'],
                'sppg_id' => $sppg->id,
                'droping_date' => $validated['droping_date'] ?? null,
                'droping_time' => $validated['droping_time'] ?? null,
                'status' => 'VALID',
            ]);

            $this->syncPurchaseOrderItems($order, $validated['items']);
            $this->publishOrResplitPurchaseOrder($order->refresh());

            return $order;
        });

        session()->forget('po_filters');

        return redirect()->route('purchase-orders.index')->with('success', 'PO berhasil dibuat.');
    }

    public function show(string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        return view('purchase-orders.show', [
            'currentUser' => $this->currentUser(),
            'order' => $this->findOrderArray($id),
            'suppliers' => $this->suppliers(),
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();

        $order = $this->findOrderModel($id);
        if ($this->isPurchaseOrderLocked($order)) {
            return $this->redirectLockedPurchaseOrder();
        }

        return view('purchase-orders.edit', [
            'currentUser' => $this->currentUser(),
            'order' => $this->orderToArray($order),
            'stockItems' => $this->stockItems(),
            'suppliers' => $this->suppliers(),
            'sppgs' => Sppg::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $order = $this->findOrderModel($id);
        if ($this->isPurchaseOrderLocked($order)) {
            return $this->redirectLockedPurchaseOrder();
        }

        $validated = $this->validatePurchaseOrder($request);

        DB::transaction(function () use ($order, $validated): void {
            $sppg = $this->sppgForRequest($validated['sppg_code'] ?? null);
            $order->update([
                'date' => $validated['date'],
                'created_by' => $validated['created_by'],
                'sppg_id' => $sppg->id,
                'droping_date' => $validated['droping_date'] ?? null,
                'droping_time' => $validated['droping_time'] ?? null,
            ]);

            // Hanya hapus item yang belum di-invoice agar invoice tetap utuh
            $order->items()->where('is_invoiced', false)->delete();

            // Filter: skip item dari form yang namanya sama dengan item yang sudah di-invoice
            $invoicedNames = $order->items()->where('is_invoiced', true)->pluck('name')->map(fn ($n) => strtoupper($n))->all();
            $newItems = collect($validated['items'])->filter(function ($item) use ($invoicedNames) {
                $name = strtoupper($item['name'] ?? '');

                return $name === '' || ! in_array($name, $invoicedNames, true);
            })->values()->all();

            $this->syncPurchaseOrderItems($order, $newItems);

            // Hanya publish/resplit jika PO belum punya invoice
            if ($order->invoices()->doesntExist()) {
                $this->publishOrResplitPurchaseOrder($order->refresh());
            }

            // Auto-tambahkan item baru ke invoice existing untuk supplier yang sama
            $order->refresh();
            $newPoItems = $order->items()->where('is_invoiced', false)->with('supplier')->get();
            foreach ($newPoItems as $poItem) {
                if (! $poItem->supplier) {
                    continue;
                }

                $existingInvoice = $order->invoices()
                    ->where('supplier_name', $poItem->supplier->name)
                    ->first();

                if ($existingInvoice) {
                    $subtotal = (int) ($poItem->qty * $poItem->price);
                    $existingInvoice->items()->create([
                        'purchase_order_item_id' => $poItem->id,
                        'name' => $poItem->name,
                        'qty' => $poItem->qty,
                        'unit' => $poItem->unit,
                        'price' => $poItem->price,
                        'subtotal' => $subtotal,
                    ]);
                    $existingInvoice->update([
                        'total_amount' => $existingInvoice->items()->sum('subtotal'),
                    ]);
                    $poItem->update(['is_invoiced' => true]);
                }
            }
        });

        session()->forget('po_filters');

        return redirect()->route('purchase-orders.index')->with('success', 'PO berhasil diperbarui.');
    }

    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $validated = $request->validate(['status' => ['required', 'in:VALID,PROCESSING,COMPLETED,INVOICED,CANCELLED']]);
        $this->findOrderModel($id)->update(['status' => $validated['status']]);

        return back()->with('success', 'Status PO berhasil diperbarui.');
    }

    /**
     * Simpan penugasan supplier dan pecah PO jika item memiliki supplier berbeda-beda.
     *
     * Logika (sama dengan prototipe React):
     * - Item yang sudah punya supplier → dikelompokkan per supplier → masing-masing jadi PO baru
     *   dengan nomor format: {id_po_asli}/PO/{DDMMYYYY}/{SUPPLIER_ABBR}/{YEAR}
     * - Item yang belum punya supplier → tetap di PO asli (status VALID, tanpa nomor)
     * - PO asli dihapus jika semua item sudah dipindahkan ke PO split
     */
    public function updateSuppliers(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $order = $this->findOrderModel($id);
        if ($this->isPurchaseOrderLocked($order)) {
            return $this->redirectLockedPurchaseOrder();
        }

        $validated = $request->validate([
            'suppliers' => ['required', 'array'],
            'suppliers.*' => ['nullable', 'exists:suppliers,name'],
            'qty_actual' => ['nullable', 'array'],
            'qty_actual.*' => ['nullable', 'numeric', 'min:0.01'],
            'prices' => ['nullable', 'array'],
            'prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);
        $splitCount = 0;

        DB::transaction(function () use ($order, $validated, &$splitCount): void {
            $items = $order->items()->orderBy('id')->get();
            $canUpdateItemValues = $order->status === 'PROCESSING';
            $submittedQuantities = $validated['qty_actual'] ?? [];
            $submittedPrices = $validated['prices'] ?? [];

            foreach (array_values($validated['suppliers']) as $index => $supplierName) {
                $supplier = $supplierName
                    ? Supplier::query()->where('name', $supplierName)->first()
                    : null;

                $item = $items->get($index);

                if (! $item) {
                    continue;
                }

                $updates = [
                    'supplier_id' => $supplier?->id,
                ];

                if ($canUpdateItemValues) {
                    $updates['qty'] = $submittedQuantities[$index] ?? $item->qty;
                    $updates['price'] = $submittedPrices[$index] ?? $item->price;
                }

                $item->update($updates);
            }

            $splitCount = $this->publishOrResplitPurchaseOrder($order->refresh());
        });

        $message = $splitCount > 0
            ? "{$splitCount} nomor PO baru diterbitkan sesuai supplier."
            : 'Penugasan supplier diperbarui.';

        return redirect()->route('purchase-orders.index')->with('success', $message);
    }

    public function destroy(string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $this->findOrderModel($id)->delete();

        session()->forget('po_filters');

        return redirect()->route('purchase-orders.index')->with('success', 'PO berhasil dihapus.');
    }

    public function preview(string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        return view('purchase-orders.preview', [
            'currentUser' => $this->currentUser(),
            'order' => $this->findOrderArray($id),
            'suppliers' => $this->suppliers(),
        ]);
    }

    private function isPurchaseOrderLocked(PurchaseOrder $order): bool
    {
        return false;
    }

    private function redirectLockedPurchaseOrder(): RedirectResponse
    {
        return redirect()
            ->route('purchase-orders.index')
            ->withErrors(['purchase_order' => 'PO yang sudah tertagih atau selesai tidak bisa diedit.']);
    }
}
