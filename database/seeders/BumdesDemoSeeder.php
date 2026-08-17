<?php

namespace Database\Seeders;

use App\Models\BatchKeroyokan;
use App\Models\Disbursement;
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
        $users = [
            ['username' => 'admin', 'nama_lengkap' => 'Admin LUDES-MARKET', 'email' => 'admin@ludesmarket.id', 'no_hp' => '0812-8257-5933', 'role' => 'admin'],

            ['username' => 'budi_pembeli', 'nama_lengkap' => 'Budi Santoso', 'email' => 'budi@gmail.com', 'no_hp' => '081234500006', 'role' => 'pembeli'],
            ['username' => 'siti_pembeli', 'nama_lengkap' => 'Siti Rahma', 'email' => 'siti@gmail.com', 'no_hp' => '081234500007', 'role' => 'pembeli'],
            ['username' => 'rudi_pembeli', 'nama_lengkap' => 'Rudi Kurniawan', 'email' => 'rudi@gmail.com', 'no_hp' => '081234500008', 'role' => 'pembeli'],
            ['username' => 'dewi_pembeli', 'nama_lengkap' => 'Dewi Lestari', 'email' => 'dewi@gmail.com', 'no_hp' => '081234500009', 'role' => 'pembeli'],
            ['username' => 'hikmah', 'nama_lengkap' => 'Nur Hikmah', 'email' => 'nurhikmahchyn27@gmail.com', 'no_hp' => '08012345678', 'role' => 'pembeli'],
            ['username' => 'mozzapiey', 'nama_lengkap' => 'mozzapiey', 'email' => 'mozzapiey@gmail.com', 'no_hp' => '0895803005021', 'role' => 'pembeli'],

            ['username' => 'umkm_wawa', 'nama_lengkap' => 'Kedai Wawa', 'email' => 'wawa@gmail.com', 'no_hp' => '081234500002', 'role' => 'penjual'],
            ['username' => 'umkm_mamaaisy', 'nama_lengkap' => 'Kedai Mama Aisy', 'email' => 'mamaaisy@gmail.com', 'no_hp' => '081234500003', 'role' => 'penjual'],
            ['username' => 'umkm_kedai_al', 'nama_lengkap' => 'Kedai AL', 'email' => 'kedai_al@gmail.com', 'no_hp' => '081234500004', 'role' => 'penjual'],
            ['username' => 'umkm_malekbi', 'nama_lengkap' => 'Kedai Malekbi', 'email' => 'malekbi@gmail.com', 'no_hp' => '081234500005', 'role' => 'penjual'],
            ['username' => 'umkm_nyemilbebs', 'nama_lengkap' => 'Nyemil Bebs', 'email' => 'nyemilbebs@gmail.com', 'no_hp' => '081234500011', 'role' => 'penjual'],
            ['username' => 'umkm_tokoafi', 'nama_lengkap' => 'Toko Afi', 'email' => 'tokoafi@gmail.com', 'no_hp' => '081234500012', 'role' => 'penjual'],
            ['username' => 'umkm_buhera', 'nama_lengkap' => 'Gorengan Bu Hera', 'email' => 'buhera@gmail.com', 'no_hp' => '081234500013', 'role' => 'penjual'],
            ['username' => 'umkm_kedainindi', 'nama_lengkap' => 'Teh Nusantara Kedai Nindi', 'email' => 'kedainindi@gmail.com', 'no_hp' => '081234500014', 'role' => 'penjual'],
            ['username' => 'umkm_keoskaki', 'nama_lengkap' => 'Lapak Keos Kaki', 'email' => 'keoskaki@gmail.com', 'no_hp' => '081234500015', 'role' => 'penjual'],
            ['username' => 'umkm_dapurawan', 'nama_lengkap' => 'Dapur Awan', 'email' => 'dapurawan@gmail.com', 'no_hp' => '081234500016', 'role' => 'penjual'],
            ['username' => 'umkm_mommadonat', 'nama_lengkap' => 'Momma Donat Shop', 'email' => 'mommadonat@gmail.com', 'no_hp' => '085242664216', 'role' => 'penjual'],
            ['username' => 'umkm_jajanalea', 'nama_lengkap' => 'Jajanan Kering Alea', 'email' => 'jajananalea@gmail.com', 'no_hp' => '081234500017', 'role' => 'penjual'],
            ['username' => 'umkm_mamakembar', 'nama_lengkap' => 'Kedai Mama Kembar', 'email' => 'mamakembar@gmail.com', 'no_hp' => '081234500018', 'role' => 'penjual'],
            ['username' => 'umkm_kedaiarisz', 'nama_lengkap' => 'Kedai Arisz', 'email' => 'kedaiarisz@gmail.com', 'no_hp' => '081234500019', 'role' => 'penjual'],
            ['username' => 'umkm_kedaianyaman', 'nama_lengkap' => 'Kedai Anyaman', 'email' => 'kedaianyaman@gmail.com', 'no_hp' => '081234500020', 'role' => 'penjual'],
        ];

        foreach ($users as $row) {
            User::create($row + ['password' => Hash::make('password123'), 'status' => 'aktif']);
        }

        $kategoriList = [
            'Kreatif',
            'Kuliner',
            'Oleh-oleh',
        ];
        foreach ($kategoriList as $name) {
            Kategori::create(['nama_kategori' => $name]);
        }

        $sellerUsers = User::where('role', 'penjual')->orderBy('id')->get();

        $umkmRows = [
            ['nama_umkm' => 'Kedai Wawa', 'pemilik' => 'Ibu Wawa', 'alamat' => 'Kawasan Kuliner Moncongloe Lappara', 'no_hp' => '081234500002', 'deskripsi' => 'Aneka pastry lezat, kue olahan selai segar, dan aneka toping pilihan.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2021, 'jumlah_karyawan' => 2, 'instagram' => '@kedai_wawa'],
            ['nama_umkm' => 'Kedai Mama Aisy', 'pemilik' => 'Mama Aisy', 'alamat' => 'Kawasan Kuliner Moncongloe Lappara', 'no_hp' => '081234500003', 'deskripsi' => 'Spesialis empek-empek kuah cuko mantap dan aneka varian jalangkote renyah gurih.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2019, 'jumlah_karyawan' => 3, 'instagram' => '@kedai_mamaaisy'],
            ['nama_umkm' => 'Kedai AL', 'pemilik' => 'Pak AL', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500004', 'deskripsi' => 'Bakso sapi keju meletus, bakso ikan segar, dan tahu gendot kenyal gurih.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2020, 'jumlah_karyawan' => 3, 'instagram' => '@kedai_al'],
            ['nama_umkm' => 'Kedai Malekbi', 'pemilik' => 'Ibu Malekbi', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500005', 'deskripsi' => 'Menyediakan sarang madu muda murni, kue tradisional, dan aneka jajanan kukusan khas.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2018, 'jumlah_karyawan' => 2, 'instagram' => '@kedai_malekbi'],
            ['nama_umkm' => 'Nyemil Bebs', 'pemilik' => 'Nyemil Bebs', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500011', 'deskripsi' => 'Camilan kekinian renyah mulai dari cookies, cookies mini, macaroni aneka rasa, hingga keripik ubi pedas.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2022, 'jumlah_karyawan' => 2, 'instagram' => '@nyemilbebs'],
            ['nama_umkm' => 'Toko Afi', 'pemilik' => 'Ibu Afi', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500012', 'deskripsi' => 'Pusat oleh-oleh keripik ikan gurih, sambel kemasan botol mantap, aneka keripik, dan kue kering.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2017, 'jumlah_karyawan' => 4, 'instagram' => '@toko_afi'],
            ['nama_umkm' => 'Gorengan Bu Hera', 'pemilik' => 'Ibu Hera', 'alamat' => 'Kawasan Kuliner Moncongloe Lappara', 'no_hp' => '081234500013', 'deskripsi' => 'Aneka gorengan hangat, lumpia renyah, risol sayur, cireng ayam, dan risol mayo lezat.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2019, 'jumlah_karyawan' => 2, 'instagram' => '@gorengan_buhera'],
            ['nama_umkm' => 'Teh Nusantara Kedai Nindi', 'pemilik' => 'Nindi', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500014', 'deskripsi' => 'Minuman segar teh nusantara, es kopi gula aren asli, dan es cendol nikmat.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2021, 'jumlah_karyawan' => 2, 'instagram' => '@kedainindi'],
            ['nama_umkm' => 'Lapak Keos Kaki', 'pemilik' => 'Lapak Keos Kaki', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500015', 'deskripsi' => 'Penyedia tas belanja pasar praktis, tas jaring elastis, serta aneka tas rajut wol buatan tangan.', 'kategori_usaha' => 'Kreatif', 'tahun_berdiri' => 2020, 'jumlah_karyawan' => 2, 'instagram' => '@lapak_keoskaki'],
            ['nama_umkm' => 'Dapur Awan', 'pemilik' => 'Dapur Awan', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500016', 'deskripsi' => 'Menu santap harian lezat mulai dari nasi goreng, mie goreng, serta aneka olahan nasi ayam spesial.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2021, 'jumlah_karyawan' => 3, 'instagram' => '@dapur_awan'],
            ['nama_umkm' => 'Momma Donat Shop', 'pemilik' => 'Sri Muliani', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '085242664216', 'deskripsi' => 'Produsen aneka donat empuk, donat mini ucapan, bomboloni lumer, hingga donat tumpeng spesial.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2020, 'jumlah_karyawan' => 5, 'instagram' => '@mommadonat'],
            ['nama_umkm' => 'Jajanan Kering Alea', 'pemilik' => 'Alea', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500017', 'deskripsi' => 'Camilan renyah makaroni asin, basreng daun jeruk pedas gurih, kacang bawang, dan kacang goreng.', 'kategori_usaha' => 'Oleh-oleh', 'tahun_berdiri' => 2020, 'jumlah_karyawan' => 2, 'instagram' => '@jajanan_alea'],
            ['nama_umkm' => 'Kedai Mama Kembar', 'pemilik' => 'Mama Kembar', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500018', 'deskripsi' => 'Jajanan tradisional dadar santan kacang legit, es kepon segar, dan pisang ijo manis gurih.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2018, 'jumlah_karyawan' => 3, 'instagram' => '@mamakembar'],
            ['nama_umkm' => 'Kedai Arisz', 'pemilik' => 'Arisz', 'alamat' => 'Moncongloe Lappara', 'no_hp' => '081234500019', 'deskripsi' => 'Roti meses mini empuk, ayam bumbu madu lezat, dan bakso tahu daging gurih.', 'kategori_usaha' => 'Kuliner', 'tahun_berdiri' => 2022, 'jumlah_karyawan' => 2, 'instagram' => '@kedai_arisz'],
            ['nama_umkm' => 'Kedai Anyaman', 'pemilik' => 'Pengrajin Anyaman', 'alamat' => 'Dusun Moncongloe Lappara', 'no_hp' => '081234500020', 'deskripsi' => 'Produk kerajinan tangan bambu, aneka anyaman tangan tradisional, dan kreasi tas rajut wol.', 'kategori_usaha' => 'Kreatif', 'tahun_berdiri' => 2016, 'jumlah_karyawan' => 3, 'instagram' => '@kedai_anyaman'],
        ];

        foreach ($umkmRows as $i => $row) {
            Umkm::create($row + ['user_id' => $sellerUsers[$i]->id, 'status' => 'aktif']);
        }

        $productsData = [
            [1, 2, 'Aneka Toping Selai', 15000, 'Ready', 30, 'Aneka varian toping selai manis dan lezat untuk aneka olahan roti dan kue.', 'products/FDgcZmUI6yhPXC6TrAFOfYUjCJxchvd0VbadvkuJ.jpg'],
            [1, 2, 'Pastry Salju', 18000, 'Ready', 25, 'Pastry renyah dengan isian keju gurih dan lumer.', 'products/cUgCZpODVOUITTzPdCBBy3lfokWVpdgUkgMEdQdE.jpg'],
            [1, 2, 'Kue Selai Strawberry', 20000, 'Ready', 20, 'Kue lembut dengan selai strawberry manis segar pilihan.', 'products/qKdVfLmjnG7q8nJADYK0upAdij9VsXllNpHJDAZJ.jpg'],
            [2, 2, 'Empek-empek', 15000, 'Ready', 35, 'Empek-empek kenyal dan gurih disajikan lengkap dengan kuah cuko mantap.', 'products/yK1uabvlLhNMGq48ow0mkxQWEiQmldgi1RIeY3vN.jpg'],
            [2, 2, 'Jalangkote Gado', 5000, 'Ready', 40, 'Jalangkote renyah dengan isian gado-gado khas dan sambal lezat.', 'products/lBzi8cyrB5lRK1ClnNUq5K5HObELNxqBlDj0y4pH.jpg'],
            [2, 2, 'Jalangkote Sayur', 5000, 'Ready', 50, 'Jalangkote renyah isi sayur wortel dan kentang segar.', 'products/TTGCoywUyk5dSlUoFxHZI83Re4x3w6u8f06ja6PX.jpg'],
            [2, 2, 'Jalangkote Telur', 6000, 'Ready', 45, 'Jalangkote gurih dengan isian potongan telur dan sayur segar.', 'products/MJ5UDfQNjjGLmXehq4iJjW5Wwkusvh7XZ87YkQDo.jpg'],
            [3, 2, 'Bakso Keju Meletus', 20000, 'Ready', 30, 'Bakso sapi kenyal dengan isian keju lumer yang meleleh di mulut.', 'products/81bRdL6XgHuIc9GGOCgqGHafBMOufHbEwvdmc11c.jpg'],
            [3, 2, 'Bakso Ikan', 15000, 'Ready', 35, 'Bakso ikan segar olahan gurih dan kenyal.', 'products/MvFotEoJNfVAZyscLDIc2o4O45VlhITRc6izvMvX.jpg'],
            [3, 2, 'Tahu Gendot', 12000, 'Ready', 40, 'Tahu gendot isi daging cincang dan rempah gurih khas.', 'products/M50Hkh7BuVNLXf59OZ3I0DzTpNsWuyyGXJPYtgBM.jpg'],
            [4, 3, 'Sarang Madu Muda', 65000, 'Ready', 15, 'Sarang madu muda murni segar dan kaya nutrisi alami.', 'products/gLERlB8StQVRBwsAGitYeCvuxmoXWKLXgWckClXD.jpg'],
            [4, 2, 'Kue Tradisional', 15000, 'Ready', 30, 'Aneka kue tradisional khas yang lezat dan otentik.', 'products/t11ouCcYipapmZrwpSyIkW2TjsLkqXr22mIlwTUX.jpg'],
            [4, 2, 'Aneka Kukusan', 15000, 'Ready', 22, 'kukusan ubi, kacang, telur dan kentang', 'products/eXa5MKsiFnJm0vW9Zy8GQ1CwBulOrpv8473cYtWe.jpg'],
            [5, 3, 'Cookies', 25000, 'Ready', 40, 'Cookies renyah manis dengan bahan berkualitas.', 'products/WxqCQCXTnXmDVEvopcdGwPzHkqFuswhHDfG1kqyZ.jpg'],
            [5, 3, 'Macaroni Aneka Rasa', 10000, 'Ready', 50, 'Macaroni renyah gurih dengan pilihan aneka bumbu rasa.', 'products/N7ZIW49OEyqdkvw5tExtQkAUOdYbXA31bGjf3LQU.jpg'],
            [5, 3, 'Cookies Mini', 15000, 'Ready', 40, 'Cookies ukuran mini cocok untuk camilan santai sehari-hari.', 'products/41AKR9f8WyoDG44lYabvhsX2Z0e3dsSjIm76JkHi.jpg'],
            [5, 3, 'Keripik Ubi Pedas', 12000, 'Ready', 45, 'Keripik ubi renyah berbalut bumbu balado pedas manis.', 'products/xK2mvnOU642yqWyyvNfGEgzhI8BExdCTJXdV9pB8.jpg'],
            [6, 3, 'Keripik Ikan', 18000, 'Ready', 40, 'Keripik ikan renyah, gurih, dan kaya protein.', 'products/hixNcFsizqWecCvEZoFLtkWxesWdjPM8g5bCv1XB.jpg'],
            [6, 3, 'Sambel Kemasan', 25000, 'Ready', 30, 'Sambel kemasan botol praktis, pedas mantap dan tahan lama.', 'products/7JXMoF3VngO5lzQI78ikBfvXgHRGaTaUaRJFiu2K.jpg'],
            [6, 3, 'Aneka Keripik Oleh Oleh', 20000, 'Ready', 35, 'Aneka pilihan keripik gurih renyah khas daerah.', 'products/cMLlVZh9pgLDsXEMsXsPOR1YKKCZItQERkaO5wZR.jpg'],
            [6, 3, 'Kue Kering', 30000, 'Ready', 25, 'Aneka kue kering toples lezat untuk camilan dan oleh-oleh.', 'products/0oDWckkRaw1GyO3W4rOAWnv24aIZ2xS61Qhf1iY4.jpg'],
            [7, 2, 'Lumpia', 4000, 'Ready', 50, 'Lumpia goreng renyah isi sayur dan rebung gurih.', 'products/B82eNTHu6O3EsuwEkmuKs0RvW4eOh9XztBwAVn2e.jpg'],
            [7, 2, 'Risol Sayur', 4000, 'Ready', 50, 'Risol goreng gurih dengan isian sayuran segar.', 'products/vRVdpobuBv1AdpPlM9Vhh9p0ibjTKFXBwrc58CdR.jpg'],
            [7, 2, 'Cireng Ayam', 5000, 'Ready', 40, 'Cireng kenyal gurih dengan isian ayam suwir pedas nikmat.', 'products/AkwIBqN3jyQxltBSTNy9YuQ0K8Gy6qrwwznM7HmT.jpg'],
            [7, 2, 'Risol Mayo', 6000, 'Ready', 45, 'Risol renyah dengan isian smoked beef/sosis, telur, dan creamy mayo.', 'products/Q5yIwgoM5z7DV6qGVRPKPdEJZzezAIxJ2jKXWN6b.jpg'],
            [8, 2, 'Teh Nusantara', 8000, 'Ready', 60, 'Racikan teh khas nusantara wangi, segar, dan nikmat.', 'products/D6GtGwPuWSyB9GxmYpZKks22SVmXMTekWYm8q551.jpg'],
            [8, 2, 'Es Kopi Gula Aren', 15000, 'Ready', 40, 'Kopi espresso susu dengan manis alami gula aren asli.', 'products/w9qhSosTbAnA6u6ihDW7f1gsNWIOxu9nmKHiHc59.jpg'],
            [8, 2, 'Cendol', 5000, 'Ready', 35, 'Es cendol segar dengan santan gurih dan kuah gula merah cair.', 'products/KIZoY1uAz8eJAmA5Lzr1loZXOyf3Mvc0Lkvux2it.jpg'],
            [9, 1, 'Tas Anyaman Plastik', 25000, 'Ready', 20, 'Tas belanja pasar yang kuat, awet, dan ramah lingkungan.', 'products/TiZYIUMxMN6QJgYKicxYBitB7H2a3aZvOkt8pBjY.jpg'],
            [9, 1, 'Tas Jaring', 20000, 'Ready', 25, 'Tas jaring elastis kekinian untuk belanja maupun santai.', 'products/cAX8ZiF5c1eeCEUHnzEuu69l17JZVWknkz7h9dyG.jpg'],
            [9, 1, 'Tas Buatan Wol', 55000, 'Ready', 15, 'Tas rajut wol cantik buatan tangan (handmade).', null],
            [10, 2, 'Nasi Goreng', 15000, 'Ready', 40, 'Nasi goreng racikan bumbu khas desa dengan telur dan kerupuk.', 'products/ZSW6xFOVPrfbRTY9zHxCJi4ynfxNEh61qX7WYGW6.jpg'],
            [10, 2, 'Mie Goreng', 15000, 'Ready', 35, 'Mie goreng gurih lezat lengkap dengan sayuran dan suwiran ayam.', 'products/BXKW4SdOxqus1ibw0R4EGrhFSEvTJ1rfhmOz71ay.jpg'],
            [10, 2, 'Nasi Ayam Suwir', 18000, 'Ready', 30, 'Nasi hangat pulen dengan lauk ayam suwir bumbu pedas gurih.', 'products/EZJifxVMHBzInYlOy3RtEFhYEU2Qbpe9CKqkpqBq.jpg'],
            [10, 2, 'Nasi Ayam Kremes', 20000, 'Ready', 30, 'Nasi hangat dengan ayam goreng empuk bertabur kremes renyah.', 'products/TwRg4Gl3FkJK0Qg3SgNtSBSYcYUtJhcoUIuYlzY3.jpg'],
            [10, 2, 'Nasi Ayam Sambal Hijau', 20000, 'Ready', 30, 'Nasi ayam goreng dipadukan dengan pedas segarnya sambal hijau.', 'products/UJbOK6wpQ9h6p4FsJPOuS9XTBbgwEVqlfVRF0FRz.jpg'],
            [10, 2, 'Nasi Kucing', 8000, 'Ready', 50, 'Nasi porsi kecil dibungkus daun pisang dilengkapi lauk sambal tempe dan teri.', 'products/hH3OOdItbIEU4wR0USBQDBfoWoM4zqPHYlsfY6ph.jpg'],
            [11, 2, 'Donat Nampah', 65000, 'Pre-Order', 15, 'Paket donat nampah aneka topping cantik untuk sajian acara.', 'products/KuHuwaeqly2vmlXuYFFDhuHAjo8C68FYGNBtBdQv.jpg', 2],
            [11, 2, 'Donat Mini Ucapan', 45000, 'Pre-Order', 20, 'Donat mini hias ucapan kustom untuk ulang tahun dan perayaan.', 'products/dcVMPzhoVo0zA4c3txiFtcrC7ZWH2pdUkiOstnVE.jpg', 2],
            [11, 2, 'Donat Kukus', 20000, 'Ready', 30, 'Donat kukus lembut dengan lelehan cokelat dan topping lezat.', 'products/CkfC2HwtApZ6InH8pAtmXU7j4buqgNiPcIQvmXax.jpg'],
            [11, 2, 'Bomboloni', 25000, 'Ready', 30, 'Bomboloni empuk dengan isian filling krim dan cokelat lumer.', 'products/Gbdn42zjYSZLOIMwGWknyrhMwolyvxHUPG6Vngkr.jpg'],
            [11, 2, 'Donat Mini Berkarakter', 35000, 'Pre-Order', 25, 'Donat mini dengan lukisan hias karakter lucu.', 'products/BUZIJZdAtUo8I1JaVD5BQzVD8jBVKmFqFE6xwaVB.jpg', 2],
            [11, 2, 'Donat Jumbo Karakter', 50000, 'Pre-Order', 15, 'Donat ukuran besar dengan dekorasi tema karakter khusus.', 'products/0gdNzLkHWJ5xRt30tySCfaVHv1bZg88bXvfeVYFQ.jpg', 2],
            [11, 2, 'Donat Sosis', 25000, 'Ready', 25, 'Donat isi sosis gurih dengan saus spesial dan mayonaise.', 'products/hzRXdxU8MrHKNTmYlDHZ377ZrfRnn17uvdxdu6ZV.jpg'],
            [11, 2, 'Donat Topping Hias', 30000, 'Ready', 30, 'Donat empuk aneka topping cantik warna-warni.', 'products/xQXWf7J5LHxgEcF5XGDs2g1HzyCFrKyUjEfZUJVk.jpg'],
            [11, 2, 'Donat Mix', 28000, 'Ready', 35, 'Paket donat lembut isi 6 perpaduan aneka rasa favorit.', 'products/ym6kQTinC19HTJ94bShjjUtUQJVOpFRlXqLQOgl1.jpg'],
            [11, 2, 'Donat Manis', 22000, 'Ready', 40, 'Donat manis empuk klasik tabur gula halus dan meses.', 'products/MEbqd8ehVWvukxIlX3WxmMS8d52u2wK9AnuKGdeV.jpg'],
            [11, 2, 'Donat Salju', 22000, 'Ready', 35, 'Donat lembut berbalut taburan gula salju dingin manis.', 'products/3BccL6IcEgxQLrCmfxSvPSzP8gVdaeYeFokLV5r9.jpg'],
            [11, 2, 'Donat Tar', 75000, 'Pre-Order', 10, 'Tart kue donat susun bertingkat spesial untuk perayaan.', 'products/tPmXSKETx5TZpNYn3DaIc8ndeaMhomPS3fUmn0XZ.jpg', 3],
            [11, 2, 'Donat Tumpeng', 90000, 'Pre-Order', 10, 'Susunan tumpeng donat mini dan jumbo meriah untuk syukuran.', 'products/ZDt5rZysLgrQ7A4ov0bL9ZuKOJkbWxyFd79ZlCzU.jpg', 3],
            [12, 3, 'Makaroni Pedas Asin', 10000, 'Ready', 50, 'Makaroni renyah bumbu gurih asin klasik.', 'products/T59PQ1m9EtTexmQE6CUMHOfisNoPCBePdSXqZePD.jpg'],
            [12, 3, 'Basreng Daun Jeruk', 15000, 'Ready', 45, 'Basreng renyah aroma wangi daun jeruk pedas gurih.', 'products/7LCe6QqWzXG9UFliq54Cf12rHBptJrGsNVzeLWf9.jpg'],
            [12, 3, 'Kacang Bawang', 18000, 'Ready', 40, 'Kacang bawang renyah dan gurih aroma bawang putih asli.', 'products/j2gpIcpq2Q2oLONNOyuzh12GVPwYc1ykxM8WIyk5.jpg'],
            [12, 3, 'Kacang Goreng', 15000, 'Ready', 40, 'Kacang tanah goreng renyah camilan santai keluarga.', 'products/W8Wgg7xTMtsh6EoCPOuYynogQPAg07hgJbPt42yj.jpg'],
            [13, 2, 'Dadar Santan Kacang', 12000, 'Ready', 30, 'Kue dadar lembut berkuah santan manis gurih dengan kacang pilihan.', 'products/RHBktReebq4fyxyrDK90GZNFtqkR5VADHTd6j35m.jpg'],
            [13, 2, 'Es Kepon Khas', 10000, 'Ready', 35, 'Minuman es kepon segar khas pelepas dahaga.', 'products/PED5ILGahfA5IYSyDnP6cxkGkT6v5tIcXeWP1Yua.jpg'],
            [13, 2, 'Pisang Ijo Monat', 15000, 'Ready', 30, 'Pisang ijo lembut disajikan dengan bubur sumsum dan sirup manis.', 'products/i3CvprL3gIHGgV5zQAkfoLohSKVcYI9p9iEJz8e5.jpg'],
            [14, 2, 'Roti MesesROti  Mini', 12000, 'Ready', 35, 'Roti manis mini lembut bertabur meses cokelat lezat.', 'products/zFbst49Y8NMynEZyWo15qgwJOU2G0lQiCRrgELPD.jpg'],
            [14, 2, 'Ayam Madu', 25000, 'Ready', 25, 'Ayam olahan bumbu saus madu manis gurih meresap sampai ke tulang.', 'products/sby1kKouKACcgLtB9Gf2xZQUQWviAeV7CWcKReCR.jpg'],
            [14, 2, 'Bakso & Tahu Daging', 18000, 'Ready', 30, 'Porsi bakso daging sapi gurih lengkap dengan tahu bakso empuk.', 'products/KcLnB5M5AvTUfesFBXc6Yjyqg6Al2FukAVAGRHJ3.jpg'],
            [15, 1, 'Kerajinan Tangan Bambu', 35000, 'Ready', 20, 'Kerajinan tangan anyaman bambu ramah lingkungan dan estetik.', 'products/rjRFvWZSNdwd1XvNLlG0DShUJJZoce8kkGXRY8K5.jpg'],
            [15, 1, 'Anyaman Tangan', 30000, 'Ready', 20, 'Aneka produk olahan anyaman tangan tradisional yang rapi dan kuat.', 'products/SExsa0J7WYqIPEgizQZ3Du45R5YknQfDCezd8yGJ.jpg'],
            [15, 1, 'Tas Buatan Wol', 50000, 'Ready', 15, 'Tas rajutan tangan dari benang wol halus berpola unik.', 'products/sxde6BLpKHx2Z40TEPVqxdOThAAXnQcbFhr34jaV.jpg'],
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

        $recommendations = [
            [
                'umkm_id' => 1,
                'judul' => 'Paket Promo Bundling Pastry & Selai',
                'isi' => 'Manfaatkan momen rapat warga dengan membuat varian paket camilan pastry dan selai dengan harga promo khusus.',
                'tipe' => 'promosi',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
            [
                'umkm_id' => 2,
                'judul' => 'Optimasi Foto Produk Jalangkote & Empek-empek',
                'isi' => 'Tambahkan foto detail isian jalangkote telur dan kuah cuko empek-empek di etalase katalog untuk menarik minat pembeli.',
                'tipe' => 'produk',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
            [
                'umkm_id' => 5,
                'judul' => 'Peningkatan Kemasan Cookies Stand Up Pouch',
                'isi' => 'Gunakan standing pouch kedap udara agar ketahanan kerenyahan cookies dan makaroni lebih optimal saat pengiriman.',
                'tipe' => 'produk',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
            [
                'umkm_id' => 11,
                'judul' => 'Penawaran Diskon Akhir Pekan Donat Mix',
                'isi' => 'Terapkan diskon 15% setiap hari Sabtu sore untuk meningkatkan volume penjualan donat box.',
                'tipe' => 'harga',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
            [
                'umkm_id' => 4,
                'judul' => 'Kerjasama Titik Jual Rest Area Moncongloe',
                'isi' => 'Pengelola platform dapat membantu menitipkan produk Sarang Madu Muda di etalase Pusat Oleh-Oleh Kecamatan.',
                'tipe' => 'distribusi',
                'periode' => date('Y-m'),
                'dibaca' => false,
            ],
        ];

        foreach ($recommendations as $rec) {
            RekomendasiStrategi::create($rec);
        }

        $kelompokSnack = KelompokKeroyokan::create([
            'kategori_id' => 2, // Kuliner
            'nama_kelompok' => 'Snack Box Acara Desa',
            'deskripsi' => 'Paket gabungan snack box lokal untuk konsumsi rapat, seminar, dan acara warga desa.',
            'aktif' => true,
        ]);

        $kelompokOlehOleh = KelompokKeroyokan::create([
            'kategori_id' => 3, // Oleh-oleh
            'nama_kelompok' => 'Paket Keripik & Oleh-oleh Khas',
            'deskripsi' => 'Gabungan aneka camilan kering dan keripik khas Moncongloe Lappara.',
            'aktif' => true,
        ]);

        Produk::whereIn('nama_produk', ['Pastry Salju', 'Jalangkote Sayur', 'Jalangkote Telur', 'Risol Mayo', 'Donat Mix', 'Dadar Santan Kacang', 'Roti MesesROti  Mini'])
            ->update(['kelompok_keroyokan_id' => $kelompokSnack->id]);

        Produk::whereIn('nama_produk', ['Keripik Ubi Pedas', 'Keripik Ikan', 'Sambel Kemasan', 'Makaroni Pedas Asin', 'Basreng Daun Jeruk', 'Kacang Bawang'])
            ->update(['kelompok_keroyokan_id' => $kelompokOlehOleh->id]);

        $budi = User::where('username', 'budi_pembeli')->first();
        $hikmah = User::where('username', 'hikmah')->first();
        $mozza = User::where('username', 'mozzapiey')->first();

        if ($budi && $kelompokSnack) {
            $batch = BatchKeroyokan::create([
                'pembeli_id' => $budi->id,
                'kelompok_keroyokan_id' => $kelompokSnack->id,
                'target_jumlah' => 15,
                'total_harga' => 260000,
            ]);

            $p6 = Produk::find(6); // Jalangkote Sayur
            $p7 = Produk::find(7); // Jalangkote Telur
            $p25 = Produk::find(25); // Risol Mayo

            if ($p6 && $p7 && $p25) {
                Pesanan::create([
                    'pembeli_id' => $budi->id,
                    'batch_keroyokan_id' => $batch->id,
                    'produk_id' => $p6->id,
                    'jumlah' => 15,
                    'total_harga' => 76667,
                    'ongkos_kirim' => 1667,
                    'biaya_packing' => 0,
                    'komisi_admin' => 2250,
                    'pendapatan_penjual' => 72750,
                    'metode_pembayaran' => 'QRIS',
                    'status_pembayaran' => 'Sudah Dibayar',
                    'alamat_pengiriman' => 'Moncongloe Lappara RT 02',
                    'zona_pengiriman' => 'Luar Desa, Dalam Kecamatan',
                    'no_hp_pembeli' => '081234500006',
                    'status' => 'Selesai',
                    'opsi_packing' => 'Standar',
                    'tanggal_pesan' => now()->subDays(2),
                ]);

                Pesanan::create([
                    'pembeli_id' => $budi->id,
                    'batch_keroyokan_id' => $batch->id,
                    'produk_id' => $p7->id,
                    'jumlah' => 15,
                    'total_harga' => 91667,
                    'ongkos_kirim' => 1667,
                    'biaya_packing' => 0,
                    'komisi_admin' => 2700,
                    'pendapatan_penjual' => 87300,
                    'metode_pembayaran' => 'QRIS',
                    'status_pembayaran' => 'Sudah Dibayar',
                    'alamat_pengiriman' => 'Moncongloe Lappara RT 02',
                    'zona_pengiriman' => 'Luar Desa, Dalam Kecamatan',
                    'no_hp_pembeli' => '081234500006',
                    'status' => 'Selesai',
                    'opsi_packing' => 'Standar',
                    'tanggal_pesan' => now()->subDays(2),
                ]);

                Pesanan::create([
                    'pembeli_id' => $budi->id,
                    'batch_keroyokan_id' => $batch->id,
                    'produk_id' => $p25->id,
                    'jumlah' => 15,
                    'total_harga' => 91666,
                    'ongkos_kirim' => 1666,
                    'biaya_packing' => 0,
                    'komisi_admin' => 2700,
                    'pendapatan_penjual' => 87300,
                    'metode_pembayaran' => 'QRIS',
                    'status_pembayaran' => 'Sudah Dibayar',
                    'alamat_pengiriman' => 'Moncongloe Lappara RT 02',
                    'zona_pengiriman' => 'Luar Desa, Dalam Kecamatan',
                    'no_hp_pembeli' => '081234500006',
                    'status' => 'Selesai',
                    'opsi_packing' => 'Standar',
                    'tanggal_pesan' => now()->subDays(2),
                ]);
            }
        }

        if ($hikmah) {
            $p4 = Produk::find(4); // Empek-empek
            if ($p4) {
                $order4 = Pesanan::create([
                    'pembeli_id' => $hikmah->id,
                    'produk_id' => $p4->id,
                    'jumlah' => 3,
                    'total_harga' => 47000,
                    'ongkos_kirim' => 2000,
                    'biaya_packing' => 0,
                    'komisi_admin' => 1350,
                    'pendapatan_penjual' => 43650,
                    'metode_pembayaran' => 'COD',
                    'status_pembayaran' => 'Sudah Dibayar',
                    'alamat_pengiriman' => 'Dusun Lappara',
                    'zona_pengiriman' => 'Dalam Desa',
                    'no_hp_pembeli' => '08012345678',
                    'status' => 'Selesai',
                    'catatan' => 'Bungkus satu-satu',
                    'opsi_packing' => 'Standar',
                    'tanggal_pesan' => now()->subDay(),
                ]);

                Ulasan::create([
                    'pesanan_id' => $order4->id,
                    'produk_id' => $p4->id,
                    'pembeli_id' => $hikmah->id,
                    'rating' => 5,
                    'komentar' => 'Pengiriman cepat dan rasa sangat mantap!',
                ]);
            }

            $p14 = Produk::find(14); // Cookies
            if ($p14) {
                $order14 = Pesanan::create([
                    'pembeli_id' => $hikmah->id,
                    'produk_id' => $p14->id,
                    'jumlah' => 10,
                    'total_harga' => 287000,
                    'ongkos_kirim' => 25000,
                    'biaya_packing' => 12000,
                    'komisi_admin' => 7500,
                    'pendapatan_penjual' => 242500,
                    'metode_pembayaran' => 'QRIS',
                    'status_pembayaran' => 'Sudah Dibayar',
                    'alamat_pengiriman' => 'Jl. Poros Moncongloe',
                    'zona_pengiriman' => 'Luar Kabupaten',
                    'no_hp_pembeli' => '08012345678',
                    'status' => 'Selesai',
                    'catatan' => 'Kemasan kado spesial',
                    'opsi_packing' => 'Hadiah',
                    'tanggal_pesan' => now()->subDay(),
                ]);

                Ulasan::create([
                    'pesanan_id' => $order14->id,
                    'produk_id' => $p14->id,
                    'pembeli_id' => $hikmah->id,
                    'rating' => 5,
                    'komentar' => 'Renyah dan manisnya pas!',
                ]);
            }

            $p52 = Produk::find(52); // Makaroni Pedas Asin
            if ($p52) {
                $order52 = Pesanan::create([
                    'pembeli_id' => $hikmah->id,
                    'produk_id' => $p52->id,
                    'jumlah' => 20,
                    'total_harga' => 332000,
                    'ongkos_kirim' => 25000,
                    'biaya_packing' => 7000,
                    'komisi_admin' => 9000,
                    'pendapatan_penjual' => 291000,
                    'metode_pembayaran' => 'QRIS',
                    'status_pembayaran' => 'Sudah Dibayar',
                    'alamat_pengiriman' => 'Hartaco Indah',
                    'zona_pengiriman' => 'Luar Kabupaten',
                    'no_hp_pembeli' => '08012345678',
                    'status' => 'Selesai',
                    'catatan' => 'Bungkus rapi',
                    'opsi_packing' => 'Premium',
                    'tanggal_pesan' => now()->subDay(),
                ]);

                Ulasan::create([
                    'pesanan_id' => $order52->id,
                    'produk_id' => $p52->id,
                    'pembeli_id' => $hikmah->id,
                    'rating' => 5,
                    'komentar' => 'Keren, rasa pedas asinnya nagih!',
                ]);
            }

            $p59 = Produk::find(59); // Ayam Madu
            if ($p59) {
                $order59 = Pesanan::create([
                    'pembeli_id' => $hikmah->id,
                    'produk_id' => $p59->id,
                    'jumlah' => 25,
                    'total_harga' => 657000,
                    'ongkos_kirim' => 25000,
                    'biaya_packing' => 7000,
                    'komisi_admin' => 18750,
                    'pendapatan_penjual' => 606250,
                    'metode_pembayaran' => 'COD',
                    'status_pembayaran' => 'Sudah Dibayar',
                    'alamat_pengiriman' => 'Jl. Urip',
                    'zona_pengiriman' => 'Luar Kabupaten',
                    'no_hp_pembeli' => '08012345678',
                    'status' => 'Selesai',
                    'catatan' => 'Bungkus box',
                    'opsi_packing' => 'Premium',
                    'tanggal_pesan' => now()->subHours(6),
                ]);

                Ulasan::create([
                    'pesanan_id' => $order59->id,
                    'produk_id' => $p59->id,
                    'pembeli_id' => $hikmah->id,
                    'rating' => 4,
                    'komentar' => 'Ayam bumbu madunya enak sekali!',
                ]);

                $adminUser = User::where('role', 'admin')->first();
                $disburse = Disbursement::create([
                    'umkm_id' => 14,
                    'jumlah' => 606250,
                    'status' => 'dibayar',
                    'catatan' => 'Pencairan dana langsung oleh Admin untuk Kedai Arisz',
                    'dibayar_at' => now(),
                    'admin_id' => $adminUser?->id,
                ]);
                $disburse->pesanan()->attach($order59->id);
            }
        }

        if ($mozza) {
            $p15 = Produk::find(15); // Macaroni Aneka Rasa
            if ($p15) {
                $order15 = Pesanan::create([
                    'pembeli_id' => $mozza->id,
                    'produk_id' => $p15->id,
                    'jumlah' => 1,
                    'total_harga' => 12000,
                    'ongkos_kirim' => 2000,
                    'biaya_packing' => 0,
                    'komisi_admin' => 300,
                    'pendapatan_penjual' => 9700,
                    'metode_pembayaran' => 'COD',
                    'status_pembayaran' => 'Sudah Dibayar',
                    'alamat_pengiriman' => 'Gedung Kemahasiswaan',
                    'zona_pengiriman' => 'Dalam Desa',
                    'no_hp_pembeli' => '0895803005021',
                    'status' => 'Selesai',
                    'catatan' => 'Banyakin saosnya',
                    'opsi_packing' => 'Standar',
                    'tanggal_pesan' => now()->subHours(2),
                ]);

                Ulasan::create([
                    'pesanan_id' => $order15->id,
                    'produk_id' => $p15->id,
                    'pembeli_id' => $mozza->id,
                    'rating' => 5,
                    'komentar' => 'Yummy dan renyah!',
                ]);
            }
        }
    }
}
