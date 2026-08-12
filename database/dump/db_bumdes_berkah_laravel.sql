-- BUMDes Berkah / Gemastik - Laravel database dump
-- Target: MySQL 8+ / MariaDB 10.6+
-- Generated: 2026-08-13
-- Demo password for every seeded account: password123

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
SET time_zone = '+08:00';

DROP TABLE IF EXISTS `log_aktivitas`;
DROP TABLE IF EXISTS `ulasan`;
DROP TABLE IF EXISTS `pesanan`;
DROP TABLE IF EXISTS `produk`;
DROP TABLE IF EXISTS `umkm`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'pembeli',
  `status` varchar(20) NOT NULL DEFAULT 'aktif',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kategori` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kategori_nama_kategori_unique` (`nama_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `umkm` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `nama_umkm` varchar(150) NOT NULL,
  `pemilik` varchar(100) NOT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `umkm_user_id_unique` (`user_id`),
  KEY `umkm_status_index` (`status`),
  CONSTRAINT `umkm_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `produk` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `umkm_id` bigint unsigned NOT NULL,
  `kategori_id` bigint unsigned NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok_status` varchar(20) NOT NULL DEFAULT 'Ready',
  `stok_jumlah` int unsigned NOT NULL DEFAULT 10,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produk_umkm_id_index` (`umkm_id`),
  KEY `produk_kategori_id_index` (`kategori_id`),
  KEY `produk_stok_status_index` (`stok_status`),
  KEY `produk_umkm_id_created_at_index` (`umkm_id`,`created_at`),
  CONSTRAINT `produk_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkm` (`id`) ON DELETE CASCADE,
  CONSTRAINT `produk_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pesanan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pembeli_id` bigint unsigned NOT NULL,
  `produk_id` bigint unsigned NOT NULL,
  `jumlah` int unsigned NOT NULL DEFAULT 1,
  `total_harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `metode_pembayaran` varchar(20) NOT NULL DEFAULT 'COD',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `alamat_pengiriman` varchar(255) DEFAULT NULL,
  `no_hp_pembeli` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Menunggu',
  `catatan` varchar(255) DEFAULT NULL,
  `tanggal_pesan` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pesanan_pembeli_id_index` (`pembeli_id`),
  KEY `pesanan_produk_id_index` (`produk_id`),
  KEY `pesanan_metode_pembayaran_index` (`metode_pembayaran`),
  KEY `pesanan_status_index` (`status`),
  KEY `pesanan_tanggal_pesan_index` (`tanggal_pesan`),
  KEY `pesanan_status_tanggal_pesan_index` (`status`,`tanggal_pesan`),
  CONSTRAINT `pesanan_pembeli_id_foreign` FOREIGN KEY (`pembeli_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pesanan_produk_id_foreign` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ulasan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pesanan_id` bigint unsigned NOT NULL,
  `produk_id` bigint unsigned NOT NULL,
  `pembeli_id` bigint unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL DEFAULT 5,
  `komentar` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ulasan_pesanan_id_unique` (`pesanan_id`),
  KEY `ulasan_produk_id_index` (`produk_id`),
  KEY `ulasan_pembeli_id_index` (`pembeli_id`),
  KEY `ulasan_rating_index` (`rating`),
  CONSTRAINT `ulasan_pesanan_id_foreign` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ulasan_produk_id_foreign` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ulasan_pembeli_id_foreign` FOREIGN KEY (`pembeli_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `log_aktivitas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_aktivitas_user_id_index` (`user_id`),
  KEY `log_aktivitas_created_at_index` (`created_at`),
  CONSTRAINT `log_aktivitas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES
(1,'2026_08_13_000001_create_users_table',1),
(2,'2026_08_13_000002_create_kategori_table',1),
(3,'2026_08_13_000003_create_umkm_table',1),
(4,'2026_08_13_000004_create_produk_table',1),
(5,'2026_08_13_000005_create_pesanan_table',1),
(6,'2026_08_13_000006_create_ulasan_table',1),
(7,'2026_08_13_000007_create_log_aktivitas_table',1);

INSERT INTO `users` (`id`,`username`,`password`,`nama_lengkap`,`email`,`no_hp`,`role`,`status`,`created_at`,`updated_at`) VALUES
(1,'admin','$2y$12$MeXMn1x5p2dWGkb6LXmkNeNee9yV7mO4NYheuhpKrubN.CB8suqdC','Ketua BUMDes Berkah','admin@bumdesberkah.id','081234500001','admin','aktif','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(2,'umkm_jalangkote','$2y$12$MeXMn1x5p2dWGkb6LXmkNeNee9yV7mO4NYheuhpKrubN.CB8suqdC','Ibu Sari','sari.jalangkote@gmail.com','081234500002','penjual','aktif','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(3,'umkm_pisangepe','$2y$12$MeXMn1x5p2dWGkb6LXmkNeNee9yV7mO4NYheuhpKrubN.CB8suqdC','Pak Baso','baso.pisangepe@gmail.com','081234500003','penjual','aktif','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(4,'umkm_kripik','$2y$12$MeXMn1x5p2dWGkb6LXmkNeNee9yV7mO4NYheuhpKrubN.CB8suqdC','Ibu Nur','nur.kripik@gmail.com','081234500004','penjual','aktif','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(5,'umkm_anyaman','$2y$12$MeXMn1x5p2dWGkb6LXmkNeNee9yV7mO4NYheuhpKrubN.CB8suqdC','Pak Dg. Tola','tola.anyaman@gmail.com','081234500005','penjual','aktif','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(6,'budi_pembeli','$2y$12$MeXMn1x5p2dWGkb6LXmkNeNee9yV7mO4NYheuhpKrubN.CB8suqdC','Budi Santoso','budi@gmail.com','081234500006','pembeli','aktif','2026-08-13 03:00:00','2026-08-13 03:00:00');

INSERT INTO `kategori` (`id`,`nama_kategori`,`created_at`,`updated_at`) VALUES
(1,'Kuliner Basah','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(2,'Produk Kering / Oleh-oleh','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(3,'Kerajinan / Kreatif','2026-08-13 03:00:00','2026-08-13 03:00:00');

INSERT INTO `umkm` (`id`,`user_id`,`nama_umkm`,`pemilik`,`alamat`,`no_hp`,`deskripsi`,`foto`,`status`,`created_at`,`updated_at`) VALUES
(1,2,'Jalangkote Bu Sari','Ibu Sari','Kawasan Kuliner Moncongloe Lappara','081234500002','Jalangkote rumahan yang dibuat segar untuk warga dan pengunjung Moncongloe Lappara.',NULL,'aktif','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(2,3,'Pisang Epe & Bakso Bakar Pak Baso','Pak Baso','Kawasan Kuliner Moncongloe Lappara','081234500003','Pisang epe, bakso bakar, dan minuman segar untuk suasana sore di Moncongloe Lappara.',NULL,'aktif','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(3,4,'Kripik Moncongloe Bu Nur','Ibu Nur','Dusun Moncongloe Lappara','081234500004','Camilan kering dan kue tradisional produksi warga Moncongloe Lappara.',NULL,'aktif','2026-08-13 03:00:00','2026-08-13 03:00:00'),
(4,5,'Anyaman Kreatif Dg. Tola','Pak Dg. Tola','Dusun Moncongloe Lappara','081234500005','Kerajinan bambu dan suvenir buatan tangan untuk kebutuhan harian dan oleh-oleh.',NULL,'aktif','2026-08-13 03:00:00','2026-08-13 03:00:00');

INSERT INTO `produk` (`id`,`umkm_id`,`kategori_id`,`nama_produk`,`harga`,`stok_status`,`stok_jumlah`,`deskripsi`,`foto`,`created_at`,`updated_at`) VALUES
(1,1,1,'Jalangkote Isi Sayur',5000.00,'Ready',30,'Jalangkote renyah isi sayur wortel dan kentang, enak dinikmati hangat.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00'),
(2,1,1,'Jalangkote Isi Telur',6000.00,'Ready',24,'Jalangkote dengan isian telur dan sayur, dibuat segar setiap hari.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00'),
(3,2,1,'Pisang Epe Coklat Keju',15000.00,'Ready',18,'Pisang epe bakar dengan gula merah, cokelat, dan keju parut.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00'),
(4,2,1,'Bakso Bakar',12000.00,'Ready',25,'Bakso bakar dengan bumbu gurih khas Moncongloe Lappara, isi 10 tusuk.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00'),
(5,2,1,'Jus Buah Segar',10000.00,'Ready',20,'Jus buah segar pilihan tanpa bahan pengawet.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00'),
(6,3,2,'Kripik Pisang Original',15000.00,'Ready',40,'Kripik pisang gurih kemasan 250 gram, cocok untuk oleh-oleh.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00'),
(7,3,2,'Kripik Singkong Moncongloe',13000.00,'Ready',36,'Kripik singkong renyah tersedia rasa original dan pedas.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00'),
(8,3,2,'Kue Tradisional Campur',20000.00,'Pre-Order',12,'Paket kue tradisional dibuat sesuai jadwal pesanan.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00'),
(9,4,3,'Anyaman Tas Bambu',45000.00,'Ready',8,'Tas anyaman bambu buatan tangan dengan karakter alami.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00'),
(10,4,3,'Suvenir Miniatur Desa Wisata',25000.00,'Pre-Order',10,'Suvenir miniatur khas desa wisata Moncongloe Lappara.',NULL,'2026-08-13 03:00:00','2026-08-13 03:00:00');

INSERT INTO `pesanan` (`id`,`pembeli_id`,`produk_id`,`jumlah`,`total_harga`,`metode_pembayaran`,`bukti_pembayaran`,`alamat_pengiriman`,`no_hp_pembeli`,`status`,`catatan`,`tanggal_pesan`,`created_at`,`updated_at`) VALUES
(1,6,3,2,30000.00,'COD',NULL,'Moncongloe Lappara','081234500006','Selesai','Bungkus terpisah','2026-08-12 17:30:00','2026-08-12 17:30:00','2026-08-12 18:40:00'),
(2,6,6,1,15000.00,'Transfer',NULL,'Moncongloe Lappara','081234500006','Menunggu',NULL,'2026-08-13 02:30:00','2026-08-13 02:30:00','2026-08-13 02:30:00');

INSERT INTO `ulasan` (`id`,`pesanan_id`,`produk_id`,`pembeli_id`,`rating`,`komentar`,`created_at`,`updated_at`) VALUES
(1,1,3,6,5,'Pisang epennya enak dan masih hangat saat diterima.','2026-08-12 19:00:00','2026-08-12 19:00:00');

SET FOREIGN_KEY_CHECKS=1;
