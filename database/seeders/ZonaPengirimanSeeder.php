<?php

namespace Database\Seeders;

use App\Models\ZonaPengiriman;
use Illuminate\Database\Seeder;

class ZonaPengirimanSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'nama_zona'  => 'Dalam Desa',
                'keterangan' => 'Moncongloe Lappara dan sekitarnya',
                'biaya'      => 2000,
                'urutan'     => 1,
                'aktif'      => true,
            ],
            [
                'nama_zona'  => 'Luar Desa, Dalam Kecamatan',
                'keterangan' => 'Moncongloe, Manuju, dan kecamatan sekitar',
                'biaya'      => 5000,
                'urutan'     => 2,
                'aktif'      => true,
            ],
            [
                'nama_zona'  => 'Luar Kecamatan',
                'keterangan' => 'Dalam Kabupaten Maros',
                'biaya'      => 15000,
                'urutan'     => 3,
                'aktif'      => true,
            ],
            [
                'nama_zona'  => 'Luar Kabupaten',
                'keterangan' => 'Makassar, Gowa, dan daerah lainnya',
                'biaya'      => 25000,
                'urutan'     => 4,
                'aktif'      => true,
            ],
        ];

        foreach ($items as $item) {
            ZonaPengiriman::updateOrCreate(['nama_zona' => $item['nama_zona']], $item);
        }
    }
}
