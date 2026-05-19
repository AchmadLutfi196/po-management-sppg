<?php

use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\StockItem;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

afterEach(function (): void {
    Carbon::setTestNow();
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

test('surat jalan menu filters by sppg', function (): void {
    [$sppg, $supplier] = menuPaginationBaseFixture();
    $otherSppg = Sppg::query()->create([
        'code' => 'M2201',
        'name' => 'SPPG Jetis',
    ]);

    createMenuPaginationOrder($sppg, $supplier, 'PO-FILTER-SPPG-1', '2026-05-19');
    createMenuPaginationOrder($otherSppg, $supplier, 'PO-FILTER-SPPG-2', '2026-05-19');

    $response = $this->get(route('surat-jalan.index', ['sppg' => $otherSppg->code]));

    $response->assertOk()
        ->assertSeeText('PO-FILTER-SPPG-2')
        ->assertDontSeeText('PO-FILTER-SPPG-1');
});

test('surat jalan menu filters by today', function (): void {
    Carbon::setTestNow('2026-05-19 08:00:00');
    [$sppg, $supplier] = menuPaginationBaseFixture();

    createMenuPaginationOrder($sppg, $supplier, 'PO-TODAY-1', '2026-05-19');
    createMenuPaginationOrder($sppg, $supplier, 'PO-TODAY-2', '2026-05-18');

    $response = $this->get(route('surat-jalan.index', ['date_filter' => 'today']));

    $response->assertOk()
        ->assertSeeText('PO-TODAY-1')
        ->assertDontSeeText('PO-TODAY-2');
});

test('surat jalan menu filters by date range', function (): void {
    [$sppg, $supplier] = menuPaginationBaseFixture();

    createMenuPaginationOrder($sppg, $supplier, 'PO-RANGE-1', '2026-05-17');
    createMenuPaginationOrder($sppg, $supplier, 'PO-RANGE-2', '2026-05-20');
    createMenuPaginationOrder($sppg, $supplier, 'PO-RANGE-3', '2026-05-25');

    $response = $this->get(route('surat-jalan.index', [
        'date_filter' => 'range',
        'date_from' => '2026-05-19',
        'date_to' => '2026-05-21',
    ]));

    $response->assertOk()
        ->assertSeeText('PO-RANGE-2')
        ->assertDontSeeText('PO-RANGE-1')
        ->assertDontSeeText('PO-RANGE-3');
});

test('surat jalan menu applies date range when date inputs are filled without selecting range', function (): void {
    [$sppg, $supplier] = menuPaginationBaseFixture();

    createMenuPaginationOrder($sppg, $supplier, 'PO-DATE-INPUT-1', '2026-05-15');
    createMenuPaginationOrder($sppg, $supplier, 'PO-DATE-INPUT-2', '2026-05-18');

    $response = $this->get(route('surat-jalan.index', [
        'date_from' => '2026-05-14',
        'date_to' => '2026-05-16',
    ]));

    $response->assertOk()
        ->assertSeeText('PO-DATE-INPUT-1')
        ->assertDontSeeText('PO-DATE-INPUT-2');

    expect($response->viewData('filters')['date_filter'])->toBe('range');
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

function createMenuPaginationOrder(Sppg $sppg, Supplier $supplier, string $number, string $deliveryDate): PurchaseOrder
{
    $order = PurchaseOrder::query()->create([
        'number' => $number,
        'date' => '2026-05-18',
        'created_by' => 'Admin Supplier',
        'sppg_id' => $sppg->id,
        'droping_date' => $deliveryDate,
        'status' => 'INVOICED',
    ]);

    $order->items()->create([
        'supplier_id' => $supplier->id,
        'name' => 'AYAM FILET '.$number,
        'qty' => 1,
        'unit' => 'KG',
        'grade' => 'A',
        'price' => 1000,
    ]);

    DeliveryNote::query()->create([
        'purchase_order_id' => $order->id,
        'number' => str_replace('PO', 'SJ', $number),
        'date' => $deliveryDate,
        'time' => '08:00',
        'driver' => 'Udin',
        'kepada' => $sppg->name,
        'kd_sppg' => $sppg->code,
        'nama_sppg' => $sppg->name,
        'pj_sppg' => $sppg->pic_name ?? '-',
        'whatsapp' => $sppg->whatsapp ?? '-',
        'notes' => '-',
        'has_photo' => false,
    ]);

    return $order;
}
