<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\StockItem;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

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

test('purchase order menu paginates after ten rows', function (): void {
    [$sppg, $supplier] = menuPaginationBaseFixture();

    createMenuPaginationOrders($sppg, $supplier, 11);

    $response = $this->get(route('purchase-orders.index'));

    $response->assertOk();
    expect($response->viewData('orders'))->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($response->viewData('orders')->count())->toBe(10)
        ->and($response->viewData('orders')->total())->toBe(11);
});

test('surat jalan menu paginates after ten rows', function (): void {
    [$sppg, $supplier] = menuPaginationBaseFixture();

    createMenuPaginationOrders($sppg, $supplier, 11, 'PROCESSING');

    $response = $this->get(route('surat-jalan.index'));

    $response->assertOk();
    expect($response->viewData('orders'))->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($response->viewData('orders')->count())->toBe(10)
        ->and($response->viewData('orders')->total())->toBe(11);
});

test('invoice menu paginates history after ten rows', function (): void {
    [$sppg, $supplier] = menuPaginationBaseFixture();
    $orders = createMenuPaginationOrders($sppg, $supplier, 11, 'INVOICED');

    foreach ($orders as $index => $order) {
        Invoice::query()->create([
            'purchase_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'number' => 'INV/PAGE/'.($index + 1),
            'date' => '2026-05-18',
            'supplier_name' => $supplier->name,
            'status' => 'UNPAID',
            'total_amount' => 1000,
        ]);
    }

    $response = $this->get(route('invoices.index', ['tab' => 'history']));

    $response->assertOk();
    expect($response->viewData('historyInvoices'))->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($response->viewData('historyInvoices')->count())->toBe(10)
        ->and($response->viewData('historyInvoices')->total())->toBe(11);
});

test('master stock menu paginates after ten rows', function (): void {
    foreach (range(1, 11) as $index) {
        StockItem::query()->create([
            'name' => 'ITEM '.$index,
            'unit' => 'KG',
        ]);
    }

    $response = $this->get(route('master-stok.index'));

    $response->assertOk();
    expect($response->viewData('items'))->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($response->viewData('items')->count())->toBe(10)
        ->and($response->viewData('items')->total())->toBe(11);
});

/**
 * @return array{Sppg, Supplier}
 */
function menuPaginationBaseFixture(): array
{
    $sppg = Sppg::query()->create([
        'code' => 'M1101',
        'name' => 'SPPG Balongsari',
    ]);

    $supplier = Supplier::query()->create(['name' => 'VIALA PANGAN']);

    return [$sppg, $supplier];
}

/**
 * @return Collection<int, PurchaseOrder>
 */
function createMenuPaginationOrders(Sppg $sppg, Supplier $supplier, int $count, string $status = 'PROCESSING')
{
    return collect(range(1, $count))->map(function (int $index) use ($sppg, $supplier, $status): PurchaseOrder {
        $order = PurchaseOrder::query()->create([
            'number' => $index.'/PO/18052026/VP/2026',
            'date' => '2026-05-18',
            'created_by' => 'Admin Supplier',
            'sppg_id' => $sppg->id,
            'status' => $status,
        ]);

        $order->items()->create([
            'supplier_id' => $supplier->id,
            'name' => 'AYAM FILET '.$index,
            'qty' => 1,
            'unit' => 'KG',
            'grade' => 'A',
            'price' => 1000,
        ]);

        return $order;
    });
}
