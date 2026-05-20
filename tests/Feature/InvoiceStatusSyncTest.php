<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withSession([
        'auth_user' => [
            'role' => 'ADMIN',
            'id' => 'admin',
            'name' => 'Admin Supplier',
        ],
    ]);
});

test('invoice status paid marks purchase order as completed', function (): void {
    [$order, $invoice] = invoiceStatusFixture('UNPAID');

    $this->patch(route('invoices.status.update', $order->id), [
        'invoice_no' => $invoice->number,
        'status' => 'PAID',
    ])->assertRedirect();

    expect($invoice->refresh()->status)->toBe('PAID')
        ->and($order->refresh()->status)->toBe('COMPLETED');
});

test('invoice status unpaid keeps purchase order as invoiced', function (): void {
    [$order, $invoice] = invoiceStatusFixture('PAID');

    $this->patch(route('invoices.status.update', $order->id), [
        'invoice_no' => $invoice->number,
        'status' => 'UNPAID',
    ])->assertRedirect();

    expect($invoice->refresh()->status)->toBe('UNPAID')
        ->and($order->refresh()->status)->toBe('INVOICED');
});

test('invoice create shows item unit beside quantity and price inputs', function (): void {
    [$order] = invoiceStatusFixture('UNPAID');
    $supplier = Supplier::query()->where('name', 'VIALA PANGAN')->firstOrFail();

    $order->items()->create([
        'supplier_id' => $supplier->id,
        'name' => 'BAWANG MERAH',
        'qty' => 12,
        'unit' => 'KG',
        'grade' => 'A',
        'price' => 15000,
    ]);

    $this->get(route('invoices.create', ['id' => $order->id, 'supplier' => $supplier->name]))
        ->assertOk()
        ->assertSee('value="KG"', false)
        ->assertSee('value="12"', false)
        ->assertSee('value="15.000"', false);
});

test('invoice create preview does not open a new tab', function (): void {
    $order = invoiceBankInfoOrder('VIALA PANGAN');

    $this->get(route('invoices.create', ['id' => $order->id, 'supplier' => 'VIALA PANGAN']))
        ->assertOk()
        ->assertSeeText('Preview PDF')
        ->assertSee('formmethod="GET"', false)
        ->assertDontSee('formtarget="_blank"', false);
});

test('invoice history shows item details', function (): void {
    [$order, $invoice] = invoiceStatusFixture('PAID');
    $supplier = Supplier::query()->where('name', 'VIALA PANGAN')->firstOrFail();

    $invoice->items()->create([
        'supplier_id' => $supplier->id,
        'name' => 'BAWANG MERAH',
        'qty' => 12,
        'unit' => 'KG',
        'price' => 15000,
        'subtotal' => 180000,
    ]);

    $this->get(route('invoices.index', ['tab' => 'history']))
        ->assertOk()
        ->assertSeeText('Rincian Barang')
        ->assertSeeText('BAWANG MERAH')
        ->assertSeeText('Ref: '.$order->number)
        ->assertSeeText('1 item')
        ->assertSeeText('Rp 500.000');
});

test('invoice history marks manually added items outside purchase order', function (): void {
    [$order, $invoice] = invoiceStatusFixture('PAID');

    $invoice->items()->create([
        'purchase_order_item_id' => null,
        'name' => 'PLASTIK PACKING',
        'qty' => 2,
        'unit' => 'PCS',
        'price' => 5000,
        'subtotal' => 10000,
    ]);

    $this->get(route('invoices.index', ['tab' => 'history']))
        ->assertOk()
        ->assertSeeText('PLASTIK PACKING')
        ->assertSeeText('Di luar PO');
});

