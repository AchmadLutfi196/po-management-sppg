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
