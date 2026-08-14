<?php
namespace Database\Seeders;
use App\Models\Kategori;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class BumdesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['username' => 'admin', 'nama_lengkap' => 'Ketua BUMDes Berkah', 'email' => 'admin@bumdesberkah.id', 'no_hp' => '081234500001', 'role' => 'admin'],
            ['username' => 'umkm_jalangkote', 'nama_lengkap' => 'Ibu Sari', 'email' => 'sari.jalangkote@gmail.com', 'no_hp' => '081234500002', 'role' => 'penjual'],
            ['username' => 'umkm_pisangepe', 'nama_lengkap' => 'Pak Baso', 'email' => 'baso.pisangepe@gmail.com', 'no_hp' => '081234500003', 'role' => 'penjual'],
            ['username' => 'umkm_kripik', 'nama_lengkap' => 'Ibu Nur', 'email' => 'nur.kripik@gmail.com', 'no_hp' => '081234500004', 'role' => 'penjual'],
            ['username' => 'umkm_anyaman', 'nama_lengkap' => 'Pak Dg. Tola', 'email' => 'tola.anyaman@gmail.com', 'no_hp' => '081234500005', 'role' => 'penjual'],
            ['username' => 'budi_pembeli', 'nama_lengkap' => 'Budi Santoso', 'email' => 'budi@gmail.com', 'no_hp' => '081234500006', 'role' => 'pembeli'],
            ['username' => 'halim', 'nama_lengkap' => 'Moammar Donat Shop', 'email' => 'halimsrimuliani@gmail.com', 'no_hp' => '085242664216', 'role' => 'penjual'],
        ];
        foreach ($users as $row)
            User::create($row + ['password' => Hash::make('password123'), 'status' => 'aktif']);

        foreach (['Kuliner Basah', 'Produk Kering / Oleh-oleh', 'Kerajinan / Kreatif'] as $name)
            Kategori::create(['nama_kategori' => $name]);
        $sellerUsers = User::where('role', 'penjual')->orderBy('id')->get();
        $umkmRows = [
            ['nama_umkm' => 'Jalangkote Bu Sari', 'pemilik' => 'Ibu Sari', 'alamat' => 'Kawasan Kuliner Moncongloe Lappara', 'no_hp' => '081234500002', 'deskripsi' => 'Jalangkote rumahan yang dibuat segar untuk warga dan pengunjung Moncongloe Lappara.'],
            ['nama_umkm' => 'Pisang Epe & Bakso Bakar Pak Baso', 'pemilik' => 'Pak Baso', 'alamat' => 'Kawasan Kuliner Moncongloe Lappara', 'no_hp' => '081234500003', 'deskripsi' => 'Pisang epe, bakso bakar, dan minuman segar untuk suasana sore di Moncongloe Lappara.'],
            ['nama_umkm' => 'Kripik Moncongloe Bu Nur', 'pemilik' => 'Ibu Nur', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500004', 'deskripsi' => 'Camilan kering dan kue tradisional produksi warga Moncongloe Lappara.'],
            ['nama_umkm' => 'Anyaman Kreatif Dg. Tola', 'pemilik' => 'Pak Dg. Tola', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500005', 'deskripsi' => 'Kerajinan bambu dan suvenir buatan tangan untuk kebutuhan harian dan oleh-oleh.'],
            ['nama_umkm' => 'Moammar Donat Shop', 'pemilik' => 'Moammar', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '085242664216', 'deskripsi' => 'Donat lembut, varian rasa melimpah, dan aneka kue khas Moammar.'],
        ];
        foreach ($umkmRows as $i => $row)
            Umkm::create($row + ['user_id' => $sellerUsers[$i]->id, 'status' => 'aktif']);
        $products = [
            [1, 1, 'Jalangkote Isi Sayur', 5000, 'Ready', 30, 'Jalangkote renyah isi sayur wortel dan kentang, enak dinikmati hangat.', 'products/umkm-1/Jalangkote isi sayur.png'],
            [1, 1, 'Jalangkote Isi Telur', 6000, 'Ready', 24, 'Jalangkote dengan isian telur dan sayur, dibuat segar setiap hari.', 'products/umkm-1/jalangkote isi telur.png'],
            [2, 1, 'Pisang Epe Coklat Keju', 15000, 'Ready', 18, 'Pisang epe bakar dengan gula merah, cokelat, dan keju parut.', 'products/umkm-2/pisang epe coklat keju.png'],
            [2, 1, 'Bakso Bakar', 12000, 'Ready', 25, 'Bakso bakar dengan bumbu gurih khas Moncongloe Lappara, isi 10 tusuk.', 'products/umkm-2/bakso bakar.png'],
            [2, 1, 'Jus Buah Segar', 10000, 'Ready', 20, 'Jus buah segar pilihan tanpa bahan pengawet.', 'products/umkm-2/jus buah segar.png'],
            [3, 2, 'Kripik Pisang Original', 15000, 'Ready', 40, 'Kripik pisang gurih kemasan 250 gram, cocok untuk oleh-oleh.', 'products/umkm-3/kripik pisang original.png'],
            [3, 2, 'Kripik Singkong Moncongloe', 13000, 'Ready', 36, 'Kripik singkong renyah tersedia rasa original dan pedas.', 'products/umkm-3/kripik singkong moncongloe.png'],
            [3, 2, 'Kue Tradisional Campur', 20000, 'Pre-Order', 12, 'Paket kue tradisional dibuat sesuai jadwal pesanan.', 'products/umkm-3/kue tradisional campur.png'],
            [4, 3, 'Anyaman Tas Bambu', 45000, 'Ready', 8, 'Tas anyaman bambu buatan tangan dengan karakter alami.', NULL],
            [4, 3, 'Suvenir Miniatur Desa Wisata', 25000, 'Pre-Order', 10, 'Suvenir miniatur khas desa wisata Moncongloe Lappara.', NULL],
            // [5, 1, 'Donat Jumbo', 30000, 'Ready', 100, 'Donat lembut empuk isi 6.', NULL],
            // [5, 2, 'Kacang Bawang', 35000, 'Ready', 80, 'Kacang bawang gurih dan renyah.', NULL],
            // [5, 1, 'Es Kepona', 16000, 'Ready', 60, 'Es kepona manis dan segar.', NULL],
            // [5, 1, 'Dadar Santan Kacang', 10000, 'Ready', 50, 'Kombinasi rasa gurih santan dan renyah kacang dalam satu gigitan yang memanjakan lidah.', NULL],
            // [5, 1, 'Pisang Ijo Monat', 12000, 'Ready', 50, 'Pisang ijo dengan saus santan dan sirup merah yang manis dan segar.', NULL],
            // [5, 1, 'Donat Tumpeng', 150000, 'Ready', 20, 'Donat tumpeng dengan varian rasa yang melimpah.', NULL],
            // [5, 1, 'Donat Tar', 70000, 'Ready', 20, 'Donat tar lembut dengan lelehan cokelat premium.', NULL],
            // [5, 1, 'Donat Salju', 30000, 'Ready', 20, 'Donat salju lembut dengan taburan gula halus.', NULL],
            // [5, 1, 'Donat Manis', 35000, 'Ready', 20, 'Donat manis.', NULL],
            // [5, 1, 'Donat Mix', 45000, 'Ready', 20, 'Donat dengan varian rasa yang melimpah.', NULL],
            // [5, 1, 'Donat Toping Hias', 45000, 'Ready', 20, 'Donat dengan toping hias.', NULL],
            // [5, 1, 'Donat Sosis', 40000, 'Ready', 20, 'Donat dengan topping sosis.', NULL],
            // [5, 1, 'Donat Karakter', 50000, 'Pre-Order', 20, 'Donat dengan karakter.', NULL],
            // [5, 1, 'Donat Karakter Jumbo', 60000, 'Pre-Order', 20, 'Donat dengan karakter hewan.', NULL],
            // [5, 1, 'Es Cendol Moammar', 5000, 'Ready', 20, 'Es cendol.', NULL],
            // [5, 1, 'Aneka Kue Tradisional', 1250, 'Ready', 20, 'Aneka kue tradisional.', NULL],
            // [5, 1, 'Donat Jumbo Ucapan', 40000, 'Ready', 20, 'Donat jumbo ucapan.', NULL],
            // [5, 1, 'Donat Mini Ucapan Karakter', 65000, 'Pre-Order', 20, 'Donat dengan ucapan.', NULL],
            // [5, 1, 'Bomboloni', 45000, 'Ready', 20, 'Bomboloni.', NULL],
            // [5, 1, 'Donat Kukus', 20000, 'Ready', 20, 'Donat kukus.', NULL],
            // [5, 1, 'Donat Mini Ucapan', 45000, 'Ready', 20, 'Donat dengan ucapan.', NULL],
            // [5, 1, 'Donat Nampah', 60000, 'Ready', 20, 'Donat nampah.', NULL],

        ];
        foreach ($products as $p)
            Produk::create(['umkm_id' => $p[0], 'kategori_id' => $p[1], 'nama_produk' => $p[2], 'harga' => $p[3], 'stok_status' => $p[4], 'stok_jumlah' => $p[5], 'deskripsi' => $p[6], 'foto' => $p[7]]);
        $buyer = User::where('username', 'budi_pembeli')->first();
        $o1 = Pesanan::create(['pembeli_id' => $buyer->id, 'produk_id' => 3, 'jumlah' => 2, 'total_harga' => 30000, 'metode_pembayaran' => 'COD', 'alamat_pengiriman' => 'Moncongloe Lappara', 'no_hp_pembeli' => $buyer->no_hp, 'status' => 'Selesai', 'catatan' => 'Bungkus terpisah']);
        Pesanan::create(['pembeli_id' => $buyer->id, 'produk_id' => 6, 'jumlah' => 1, 'total_harga' => 15000, 'metode_pembayaran' => 'Transfer', 'alamat_pengiriman' => 'Moncongloe Lappara', 'no_hp_pembeli' => $buyer->no_hp, 'status' => 'Menunggu']);
        Ulasan::create(['pesanan_id' => $o1->id, 'produk_id' => 3, 'pembeli_id' => $buyer->id, 'rating' => 5, 'komentar' => 'Pisang epennya enak dan masih hangat saat diterima.']);

        $kelompokSnack = \App\Models\KelompokKeroyokan::create([
            'kategori_id' => 1,
            'nama_kelompok' => 'Snack Box Acara Desa',
            'deskripsi' => 'Paket gabungan snack box lokal untuk konsumsi rapat, seminar, dan acara warga desa.',
            'aktif' => true,
        ]);

        $kelompokOlehOleh = \App\Models\KelompokKeroyokan::create([
            'kategori_id' => 2,
            'nama_kelompok' => 'Paket Keripik & Oleh-oleh Khas',
            'deskripsi' => 'Gabungan aneka camilan kering dan keripik khas Moncongloe Lappara.',
            'aktif' => true,
        ]);

        Produk::whereIn('id', [1, 2, 3, 4, 11, 13, 14, 15])->update(['kelompok_keroyokan_id' => $kelompokSnack->id]);
        Produk::whereIn('id', [6, 7, 8, 12])->update(['kelompok_keroyokan_id' => $kelompokOlehOleh->id]);
    }
}
