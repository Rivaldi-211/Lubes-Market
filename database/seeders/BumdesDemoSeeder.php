<?php

namespace Database\Seeders;

use App\Models\BatchKeroyokan;
use App\Models\Kategori;
use App\Models\KelompokKeroyokan;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\RekomendasiStrategi;
use App\Models\Ulasan;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BumdesDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users (Admin, 5 Pembeli, 15 Penjual)
        $users = [
            ['username' => 'admin', 'nama_lengkap' => 'Ketua BUMDes Berkah', 'email' => 'admin@bumdesberkah.id', 'no_hp' => '081234500001', 'role' => 'admin'],

            // Pembeli
            ['username' => 'budi_pembeli', 'nama_lengkap' => 'Budi Santoso', 'email' => 'budi@gmail.com', 'no_hp' => '081234500006', 'role' => 'pembeli'],
            ['username' => 'siti_pembeli', 'nama_lengkap' => 'Siti Rahma', 'email' => 'siti@gmail.com', 'no_hp' => '081234500007', 'role' => 'pembeli'],
            ['username' => 'rudi_pembeli', 'nama_lengkap' => 'Rudi Kurniawan', 'email' => 'rudi@gmail.com', 'no_hp' => '081234500008', 'role' => 'pembeli'],
            ['username' => 'dewi_pembeli', 'nama_lengkap' => 'Dewi Lestari', 'email' => 'dewi@gmail.com', 'no_hp' => '081234500009', 'role' => 'pembeli'],
            ['username' => 'hikmah', 'nama_lengkap' => 'Nur Hikmah', 'email' => 'nurhikmahchyn27@gmail.com', 'no_hp' => '08012345678', 'role' => 'pembeli'],

            // 15 Penjual UMKM
            ['username' => 'umkm_jalangkote', 'nama_lengkap' => 'Ibu Sari', 'email' => 'sari.jalangkote@gmail.com', 'no_hp' => '081234500002', 'role' => 'penjual'],
            ['username' => 'umkm_pisangepe', 'nama_lengkap' => 'Pak Baso', 'email' => 'baso.pisangepe@gmail.com', 'no_hp' => '081234500003', 'role' => 'penjual'],
            ['username' => 'umkm_kripik', 'nama_lengkap' => 'Ibu Nur', 'email' => 'nur.kripik@gmail.com', 'no_hp' => '081234500004', 'role' => 'penjual'],
            ['username' => 'umkm_anyaman', 'nama_lengkap' => 'Pak Dg. Tola', 'email' => 'tola.anyaman@gmail.com', 'no_hp' => '081234500005', 'role' => 'penjual'],
            ['username' => 'umkm_moammar', 'nama_lengkap' => 'Moammar Donat Shop', 'email' => 'halimsrimuliani@gmail.com', 'no_hp' => '085242664216', 'role' => 'penjual'],
            ['username' => 'umkm_madu', 'nama_lengkap' => 'Pak Rudi', 'email' => 'rudi.madu@gmail.com', 'no_hp' => '081234500011', 'role' => 'penjual'],
            ['username' => 'umkm_batik', 'nama_lengkap' => 'Ibu Aminah', 'email' => 'aminah.batik@gmail.com', 'no_hp' => '081234500012', 'role' => 'penjual'],
            ['username' => 'umkm_kopi', 'nama_lengkap' => 'Pak Andi', 'email' => 'andi.kopi@gmail.com', 'no_hp' => '081234500013', 'role' => 'penjual'],
            ['username' => 'umkm_tempe', 'nama_lengkap' => 'Ibu Ramlah', 'email' => 'ramlah.tempe@gmail.com', 'no_hp' => '081234500014', 'role' => 'penjual'],
            ['username' => 'umkm_sambal', 'nama_lengkap' => 'Ibu Hasna', 'email' => 'hasna.sambal@gmail.com', 'no_hp' => '081234500015', 'role' => 'penjual'],
            ['username' => 'umkm_telur', 'nama_lengkap' => 'Pak Dg. Nai', 'email' => 'nai.telur@gmail.com', 'no_hp' => '081234500016', 'role' => 'penjual'],
            ['username' => 'umkm_kerupuk', 'nama_lengkap' => 'Ibu Marlina', 'email' => 'marlina.kerupuk@gmail.com', 'no_hp' => '081234500017', 'role' => 'penjual'],
            ['username' => 'umkm_tepung', 'nama_lengkap' => 'Pak Halim Mocaf', 'email' => 'halim.mocaf@gmail.com', 'no_hp' => '081234500018', 'role' => 'penjual'],
            ['username' => 'umkm_sabun', 'nama_lengkap' => 'Ibu Dg. Ti', 'email' => 'ti.sabun@gmail.com', 'no_hp' => '081234500019', 'role' => 'penjual'],
            ['username' => 'umkm_sulam', 'nama_lengkap' => 'Ibu Jumriah', 'email' => 'jumriah.sulam@gmail.com', 'no_hp' => '081234500020', 'role' => 'penjual'],
        ];

        foreach ($users as $row) {
            User::create($row + ['password' => Hash::make('password123'), 'status' => 'aktif']);
        }

        // 2. Kategori
        $kategoriList = [
            'Kreatif',
            'Kuliner',
            'Oleh-oleh',
        ];
        foreach ($kategoriList as $name) {
            Kategori::create(['nama_kategori' => $name]);
        }

        // 3. 15 UMKM Data
        $sellerUsers = User::where('role', 'penjual')->orderBy('id')->get();

        $umkmRows = [
            ['nama_umkm' => 'Jalangkote Bu Sari', 'pemilik' => 'Ibu Sari', 'alamat' => 'Kawasan Kuliner Moncongloe Lappara', 'no_hp' => '081234500002', 'deskripsi' => 'Jalangkote rumahan yang dibuat segar untuk warga dan pengunjung Moncongloe Lappara.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2018, 'jumlah_karyawan' => 3, 'instagram' => '@jalangkote_busari'],
            ['nama_umkm' => 'Pisang Epe & Bakso Bakar Pak Baso', 'pemilik' => 'Pak Baso', 'alamat' => 'Kawasan Kuliner Moncongloe Lappara', 'no_hp' => '081234500003', 'deskripsi' => 'Pisang epe, bakso bakar, dan minuman segar untuk suasana sore di Moncongloe Lappara.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2019, 'jumlah_karyawan' => 2, 'instagram' => '@pisangepe_pakbaso'],
            ['nama_umkm' => 'Kripik Moncongloe Bu Nur', 'pemilik' => 'Ibu Nur', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500004', 'deskripsi' => 'Camilan kering dan kue tradisional produksi warga Moncongloe Lappara.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2017, 'jumlah_karyawan' => 4, 'instagram' => '@kripik_bunur'],
            ['nama_umkm' => 'Anyaman Kreatif Dg. Tola', 'pemilik' => 'Pak Dg. Tola', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500005', 'deskripsi' => 'Kerajinan bambu dan suvenir buatan tangan untuk kebutuhan harian dan oleh-oleh.', 'kategori_usaha' => 'Kreatif', 'tahun_berdiri' => 2015, 'jumlah_karyawan' => 2, 'instagram' => '@anyaman_dgtola'],
            ['nama_umkm' => 'Moammar Donat Shop', 'pemilik' => 'Moammar', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '085242664216', 'deskripsi' => 'Donat lembut, varian rasa melimpah, dan aneka kue khas Moammar.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2020, 'jumlah_karyawan' => 5, 'instagram' => '@moammardonat'],
            ['nama_umkm' => 'Madu Hutan Pak Rudi', 'pemilik' => 'Pak Rudi', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500011', 'deskripsi' => 'Madu murni asli hutan Moncongloe dipanen secara berkelanjutan.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2018, 'jumlah_karyawan' => 2, 'instagram' => '@maduhutan_pakrudi'],
            ['nama_umkm' => 'Batik Tulis Aminah', 'pemilik' => 'Ibu Aminah', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500012', 'deskripsi' => 'Batik tulis dan kain khas corak lokal buatan kelompok ibu-ibu desa.', 'kategori_usaha' => 'Kreatif', 'tahun_berdiri' => 2016, 'jumlah_karyawan' => 3, 'instagram' => '@batiktulis_aminah'],
            ['nama_umkm' => 'Kopi Robusta Moncongloe', 'pemilik' => 'Pak Andi', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500013', 'deskripsi' => 'Kopi sangrai pilihan dengan aroma khas pegunungan Moncongloe.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2021, 'jumlah_karyawan' => 2, 'instagram' => '@kopi_moncongloe'],
            ['nama_umkm' => 'Tempe & Tahu Ramlah', 'pemilik' => 'Ibu Ramlah', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500014', 'deskripsi' => 'Olahan tempe dan tahu segar tanpa pengawet buatan harian.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2015, 'jumlah_karyawan' => 4, 'instagram' => '@tempe_ramlah'],
            ['nama_umkm' => 'Sambal Kemasan Hasna', 'pemilik' => 'Ibu Hasna', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500015', 'deskripsi' => 'Sambal botol khas Makassar dengan varian cumi, roa, dan terasi pedas gurih.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2022, 'jumlah_karyawan' => 2, 'instagram' => '@sambal_hasna'],
            ['nama_umkm' => 'Telur Ayam Kampung Dg. Nai', 'pemilik' => 'Pak Dg. Nai', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500016', 'deskripsi' => 'Telur ayam kampung organik dari peternakan herbal ternak warga.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2019, 'jumlah_karyawan' => 3, 'instagram' => '@telur_dgnai'],
            ['nama_umkm' => 'Kerupuk Ikan Marlina', 'pemilik' => 'Ibu Marlina', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500017', 'deskripsi' => 'Kerupuk ikan gurih dan renyah kemasan hemat untuk stok rumah tangga.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2020, 'jumlah_karyawan' => 2, 'instagram' => '@kerupuk_marlina'],
            ['nama_umkm' => 'Tepung Mocaf Pak Halim', 'pemilik' => 'Pak Halim', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500018', 'deskripsi' => 'Tepung singkong terfermentasi sehat (gluten-free) olahan BUMDes.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2021, 'jumlah_karyawan' => 3, 'instagram' => '@mocaf_pakhalim'],
            ['nama_umkm' => 'Sabun Herbal Dg. Ti', 'pemilik' => 'Ibu Dg. Ti', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500019', 'deskripsi' => 'Sabun mandi berbahan minyak kelapa dan ekstrak lidah buaya lokal.', 'kategori_usaha' => 'Kreatif', 'tahun_berdiri' => 2022, 'jumlah_karyawan' => 2, 'instagram' => '@sabun_dgti'],
            ['nama_umkm' => 'Sulam & Bordir Jumriah', 'pemilik' => 'Ibu Jumriah', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500020', 'deskripsi' => 'Taplak meja dan hiasan dinding sulam tangan bernilai seni tinggi.', 'kategori_usaha' => 'Kreatif', 'tahun_berdiri' => 2017, 'jumlah_karyawan' => 3, 'instagram' => '@sulam_jumriah'],
        ];

        foreach ($umkmRows as $i => $row) {
            Umkm::create($row + ['user_id' => $sellerUsers[$i]->id, 'status' => 'aktif']);
        }

        $this->call(RekeningBankSeeder::class);

        // 4. Products List (1: Kreatif, 2: Kuliner, 3: Oleh-oleh)
        $productsData = [
            // UMKM 1
            [1, 2, 'Jalangkote Isi Sayur', 5000, 'Ready', 50, 'Jalangkote renyah isi sayur wortel dan kentang, enak dinikmati hangat.', 'products/umkm-1/Jalangkote isi sayur.png'],
            [1, 2, 'Jalangkote Isi Telur', 6000, 'Ready', 40, 'Jalangkote dengan isian telur dan sayur, dibuat segar setiap hari.', 'products/umkm-1/jalangkote isi telur.png'],

            // UMKM 2
            [2, 2, 'Pisang Epe Coklat Keju', 15000, 'Ready', 30, 'Pisang epe bakar dengan gula merah, cokelat, dan keju parut.', 'products/umkm-2/pisang epe coklat keju.png'],
            [2, 2, 'Bakso Bakar', 12000, 'Ready', 35, 'Bakso bakar dengan bumbu gurih khas Moncongloe Lappara, isi 10 tusuk.', 'products/umkm-2/bakso bakar.png'],
            [2, 2, 'Jus Buah Segar', 10000, 'Ready', 25, 'Jus buah segar pilihan tanpa bahan pengawet.', 'products/umkm-2/jus buah segar.png'],

            // UMKM 3
            [3, 3, 'Kripik Pisang Original', 15000, 'Ready', 60, 'Kripik pisang gurih kemasan 250 gram, cocok untuk oleh-oleh.', 'products/umkm-3/kripik pisang original.png'],
            [3, 3, 'Kripik Singkong Moncongloe', 13000, 'Ready', 50, 'Kripik singkong renyah tersedia rasa original dan pedas.', 'products/umkm-3/kripik singkong moncongloe.png'],
            [3, 3, 'Kue Tradisional Campur', 20000, 'Pre-Order', 20, 'Paket kue tradisional dibuat sesuai jadwal pesanan.', 'products/umkm-3/kue tradisional campur.png', 3],

            // UMKM 4
            [4, 1, 'Anyaman Tas Bambu', 45000, 'Ready', 15, 'Tas anyaman bambu buatan tangan dengan karakter alami.', null],
            [4, 1, 'Suvenir Miniatur Desa Wisata', 25000, 'Pre-Order', 20, 'Suvenir miniatur khas desa wisata Moncongloe Lappara.', null, 7],

            // UMKM 5 (Moammar Donat Shop)
            [5, 2, 'Donat Jumbo Mix', 30000, 'Ready', 40, 'Donat empuk ukuran jumbo isi 6 dengan topping aneka rasa.', null],
            [5, 2, 'Donat Karakter Hias', 50000, 'Pre-Order', 15, 'Donat hias karakter ulu ulang tahun atau acara spesial.', null, 2],
            [5, 2, 'Es Cendol Moammar', 10000, 'Ready', 30, 'Es cendol gula merah santan segar racikan rumah.', null],

            // UMKM 6
            [6, 3, 'Madu Hutan Murni 500ml', 85000, 'Ready', 25, 'Madu murni alami 100% dipanen dari hutan Moncongloe.', null],
            [6, 3, 'Madu Sarang Asli 250g', 65000, 'Ready', 15, 'Madu sarang murni utuh kaya nutrisi alami.', null],

            // UMKM 7
            [7, 1, 'Kain Batik Tulis Moncongloe', 180000, 'Ready', 10, 'Kain batik tulis halus dengan motif flora khas desa.', null],
            [7, 1, 'Selendang Batik Sutra', 120000, 'Ready', 8, 'Selendang cantik elegan corak tradisional.', null],

            // UMKM 8
            [8, 3, 'Kopi Robusta Sangrai 200g', 35000, 'Ready', 45, 'Bubuk kopi sangrai pilihan dengan aroma khas pegunungan Moncongloe.', null],
            [8, 3, 'Kopi House Blend Sachet', 25000, 'Ready', 50, 'Paket kopi sachet siap seduh praktis.', null],

            // UMKM 9
            [9, 2, 'Tempe Organik Papan', 8000, 'Ready', 60, 'Tempe kedelai murni sehat diproduksi higienis.', null],
            [9, 2, 'Tahu Sutra Segar', 10000, 'Ready', 40, 'Tahu sutra lembut isi 10 potong segar.', null],

            // UMKM 10
            [10, 3, 'Sambal Cumi Pedas 150g', 28000, 'Ready', 35, 'Sambal botol cumi asin pedas mantap rasanya.', null],
            [10, 3, 'Sambal Roa Makassar 150g', 32000, 'Ready', 30, 'Sambal ikan roa asap pedas harum khas Sulawesi.', null],

            // UMKM 11
            [11, 3, 'Telur Ayam Kampung 1 Tray (30 Butir)', 65000, 'Ready', 20, 'Telur ayam kampung murni segar langsung dari kandang.', null],

            // UMKM 12
            [12, 3, 'Kerupuk Ikan Tenggiri 250g', 18000, 'Ready', 40, 'Kerupuk ikan gurih dan renyah kemasan hemat.', null],

            // UMKM 13
            [13, 3, 'Tepung Mocaf Gluten-Free 1kg', 22000, 'Ready', 50, 'Tepung singkong terfermentasi sehat (gluten-free).', null],

            // UMKM 14
            [14, 1, 'Sabun Herbal VCO & Lidah Buaya', 15000, 'Ready', 30, 'Sabun mandi alami melembutkan kulit tanpa bahan kimia keras.', null],

            // UMKM 15
            [15, 1, 'Taplak Meja Sulam Tangan', 95000, 'Pre-Order', 10, 'Taplak meja hiasan sulam renda cantik.', null, 14],
        ];

        foreach ($productsData as $p) {
            Produk::create([
                'umkm_id' => $p[0],
                'kategori_id' => $p[1],
                'nama_produk' => $p[2],
                'harga' => $p[3],
                'stok_status' => $p[4],
                'stok_jumlah' => $p[5],
                'deskripsi' => $p[6],
                'foto' => $p[7] ?? null,
                'estimasi_po_hari' => $p[8] ?? ($p[4] === 'Pre-Order' ? 7 : null),
            ]);
        }

        // 5. Strategy Recommendations Demo Data (RekomendasiStrategi)
        $recommendations = [
            [
                'umkm_id' => 1,
                'judul' => 'Paket Promo Bundling Snack Box Desa',
                'isi' => 'Manfaatkan momen rapat warga dengan membuat varian isi paket isi 3 jalangkote + jus buah segar dengan harga khusus Rp15.000.',
                'tipe' => 'promosi',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
            [
                'umkm_id' => 2,
                'judul' => 'Optimasi Foto Produk Pisang Epe Cokelat',
                'isi' => 'Tambahkan opsi varian topping keju melimpah di foto katalog untuk menarik minat pembeli muda.',
                'tipe' => 'produk',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
            [
                'umkm_id' => 3,
                'judul' => 'Peningkatan Kemasan Stand Up Pouch Pouch Foil',
                'isi' => 'Ganti kemasan plastik bening dengan standing pouch aluminium foil agar keripik tahan renyah hingga 3 bulan.',
                'tipe' => 'produk',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
            [
                'umkm_id' => 5,
                'judul' => 'Penawaran Diskon Akhir Pekan Donat Mix',
                'isi' => 'Terapkan diskon 15% setiap hari Sabtu sore untuk menghabiskan stok donat segar sebelum toko tutup.',
                'tipe' => 'harga',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
            [
                'umkm_id' => 6,
                'judul' => 'Kerjasama Titik Jual Rest Area Moncongloe',
                'isi' => 'BUMDes dapat membantu menitipkan produk Madu Hutan di etalase Pusat Oleh-Oleh Kecamatan.',
                'tipe' => 'distribusi',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
        ];

        foreach ($recommendations as $rec) {
            RekomendasiStrategi::create($rec);
        }

        // 8. Kelompok Keroyokan
        $kelompokSnack = KelompokKeroyokan::create([
            'kategori_id' => 1,
            'nama_kelompok' => 'Snack Box Acara Desa',
            'deskripsi' => 'Paket gabungan snack box lokal untuk konsumsi rapat, seminar, dan acara warga desa.',
            'aktif' => true,
        ]);

        $kelompokOlehOleh = KelompokKeroyokan::create([
            'kategori_id' => 2,
            'nama_kelompok' => 'Paket Keripik & Oleh-oleh Khas',
            'deskripsi' => 'Gabungan aneka camilan kering dan keripik khas Moncongloe Lappara.',
            'aktif' => true,
        ]);

        Produk::whereIn('id', [1, 2, 3, 4, 11, 12, 13])->update(['kelompok_keroyokan_id' => $kelompokSnack->id]);
        Produk::whereIn('id', [6, 7, 8, 20, 21])->update(['kelompok_keroyokan_id' => $kelompokOlehOleh->id]);
    }
}
