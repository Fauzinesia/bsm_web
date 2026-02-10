-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 13, 2026 at 02:28 AM
-- Server version: 8.0.30
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bsm_web`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_biaya_operasional`
--

CREATE TABLE `tb_biaya_operasional` (
  `id_biaya` int NOT NULL,
  `id_kendaraan` int DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jenis_biaya` enum('BBM','Tol','Parkir','Pajak','Asuransi','Lainnya') DEFAULT 'Lainnya',
  `nominal` decimal(15,2) DEFAULT NULL,
  `keterangan` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_biaya_operasional`
--

INSERT INTO `tb_biaya_operasional` (`id_biaya`, `id_kendaraan`, `tanggal`, `jenis_biaya`, `nominal`, `keterangan`) VALUES
(1, 2, '2025-11-07', 'BBM', '300000.00', 'Pembelian BBM '),
(2, 5, '2025-11-07', 'Pajak', '1200000.00', 'Pembayaran Pajak Tahunan'),
(3, 2, '2025-11-25', 'Lainnya', '500000.00', 'ganti oli'),
(4, 4, '2025-12-03', 'Lainnya', '1500000.00', 'Rujukan maintenance #4 (2025-12-03) Service Ringan pada Mitsubishi L300 DA 9012 EF\nRef Maintenance #4'),
(6, 3, '2025-12-03', 'Lainnya', '5000000.00', 'service');

-- --------------------------------------------------------

--
-- Table structure for table `tb_inspeksi`
--

CREATE TABLE `tb_inspeksi` (
  `id_inspeksi` int NOT NULL,
  `id_kendaraan` int DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `kondisi_ban` enum('Baik','Perlu dicek','Rusak') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kondisi_lampu` enum('Baik','Rusak') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `oli_mesin` enum('Baik','Kurang','Harus ganti') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rem` enum('Baik','Perlu diperhatikan','Rusak') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kebersihan` enum('Bersih','Cukup','Kotor') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_inspeksi`
--

INSERT INTO `tb_inspeksi` (`id_inspeksi`, `id_kendaraan`, `tanggal`, `kondisi_ban`, `kondisi_lampu`, `oli_mesin`, `rem`, `kebersihan`, `catatan`) VALUES
(2, 4, '2025-12-03', 'Perlu dicek', 'Rusak', 'Harus ganti', 'Perlu diperhatikan', 'Cukup', 'Maintenece'),
(4, 3, '2025-12-03', 'Baik', 'Baik', 'Baik', 'Baik', 'Bersih', 'Cek tekanan ban dan kondisi rem'),
(5, 2, '2026-01-09', 'Baik', 'Baik', 'Baik', 'Perlu diperhatikan', 'Bersih', ''),
(6, 4, '2026-01-09', 'Baik', 'Baik', 'Baik', 'Baik', 'Bersih', ''),
(7, 4, '2026-01-09', 'Baik', 'Baik', 'Baik', 'Baik', 'Cukup', 'cek tekanan ban');

-- --------------------------------------------------------

--
-- Table structure for table `tb_kendaraan`
--

CREATE TABLE `tb_kendaraan` (
  `id_kendaraan` int NOT NULL,
  `nomor_polisi` varchar(15) NOT NULL,
  `nama_kendaraan` varchar(100) NOT NULL,
  `merk` varchar(100) DEFAULT NULL,
  `tipe` varchar(100) DEFAULT NULL,
  `tahun` int DEFAULT NULL,
  `warna` varchar(50) DEFAULT NULL,
  `nomor_rangka` varchar(50) DEFAULT NULL,
  `nomor_mesin` varchar(50) DEFAULT NULL,
  `nomor_bpkb` varchar(50) DEFAULT NULL,
  `masa_berlaku_stnk` date DEFAULT NULL,
  `masa_berlaku_pajak` date DEFAULT NULL,
  `masa_berlaku_asuransi` date DEFAULT NULL,
  `status_operasional` enum('Aktif','Perawatan','Rusak','Disewa') DEFAULT 'Aktif',
  `foto_kendaraan` varchar(255) DEFAULT NULL,
  `dokumen_stnk` varchar(255) DEFAULT NULL,
  `dokumen_bpkb` varchar(255) DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_kendaraan`
--

INSERT INTO `tb_kendaraan` (`id_kendaraan`, `nomor_polisi`, `nama_kendaraan`, `merk`, `tipe`, `tahun`, `warna`, `nomor_rangka`, `nomor_mesin`, `nomor_bpkb`, `masa_berlaku_stnk`, `masa_berlaku_pajak`, `masa_berlaku_asuransi`, `status_operasional`, `foto_kendaraan`, `dokumen_stnk`, `dokumen_bpkb`, `keterangan`, `created_at`, `updated_at`) VALUES
(2, 'DA 1234 AB', 'Toyota Avanza 1.3 G', 'Toyota', 'MPV', 2019, 'Silver', 'MHX12345AVZ67890', '1NR1234567', 'BPKB001122', '2026-03-15', '2026-03-15', '2026-03-15', 'Aktif', 'uploads/kendaraan/foto_690df4e8b2513.jpeg', 'uploads/dokumen/stnk_avanza.pdf', 'uploads/dokumen/bpkb_avanza.pdf', 'Kendaraan dinas bagian umum.', '2025-11-07 13:29:25', '2025-11-07 13:32:24'),
(3, 'DA 5678 CD', 'Honda HR-V 1.5 E', 'Honda', 'SUV', 2020, 'Putih', 'MHX67890HRV12345', 'L15Z112233', 'BPKB334455', '2026-06-20', '2026-06-20', '2026-06-20', 'Perawatan', 'uploads/kendaraan/foto_690df4f34db95.jpeg', 'uploads/dokumen/stnk_hrv.pdf', 'uploads/dokumen/bpkb_hrv.pdf', 'Sedang dalam perawatan rutin di bengkel resmi.', '2025-11-07 13:29:25', '2025-11-07 13:32:35'),
(4, 'DA 9012 EF', 'Mitsubishi L300', 'Mitsubishi', 'Pick Up', 2018, 'Hitam', 'MHX54321L30098765', '4D56U765432', 'BPKB667788', '2025-09-10', '2025-09-10', '2025-09-10', 'Aktif', 'uploads/kendaraan/foto_690df5039fabd.jpeg', 'uploads/dokumen/stnk_l300.pdf', 'uploads/dokumen/bpkb_l300.pdf', 'Digunakan untuk operasional logistik.', '2025-11-07 13:29:25', '2025-11-07 13:32:51'),
(5, 'DA 3456 GH', 'Suzuki Ertiga GL', 'Suzuki', 'MPV', 2021, 'Merah', 'MHX99887ERT66789', 'K15B445566', 'BPKB990011', '2026-12-05', '2026-12-05', '2026-12-05', 'Disewa', 'uploads/kendaraan/foto_690df51073026.jpg', 'uploads/dokumen/stnk_ertiga.pdf', 'uploads/dokumen/bpkb_ertiga.pdf', 'Sedang disewa oleh bagian humas.', '2025-11-07 13:29:25', '2025-11-07 13:33:04'),
(6, 'DA 7890 IJ', 'Isuzu Elf NLR', 'Isuzu', 'Bus Mini', 2017, 'Biru', 'MHXELF7788990011', '4JJ1156789', 'BPKB223344', '2025-08-30', '2025-08-30', '2025-08-30', 'Rusak', 'uploads/kendaraan/foto_690df51ddb279.jpeg', 'uploads/dokumen/stnk_elf.pdf', 'uploads/dokumen/bpkb_elf.pdf', 'Dalam perbaikan berat akibat kerusakan mesin.', '2025-11-07 13:29:25', '2025-11-07 13:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `tb_maintenance`
--

CREATE TABLE `tb_maintenance` (
  `id_maintenance` int NOT NULL,
  `id_kendaraan` int DEFAULT NULL,
  `tanggal_service` date DEFAULT NULL,
  `jenis_service` varchar(100) DEFAULT NULL,
  `deskripsi` text,
  `biaya` decimal(15,2) DEFAULT NULL,
  `status` enum('Dijadwalkan','Proses','Selesai') DEFAULT 'Dijadwalkan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_maintenance`
--

INSERT INTO `tb_maintenance` (`id_maintenance`, `id_kendaraan`, `tanggal_service`, `jenis_service`, `deskripsi`, `biaya`, `status`) VALUES
(2, 5, '2025-11-08', 'Service Ringan', '-Ganti oli\r\n-Ganti Filter Oli\r\n-Bersihkan Filter udara\r\n-bersihkan Filter AC', '450000.00', 'Selesai'),
(3, 2, '2025-11-25', 'Service Ringan', '-ganti oli\r\n-service filter\r\n-ganti gas\r\n-ganti oli transmisi', '500.00', 'Selesai'),
(4, 4, '2025-12-03', 'Service Ringan', 'Rujukan inspeksi #2 (2025-12-03) pada Mitsubishi L300 DA 9012 EF\r\nBan: Perlu dicek\r\nLampu: Rusak\r\nOli: Harus ganti\r\nRem: Perlu diperhatikan\r\nKebersihan: Cukup\r\nCatatan: Maintenece\nRef Inspeksi #2', '1500000.00', 'Selesai'),
(6, 3, '2025-12-03', 'Service Ringan', 'Rujukan inspeksi #4 (2025-12-03) pada Honda HR-V 1.5 E DA 5678 CD\r\nBan: Perlu dicek\r\nLampu: Baik\r\nOli: Harus ganti\r\nRem: Perlu diperhatikan\r\nKebersihan: Bersih\r\nCatatan: Cek tekanan ban dan kondisi rem\r\nRef Inspeksi #4', '500000.00', 'Selesai'),
(7, 6, '2025-12-22', 'Service', 'asada', '299999.00', 'Dijadwalkan'),
(8, 5, '1980-03-26', 'Explicabo Reprehend', 'Nemo quis at aute iu', '75.00', 'Dijadwalkan'),
(9, 6, '2025-12-22', 'Ab in minus distinct', 'Quibusdam aspernatur', '57.00', 'Proses'),
(10, 2, '2026-01-09', 'Service Ringan', 'Rujukan inspeksi #5 (2026-01-09) pada Toyota Avanza 1.3 G DA 1234 AB\r\nBan: Baik\r\nLampu: Baik\r\nOli: Baik\r\nRem: Baik\r\nKebersihan: Bersih\nRef Inspeksi #5', NULL, 'Dijadwalkan');

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengguna`
--

CREATE TABLE `tb_pengguna` (
  `id_pengguna` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','User') DEFAULT 'User',
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_pengguna`
--

INSERT INTO `tb_pengguna` (`id_pengguna`, `nama`, `username`, `password`, `role`, `status`, `created_at`) VALUES
(3, 'admin', 'admin', '$2y$10$wKxRDmJsdO5Irejz4GoaluYikb3OebLmgCpL52.Q39SgtSc7gVCBC', 'Admin', 'Aktif', '2025-10-11 02:16:06'),
(4, 'UJI COBA', 'coba', '$2y$10$ePYx4hPfZZX48vTeE.dfhe.V5wDGbNjhd2wW74DWV1Dlsz/rHd1L.', 'User', 'Aktif', '2025-10-11 13:13:39');

-- --------------------------------------------------------

--
-- Table structure for table `tb_penggunaan`
--

CREATE TABLE `tb_penggunaan` (
  `id_penggunaan` int NOT NULL,
  `id_kendaraan` int DEFAULT NULL,
  `id_pengguna` int DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `keperluan` text,
  `status` enum('Berjalan','Selesai') DEFAULT 'Berjalan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_penggunaan`
--

INSERT INTO `tb_penggunaan` (`id_penggunaan`, `id_kendaraan`, `id_pengguna`, `tanggal_mulai`, `tanggal_selesai`, `keperluan`, `status`) VALUES
(9, 3, 4, '2025-12-22', '2025-12-25', 'SEwa', 'Selesai');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_biaya_operasional`
--
ALTER TABLE `tb_biaya_operasional`
  ADD PRIMARY KEY (`id_biaya`),
  ADD KEY `id_kendaraan` (`id_kendaraan`);

--
-- Indexes for table `tb_inspeksi`
--
ALTER TABLE `tb_inspeksi`
  ADD PRIMARY KEY (`id_inspeksi`),
  ADD KEY `id_kendaraan` (`id_kendaraan`);

--
-- Indexes for table `tb_kendaraan`
--
ALTER TABLE `tb_kendaraan`
  ADD PRIMARY KEY (`id_kendaraan`);

--
-- Indexes for table `tb_maintenance`
--
ALTER TABLE `tb_maintenance`
  ADD PRIMARY KEY (`id_maintenance`),
  ADD KEY `id_kendaraan` (`id_kendaraan`);

--
-- Indexes for table `tb_pengguna`
--
ALTER TABLE `tb_pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `tb_penggunaan`
--
ALTER TABLE `tb_penggunaan`
  ADD PRIMARY KEY (`id_penggunaan`),
  ADD KEY `id_kendaraan` (`id_kendaraan`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_biaya_operasional`
--
ALTER TABLE `tb_biaya_operasional`
  MODIFY `id_biaya` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_inspeksi`
--
ALTER TABLE `tb_inspeksi`
  MODIFY `id_inspeksi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tb_kendaraan`
--
ALTER TABLE `tb_kendaraan`
  MODIFY `id_kendaraan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tb_maintenance`
--
ALTER TABLE `tb_maintenance`
  MODIFY `id_maintenance` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tb_pengguna`
--
ALTER TABLE `tb_pengguna`
  MODIFY `id_pengguna` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_penggunaan`
--
ALTER TABLE `tb_penggunaan`
  MODIFY `id_penggunaan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_biaya_operasional`
--
ALTER TABLE `tb_biaya_operasional`
  ADD CONSTRAINT `tb_biaya_operasional_ibfk_1` FOREIGN KEY (`id_kendaraan`) REFERENCES `tb_kendaraan` (`id_kendaraan`) ON DELETE CASCADE;

--
-- Constraints for table `tb_inspeksi`
--
ALTER TABLE `tb_inspeksi`
  ADD CONSTRAINT `tb_inspeksi_ibfk_1` FOREIGN KEY (`id_kendaraan`) REFERENCES `tb_kendaraan` (`id_kendaraan`) ON DELETE CASCADE;

--
-- Constraints for table `tb_maintenance`
--
ALTER TABLE `tb_maintenance`
  ADD CONSTRAINT `tb_maintenance_ibfk_1` FOREIGN KEY (`id_kendaraan`) REFERENCES `tb_kendaraan` (`id_kendaraan`) ON DELETE CASCADE;

--
-- Constraints for table `tb_penggunaan`
--
ALTER TABLE `tb_penggunaan`
  ADD CONSTRAINT `tb_penggunaan_ibfk_1` FOREIGN KEY (`id_kendaraan`) REFERENCES `tb_kendaraan` (`id_kendaraan`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_penggunaan_ibfk_2` FOREIGN KEY (`id_pengguna`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
