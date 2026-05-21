<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Traits\ProcurementHelpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

        if ($request->has('clear')) {
            $request->session()->forget('sj_filters');

            return redirect()->route('surat-jalan.index');
        }

        $hasQueryParams = $request->has('search') ||
            $request->has('sppg') ||
            $request->has('date_filter') ||
            $request->has('date_from') ||
            $request->has('date_to') ||
            $request->has('page');

        if ($hasQueryParams) {
            $filters = [
                'search' => $request->string('search')->toString(),
                'sppg' => $request->string('sppg')->toString(),
                'date_filter' => $request->string('date_filter')->toString() ?: 'all',
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'page' => $request->string('page')->toString(),
            ];
            $request->session()->put('sj_filters', $filters);
        } else {
            if ($request->session()->has('sj_filters')) {
                return redirect()->route('surat-jalan.index', $request->session()->get('sj_filters'));
            }
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'sppg' => ['nullable', 'string', 'exists:sppgs,code'],
            'date_filter' => ['nullable', 'in:all,today,range'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $filters = [
            'search' => $filters['search'] ?? '',
            'sppg' => $filters['sppg'] ?? '',
            'date_filter' => $filters['date_filter'] ?? 'all',
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
        ];

        if (($filters['date_from'] !== '' || $filters['date_to'] !== '') && $filters['date_filter'] !== 'today') {
            $filters['date_filter'] = 'range';
        }

        $query = $this->visibleOrdersQuery()->whereIn('status', ['PROCESSING', 'COMPLETED', 'INVOICED']);
        $search = strtolower($filters['search']);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->whereRaw('LOWER(number) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('deliveryNote', fn (Builder $delivery): Builder => $delivery->whereRaw('LOWER(number) LIKE ?', ["%{$search}%"]))
                    ->orWhereHas('items', fn (Builder $item): Builder => $item->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]));
            });
        }

        if ($filters['sppg'] !== '') {
            $query->whereHas('sppg', fn (Builder $sppg): Builder => $sppg->where('code', $filters['sppg']));
        }

        if ($filters['date_filter'] === 'today') {
            $today = now()->toDateString();
            $this->applyDeliveryDateFilter($query, $today, $today);
        }

        if ($filters['date_filter'] === 'range' && ($filters['date_from'] !== '' || $filters['date_to'] !== '')) {
            $this->applyDeliveryDateFilter(
                $query,
                $filters['date_from'] !== '' ? $filters['date_from'] : null,
                $filters['date_to'] !== '' ? $filters['date_to'] : null,
            );
        }

        return view('surat-jalan.index', [
            'currentUser' => $this->currentUser(),
            'orders' => $query
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (PurchaseOrder $order): array => $this->orderToArray($order)),
            'filters' => $filters,
            'sppgs' => $this->filterableSppgs(),
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
        $canUpdateItemValues = $existingDeliveryNote === null;
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
            'item_photos' => ['nullable', 'array'],
            'item_photos.*' => ['nullable', 'image', 'max:5120'],
            'proof_photo' => ['nullable', 'image', 'max:10240'],
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

        DB::transaction(function () use ($order, $validated, $uploadedPhotos, $hasPhoto, $proofPhotoPath, $canUpdateItemValues, $existingDeliveryNote): void {
            $order->deliveryNote()->updateOrCreate(
                ['purchase_order_id' => $order->id],
                [
                    'number' => $validated['surat_jalan_no'],
                    'date' => $existingDeliveryNote ? $existingDeliveryNote->date->toDateString() : now()->toDateString(),
                    'time' => $existingDeliveryNote ? ($existingDeliveryNote->time ? substr((string) $existingDeliveryNote->time, 0, 5) : now()->format('H:i')) : now()->format('H:i'),
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
                if (! $canUpdateItemValues) {
                    continue;
                }

                $item->update([
                    'qty' => $validated['qty_actual'][$index] ?? $item->qty,
                    'price' => $validated['prices'][$index] ?? $item->price,
                ]);
            }

            $order->update([
                'status' => 'INVOICED',
                'droping_date' => $validated['delivery_date'],
                'droping_time' => $validated['delivery_time'] ?? null,
            ]);
        });

        return redirect()->route('surat-jalan.show', $order->id)->with('success', 'Surat Jalan berhasil disimpan.');
    }

    public function preview(string $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $order = $this->findOrderArray($id);
        $supplierName = collect($order['items'])->pluck('supplier')->filter(fn ($s) => $s !== '-' && filled($s))->first();

        return view('surat-jalan.preview', [
            'currentUser' => $this->currentUser(),
            'order' => $order,
            'preparedBy' => $this->deliveryNotePreparedBy($order),
            'supplier' => $this->supplierDetails($supplierName),
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
        ]);

        $order = $this->findOrderArray($id);
        $existingDelivery = $order['delivery'] ?? null;
        $order['droping_date'] = $validated['delivery_date'];
        $order['droping_time'] = $validated['delivery_time'] ?? null;

        $order['delivery'] = [
            'number' => $validated['surat_jalan_no'],
            'date' => $existingDelivery ? $existingDelivery['date'] : now()->toDateString(),
            'time' => $existingDelivery ? $existingDelivery['time'] : now()->format('H:i'),
            'driver' => $validated['driver'] ?: 'Nama Pengirim',
            'notes' => $validated['notes'] ?: '-',
            'kepada' => $validated['kepada'],
            'kd_sppg' => $validated['kd_sppg'] ?: $order['sppg_code'],
            'nama_sppg' => $validated['nama_sppg'],
            'pj_sppg' => $validated['pj_sppg'] ?: '-',
            'whatsapp' => $validated['whatsapp'] ?: '-',
            'has_photo' => $existingDelivery ? $existingDelivery['has_photo'] : false,
            'proof_photo' => $existingDelivery ? $existingDelivery['proof_photo'] : null,
            'item_photos' => $existingDelivery ? $existingDelivery['item_photos'] : [],
        ];

        foreach ($order['items'] as $index => $item) {
            $order['items'][$index]['qty'] = (float) ($validated['qty_actual'][$index] ?? $item['qty']);
            $order['items'][$index]['price'] = $validated['prices'][$index] ?? $item['price'];
        }

        return view('surat-jalan.preview', [
            'currentUser' => $this->currentUser(),
            'order' => $order,
            'preparedBy' => $this->deliveryNotePreparedBy($order),
            'supplier' => $this->supplierDetails(collect($order['items'])->pluck('supplier')->filter(fn ($s) => $s !== '-' && filled($s))->first()),
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

    private function applyDeliveryDateFilter(Builder $query, ?string $from, ?string $to): void
    {
        $query->where(function (Builder $builder) use ($from, $to): void {
            $builder
                ->whereHas('deliveryNote', function (Builder $delivery) use ($from, $to): void {
                    $this->applyDateBounds($delivery, 'date', $from, $to);
                })
                ->orWhere(function (Builder $fallback) use ($from, $to): void {
                    $fallback
                        ->whereDoesntHave('deliveryNote')
                        ->whereNotNull('droping_date');

                    $this->applyDateBounds($fallback, 'droping_date', $from, $to);
                });
        });
    }

    private function applyDateBounds(Builder $query, string $column, ?string $from, ?string $to): void
    {
        if ($from !== null) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate($column, '<=', $to);
        }
    }

    private function filterableSppgs(): EloquentCollection
    {
        return Sppg::query()
            ->when(
                ($this->currentUser()['role'] ?? null) === 'SPPG',
                fn (Builder $query): Builder => $query->where('code', $this->currentUser()['id'])
            )
            ->orderBy('name')
            ->get();
    }
}
