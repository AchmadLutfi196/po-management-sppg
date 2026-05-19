<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sppg dashboard only counts invoices and estimates for its own orders', function (): void {
    $pulo = Sppg::query()->create([
        'code' => 'M1102',
        'name' => 'SPPG-Pulo',
    ]);
    $balongsari = Sppg::query()->create([
        'code' => 'M1101',
        'name' => 'SPPG-Balongsari',
    ]);
    $supplier = Supplier::query()->create(['name' => 'DUNIA BUMBU MOJOKERTO']);

    $otherOrder = PurchaseOrder::query()->create([
        'number' => '1/PO/19052026/DBM/2026',
        'date' => '2026-05-19',
        'created_by' => 'Admin Supplier',
        'sppg_id' => $balongsari->id,
        'status' => 'INVOICED',
    ]);
    $otherOrder->items()->create([
        'supplier_id' => $supplier->id,
        'name' => 'AYAM FILET',
        'qty' => 10,
        'unit' => 'KG',
        'grade' => 'A',
        'price' => 150000,
        'is_invoiced' => false,
    ]);
    Invoice::query()->create([
        'purchase_order_id' => $otherOrder->id,
        'supplier_id' => $supplier->id,
        'number' => 'INV/DUNIA/190526',
        'date' => '2026-05-19',
        'supplier_name' => $supplier->name,
        'status' => 'UNPAID',
        'total_amount' => 4602000,
    ]);

    $response = $this
        ->withSession([
            'auth_user' => [
                'role' => 'SPPG',
                'id' => $pulo->code,
                'name' => $pulo->name,
            ],
        ])
        ->get(route('dashboard'));

    $response->assertOk();

    $stats = $response->viewData('stats');

    expect($stats['total_po'])->toBe(0)
        ->and($stats['estimated_unbilled'])->toBe(0)
        ->and($stats['invoice_unpaid'])->toBe(0)
        ->and($stats['unpaid'])->toBe(0);
});
