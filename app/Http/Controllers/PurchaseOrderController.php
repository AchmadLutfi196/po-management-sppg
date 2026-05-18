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

        $query = $this->visibleOrdersQuery();
        $search = strtolower($request->string('search')->toString());
        $status = $request->string('status')->toString();

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

        $orders = $query->latest('id')->get()->map(fn (PurchaseOrder $order): array => $this->orderToArray($order));

        return view('purchase-orders.index', [
            'currentUser' => $this->currentUser(),
            'orders' => $orders->values(),
            'stats' => $this->poStats($this->visibleOrders()),
            'filters' => ['search' => $request->string('search')->toString(), 'status' => $status ?: 'ALL'],
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

            return $order;
        });

        return redirect()->route('purchase-orders.show', $order->id)->with('success', 'PO berhasil dibuat. Silakan tentukan supplier untuk menerbitkan nomor PO.');
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

        return view('purchase-orders.edit', [
            'currentUser' => $this->currentUser(),
            'order' => $this->findOrderArray($id),
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
        $validated = $this->validatePurchaseOrder($request);
        $order = $this->findOrderModel($id);

        DB::transaction(function () use ($order, $validated): void {
            $sppg = $this->sppgForRequest($validated['sppg_code'] ?? null);
            $order->update([
                'date' => $validated['date'],
                'created_by' => $validated['created_by'],
                'sppg_id' => $sppg->id,
                'droping_date' => $validated['droping_date'] ?? null,
                'droping_time' => $validated['droping_time'] ?? null,
            ]);
            $order->items()->delete();
            $this->syncPurchaseOrderItems($order, $validated['items']);
        });

        return redirect()->route('purchase-orders.show', $order->id)->with('success', 'PO berhasil diperbarui.');
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
        $validated = $request->validate([
            'suppliers' => ['required', 'array'],
            'suppliers.*' => ['nullable', 'exists:suppliers,name'],
        ]);
        $order = $this->findOrderModel($id);
        $splitCount = 0;

        DB::transaction(function () use ($order, $validated, &$splitCount): void {
            // 1. Update supplier_id setiap item sesuai urutan input form
            foreach (array_values($validated['suppliers']) as $index => $supplierName) {
                $supplier = $supplierName
                    ? Supplier::query()->where('name', $supplierName)->first()
                    : null;

                $order->items()->skip($index)->first()?->update(['supplier_id' => $supplier?->id]);
            }

            // 2. Reload items beserta relasi supplier
            $order->refresh()->load('items.supplier');

            $assignedItems = $order->items->filter(fn ($item) => $item->supplier_id !== null);
            $unassignedItems = $order->items->filter(fn ($item) => $item->supplier_id === null);

            // Jika belum ada yang di-assign, tidak ada yang perlu dilakukan
            if ($assignedItems->isEmpty()) {
                return;
            }

            // 3. Kelompokkan item yang sudah di-assign berdasarkan supplier_id
            //    lalu buat PO baru per supplier dengan nomor unik masing-masing
            $compactDate = now()->format('dmY');
            $year = now()->format('Y');

            foreach ($assignedItems->groupBy('supplier_id') as $items) {
                $supplierName = $items->first()->supplier->name;
                $abbr = $this->supplierAbbreviation($supplierName);

                // Format nomor: {id_po_asli}/PO/{DDMMYYYY}/{ABBR}/{YEAR}
                // Contoh: 5/PO/18052026/DBM/2026
                $number = "{$order->id}/PO/{$compactDate}/{$abbr}/{$year}";

                $newOrder = PurchaseOrder::query()->create([
                    'number' => $number,
                    'date' => $order->date,
                    'created_by' => $order->created_by,
                    'sppg_id' => $order->sppg_id,
                    'droping_date' => $order->droping_date,
                    'droping_time' => $order->droping_time,
                    'status' => 'PROCESSING',
                ]);

                // Pindahkan item ke PO baru
                $items->each(fn ($item) => $item->update(['purchase_order_id' => $newOrder->id]));
                $splitCount++;
            }

            // 4. Jika semua item sudah dipindahkan → hapus PO asli (sudah tidak punya item)
            //    Jika masih ada item unassigned → PO asli tetap hidup (VALID, tanpa nomor)
            if ($unassignedItems->isEmpty()) {
                $order->delete();
            }
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
}
