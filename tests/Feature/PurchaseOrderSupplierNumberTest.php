<?php

use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\StockItem;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-05-18 09:00:00');

    $this->withSession([
        'auth_user' => [
            'role' => 'ADMIN',
            'id' => 'admin',
            'name' => 'Admin Supplier',
        ],
    ]);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('PurchaseOrderSupplier store publishes a number when supplier is entered during create', function (): void {
    $data = purchaseOrderSupplierFixture();

    $this->post(route('purchase-orders.store'), purchaseOrderPayload($data, [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'VIALA PANGAN'],
    ]))->assertRedirect(route('purchase-orders.index'));

    $order = PurchaseOrder::query()->firstOrFail();

    expect($order->number)->toBe($order->id.'/PO/18052026/VP/2026')
        ->and($order->status)->toBe('PROCESSING')
        ->and($order->items()->first()->supplier->name)->toBe('VIALA PANGAN');
});

test('PurchaseOrderSupplier sequence number is not reused after deleting purchase order', function (): void {
    $data = purchaseOrderSupplierFixture();

    $this->post(route('purchase-orders.store'), purchaseOrderPayload($data, [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'VIALA PANGAN'],
    ]))->assertRedirect(route('purchase-orders.index'));

    $firstOrder = PurchaseOrder::query()->firstOrFail();
    $this->delete(route('purchase-orders.destroy', $firstOrder->id))
        ->assertRedirect(route('purchase-orders.index'));

    $this->post(route('purchase-orders.store'), purchaseOrderPayload($data, [
        ['stock' => $data['stocks']['telur'], 'supplier' => 'NUTRIVA FOODS'],
    ]))->assertRedirect(route('purchase-orders.index'));

    expect(PurchaseOrder::query()->firstOrFail()->number)->toBe('2/PO/18052026/NF/2026');
});

test('PurchaseOrderSupplier sequence number resets for a new year', function (): void {
    $data = purchaseOrderSupplierFixture();

    $this->post(route('purchase-orders.store'), purchaseOrderPayload($data, [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'VIALA PANGAN'],
    ]))->assertRedirect(route('purchase-orders.index'));

    Carbon::setTestNow('2027-01-01 09:00:00');

    $this->post(route('purchase-orders.store'), purchaseOrderPayload($data, [
        ['stock' => $data['stocks']['telur'], 'supplier' => 'NUTRIVA FOODS'],
    ]))->assertRedirect(route('purchase-orders.index'));

    $numbers = PurchaseOrder::query()->orderBy('id')->pluck('number')->all();

    expect($numbers)->toBe([
        '1/PO/18052026/VP/2026',
        '1/PO/01012027/NF/2027',
    ]);
});

test('PurchaseOrderSupplier draft edit publishes a number when every item has supplier', function (): void {
    $data = purchaseOrderSupplierFixture();
    $order = draftPurchaseOrder($data);

    $this->patch(route('purchase-orders.update', $order->id), purchaseOrderPayload($data, [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'DUNIA BUMBU MOJOKERTO'],
    ]))->assertRedirect(route('purchase-orders.index'));

    $order->refresh();

    expect($order->number)->toBe($order->id.'/PO/18052026/DBM/2026')
        ->and($order->status)->toBe('PROCESSING')
        ->and($order->items()->count())->toBe(1);
});

test('PurchaseOrderSupplier draft edit splits multiple suppliers using original base number', function (): void {
    $data = purchaseOrderSupplierFixture();
    $order = draftPurchaseOrder($data);

    $this->patch(route('purchase-orders.update', $order->id), purchaseOrderPayload($data, [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'DUNIA BUMBU MOJOKERTO'],
        ['stock' => $data['stocks']['telur'], 'supplier' => 'NUTRIVA FOODS'],
    ]))->assertRedirect(route('purchase-orders.index'));

    $numbers = PurchaseOrder::query()->orderBy('id')->pluck('number')->all();

    expect($numbers)->toBe([
        $order->id.'/PO/18052026/DBM/2026',
        $order->id.'/PO/18052026/NF/2026',
    ]);
});

