<?php

namespace Database\Seeders;

use App\Models\RekeningBank;
use Illuminate\Database\Seeder;

class RekeningBankSeeder extends Seeder
{
    public function run(): void
    {
        $umkms = \App\Models\Umkm::take(3)->get();
        $umkm1 = $umkms->get(0);
        $umkm2 = $umkms->get(1);
        $umkm3 = $umkms->get(2);

        $accounts = [
            // Platform Bank Accounts (untuk pembayaran transfer pembeli ke rekening bersama BUMDes)
            [
                'umkm_id' => null,
                'nama_bank' => 'Bank BRI Platform',
                'nomor_rekening' => '0234-01-000000-50-1',
                'atas_nama' => 'BUMDes Berkah Moncongloe',
                'aktif' => true,
                'urutan' => 1,
            ],
            [
                'umkm_id' => null,
                'nama_bank' => 'Bank Sulselbar Platform',
                'nomor_rekening' => '0892-0000-11',
                'atas_nama' => 'BUMDes Berkah Moncongloe',
                'aktif' => true,
                'urutan' => 2,
            ],

            // UMKM Bank Accounts (untuk pencairan saldo ke masing-masing penjual)
            [
                'umkm_id' => $umkm1?->id,
                'nama_bank' => 'Bank BRI',
                'nomor_rekening' => '0234-01-001892-53-4',
                'atas_nama' => $umkm1 ? $umkm1->pemilik : 'Ibu Sari',
                'aktif' => true,
                'urutan' => 1,
            ],
            [
                'umkm_id' => $umkm2?->id,
                'nama_bank' => 'Bank BNI',
                'nomor_rekening' => '0892-3481-90',
                'atas_nama' => $umkm2 ? $umkm2->pemilik : 'Pak Baso',
                'aktif' => true,
                'urutan' => 1,
            ],
            [
                'umkm_id' => $umkm3?->id,
                'nama_bank' => 'Bank Mandiri',
                'nomor_rekening' => '152-00-9834210-8',
                'atas_nama' => $umkm3 ? $umkm3->pemilik : 'Ibu Nur',
                'aktif' => true,
                'urutan' => 1,
            ],
        ];

        foreach ($accounts as $acc) {
            RekeningBank::updateOrCreate(
                ['nomor_rekening' => $acc['nomor_rekening']],
                $acc
            );
        }
    }
}
