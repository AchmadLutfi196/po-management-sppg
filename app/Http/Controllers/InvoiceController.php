<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Traits\ProcurementHelpers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use ProcurementHelpers;

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $orders = $this->visibleOrdersQuery()->latest('id')->get();
        $historyInvoices = Invoice::query()
            ->with(['purchaseOrder.sppg', 'items', 'supplier'])
            ->latest('id')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'order' => $this->orderToArray($invoice->purchaseOrder),
                'invoice' => $this->invoiceToArray($invoice),
            ]);

        $publishedKeys = $historyInvoices
            ->map(fn (array $entry): string => $entry['order']['id'].'|'.$entry['invoice']['supplier'])
            ->all();

        $pendingInvoices = $orders
            ->filter(fn (PurchaseOrder $order): bool => in_array($order->status, ['COMPLETED', 'INVOICED'], true))
            ->flatMap(function (PurchaseOrder $order) use ($publishedKeys): array {
                return $order->items
                    ->filter(fn (PurchaseOrderItem $item): bool => ! $item->is_invoiced)
                    ->groupBy(fn (PurchaseOrderItem $item): string => $item->supplier?->name ?? '-')
                    ->reject(fn (Collection $items, string $supplier): bool => in_array($order->id.'|'.$supplier, $publishedKeys, true))
                    ->map(fn (Collection $items, string $supplier): array => [
                        'order' => $this->orderToArray($order),
                        'supplier' => $supplier,
                        'items' => $items->map(fn (PurchaseOrderItem $item): array => $this->itemToArray($item))->values(),
                        'total' => $items->sum(fn (PurchaseOrderItem $item): float|int => $item->qty * $item->price),
                    ])
                    ->values()
                    ->all();
            })
            ->values();
        $paginatedPendingInvoices = $this->paginateCollection($pendingInvoices, $request);
        $paginatedHistoryInvoices = $this->paginateCollection($historyInvoices, $request);

        return view('invoices.index', [
            'currentUser' => $this->currentUser(),
            'pendingInvoices' => $paginatedPendingInvoices,
            'historyInvoices' => $paginatedHistoryInvoices,
            'activeTab' => $request->string('tab')->toString() === 'history' ? 'history' : 'pending',
            'stats' => [
                'total' => $historyInvoices->sum(fn (array $entry): int|float => $entry['invoice']['total_amount']),
                'paid' => $historyInvoices->sum(fn (array $entry): int|float => $entry['invoice']['status'] === 'PAID' ? $entry['invoice']['total_amount'] : 0),
                'unpaid' => $historyInvoices->sum(fn (array $entry): int|float => $entry['invoice']['status'] === 'UNPAID' ? $entry['invoice']['total_amount'] : 0),
                'count' => $historyInvoices->count(),
            ],
        ]);
    }

    public function create(Request $request, string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $order = $this->findOrderModel($id);
        $supplierName = $request->string('supplier')->toString() ?: $order->items->first()?->supplier?->name;
        $items = $order->items
            ->filter(fn (PurchaseOrderItem $item): bool => ($item->supplier?->name === $supplierName) && ! $item->is_invoiced)
            ->map(fn (PurchaseOrderItem $item): array => $this->itemToArray($item))
            ->values();

        return view('invoices.create', [
            'currentUser' => $this->currentUser(),
            'order' => $this->orderToArray($order),
            'items' => $items,
            'invoiceNumber' => $this->invoiceNumberFor($supplierName, $order->number),
            'supplier' => $this->supplierDetails($supplierName),
        ]);
    }

    public function store(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $order = $this->findOrderModel($id);
        $validated = $request->validate([
            'supplier' => ['required', 'exists:suppliers,name'],
            'invoice_no' => ['required', 'string', 'max:80', 'unique:invoices,number'],
            'invoice_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'exists:purchase_order_items,id'],
            'items.*.name' => ['required', 'string', 'max:120'],
            'items.*.unit' => ['required', 'string', 'max:20'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:1'],
        ]);

        DB::transaction(function () use ($order, $validated): void {
            $supplier = Supplier::query()->where('name', $validated['supplier'])->firstOrFail();
            $invoice = Invoice::query()->create([
                'purchase_order_id' => $order->id,
                'supplier_id' => $supplier->id,
                'number' => $validated['invoice_no'],
                'date' => $validated['invoice_date'],
                'supplier_name' => $supplier->name,
                'status' => 'UNPAID',
                'total_amount' => 0,
            ]);

            $total = 0;
            foreach ($validated['items'] as $itemData) {
                $subtotal = (int) ($itemData['qty'] * $itemData['price']);
                $total += $subtotal;
                $invoice->items()->create([
                    'purchase_order_item_id' => $itemData['id'] ?? null,
                    'name' => $itemData['name'],
                    'qty' => $itemData['qty'],
                    'unit' => $itemData['unit'],
                    'price' => $itemData['price'],
                    'subtotal' => $subtotal,
                ]);

                if (! empty($itemData['id'])) {
                    PurchaseOrderItem::query()->whereKey($itemData['id'])->update([
                        'price' => $itemData['price'],
                        'is_invoiced' => true,
                    ]);
                }
            }

            $invoice->update(['total_amount' => $total]);

            if ($order->items()->where('is_invoiced', false)->doesntExist()) {
                $order->update(['status' => 'INVOICED']);
            }
        });

        return redirect()->route('invoices.index', ['tab' => 'history'])->with('success', 'Invoice berhasil diterbitkan.');
    }

    public function show(string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        return view('invoices.show', [
            'currentUser' => $this->currentUser(),
            'order' => $this->findOrderArray($id),
        ]);
    }

    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $validated = $request->validate([
            'invoice_no' => ['required', 'string'],
            'status' => ['required', 'in:PAID,UNPAID'],
        ]);
        $order = $this->findOrderModel($id);

        DB::transaction(function () use ($order, $validated): void {
            Invoice::query()
                ->where('purchase_order_id', $order->id)
                ->where('number', $validated['invoice_no'])
                ->update(['status' => $validated['status']]);

            $hasUnpaidInvoice = $order->invoices()->where('status', 'UNPAID')->exists();
            $order->update(['status' => $hasUnpaidInvoice ? 'INVOICED' : 'COMPLETED']);
        });

        return back()->with('success', 'Status invoice berhasil diperbarui.');
    }

    public function preview(Request $request, string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $order = $this->findOrderModel($id);
        $invoice = Invoice::query()
            ->with(['items', 'supplier'])
            ->where('purchase_order_id', $order->id)
            ->where('number', $request->string('invoice')->toString())
            ->first();
        $supplierName = $request->string('supplier')->toString() ?: ($invoice?->supplier_name ?? $order->items->first()?->supplier?->name);
        $requestItems = $request->input('items', []);

        $items = $invoice
            ? $invoice->items->map(fn ($item): array => [
                'name' => $item->name,
                'qty' => (float) $item->qty,
                'unit' => $item->unit,
                'price' => $item->price,
            ])
            : $order->items->where('supplier.name', $supplierName)->map(function (PurchaseOrderItem $item) use ($requestItems): array {
                $itemArray = $this->itemToArray($item);

                $reqItem = collect($requestItems)->firstWhere('id', (string) $item->id);
                if ($reqItem) {
                    $itemArray['price'] = (float) preg_replace('/\D+/', '', $reqItem['price'] ?? 0);
                    $itemArray['qty'] = (float) ($reqItem['qty'] ?? $item->qty);
                }

                return $itemArray;
            })->values();
        $invoiceArray = $invoice ? $this->invoiceToArray($invoice) : [
            'number' => $this->invoiceNumberFor($supplierName, $order->number),
            'date' => now()->format('Y-m-d'),
            'supplier' => $supplierName,
            'status' => 'UNPAID',
            'total_amount' => $items->sum(fn (array $item): int|float => $item['qty'] * $item['price']),
        ];

        return view('invoices.preview', [
            'currentUser' => $this->currentUser(),
            'order' => $this->orderToArray($order),
            'invoice' => $invoiceArray,
            'items' => $items,
            'supplier' => $this->supplierDetails($supplierName),
        ]);
    }
}
