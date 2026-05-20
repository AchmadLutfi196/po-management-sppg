<?php

namespace Database\Seeders;

use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // === Users ===
        User::query()->updateOrCreate(
            ['email' => 'admin@supplier.local'],
            [
                'name' => 'admin',
                'password' => 'admin123',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'operator@supplier.local'],
            [
                'name' => 'operator',
                'password' => 'operator123',
            ],
        );

        // === SPPG Units ===
        $sppgs = [
            ['code' => 'M1101', 'name' => 'SPPG-Balongsari', 'location' => 'Mojokerto', 'pic_name' => 'Datok', 'whatsapp' => '0894334444'],
            ['code' => 'M1102', 'name' => 'SPPG-Trowulan', 'location' => 'Trowulan', 'pic_name' => 'Budi Santoso', 'whatsapp' => '0812345678'],
            ['code' => 'M1103', 'name' => 'SPPG-Jetis', 'location' => 'Jetis', 'pic_name' => 'Rina Wati', 'whatsapp' => '0856789012'],
        ];

        foreach ($sppgs as $sppgData) {
            Sppg::query()->updateOrCreate(['code' => $sppgData['code']], $sppgData);
        }

        // === Suppliers ===
        $suppliers = [
            [
                'name' => 'DUNIA BUMBU MOJOKERTO',
                'address' => 'GPM Bypass B1 No 4 Kota Mojokerto',
                'phone' => '0321-123456',
                'logo_path' => 'logo-duniabumbu.jpeg',
                'stamp_path' => 'stamp-duniabumbu.jpeg',
                'theme_color' => '#16a34a',
                'bank_name' => 'MANDIRI',
                'bank_account_name' => 'Arif Rakhman Hadi',
                'bank_account_number' => '1420015180150',
            ],
            [
                'name' => 'NUTRIVA FOODS',
                'address' => '01/01 Pesanan Bicak Trowulan',
                'phone' => '0321-654321',
                'logo_path' => 'logo-nutrifa.jpeg',
                'stamp_path' => 'stamp-nutriva.jpeg',
                'theme_color' => '#ea580c',
                'bank_name' => 'MANDIRI',
                'bank_account_name' => 'Dessy Istuning Tiyas',
                'bank_account_number' => '1420026949973',
            ],
            [
                'name' => 'VIALA PANGAN',
                'address' => 'Perum Graha Majapahit Jl Village Ave 89',
                'phone' => '0321-789012',
                'logo_path' => 'logo-viala.jpeg',
                'stamp_path' => 'stamp-viala.jpeg',
                'theme_color' => '#2563eb',
                'bank_name' => 'MANDIRI',
                'bank_account_name' => 'Dwi Silvia Anggraini',
                'bank_account_number' => '1420026949999',
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::query()->updateOrCreate(['name' => $supplierData['name']], $supplierData);
        }

        // === Stock Items ===
        $stockItems = [
            ['name' => 'AYAM FILET', 'unit' => 'KG', 'category' => 'Protein'],
            ['name' => 'TELUR AYAM', 'unit' => 'BUTIR', 'category' => 'Protein'],
            ['name' => 'DAGING SAPI', 'unit' => 'KG', 'category' => 'Protein'],
            ['name' => 'IKAN DORI', 'unit' => 'KG', 'category' => 'Protein'],
            ['name' => 'UDANG', 'unit' => 'KG', 'category' => 'Protein'],
            ['name' => 'BAWANG MERAH', 'unit' => 'KG', 'category' => 'Bumbu'],
            ['name' => 'BAWANG PUTIH', 'unit' => 'KG', 'category' => 'Bumbu'],
            ['name' => 'CABAI MERAH', 'unit' => 'KG', 'category' => 'Bumbu'],
            ['name' => 'CABAI RAWIT', 'unit' => 'KG', 'category' => 'Bumbu'],
            ['name' => 'JAHE', 'unit' => 'KG', 'category' => 'Bumbu'],
            ['name' => 'KUNYIT', 'unit' => 'KG', 'category' => 'Bumbu'],
            ['name' => 'LENGKUAS', 'unit' => 'KG', 'category' => 'Bumbu'],
            ['name' => 'ROTI BURGER', 'unit' => 'PCS', 'category' => 'Roti'],
            ['name' => 'ROTI TAWAR', 'unit' => 'PCS', 'category' => 'Roti'],
            ['name' => 'MINYAK GORENG', 'unit' => 'LITER', 'category' => 'Minyak'],
            ['name' => 'BERAS', 'unit' => 'KG', 'category' => 'Pokok'],
            ['name' => 'GULA PASIR', 'unit' => 'KG', 'category' => 'Pokok'],
            ['name' => 'TEPUNG TERIGU', 'unit' => 'KG', 'category' => 'Pokok'],
            ['name' => 'WORTEL', 'unit' => 'KG', 'category' => 'Sayur'],
            ['name' => 'KENTANG', 'unit' => 'KG', 'category' => 'Sayur'],
        ];

        foreach ($stockItems as $stockItemData) {
            StockItem::query()->firstOrCreate(
                ['name' => $stockItemData['name'], 'unit' => $stockItemData['unit']],
                ['category' => $stockItemData['category'], 'status' => 'Aktif'],
            );
        }

        // === PO Sequence ===
        DB::table('po_sequences')->insertOrIgnore([
            'year' => 2026,
            'last_number' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // === Purchase Orders with Items ===
        $sppgBalongsari = Sppg::query()->where('code', 'M1101')->first();
        $sppgTrowulan = Sppg::query()->where('code', 'M1102')->first();
        $sppgJetis = Sppg::query()->where('code', 'M1103')->first();

        $duniaBumbu = Supplier::query()->where('name', 'DUNIA BUMBU MOJOKERTO')->first();
        $nutriva = Supplier::query()->where('name', 'NUTRIVA FOODS')->first();
        $viala = Supplier::query()->where('name', 'VIALA PANGAN')->first();

        $stockMap = StockItem::query()->pluck('id', 'name');

        // --- PO 1: COMPLETED with Delivery Note & Invoice ---
        $po1 = PurchaseOrder::query()->updateOrCreate(
            ['number' => '1/PO/15052026/DBM/2026'],
            [
                'date' => '2026-05-15',
                'created_by' => 'admin',
                'sppg_id' => $sppgBalongsari->id,
                'droping_date' => '2026-05-16',
                'droping_time' => '08:00',
                'status' => 'COMPLETED',
            ],
        );

        $po1Items = [
            ['stock_item_id' => $stockMap['AYAM FILET'], 'supplier_id' => $duniaBumbu->id, 'name' => 'AYAM FILET', 'qty' => 50, 'unit' => 'KG', 'grade' => 'A', 'price' => 32000, 'is_invoiced' => true],
            ['stock_item_id' => $stockMap['BAWANG MERAH'], 'supplier_id' => $duniaBumbu->id, 'name' => 'BAWANG MERAH', 'qty' => 20, 'unit' => 'KG', 'grade' => 'A', 'price' => 35000, 'is_invoiced' => true],
            ['stock_item_id' => $stockMap['CABAI MERAH'], 'supplier_id' => $duniaBumbu->id, 'name' => 'CABAI MERAH', 'qty' => 10, 'unit' => 'KG', 'grade' => 'A', 'price' => 45000, 'is_invoiced' => true],
            ['stock_item_id' => $stockMap['MINYAK GORENG'], 'supplier_id' => $duniaBumbu->id, 'name' => 'MINYAK GORENG', 'qty' => 30, 'unit' => 'LITER', 'grade' => 'A', 'price' => 18000, 'is_invoiced' => true],
        ];

        $po1->items()->delete();
        foreach ($po1Items as $item) {
            $po1->items()->create($item);
        }

        // Delivery Note for PO1
        DeliveryNote::query()->updateOrCreate(
            ['purchase_order_id' => $po1->id],
            [
                'number' => '1/SJ/15052026/DBM/2026',
                'date' => '2026-05-16',
                'driver' => 'Pak Joko',
                'kepada' => 'SPPG-Balongsari',
                'kd_sppg' => 'M1101',
                'nama_sppg' => 'SPPG-Balongsari',
                'pj_sppg' => 'Datok',
                'whatsapp' => '0894334444',
                'notes' => 'Barang diterima lengkap',
                'has_photo' => true,
            ],
        );

        // Invoice for PO1
        $invoice1 = Invoice::query()->updateOrCreate(
            ['number' => 'INV/DUNIA/150526001'],
            [
                'purchase_order_id' => $po1->id,
                'supplier_id' => $duniaBumbu->id,
                'date' => '2026-05-16',
                'supplier_name' => 'DUNIA BUMBU MOJOKERTO',
                'status' => 'PAID',
                'total_amount' => (50 * 32000) + (20 * 35000) + (10 * 45000) + (30 * 18000),
            ],
        );

        $invoice1->items()->delete();
        $po1ItemModels = $po1->items()->get();
        foreach ($po1ItemModels as $poItem) {
            $invoice1->items()->create([
                'purchase_order_item_id' => $poItem->id,
                'name' => $poItem->name,
                'qty' => $poItem->qty,
                'unit' => $poItem->unit,
                'price' => $poItem->price,
                'subtotal' => $poItem->qty * $poItem->price,
            ]);
        }

        // --- PO 2: PROCESSING (Nutriva) ---
        $po2 = PurchaseOrder::query()->updateOrCreate(
            ['number' => '2/PO/16052026/NF/2026'],
            [
                'date' => '2026-05-16',
                'created_by' => 'admin',
                'sppg_id' => $sppgTrowulan->id,
                'droping_date' => '2026-05-17',
                'droping_time' => '09:00',
                'status' => 'PROCESSING',
            ],
        );

        $po2Items = [
            ['stock_item_id' => $stockMap['DAGING SAPI'], 'supplier_id' => $nutriva->id, 'name' => 'DAGING SAPI', 'qty' => 25, 'unit' => 'KG', 'grade' => 'A', 'price' => 130000],
            ['stock_item_id' => $stockMap['UDANG'], 'supplier_id' => $nutriva->id, 'name' => 'UDANG', 'qty' => 15, 'unit' => 'KG', 'grade' => 'A', 'price' => 85000],
            ['stock_item_id' => $stockMap['IKAN DORI'], 'supplier_id' => $nutriva->id, 'name' => 'IKAN DORI', 'qty' => 20, 'unit' => 'KG', 'grade' => 'B', 'price' => 55000],
            ['stock_item_id' => $stockMap['TELUR AYAM'], 'supplier_id' => $nutriva->id, 'name' => 'TELUR AYAM', 'qty' => 500, 'unit' => 'BUTIR', 'grade' => 'A', 'price' => 2500],
        ];

        $po2->items()->delete();
        foreach ($po2Items as $item) {
            $po2->items()->create($item);
        }

        // --- PO 3: PROCESSING (Viala) with Delivery Note ---
        $po3 = PurchaseOrder::query()->updateOrCreate(
            ['number' => '3/PO/17052026/VP/2026'],
            [
                'date' => '2026-05-17',
                'created_by' => 'operator',
                'sppg_id' => $sppgJetis->id,
                'droping_date' => '2026-05-18',
                'droping_time' => '07:30',
                'status' => 'PROCESSING',
            ],
        );

        $po3Items = [
            ['stock_item_id' => $stockMap['BERAS'], 'supplier_id' => $viala->id, 'name' => 'BERAS', 'qty' => 100, 'unit' => 'KG', 'grade' => 'A', 'price' => 14000],
            ['stock_item_id' => $stockMap['GULA PASIR'], 'supplier_id' => $viala->id, 'name' => 'GULA PASIR', 'qty' => 50, 'unit' => 'KG', 'grade' => 'A', 'price' => 16000],
            ['stock_item_id' => $stockMap['TEPUNG TERIGU'], 'supplier_id' => $viala->id, 'name' => 'TEPUNG TERIGU', 'qty' => 30, 'unit' => 'KG', 'grade' => 'A', 'price' => 12000],
            ['stock_item_id' => $stockMap['MINYAK GORENG'], 'supplier_id' => $viala->id, 'name' => 'MINYAK GORENG', 'qty' => 40, 'unit' => 'LITER', 'grade' => 'A', 'price' => 18000],
            ['stock_item_id' => $stockMap['WORTEL'], 'supplier_id' => $viala->id, 'name' => 'WORTEL', 'qty' => 15, 'unit' => 'KG', 'grade' => 'A', 'price' => 12000],
        ];

        $po3->items()->delete();
        foreach ($po3Items as $item) {
            $po3->items()->create($item);
        }

        DeliveryNote::query()->updateOrCreate(
            ['purchase_order_id' => $po3->id],
            [
                'number' => '3/SJ/17052026/VP/2026',
                'date' => '2026-05-18',
                'driver' => 'Pak Slamet',
                'kepada' => 'SPPG-Jetis',
                'kd_sppg' => 'M1103',
                'nama_sppg' => 'SPPG-Jetis',
                'pj_sppg' => 'Rina Wati',
                'whatsapp' => '0856789012',
                'notes' => 'Kirim pagi sebelum jam 8',
                'has_photo' => false,
            ],
        );

        // --- PO 4: COMPLETED (Dunia Bumbu) with Invoice UNPAID ---
        $po4 = PurchaseOrder::query()->updateOrCreate(
            ['number' => '4/PO/18052026/DBM/2026'],
            [
                'date' => '2026-05-18',
                'created_by' => 'admin',
                'sppg_id' => $sppgBalongsari->id,
                'droping_date' => '2026-05-19',
                'droping_time' => '10:00',
                'status' => 'COMPLETED',
            ],
        );

        $po4Items = [
            ['stock_item_id' => $stockMap['BAWANG PUTIH'], 'supplier_id' => $duniaBumbu->id, 'name' => 'BAWANG PUTIH', 'qty' => 25, 'unit' => 'KG', 'grade' => 'A', 'price' => 40000, 'is_invoiced' => true],
            ['stock_item_id' => $stockMap['JAHE'], 'supplier_id' => $duniaBumbu->id, 'name' => 'JAHE', 'qty' => 10, 'unit' => 'KG', 'grade' => 'A', 'price' => 28000, 'is_invoiced' => true],
            ['stock_item_id' => $stockMap['KUNYIT'], 'supplier_id' => $duniaBumbu->id, 'name' => 'KUNYIT', 'qty' => 8, 'unit' => 'KG', 'grade' => 'A', 'price' => 25000, 'is_invoiced' => true],
            ['stock_item_id' => $stockMap['LENGKUAS'], 'supplier_id' => $duniaBumbu->id, 'name' => 'LENGKUAS', 'qty' => 12, 'unit' => 'KG', 'grade' => 'A', 'price' => 15000, 'is_invoiced' => true],
            ['stock_item_id' => $stockMap['CABAI RAWIT'], 'supplier_id' => $duniaBumbu->id, 'name' => 'CABAI RAWIT', 'qty' => 5, 'unit' => 'KG', 'grade' => 'A', 'price' => 55000, 'is_invoiced' => true],
        ];

        $po4->items()->delete();
        foreach ($po4Items as $item) {
            $po4->items()->create($item);
        }

        DeliveryNote::query()->updateOrCreate(
            ['purchase_order_id' => $po4->id],
            [
                'number' => '4/SJ/18052026/DBM/2026',
                'date' => '2026-05-19',
                'driver' => 'Pak Agus',
                'kepada' => 'SPPG-Balongsari',
                'kd_sppg' => 'M1101',
                'nama_sppg' => 'SPPG-Balongsari',
                'pj_sppg' => 'Datok',
                'whatsapp' => '0894334444',
                'notes' => null,
                'has_photo' => true,
            ],
        );

        $invoice4 = Invoice::query()->updateOrCreate(
            ['number' => 'INV/DUNIA/180526002'],
            [
                'purchase_order_id' => $po4->id,
                'supplier_id' => $duniaBumbu->id,
                'date' => '2026-05-19',
                'supplier_name' => 'DUNIA BUMBU MOJOKERTO',
                'status' => 'UNPAID',
                'total_amount' => (25 * 40000) + (10 * 28000) + (8 * 25000) + (12 * 15000) + (5 * 55000),
            ],
        );

        $invoice4->items()->delete();
        $po4ItemModels = $po4->items()->get();
        foreach ($po4ItemModels as $poItem) {
            $invoice4->items()->create([
                'purchase_order_item_id' => $poItem->id,
                'name' => $poItem->name,
                'qty' => $poItem->qty,
                'unit' => $poItem->unit,
                'price' => $poItem->price,
                'subtotal' => $poItem->qty * $poItem->price,
            ]);
        }

        // --- PO 5: VALID (belum ada supplier assignment) ---
        $po5 = PurchaseOrder::query()->updateOrCreate(
            ['number' => null],
            [
                'date' => '2026-05-19',
                'created_by' => 'operator',
                'sppg_id' => $sppgTrowulan->id,
                'droping_date' => '2026-05-20',
                'droping_time' => '08:30',
                'status' => 'VALID',
            ],
        );

        $po5Items = [
            ['stock_item_id' => $stockMap['KENTANG'], 'supplier_id' => null, 'name' => 'KENTANG', 'qty' => 30, 'unit' => 'KG', 'grade' => 'A', 'price' => 15000],
            ['stock_item_id' => $stockMap['WORTEL'], 'supplier_id' => null, 'name' => 'WORTEL', 'qty' => 20, 'unit' => 'KG', 'grade' => 'A', 'price' => 12000],
            ['stock_item_id' => $stockMap['ROTI BURGER'], 'supplier_id' => null, 'name' => 'ROTI BURGER', 'qty' => 200, 'unit' => 'PCS', 'grade' => 'A', 'price' => 3500],
            ['stock_item_id' => $stockMap['ROTI TAWAR'], 'supplier_id' => null, 'name' => 'ROTI TAWAR', 'qty' => 100, 'unit' => 'PCS', 'grade' => 'A', 'price' => 4000],
        ];

        $po5->items()->delete();
        foreach ($po5Items as $item) {
            $po5->items()->create($item);
        }
    }
}
