<?php

use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::query()->create([
        'name' => 'admin',
        'email' => 'admin@example.test',
        'password' => 'secret123',
    ]);

    $this->withSession([
        'auth_user' => [
            'role' => 'ADMIN',
            'id' => (string) $this->admin->id,
            'name' => $this->admin->name,
        ],
    ]);
});

test('admin can manage sppg records', function (): void {
    $this->get(route('master-sppg.index'))
        ->assertOk()
        ->assertSeeText('Master SPPG');

    $this->post(route('master-sppg.store'), [
        'code' => 'm2201',
        'name' => 'SPPG Gedeg',
        'location' => 'Mojokerto',
        'pic_name' => 'Ibu Sari',
        'whatsapp' => '08123456789',
    ])->assertRedirect(route('master-sppg.index'));

    $sppg = Sppg::query()->where('code', 'M2201')->firstOrFail();

    expect($sppg->name)->toBe('SPPG Gedeg')
        ->and($sppg->pic_name)->toBe('Ibu Sari');

    $this->patch(route('master-sppg.update', $sppg->id), [
        'code' => 'M2202',
        'name' => 'SPPG Jetis',
        'location' => 'Mojokerto Utara',
        'pic_name' => 'Pak Budi',
        'whatsapp' => '08990000111',
    ])->assertRedirect(route('master-sppg.index'));

    expect($sppg->refresh()->code)->toBe('M2202')
        ->and($sppg->name)->toBe('SPPG Jetis');

    $this->delete(route('master-sppg.destroy', $sppg->id))
        ->assertRedirect(route('master-sppg.index'));

    expect(Sppg::query()->whereKey($sppg->id)->exists())->toBeFalse();
});

test('admin cannot delete sppg used by purchase orders', function (): void {
    $sppg = Sppg::query()->create([
        'code' => 'M1101',
        'name' => 'SPPG Balongsari',
    ]);

    PurchaseOrder::query()->create([
        'number' => '1/PO/19052026/VP/2026',
        'date' => '2026-05-19',
        'created_by' => 'admin',
        'sppg_id' => $sppg->id,
        'status' => 'PROCESSING',
    ]);

    $this->delete(route('master-sppg.destroy', $sppg->id))
        ->assertRedirect(route('master-sppg.index'))
        ->assertSessionHasErrors('sppg');

    expect(Sppg::query()->whereKey($sppg->id)->exists())->toBeTrue();
});

