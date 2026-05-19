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
        ->assertSeeText('Qty Tagihan (KG)')
        ->assertSeeText('Harga per KG');
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
        ->assertSeeText('12 KG x Rp 15.000')
        ->assertSeeText('Rp 180.000');
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
        ['AN. Dwi Silvia Anggraini', 'BCA: 6140564859', 'BRI: 785601005827536', 'Bank JATIM: 0163203189'],
    ],
    'nutriva foods' => [
        'NUTRIVA FOODS',
        ['AN. Dessy Istuning Tiyas', 'MANDIRI: 1420026949973'],
    ],
    'dunia bumbu mojokerto' => [
        'DUNIA BUMBU MOJOKERTO',
        ['AN. Arif Rakhman Hadi', 'MANDIRI: 1420015180150'],
    ],
]);

test('invoice preview signature uses supplier account holder name', function (): void {
    $order = invoiceBankInfoOrder('VIALA PANGAN');

    $this->get(route('invoices.preview', ['id' => $order->id, 'supplier' => 'VIALA PANGAN']))
        ->assertOk()
        ->assertSeeText('Dwi Silvia Anggraini')
        ->assertDontSeeText('ARIF RAKHMAN HADI');
});

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
