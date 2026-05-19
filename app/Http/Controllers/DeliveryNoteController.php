<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Traits\ProcurementHelpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryNoteController extends Controller
{
    use ProcurementHelpers;

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $query = $this->visibleOrdersQuery()->whereIn('status', ['PROCESSING', 'COMPLETED', 'INVOICED']);
        $search = strtolower($request->string('search')->toString());

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->whereRaw('LOWER(number) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('deliveryNote', fn (Builder $delivery): Builder => $delivery->whereRaw('LOWER(number) LIKE ?', ["%{$search}%"]))
                    ->orWhereHas('items', fn (Builder $item): Builder => $item->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]));
            });
        }

        return view('surat-jalan.index', [
            'currentUser' => $this->currentUser(),
            'orders' => $query
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (PurchaseOrder $order): array => $this->orderToArray($order)),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function show(string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $orderModel = $this->findOrderModel($id);
        $sppg = $orderModel->sppg;

        return view('surat-jalan.show', [
            'currentUser' => $this->currentUser(),
            'order' => $this->orderToArray($orderModel),
            'suppliers' => $this->suppliers(),
            'sppg' => [
                'code' => $sppg->code,
                'name' => $sppg->name,
                'location' => $sppg->location ?? '',
                'pic_name' => $sppg->pic_name ?? '',
                'whatsapp' => $sppg->whatsapp ?? '',
            ],
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();
        $order = $this->findOrderModel($id);
        $existingDeliveryNote = $order->deliveryNote;
        $validated = $request->validate([
            'kepada' => ['required', 'string', 'max:120'],
            'kd_sppg' => ['nullable', 'string', 'max:40'],
            'nama_sppg' => ['required', 'string', 'max:120'],
            'pj_sppg' => ['nullable', 'string', 'max:120'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'surat_jalan_no' => ['required', 'string', 'max:80'],
            'delivery_date' => ['required', 'date'],
            'delivery_time' => ['nullable', 'date_format:H:i'],
            'driver' => ['required', 'string', 'max:120'],
            'notes' => ['required', 'string', 'max:500'],
            'qty_actual' => ['required', 'array'],
            'qty_actual.*' => ['required', 'numeric', 'min:0'],
            'prices' => ['required', 'array'],
            'prices.*' => ['required', 'numeric', 'min:0'],
            'suppliers' => ['required', 'array'],
            'suppliers.*' => ['required', 'exists:suppliers,name'],
            'item_photos' => ['nullable', 'array'],
            'item_photos.*' => ['nullable', 'image', 'max:5120'],
            'proof_photo' => [$existingDeliveryNote?->proof_photo ? 'nullable' : 'required', 'image', 'max:10240'],
        ]);

        // Proses upload foto per item
        $existingPhotos = $order->deliveryNote?->item_photos ?? [];
        $uploadedPhotos = $existingPhotos;

        if ($request->hasFile('item_photos')) {
            foreach ($request->file('item_photos') as $index => $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('delivery-photos', 'public');
                    $uploadedPhotos[$index] = $path;
                }
            }
        }

        // Proses upload foto utama (proof_photo)
        $proofPhotoPath = $order->deliveryNote?->proof_photo ?? null;
        if ($request->hasFile('proof_photo') && $request->file('proof_photo')->isValid()) {
            $proofPhotoPath = $request->file('proof_photo')->store('delivery-proofs', 'public');
        }

        $hasPhoto = count(array_filter($uploadedPhotos)) > 0 || $proofPhotoPath !== null;

        DB::transaction(function () use ($order, $validated, $uploadedPhotos, $hasPhoto, $proofPhotoPath): void {
            $order->deliveryNote()->updateOrCreate(
                ['purchase_order_id' => $order->id],
                [
                    'number' => $validated['surat_jalan_no'],
                    'date' => $validated['delivery_date'],
                    'time' => $validated['delivery_time'] ?? null,
                    'driver' => $validated['driver'] ?: 'Nama Pengirim',
                    'notes' => $validated['notes'] ?: '-',
                    'kepada' => $validated['kepada'],
                    'kd_sppg' => $validated['kd_sppg'] ?: $order->sppg->code,
                    'nama_sppg' => $validated['nama_sppg'],
                    'pj_sppg' => $validated['pj_sppg'] ?: '-',
                    'whatsapp' => $validated['whatsapp'] ?: '-',
                    'item_photos' => $uploadedPhotos,
                    'proof_photo' => $proofPhotoPath,
                    'has_photo' => $hasPhoto,
                ],
            );

            foreach ($order->items as $index => $item) {
                $supplier = Supplier::query()->where('name', $validated['suppliers'][$index] ?? null)->first();
                $item->update([
                    'qty' => $validated['qty_actual'][$index] ?? $item->qty,
                    'price' => $validated['prices'][$index] ?? $item->price,
                    'supplier_id' => $supplier?->id ?? $item->supplier_id,
                ]);
            }

            $order->update(['status' => 'INVOICED']);
        });

        return redirect()->route('surat-jalan.show', $order->id)->with('success', 'Surat Jalan berhasil disimpan.');
    }

    public function preview(string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $order = $this->findOrderArray($id);

        return view('surat-jalan.preview', [
            'currentUser' => $this->currentUser(),
            'order' => $order,
            'preparedBy' => $this->deliveryNotePreparedBy($order),
        ]);
    }

    public function previewFromForm(Request $request, string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $validated = $request->validate([
            'kepada' => ['required', 'string', 'max:120'],
            'kd_sppg' => ['nullable', 'string', 'max:40'],
            'nama_sppg' => ['required', 'string', 'max:120'],
            'pj_sppg' => ['nullable', 'string', 'max:120'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'surat_jalan_no' => ['required', 'string', 'max:80'],
            'delivery_date' => ['required', 'date'],
            'delivery_time' => ['nullable', 'date_format:H:i'],
            'driver' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'qty_actual' => ['required', 'array'],
            'qty_actual.*' => ['required', 'numeric', 'min:0'],
            'prices' => ['required', 'array'],
            'prices.*' => ['required', 'numeric', 'min:0'],
            'suppliers' => ['required', 'array'],
            'suppliers.*' => ['required', 'exists:suppliers,name'],
        ]);

        $order = $this->findOrderArray($id);
        $order['delivery'] = [
            'number' => $validated['surat_jalan_no'],
            'date' => $validated['delivery_date'],
            'time' => $validated['delivery_time'] ?? null,
            'driver' => $validated['driver'] ?: 'Nama Pengirim',
            'notes' => $validated['notes'] ?: '-',
            'kepada' => $validated['kepada'],
            'kd_sppg' => $validated['kd_sppg'] ?: $order['sppg_code'],
            'nama_sppg' => $validated['nama_sppg'],
            'pj_sppg' => $validated['pj_sppg'] ?: '-',
            'whatsapp' => $validated['whatsapp'] ?: '-',
            'has_photo' => false,
            'proof_photo' => null,
            'item_photos' => [],
        ];

        foreach ($order['items'] as $index => $item) {
            $order['items'][$index]['qty'] = (float) ($validated['qty_actual'][$index] ?? $item['qty']);
            $order['items'][$index]['price'] = $validated['prices'][$index] ?? $item['price'];
            $order['items'][$index]['supplier'] = $validated['suppliers'][$index] ?? $item['supplier'];
        }

        return view('surat-jalan.preview', [
            'currentUser' => $this->currentUser(),
            'order' => $order,
            'preparedBy' => $this->deliveryNotePreparedBy($order),
        ]);
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function deliveryNotePreparedBy(array $order): string
    {
        $supplierName = collect($order['items'])
            ->pluck('supplier')
            ->filter(fn (?string $supplier): bool => filled($supplier) && $supplier !== '-')
            ->first();

        return $this->supplierDetails($supplierName)['managing_director_name'];
    }
}
