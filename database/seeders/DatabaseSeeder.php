<?php

namespace Database\Seeders;

use App\Models\Sppg;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['name' => 'admin'],
            [
                'email' => 'admin@supplier.local',
                'password' => 'admin123',
            ],
        );

        Sppg::query()->updateOrCreate(
            ['code' => 'M1101'],
            [
                'name' => 'SPPG-Balongsari',
                'location' => 'Mojokerto',
                'pic_name' => 'Datok',
                'whatsapp' => '0894334444',
            ],
        );

        $suppliers = [
            [
                'name' => 'DUNIA BUMBU MOJOKERTO',
                'address' => 'GPM Bypass B1 No 4 Kota Mojokerto',
                'logo_path' => 'logo-duniabumbu.jpeg',
                'stamp_path' => 'stamp-duniabumbu.jpeg',
                'theme_color' => '#16a34a',
            ],
            [
                'name' => 'NUTRIVA FOODS',
                'address' => '01/01 Pesanan Bicak Trowulan',
                'logo_path' => 'logo-nutrifa.jpeg',
                'stamp_path' => 'stamp-nutriva.jpeg',
                'theme_color' => '#ea580c',
            ],
            [
                'name' => 'VIALA PANGAN',
                'address' => 'Perum Graha Majapahit Jl Village Ave 89',
                'logo_path' => 'logo-viala.jpeg',
                'stamp_path' => 'stamp-viala.jpeg',
                'theme_color' => '#2563eb',
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::query()->updateOrCreate(
                ['name' => $supplierData['name']],
                $supplierData,
            );
        }

        $stockItems = [
            ['name' => 'AYAM FILET', 'unit' => 'KG', 'category' => 'Protein'],
            ['name' => 'TELUR AYAM', 'unit' => 'BUTIR', 'category' => 'Protein'],
            ['name' => 'DAGING', 'unit' => 'KG', 'category' => 'Protein'],
            ['name' => 'BAWANG MERAH', 'unit' => 'KG', 'category' => 'Bumbu'],
            ['name' => 'ROTI BURGER', 'unit' => 'PCS', 'category' => 'Roti'],
        ];

        foreach ($stockItems as $stockItemData) {
            StockItem::query()->firstOrCreate(
                [
                    'name' => $stockItemData['name'],
                    'unit' => $stockItemData['unit'],
                    'category' => $stockItemData['category'],
                ],
                ['status' => 'Aktif'],
            );
        }
    }
}