test('admin can update profile username and password', function (): void {
    $this->patch(route('profile.update'), [
        'name' => 'superadmin',
        'password_current' => 'secret123',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertRedirect(route('profile.edit'));

    $this->admin->refresh();

    expect($this->admin->name)->toBe('superadmin')
        ->and(Hash::check('newpass123', $this->admin->password))->toBeTrue()
        ->and(session('auth_user.name'))->toBe('superadmin');
});

test('admin profile rejects wrong current password', function (): void {
    $this->patch(route('profile.update'), [
        'name' => 'superadmin',
        'password_current' => 'wrong-password',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertSessionHasErrors('password_current');

    expect(Hash::check('secret123', $this->admin->refresh()->password))->toBeTrue();
});

test('sppg role sees surat jalan as read only', function (): void {
    $order = readOnlyRoleOrder();

    DeliveryNote::query()->create([
        'purchase_order_id' => $order->id,
        'number' => '1/SJ/19052026/VP/2026',
        'date' => '2026-05-19',
        'driver' => 'Udin',
        'kepada' => 'SPPG Balongsari',
        'kd_sppg' => 'M1101',
        'nama_sppg' => 'SPPG Balongsari',
        'pj_sppg' => 'Datok',
        'whatsapp' => '0894334444',
        'notes' => 'Aman',
        'item_photos' => [],
        'has_photo' => false,
    ]);

    $this->withSession([
        'auth_user' => [
            'role' => 'SPPG',
            'id' => 'M1101',
            'name' => 'SPPG Balongsari',
        ],
    ]);

    $this->get(route('surat-jalan.index'))
        ->assertOk()
        ->assertSeeText('Lihat')
        ->assertDontSeeText('Lihat / Edit')
        ->assertDontSeeText('Proses Kirim');

    $this->get(route('surat-jalan.show', $order->id))
        ->assertOk()
        ->assertSeeText('Cetak PDF')
        ->assertSee(route('surat-jalan.preview', $order->id), false)
        ->assertDontSeeText('Simpan & Terbitkan Surat Jalan')
        ->assertDontSeeText('Hapus & Ganti Foto')
        ->assertDontSeeText('Ambil / Pilih Foto');
});

test('sppg role sees invoice menu with print but without create or edit controls', function (): void {
    $order = readOnlyRoleOrder();
    $supplier = Supplier::query()->where('name', 'VIALA PANGAN')->firstOrFail();

    $invoice = Invoice::query()->create([
        'purchase_order_id' => $order->id,
        'supplier_id' => $supplier->id,
        'number' => 'INV/VIALA/123456',
        'date' => '2026-05-19',
        'supplier_name' => $supplier->name,
        'status' => 'UNPAID',
        'total_amount' => 100000,
    ]);

    $invoice->items()->create([
        'name' => 'BAWANG MERAH',
        'qty' => 5,
        'unit' => 'KG',
        'price' => 20000,
        'subtotal' => 100000,
    ]);

    $this->withSession([
        'auth_user' => [
            'role' => 'SPPG',
            'id' => 'M1101',
            'name' => 'SPPG Balongsari',
        ],
    ]);

    $this->get(route('invoices.index'))
        ->assertOk();

    $this->get(route('invoices.index', ['tab' => 'history']))
        ->assertOk()
        ->assertSeeText('Cetak')
        ->assertSee('/invoices/'.$order->id.'/preview', false)
        ->assertSee('invoice=INV%2FVIALA%2F123456', false)
        ->assertSee('supplier=VIALA%20PANGAN', false)
        ->assertDontSee('name="status"', false);

    $this->get(route('invoices.preview', ['id' => $order->id, 'invoice' => $invoice->number, 'supplier' => $supplier->name]))
        ->assertOk()
        ->assertSeeText($invoice->number);
});

test('sppg role can see po create shortcut on po page without export action', function (): void {
    $this->withSession([
        'auth_user' => [
            'role' => 'SPPG',
            'id' => 'M1101',
            'name' => 'SPPG Balongsari',
        ],
    ]);

    $this->get(route('purchase-orders.index'))
        ->assertOk()
        ->assertSeeText('PO Baru')
        ->assertDontSeeText('Ekspor');

    $this->get(route('surat-jalan.index'))
        ->assertOk()
        ->assertDontSeeText('PO Baru')
        ->assertDontSeeText('Ekspor');
});

function readOnlyRoleOrder(): PurchaseOrder
{
    $sppg = Sppg::query()->create([
        'code' => 'M1101',
        'name' => 'SPPG Balongsari',
        'location' => 'Mojokerto',
    ]);

    $supplier = Supplier::query()->create(['name' => 'VIALA PANGAN']);
    $stock = StockItem::query()->create(['name' => 'BAWANG MERAH', 'unit' => 'KG']);

    $order = PurchaseOrder::query()->create([
        'number' => '1/PO/19052026/VP/2026',
        'date' => '2026-05-19',
        'created_by' => 'admin',
        'sppg_id' => $sppg->id,
        'status' => 'INVOICED',
    ]);

    $order->items()->create([
        'stock_item_id' => $stock->id,
        'supplier_id' => $supplier->id,
        'name' => $stock->name,
        'qty' => 5,
        'unit' => $stock->unit,
        'grade' => 'A',
        'price' => 20000,
    ]);

    return $order;
}