test('invoice history can be searched and filtered', function (): void {
    [$order, $invoice] = invoiceStatusFixture('PAID');
    $invoice->items()->create([
        'name' => 'BAWANG MERAH',
        'qty' => 12,
        'unit' => 'KG',
        'price' => 15000,
        'subtotal' => 180000,
    ]);

    $sppg = Sppg::query()->create([
        'code' => 'M1102',
        'name' => 'SPPG Pulo',
    ]);
    $supplier = Supplier::query()->create(['name' => 'DUNIA BUMBU MOJOKERTO']);
    $matchingOrder = PurchaseOrder::query()->create([
        'number' => '15/PO/19052026/DBM/2026',
        'date' => '2026-05-19',
        'created_by' => 'Admin Supplier',
        'sppg_id' => $sppg->id,
        'status' => 'INVOICED',
    ]);
    $matchingInvoice = Invoice::query()->create([
        'purchase_order_id' => $matchingOrder->id,
        'supplier_id' => $supplier->id,
        'number' => 'INV/DUNIA/FILTER',
        'date' => '2026-05-20',
        'supplier_name' => $supplier->name,
        'status' => 'UNPAID',
        'total_amount' => 150000,
    ]);
    $matchingInvoice->items()->create([
        'name' => 'PISANG',
        'qty' => 15,
        'unit' => 'PCS',
        'price' => 10000,
        'subtotal' => 150000,
    ]);

    $this->get(route('invoices.index', [
        'tab' => 'history',
        'search' => 'pisang',
        'status' => 'UNPAID',
        'supplier' => 'DUNIA BUMBU MOJOKERTO',
        'sppg' => 'M1102',
        'date_from' => '2026-05-20',
        'date_to' => '2026-05-20',
    ]))
        ->assertOk()
        ->assertSeeText('INV/DUNIA/FILTER')
        ->assertSeeText('PISANG')
        ->assertSeeText('SPPG Pulo')
        ->assertSeeText('Rp 150.000')
        ->assertDontSeeText($invoice->number)
        ->assertDontSeeText($order->number)
        ->assertDontSeeText('BAWANG MERAH');
});

test('creating invoice updates purchase order item quantity and price', function (): void {
    $order = invoiceBankInfoOrder('VIALA PANGAN');
    $item = $order->items()->firstOrFail();
    $originalQty = (float) $item->qty;
    $originalPrice = $item->price;

    $this->post(route('invoices.store', $order->id), [
        'supplier' => 'VIALA PANGAN',
        'invoice_no' => 'INV/VIALA/SYNC-1',
        'invoice_date' => '2026-05-19',
        'items' => [
            [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'qty' => 200,
                'price' => 20000,
            ],
        ],
    ])->assertRedirect(route('invoices.index', ['tab' => 'history']));

    $item->refresh();

    // Qty & price di PO juga diupdate mengikuti invoice jika item berasal dari PO
    expect((float) $item->qty)->toBe(200.0)
        ->and($item->price)->toBe(20000)
        ->and($item->is_invoiced)->toBeTrue();
});

test('creating invoice accepts formatted price input', function (): void {
    $order = invoiceBankInfoOrder('VIALA PANGAN');
    $item = $order->items()->firstOrFail();

    $this->post(route('invoices.store', $order->id), [
        'supplier' => 'VIALA PANGAN',
        'invoice_no' => 'INV/VIALA/FORMAT-1',
        'invoice_date' => '2026-05-19',
        'items' => [
            [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'qty' => 150,
                'price' => '12.500',
            ],
        ],
    ])->assertRedirect(route('invoices.index', ['tab' => 'history']));

    $invoice = Invoice::query()->where('number', 'INV/VIALA/FORMAT-1')->firstOrFail();

    expect($invoice->total_amount)->toBe(1875000)
        ->and($invoice->items()->first()->price)->toBe(12500);
});

test('invoice preview marks manually added items outside purchase order', function (): void {
    [$order, $invoice] = invoiceStatusFixture('UNPAID');

    $invoice->items()->create([
        'purchase_order_item_id' => null,
        'name' => 'ONGKOS PACKING',
        'qty' => 1,
        'unit' => 'PCS',
        'price' => 25000,
        'subtotal' => 25000,
    ]);

    $this->get(route('invoices.preview', ['id' => $order->id, 'invoice' => $invoice->number, 'supplier' => $invoice->supplier_name]))
        ->assertOk()
        ->assertSeeText('ONGKOS PACKING')
        ->assertSeeText('Barang tidak termasuk dalam PO');
});