test('PurchaseOrderSupplier published supplier change only updates supplier code in number', function (): void {
    $data = purchaseOrderSupplierFixture();
    $order = publishedPurchaseOrder($data, '22/PO/17052026/DBM/2026', [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'DUNIA BUMBU MOJOKERTO'],
    ]);

    $this->patch(route('purchase-orders.suppliers.update', $order->id), [
        'suppliers' => ['VIALA PANGAN'],
    ])->assertRedirect(route('purchase-orders.index'));

    expect($order->refresh()->number)->toBe('22/PO/17052026/VP/2026')
        ->and($order->items()->first()->supplier->name)->toBe('VIALA PANGAN');
});

test('PurchaseOrderSupplier processing detail can update quantity and price', function (): void {
    $data = purchaseOrderSupplierFixture();
    $order = publishedPurchaseOrder($data, '22/PO/17052026/VP/2026', [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'VIALA PANGAN'],
    ]);

    $this->get(route('purchase-orders.show', $order->id))
        ->assertOk()
        ->assertSee('name="qty_actual[]"', false)
        ->assertSee('name="prices[]"', false);

    $this->patch(route('purchase-orders.suppliers.update', $order->id), [
        'suppliers' => ['VIALA PANGAN'],
        'qty_actual' => [5],
        'prices' => [7500],
    ])->assertRedirect(route('purchase-orders.index'));

    $item = $order->refresh()->items()->first();

    expect((float) $item->qty)->toBe(5.0)
        ->and((int) $item->price)->toBe(7500);
});

test('PurchaseOrderSupplier published resplit keeps original base date and year', function (): void {
    $data = purchaseOrderSupplierFixture();
    $order = publishedPurchaseOrder($data, '22/PO/17052026/DBM/2026', [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'DUNIA BUMBU MOJOKERTO'],
        ['stock' => $data['stocks']['telur'], 'supplier' => 'DUNIA BUMBU MOJOKERTO'],
    ]);

    $this->patch(route('purchase-orders.suppliers.update', $order->id), [
        'suppliers' => ['DUNIA BUMBU MOJOKERTO', 'NUTRIVA FOODS'],
    ])->assertRedirect(route('purchase-orders.index'));

    $numbers = PurchaseOrder::query()->orderBy('id')->pluck('number')->all();

    expect($numbers)->toBe([
        '22/PO/17052026/DBM/2026',
        '22/PO/17052026/NF/2026',
    ]);
});

test('PurchaseOrderSupplier published supplier change merges into existing supplier PO with same base number', function (): void {
    $data = purchaseOrderSupplierFixture();
    $nfOrder = publishedPurchaseOrder($data, '25/PO/18052026/NF/2026', [
        ['stock' => $data['stocks']['telur'], 'supplier' => 'NUTRIVA FOODS'],
    ]);
    $vpOrder = publishedPurchaseOrder($data, '25/PO/18052026/VP/2026', [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'VIALA PANGAN'],
    ]);

    $this->patch(route('purchase-orders.suppliers.update', $nfOrder->id), [
        'suppliers' => ['VIALA PANGAN'],
    ])->assertRedirect(route('purchase-orders.index'));

    expect(PurchaseOrder::query()->where('number', '25/PO/18052026/NF/2026')->exists())->toBeFalse()
        ->and($vpOrder->refresh()->items()->count())->toBe(2)
        ->and($vpOrder->items()->whereHas('supplier', fn ($query) => $query->where('name', 'VIALA PANGAN'))->count())->toBe(2);
});

test('PurchaseOrderSupplier published edit rejects items without supplier', function (): void {
    $data = purchaseOrderSupplierFixture();
    $order = publishedPurchaseOrder($data, '22/PO/17052026/DBM/2026', [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'DUNIA BUMBU MOJOKERTO'],
    ]);

    $this->patch(route('purchase-orders.update', $order->id), purchaseOrderPayload($data, [
        ['stock' => $data['stocks']['ayam'], 'supplier' => ''],
    ]))->assertSessionHasErrors('suppliers');

    expect($order->refresh()->number)->toBe('22/PO/17052026/DBM/2026')
        ->and($order->items()->first()->supplier->name)->toBe('DUNIA BUMBU MOJOKERTO');
});

