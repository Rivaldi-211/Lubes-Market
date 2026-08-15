<?php

namespace Database\Seeders;

use App\Models\OpsiPacking;
use Illuminate\Database\Seeder;

class OpsiPackingSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'nama' => 'Standar',
                'deskripsi' => 'Plastik biasa / kemasan standar',
                'biaya' => 0,
                'urutan' => 1,
                'aktif' => true,
            ],
            [
                'nama' => 'Aman',
                'deskripsi' => 'Bubble wrap ekstra + kardus pelindung',
                'biaya' => 3000,
                'urutan' => 2,
                'aktif' => true,
            ],
            [
                'nama' => 'Premium',
                'deskripsi' => 'Box branded LUDES-MARKET + pita hias',
                'biaya' => 7000,
                'urutan' => 3,
                'aktif' => true,
            ],
            [
                'nama' => 'Hadiah',
                'deskripsi' => 'Gift wrap + kartu ucapan kustom',
                'biaya' => 12000,
                'urutan' => 4,
                'aktif' => true,
            ],
        ];

        foreach ($items as $item) {
            OpsiPacking::updateOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