test('invoice create shows supplier bank accounts', function (string $supplierName, array $expectedTexts): void {
    $order = invoiceBankInfoOrder($supplierName);

    $response = $this->get(route('invoices.create', ['id' => $order->id, 'supplier' => $supplierName]))
        ->assertOk();

    foreach ($expectedTexts as $expectedText) {
        $response->assertSeeText($expectedText);
    }
})->with([
    'viala pangan' => [
        'VIALA PANGAN',
        ['AN. Arif Rakhman Hadi', 'MANDIRI: 1420015180150'],
    ],
    'nutriva foods' => [
        'NUTRIVA FOODS',
        ['AN. Arif Rakhman Hadi', 'MANDIRI: 1420015180150'],
    ],
    'dunia bumbu mojokerto' => [
        'DUNIA BUMBU MOJOKERTO',
        ['AN. Arif Rakhman Hadi', 'MANDIRI: 1420015180150'],
    ],
]);

test('invoice preview uses global bank account and supplier managing director', function (): void {
    $order = invoiceBankInfoOrder('VIALA PANGAN');

    $this->get(route('invoices.preview', ['id' => $order->id, 'supplier' => 'VIALA PANGAN']))
        ->assertOk()
        ->assertSeeText('AN. Arif Rakhman Hadi')
        ->assertSeeText('Dwi Silvia Anggraini')
        ->assertSeeText('MANDIRI')
        ->assertSeeText('1420015180150');
});

test('dunia bumbu invoice preview uses arif as managing director', function (): void {
    $order = invoiceBankInfoOrder('DUNIA BUMBU MOJOKERTO');

    $this->get(route('invoices.preview', ['id' => $order->id, 'supplier' => 'DUNIA BUMBU MOJOKERTO']))
        ->assertOk()
        ->assertSeeText('Arif Rakhman Hadi')
        ->assertDontSee('>Dunia Bumbu</div>', false);
});

test('invoice preview status badge colors follow payment status', function (string $status, string $label, string $color): void {
    [$order, $invoice] = invoiceStatusFixture($status);

    $this->get(route('invoices.preview', ['id' => $order->id, 'invoice' => $invoice->number, 'supplier' => $invoice->supplier_name]))
        ->assertOk()
        ->assertSeeText($label)
        ->assertSee("background-color: {$color}", false);
})->with([
    'unpaid is red' => ['UNPAID', 'Belum Bayar', '#dc2626'],
    'paid is green' => ['PAID', 'Lunas', '#16a34a'],
]);

/**
 * @return array{PurchaseOrder, Invoice}
 */
function invoiceStatusFixture(string $invoiceStatus): array
{
    $sppg = Sppg::query()->create([
        'code' => 'M1101',
        'name' => 'SPPG Balongsari',
    ]);

    $supplier = Supplier::query()->create(['name' => 'VIALA PANGAN']);

    $order = PurchaseOrder::query()->create([
        'number' => '12/PO/18052026/VP/2026',
        'date' => '2026-05-18',
        'created_by' => 'Admin Supplier',
        'sppg_id' => $sppg->id,
        'status' => 'INVOICED',
    ]);

    $invoice = Invoice::query()->create([
        'purchase_order_id' => $order->id,
        'supplier_id' => $supplier->id,
        'number' => 'INV/VIALA/123456',
        'date' => '2026-05-18',
        'supplier_name' => $supplier->name,
        'status' => $invoiceStatus,
        'total_amount' => 500000,
    ]);

    return [$order, $invoice];
}

function invoiceBankInfoOrder(string $supplierName): PurchaseOrder
{
    $sppg = Sppg::query()->create([
        'code' => 'M'.$supplierName,
        'name' => 'SPPG Balongsari',
    ]);

    $supplier = Supplier::query()->create(['name' => $supplierName]);

    $order = PurchaseOrder::query()->create([
        'number' => '12/PO/18052026/VP/2026',
        'date' => '2026-05-18',
        'created_by' => 'Admin Supplier',
        'sppg_id' => $sppg->id,
        'status' => 'INVOICED',
    ]);

    $order->items()->create([
        'supplier_id' => $supplier->id,
        'name' => 'BAWANG MERAH',
        'qty' => 12,
        'unit' => 'KG',
        'grade' => 'A',
        'price' => 15000,
    ]);

    return $order;
}