test('PurchaseOrderSupplier invoiced and completed purchase orders cannot be edited', function (string $status): void {
    $data = purchaseOrderSupplierFixture();
    $order = publishedPurchaseOrder($data, '22/PO/17052026/DBM/2026', [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'DUNIA BUMBU MOJOKERTO'],
    ]);
    $order->update(['status' => $status]);

    $this->get(route('purchase-orders.edit', $order->id))
        ->assertRedirect(route('purchase-orders.index'))
        ->assertSessionHasErrors('purchase_order');

    $this->patch(route('purchase-orders.update', $order->id), purchaseOrderPayload($data, [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'VIALA PANGAN'],
    ]))
        ->assertRedirect(route('purchase-orders.index'))
        ->assertSessionHasErrors('purchase_order');

    $this->patch(route('purchase-orders.suppliers.update', $order->id), [
        'suppliers' => ['VIALA PANGAN'],
    ])
        ->assertRedirect(route('purchase-orders.index'))
        ->assertSessionHasErrors('purchase_order');

    expect($order->refresh()->number)->toBe('22/PO/17052026/DBM/2026')
        ->and($order->status)->toBe($status)
        ->and($order->items()->first()->supplier->name)->toBe('DUNIA BUMBU MOJOKERTO');
})->with(['COMPLETED', 'INVOICED']);

test('PurchaseOrderSupplier locked purchase orders hide edit controls', function (): void {
    $data = purchaseOrderSupplierFixture();
    $order = publishedPurchaseOrder($data, '22/PO/17052026/DBM/2026', [
        ['stock' => $data['stocks']['ayam'], 'supplier' => 'DUNIA BUMBU MOJOKERTO'],
    ]);
    $order->update(['status' => 'INVOICED']);

    $this->get(route('purchase-orders.index'))
        ->assertOk()
        ->assertDontSee(route('purchase-orders.edit', $order->id), false);

    $this->get(route('purchase-orders.show', $order->id))
        ->assertOk()
        ->assertDontSee('name="suppliers[]"', false)
        ->assertDontSee('Simpan Sekarang')
        ->assertDontSee('Simpan Penugasan', false);
});

/**
 * @return array{sppg: Sppg, stocks: array<string, StockItem>, suppliers: array<string, Supplier>}
 */
function purchaseOrderSupplierFixture(): array
{
    $sppg = Sppg::query()->create([
        'code' => 'M1101',
        'name' => 'SPPG Balongsari',
        'location' => 'Mojokerto',
    ]);

    $stocks = [
        'ayam' => StockItem::query()->create(['name' => 'AYAM FILET', 'unit' => 'KG']),
        'telur' => StockItem::query()->create(['name' => 'TELUR AYAM', 'unit' => 'BUTIR']),
    ];

    $suppliers = collect(['DUNIA BUMBU MOJOKERTO', 'NUTRIVA FOODS', 'VIALA PANGAN'])
        ->mapWithKeys(fn (string $name): array => [$name => Supplier::query()->create(['name' => $name])])
        ->all();

    return compact('sppg', 'stocks', 'suppliers');
}

function draftPurchaseOrder(array $data): PurchaseOrder
{
    return PurchaseOrder::query()->create([
        'number' => null,
        'date' => '2026-05-18',
        'created_by' => 'Admin Supplier',
        'sppg_id' => $data['sppg']->id,
        'status' => 'VALID',
    ]);
}

function publishedPurchaseOrder(array $data, string $number, array $items): PurchaseOrder
{
    $order = PurchaseOrder::query()->create([
        'number' => $number,
        'date' => '2026-05-18',
        'created_by' => 'Admin Supplier',
        'sppg_id' => $data['sppg']->id,
        'status' => 'PROCESSING',
    ]);

    foreach ($items as $item) {
        $order->items()->create([
            'stock_item_id' => $item['stock']->id,
            'supplier_id' => $data['suppliers'][$item['supplier']]->id,
            'name' => $item['stock']->name,
            'grade' => 'A',
            'qty' => 1,
            'unit' => $item['stock']->unit,
            'price' => 1000,
        ]);
    }

    return $order;
}

function purchaseOrderPayload(array $data, array $items): array
{
    return [
        'date' => '2026-05-18',
        'created_by' => 'Admin Supplier',
        'sppg_code' => $data['sppg']->code,
        'droping_date' => null,
        'droping_time' => null,
        'items' => collect($items)->map(fn (array $item): array => [
            'stock_item_id' => $item['stock']->id,
            'name' => '',
            'grade' => 'A',
            'qty' => 1,
            'unit' => $item['stock']->unit,
            'price' => 1000,
            'supplier' => $item['supplier'],
            'request' => null,
        ])->all(),
    ];
}
