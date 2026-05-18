<?php

namespace App\Http\Controllers;

use App\Models\StockItem;
use App\Traits\ProcurementHelpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockItemController extends Controller
{
    use ProcurementHelpers;

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $items = StockItem::query()
            ->when($request->filled('search'), fn (Builder $query): Builder => $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($request->string('search')->toString()).'%']))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (StockItem $item): array => $this->stockItemToArray($item));

        return view('master-stok.index', [
            'currentUser' => $this->currentUser(),
            'items' => $items,
            'filters' => ['search' => $request->string('search')->toString()],
            'editItem' => $request->filled('edit') ? $this->stockItemToArray(StockItem::query()->find($request->string('edit')->toString())) : null,
            'isCreating' => $request->string('mode')->toString() === 'create',
        ]);
    }

    public function create(): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();

        return redirect()->route('master-stok.index', ['mode' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:20'],
        ]);
        StockItem::query()->create([
            'name' => strtoupper($validated['name']),
            'unit' => strtoupper($validated['unit']),
            'category' => 'Operasional',
            'status' => 'Aktif',
        ]);

        return redirect()->route('master-stok.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function show(string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        return view('master-stok.show', [
            'currentUser' => $this->currentUser(),
            'item' => $this->stockItemToArray(StockItem::query()->findOrFail($id)),
        ]);
    }

    public function edit(string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();

        return redirect()->route('master-stok.index', ['edit' => $id]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:20'],
        ]);
        StockItem::query()->findOrFail($id)->update([
            'name' => strtoupper($validated['name']),
            'unit' => strtoupper($validated['unit']),
        ]);

        return redirect()->route('master-stok.index')->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        StockItem::query()->findOrFail($id)->delete();

        return redirect()->route('master-stok.index')->with('success', 'Barang berhasil dihapus.');
    }
}
