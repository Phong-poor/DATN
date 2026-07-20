-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 21, 2026 at 06:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laravel`
--

-- --------------------------------------------------------

--
-- Table structure for table `affiliate_gioi_thieu`
--

CREATE TABLE `affiliate_gioi_thieu` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_affiliate_khachhang` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang_duoc_gioithieu` bigint(20) UNSIGNED NOT NULL,
  `ma_ref` varchar(255) DEFAULT NULL,
  `da_dang_ky_luc` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `affiliate_yeu_cau_rut_tien`
--

CREATE TABLE `affiliate_yeu_cau_rut_tien` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_affiliate_khachhang` bigint(20) UNSIGNED NOT NULL,
  `so_tien` decimal(15,2) NOT NULL,
  `ten_ngan_hang` varchar(255) DEFAULT NULL,
  `ten_chu_tai_khoan` varchar(255) DEFAULT NULL,
  `so_tai_khoan` varchar(255) DEFAULT NULL,
  `trangthai` varchar(255) NOT NULL DEFAULT '''pending''',
  `ghichu` text DEFAULT NULL,
  `duoc_duyet_luc` timestamp NULL DEFAULT NULL,
  `duoc_thanh_toan_luc` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tieude` varchar(255) NOT NULL,
  `phude` varchar(255) DEFAULT NULL,
  `chudenho` varchar(120) DEFAULT NULL,
  `noibat` varchar(180) DEFAULT NULL,
  `mota` text DEFAULT NULL,
  `hinhanh` varchar(255) NOT NULL,
  `loaimedia` varchar(255) NOT NULL DEFAULT '''image''',
  `hinhanh_mobile` varchar(255) DEFAULT NULL,
  `loai_media_mobile` varchar(255) DEFAULT NULL,
  `duongdan` varchar(255) DEFAULT NULL,
  `id_sanpham` bigint(20) UNSIGNED DEFAULT NULL,
  `nhanchinh` varchar(60) DEFAULT NULL,
  `nhanphu` varchar(60) DEFAULT NULL,
  `huyhieu_sanpham` varchar(80) DEFAULT NULL,
  `dactinh_sanpham` varchar(120) DEFAULT NULL,
  `vitri` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `trangthai` tinyint(1) NOT NULL DEFAULT 1,
  `batdauluc` timestamp NULL DEFAULT NULL,
  `ketthucluc` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `tieude`, `phude`, `chudenho`, `noibat`, `mota`, `hinhanh`, `loaimedia`, `hinhanh_mobile`, `loai_media_mobile`, `duongdan`, `id_sanpham`, `nhanchinh`, `nhanphu`, `huyhieu_sanpham`, `dactinh_sanpham`, `vitri`, `trangthai`, `batdauluc`, `ketthucluc`, `created_at`, `updated_at`) VALUES
(1, 'Sức Mạnh Hội Tụ', 'Sự Tinh Tế Chuyên Sâu', 'PREMIUM LAPTOP STORE 2026', 'Sự Tinh Tế Chuyên Sâu', 'Laptop cao cấp chế tác riêng cho nhà sáng tạo, game thủ chuyên nghiệp và kỹ sư công nghệ. Trải nghiệm hiệu năng vượt giới hạn vật lý với màn hình OLED đỉnh cao.', '/Gemini_Generated_Image_v5vppjv5vppjv5vp (1).png', 'image', NULL, NULL, '/products/29', 29, 'Mua ngay', 'Xem bộ sưu tập', 'TRENDING NOW', 'RTX 40-Series', 0, 1, NULL, NULL, '2026-06-07 21:01:32', '2026-06-07 21:01:32'),
(2, 'Hiệu Năng Vượt Trội', 'Kiến Trúc AI Thế Hệ Mới', 'NEW GENERATION CHIPS', 'Kiến Trúc AI Thế Hệ Mới', 'Sở hữu ngay các cỗ máy tối tân trang bị NPU tăng tốc AI cục bộ đến 45 TOPs. Đáp ứng hoàn hảo mọi tác vụ deep learning và dựng hình 3D real-time.', '/Gemini_Generated_Image_7xfvdr7xfvdr7xfv.png', 'image', NULL, NULL, '/products/28', 28, 'Khám phá ngay', 'Tư vấn cấu hình', 'AI READY', 'NPU 45 TOPs', 1, 1, NULL, NULL, '2026-06-07 21:01:32', '2026-06-07 21:01:32'),
(3, 'Trải Nghiệm Đắm Chìm', 'Nebula OLED 240Hz', 'NEBULA DISPLAY TECHNOLOGY', 'Nebula OLED 240Hz', 'Độ sâu màu 10-bit đích thực, độ tương phản tuyệt đối 1.000.000:1 cùng tần số quét 240Hz siêu mượt. Sắc sảo trong từng chuyển động game AAA.', '/Gemini_Generated_Image_j1cibhj1cibhj1ci.png', 'image', NULL, NULL, '/products/26', 26, 'Xem ưu đãi', 'So sánh sản phẩm', 'TRENDING NOW', 'RTX 40-Series', 2, 1, NULL, NULL, '2026-06-07 21:01:32', '2026-06-07 21:01:32'),
(4, 'Trải Nghiệm Đắm Chìm', 'Không Gian Cao Cấp', 'PREDATOR SHOWROOM', 'Không Gian Cao Cấp', 'Khám phá không gian laptop hiện đại với các dòng máy cao cấp được trưng bày thực tế cho game, sáng tạo và công việc chuyên nghiệp.', '/Gemini_Generated_Image_dp15ytdp15ytdp15.png', 'image', NULL, NULL, '/products/30', 30, 'Xem showroom', 'Liên hệ tư vấn', 'SHOWROOM', 'Trải nghiệm trực tiếp', 3, 1, NULL, NULL, '2026-06-07 21:01:32', '2026-06-07 21:01:32');

-- --------------------------------------------------------

--
-- Table structure for table `bienthe`
--

CREATE TABLE `bienthe` (
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `id_sanpham` bigint(20) UNSIGNED NOT NULL,
  `ten_bienthe` varchar(255) DEFAULT NULL,
  `gia` decimal(12,2) NOT NULL,
  `soluong` int(11) NOT NULL,
  `thuoc_tinh_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bienthe`
--

INSERT INTO `bienthe` (`id_bienthe`, `id_sanpham`, `ten_bienthe`, `gia`, `soluong`, `thuoc_tinh_json`) VALUES
(13690, 30, '32GB', 22800000.00, 17, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null}]'),
(13691, 30, '64GB', 25200000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null}]'),
(13692, 30, '16GB', 21800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null}]'),
(13701, 29, '64GB - Ryzen 7 7800X3D - Đen', 19700000.00, 19, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 7 7800X3D\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đen\",\"hex\":\"#000000\"}]'),
(13702, 29, '64GB - Ryzen 7 7800X3D - Xanh lá', 19700000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 7 7800X3D\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Xanh lá\",\"hex\":\"#008001\"}]'),
(13703, 29, '64GB - Ryzen 9 7950X - Đen', 22200000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 9 7950X\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đen\",\"hex\":\"#000000\"}]'),
(13704, 29, '64GB - Ryzen 9 7950X - Xanh lá', 22200000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 9 7950X\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Xanh lá\",\"hex\":\"#008001\"}]'),
(13705, 29, '128GB - Ryzen 7 7800X3D - Đen', 25300000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 7 7800X3D\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đen\",\"hex\":\"#000000\"}]'),
(13706, 29, '128GB - Ryzen 7 7800X3D - Xanh lá', 25300000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 7 7800X3D\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Xanh lá\",\"hex\":\"#008001\"}]'),
(13707, 29, '128GB - Ryzen 9 7950X - Đen', 27800000.00, 14, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 9 7950X\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đen\",\"hex\":\"#000000\"}]'),
(13708, 29, '128GB - Ryzen 9 7950X - Xanh lá', 27800000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 9 7950X\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Xanh lá\",\"hex\":\"#008001\"}]'),
(13709, 28, '32GB - Apple M2 Ultra - Vàng', 33800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13710, 28, '32GB - Apple M2 Ultra - Nâu', 33800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13711, 28, '32GB - Apple M3 Max - Vàng', 25800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13712, 28, '32GB - Apple M3 Max - Nâu', 25800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13713, 28, '16GB - Apple M2 Ultra - Vàng', 32800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13714, 28, '16GB - Apple M2 Ultra - Nâu', 32800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13715, 28, '16GB - Apple M3 Max - Vàng', 24800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13716, 28, '16GB - Apple M3 Max - Nâu', 24800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13717, 27, '́́8GB - Intel Core i5-13600K - Vàng', 22800000.00, 32, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"́́8GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13718, 27, '́́8GB - Intel Core i5-13600K - Nâu', 22800000.00, 29, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"́́8GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13719, 27, '́́8GB - Intel Core i5-14400 - Vàng', 21800000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"́́8GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13720, 27, '́́8GB - Intel Core i5-14400 - Nâu', 21800000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"́́8GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13721, 27, '32GB - Intel Core i5-13600K - Vàng', 24300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13722, 27, '32GB - Intel Core i5-13600K - Nâu', 24300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13723, 27, '32GB - Intel Core i5-14400 - Vàng', 23300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13724, 27, '32GB - Intel Core i5-14400 - Nâu', 23300000.00, 28, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13725, 27, '16GB - Intel Core i5-13600K - Vàng', 23300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13726, 27, '16GB - Intel Core i5-13600K - Nâu', 23300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13727, 27, '16GB - Intel Core i5-14400 - Vàng', 22300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13728, 27, '16GB - Intel Core i5-14400 - Nâu', 22300000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13729, 26, '32GB - Intel Core i9-14900K - Vàng', 30800000.00, 51, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13730, 26, '32GB - Intel Core i9-14900K - Đỏ', 30800000.00, 50, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13731, 26, '32GB - Intel Core i9-13900H - Vàng', 21800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13732, 26, '32GB - Intel Core i9-13900H - Đỏ', 21800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13733, 26, '64GB - Intel Core i9-14900K - Vàng', 33200000.00, 55, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13734, 26, '64GB - Intel Core i9-14900K - Đỏ', 33200000.00, 50, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13735, 26, '64GB - Intel Core i9-13900H - Vàng', 24200000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13736, 26, '64GB - Intel Core i9-13900H - Đỏ', 24200000.00, 18, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13737, 26, '128GB - Intel Core i9-14900K - Vàng', 33800000.00, 19, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13738, 26, '128GB - Intel Core i9-14900K - Đỏ', 33800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13739, 26, '128GB - Intel Core i9-13900H - Vàng', 29800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13740, 26, '128GB - Intel Core i9-13900H - Đỏ', 29800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13741, 25, '16GB - Apple M2 Ultra - Nâu', 32800000.00, 49, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13742, 25, '16GB - Apple M2 Ultra - Vàng', 32800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13743, 25, '16GB - Apple M3 Max - Nâu', 24800000.00, 49, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13744, 25, '16GB - Apple M3 Max - Vàng', 24800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13745, 25, '32GB - Apple M2 Ultra - Nâu', 33800000.00, 50, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13746, 25, '32GB - Apple M2 Ultra - Vàng', 33800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13747, 25, '32GB - Apple M3 Max - Nâu', 25800000.00, 49, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13748, 25, '32GB - Apple M3 Max - Vàng', 25800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13749, 34, 'Đen', 400000.00, 19, '[{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đen\",\"hex\":\"#000000\"}]'),
(13750, 34, 'Trắng', 400000.00, 19, '[{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Trắng\",\"hex\":\"#FFFFFF\"}]'),
(13751, 35, 'Trắng', 2490000.00, 19, '[{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Trắng\",\"hex\":\"#FFFFFF\"}]'),
(13752, 35, 'Đỏ', 2490000.00, 19, '[{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `bienthe_combo_offers`
--

CREATE TABLE `bienthe_combo_offers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `id_combo` bigint(20) UNSIGNED NOT NULL,
  `loai_uudai` enum('free','discount') NOT NULL DEFAULT 'free',
  `giakhuyenmai_override` decimal(12,2) DEFAULT NULL,
  `mota_uudai` varchar(255) DEFAULT NULL,
  `gioi_han_soluong` int(10) UNSIGNED DEFAULT NULL,
  `da_su_dung` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ngay_het_han` datetime DEFAULT NULL,
  `trangthai` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bienthe_combo_offers`
--

INSERT INTO `bienthe_combo_offers` (`id`, `id_bienthe`, `id_combo`, `loai_uudai`, `giakhuyenmai_override`, `mota_uudai`, `gioi_han_soluong`, `da_su_dung`, `ngay_het_han`, `trangthai`, `created_at`, `updated_at`) VALUES
(1, 13707, 1, 'free', 0.00, 'hdsfdhjkjgfdfgh', 7, 1, '2026-06-05 00:00:00', 1, '2026-05-30 08:58:14', '2026-05-30 08:58:14');

-- --------------------------------------------------------

--
-- Table structure for table `bienthe_hinhanh`
--

CREATE TABLE `bienthe_hinhanh` (
  `id_bienthe_hinhanh` bigint(20) UNSIGNED NOT NULL,
  `id_sanpham` bigint(20) UNSIGNED DEFAULT NULL,
  `duongdan` varchar(1000) NOT NULL,
  `thutu` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bienthe_hinhanh`
--

INSERT INTO `bienthe_hinhanh` (`id_bienthe_hinhanh`, `id_sanpham`, `duongdan`, `thutu`) VALUES
(292, 30, 'uploads/sanpham/1591e355d9fbd1cf056de44b06ed1462.webp', 0),
(293, 30, 'uploads/sanpham/3f19480e9bd7c4c62e4c8966bc50938e.webp', 1),
(294, 30, 'uploads/sanpham/7fca7f0c5803a8beb95ac707b2ba5a15.webp', 2),
(295, 30, 'uploads/sanpham/a4c1e2dbbbfe40ef3b42ddbabbe5ce76.webp', 3),
(296, 29, 'uploads/sanpham/77a5a51f367ebb00849c1b0812ffa543.webp', 0),
(297, 29, 'uploads/sanpham/f93e4ab6342d4361e5eef29a9bd389ce.png', 1),
(298, 29, 'uploads/sanpham/6007c2076eb1f4eb931ea481c3705c69.webp', 2),
(299, 29, 'uploads/sanpham/26159d4aba1ce2ee322e02ea770b579d.webp', 3),
(300, 28, 'uploads/sanpham/0bd0cf0e77400a09870d885114237cfd.webp', 0),
(301, 28, 'uploads/sanpham/abb6d6240853601e5538ebbdfe6c5687.webp', 1),
(302, 28, 'uploads/sanpham/2a510aca62194e0a6edecb741bcd0127.webp', 2),
(303, 28, 'uploads/sanpham/1fd801ef4e5694aedd118e26748a2371.webp', 3),
(304, 27, 'uploads/sanpham/ec6dff602b5677bced195eee27504f1e.webp', 0),
(305, 27, 'uploads/sanpham/5141a3a921949d371fe155075d71c58f.webp', 1),
(306, 27, 'uploads/sanpham/27583865d48c4896c5906cf81b520526.webp', 2),
(307, 27, 'uploads/sanpham/216e7060bc36f8ac305afb5ccabf474f.webp', 3),
(308, 26, 'uploads/sanpham/08d9cdba37cfa4ef30e63427bac99487.webp', 0),
(309, 26, 'uploads/sanpham/c4fce141394284ecd361fd7f23c1ac05.webp', 1),
(310, 26, 'uploads/sanpham/9d89d03ada372c0f522f3f2310b0c50d.webp', 2),
(311, 26, 'uploads/sanpham/ab4da7e5f8ef7272edec4fbab321a757.webp', 3),
(312, 25, 'uploads/sanpham/bfefc47cd6cc477e040c5e1d4a6d535a.webp', 0),
(313, 25, 'uploads/sanpham/834171cce0674143ed2ed07af4388f08.webp', 1),
(314, 25, 'uploads/sanpham/1fd801ef4e5694aedd118e26748a2371.webp', 2),
(315, 34, 'uploads/sanpham/a559c7be8d534de5ed76baa53e037c0a.webp', 0),
(316, 34, 'uploads/sanpham/a1213af2d6ffebdf5af98a5a75f2fc1f.png', 1),
(317, 34, 'uploads/sanpham/5cd3b0ad0e1192c1d428c5dbcd555474.webp', 2),
(318, 34, 'uploads/sanpham/f8d00476f62175a0d573305430ce8238.jpeg', 3),
(319, 34, 'uploads/sanpham/7f0b807c5e116a7247cc9e1b6c5a1049.webp', 4),
(320, 34, 'uploads/sanpham/5bbb7356b2069fcaec5aef50b7acc12c.jpeg', 5),
(321, 34, 'uploads/sanpham/4cc9752cdfacf74d7ce1cb60f5baa445.jpeg', 6),
(331, 35, 'uploads/sanpham/71d544d149b673f0c8fb7888f27591d7.jpeg', 0),
(332, 35, 'uploads/sanpham/7755c0f94003442f785cc8398d61e7c3.png', 1),
(333, 35, 'uploads/sanpham/ecf84864261e519739659bb4f6b14577.jpeg', 2);

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cai_dat_ma_sinh_nhat`
--

CREATE TABLE `cai_dat_ma_sinh_nhat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kichhoat` tinyint(1) NOT NULL DEFAULT 1,
  `giochay` time NOT NULL DEFAULT '08:00:00',
  `id_voucher` int(11) DEFAULT NULL,
  `mavoucher` varchar(255) NOT NULL DEFAULT 'BIRTHDAY',
  `id_mau_email` varchar(255) NOT NULL DEFAULT 'birthday_default',
  `gui_mot_lan_moi_nam` tinyint(1) NOT NULL DEFAULT 1,
  `thu_lai_khi_that_bai` tinyint(1) NOT NULL DEFAULT 1,
  `thongbao_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cai_dat_ma_sinh_nhat`
--

INSERT INTO `cai_dat_ma_sinh_nhat` (`id`, `kichhoat`, `giochay`, `id_voucher`, `mavoucher`, `id_mau_email`, `gui_mot_lan_moi_nam`, `thu_lai_khi_that_bai`, `thongbao_admin`, `created_at`, `updated_at`) VALUES
(1, 1, '10:50:00', 23, 'BIRTHDAY', 'birthday_default', 1, 1, 0, '2026-06-09 08:39:13', '2026-06-09 08:47:35');

-- --------------------------------------------------------

--
-- Table structure for table `combos`
--

CREATE TABLE `combos` (
  `id_combo` bigint(20) UNSIGNED NOT NULL,
  `ten_combo` varchar(255) NOT NULL,
  `hinhanh` varchar(255) DEFAULT NULL,
  `mota` text DEFAULT NULL,
  `giakhuyenmai` decimal(12,2) NOT NULL,
  `trangthai` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `combos`
--

INSERT INTO `combos` (`id_combo`, `ten_combo`, `hinhanh`, `mota`, `giakhuyenmai`, `trangthai`, `created_at`, `updated_at`) VALUES
(1, 'Combo Bàn phím Logitech G Pro X TKL Light Speed + Chuột Gaming Logitech G102 LightSync Gen 2', 'uploads/sanpham/860d02bf1cc1683fbde3d3b1193b6080.png', 'Combo gồm bàn phím Logitech G Pro X TKL Lightspeed và chuột Logitech G102 Lightsync Gen 2, mang đến trải nghiệm gaming mượt mà với thiết kế hiện đại, độ chính xác cao và hệ thống đèn RGB nổi bật. Phù hợp cho game thủ, học tập và làm việc hằng ngày.', 2457000.00, 1, '2026-05-27 14:27:40', '2026-05-27 14:40:37');

-- --------------------------------------------------------

--
-- Table structure for table `combo_sanpham`
--

CREATE TABLE `combo_sanpham` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_combo` bigint(20) UNSIGNED NOT NULL,
  `id_sanpham` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `combo_sanpham`
--

INSERT INTO `combo_sanpham` (`id`, `id_combo`, `id_sanpham`, `created_at`, `updated_at`) VALUES
(1, 1, 35, NULL, NULL),
(2, 1, 34, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cuoc_tro_chuyen`
--

CREATE TABLE `cuoc_tro_chuyen` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang` bigint(20) UNSIGNED NOT NULL,
  `tin_nhan_cuoi` text DEFAULT NULL,
  `tin_nhan_cuoi_luc` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cuoc_tro_chuyen`
--

INSERT INTO `cuoc_tro_chuyen` (`id`, `id_khachhang`, `tin_nhan_cuoi`, `tin_nhan_cuoi_luc`, `created_at`, `updated_at`) VALUES
(3, 16, 'svsvdf', '2026-06-19 17:03:59', '2026-05-31 20:57:14', '2026-06-19 17:03:59'),
(4, 4, NULL, NULL, '2026-05-31 21:00:47', '2026-05-31 21:00:47');

-- --------------------------------------------------------

--
-- Table structure for table `danhgia`
--

CREATE TABLE `danhgia` (
  `id_danhgia` bigint(20) UNSIGNED NOT NULL,
  `id_dathang` bigint(20) UNSIGNED NOT NULL,
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `danhgia` int(11) NOT NULL,
  `binhluan` text DEFAULT NULL,
  `trangthai` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `danhmuc`
--

CREATE TABLE `danhmuc` (
  `id_danhmuc` bigint(20) UNSIGNED NOT NULL,
  `id_danhmuc_cha` bigint(20) UNSIGNED DEFAULT NULL,
  `ten_danhmuc` varchar(255) NOT NULL,
  `trangthai` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `danhmuc`
--

INSERT INTO `danhmuc` (`id_danhmuc`, `id_danhmuc_cha`, `ten_danhmuc`, `trangthai`) VALUES
(2, 8, 'Laptop Gaming', 'active'),
(3, 8, 'Laptop văn phòng', 'active'),
(4, 8, 'Macbook', 'active'),
(7, 8, 'Laptop học sinh', 'active'),
(10, 9, 'Chuột', 'active'),
(11, 9, 'Bàn phím', 'active'),
(12, 9, 'Tai nghe', 'active'),
(13, 9, 'Lót chuột', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `danhmuc_cha`
--

CREATE TABLE `danhmuc_cha` (
  `id_danhmuc_cha` bigint(20) UNSIGNED NOT NULL,
  `ten_danhmuc` varchar(255) NOT NULL,
  `trangthai` enum('active','hidden') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `danhmuc_cha`
--

INSERT INTO `danhmuc_cha` (`id_danhmuc_cha`, `ten_danhmuc`, `trangthai`) VALUES
(8, 'Laptop', 'active'),
(9, 'Phụ kiện', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `danh_sach_flashsale`
--

CREATE TABLE `danh_sach_flashsale` (
  `id_session` bigint(20) UNSIGNED NOT NULL,
  `ten_dot` varchar(255) NOT NULL,
  `thoi_gian_bat_dau` timestamp NULL DEFAULT NULL,
  `thoi_gian_ket_thuc` timestamp NULL DEFAULT NULL,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `danh_sach_flashsale`
--

INSERT INTO `danh_sach_flashsale` (`id_session`, `ten_dot`, `thoi_gian_bat_dau`, `thoi_gian_ket_thuc`, `trang_thai`, `created_at`, `updated_at`) VALUES
(1, 'svdsvdsvdsvdskvds', '2026-06-18 17:00:00', '2026-06-22 17:00:00', 1, '2026-06-18 17:05:49', '2026-06-18 17:28:13');

-- --------------------------------------------------------

--
-- Table structure for table `dathang`
--

CREATE TABLE `dathang` (
  `id_dathang` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang` bigint(20) UNSIGNED NOT NULL,
  `tongtien` decimal(12,2) NOT NULL,
  `trangthai` varchar(255) NOT NULL DEFAULT 'pending',
  `diachi` varchar(255) DEFAULT NULL,
  `PTTT` varchar(255) DEFAULT NULL,
  `trang_thai_thanh_toan` varchar(255) NOT NULL DEFAULT 'unpaid',
  `nha_cung_cap_thanh_toan` varchar(255) DEFAULT NULL,
  `ma_don_hang_thanh_toan` varchar(255) DEFAULT NULL,
  `ma_yeu_cau_thanh_toan` varchar(255) DEFAULT NULL,
  `ma_giao_dich_thanh_toan` varchar(255) DEFAULT NULL,
  `ma_ket_qua_thanh_toan` int(11) DEFAULT NULL,
  `thong_bao_thanh_toan` varchar(255) DEFAULT NULL,
  `kieu_thanh_toan` varchar(255) DEFAULT NULL,
  `thanh_toan_luc` timestamp NULL DEFAULT NULL,
  `du_lieu_thanh_toan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `id_khuyenmai` bigint(20) UNSIGNED DEFAULT NULL,
  `giam_gia` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lydo` varchar(255) DEFAULT NULL,
  `minh_chung_hoan_tien` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dathang`
--

INSERT INTO `dathang` (`id_dathang`, `id_khachhang`, `tongtien`, `trangthai`, `diachi`, `PTTT`, `trang_thai_thanh_toan`, `nha_cung_cap_thanh_toan`, `ma_don_hang_thanh_toan`, `ma_yeu_cau_thanh_toan`, `ma_giao_dich_thanh_toan`, `ma_ket_qua_thanh_toan`, `thong_bao_thanh_toan`, `kieu_thanh_toan`, `thanh_toan_luc`, `du_lieu_thanh_toan`, `id_khuyenmai`, `giam_gia`, `created_at`, `updated_at`, `lydo`, `minh_chung_hoan_tien`) VALUES
(35, 12, 32800000.00, 'done', 'gihdfvihdfvhdchvdbvd', 'COD', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 30000.00, '2026-04-20 19:25:37', '2026-04-20 19:25:57', NULL, NULL),
(36, 7, 46600000.00, 'pending', 'dfhv dchvbdfhbvdshuvbdu', 'Ví điện tử', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 30000.00, '2026-04-20 19:30:25', '2026-04-20 19:30:25', NULL, NULL),
(37, 12, 46100000.00, 'done', 'ghfvgvgcvdgsvdsucvdsu', 'Ví điện tử', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 30000.00, '2026-04-20 19:32:33', '2026-04-20 19:34:28', NULL, NULL),
(38, 12, 48100000.00, 'pending', 'hdfbdfbdfbdfbdfbet', 'Ví điện tử', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 30000.00, '2026-04-20 19:38:38', '2026-04-20 19:38:38', NULL, NULL),
(39, 12, 18745000.00, 'cancelled', 'gdfvdbdfbdf', 'Ví điện tử', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 985000.00, '2026-04-24 23:47:50', '2026-05-31 20:58:16', 'hfshjvdshjvsdhvsdyvsd', NULL),
(40, 12, 39400000.00, 'done', 'fbcxdfvbdsvdsvs', 'Ví điện tử', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 30000.00, '2026-04-24 23:49:09', '2026-04-25 00:28:30', NULL, NULL),
(41, 12, 108200000.00, 'done', 'bvbdhvdjvdveru', 'Ví điện tử', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3030000.00, '2026-04-25 01:07:22', '2026-05-31 20:58:17', NULL, NULL),
(42, 12, 157700000.00, 'cancelled', 'vdshvdsjvdssvs', 'Ví điện tử', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 8330000.00, '2026-04-25 01:27:12', '2026-05-31 20:58:16', NULL, NULL),
(43, 12, 36400000.00, 'cancelled', 'FBFVDSVDSVSDVD', 'COD', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3030000.00, '2026-04-25 01:46:47', '2026-05-31 20:39:13', 'B VDBSJVDSUGVDSU', NULL),
(44, 7, 19730000.00, 'pending', 'hjbigbjkdfbndfjb v', 'Ví điện tử', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-05-04 01:43:44', '2026-05-04 01:43:44', NULL, NULL),
(45, 7, 669030000.00, 'pending', 'phppmgg', 'Ví điện tử', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-05-04 01:45:55', '2026-05-04 01:45:55', NULL, NULL),
(50, 15, 22830000.00, 'pending', 'dgdfgdg', 'COD', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-05-28 05:13:32', '2026-05-28 05:13:32', NULL, NULL),
(51, 15, 430000.00, 'refunded', 'gdfgdg', 'COD', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-05-28 06:22:19', '2026-05-28 08:17:12', 'fgdfgdfgdfg', 'refunds/LqQyDmbnJ1A96r2J75EsvXFsE6tvpxea0oa0iNx3.mp4'),
(52, 12, 2487000.00, 'done', '141/5 Phan Bội Châu, Phường Buôn Ma Thuột, Tỉnh Đắk Lắk', 'COD', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-05-29 07:58:10', '2026-05-29 08:00:24', 'fghgjgfndsfdasfgjhj', 'refunds/IolFPQPGRhgaSSFBK5uIoQ2WfXAsSIiGw9PnTAIp.mp4'),
(53, 12, 27830000.00, 'pending', '141/5 Phan Bội Châu, Phường Buôn Ma Thuột, Tỉnh Đắk Lắk', 'COD', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-05-31 09:05:01', '2026-05-31 09:05:01', NULL, NULL),
(54, 17, 33830000.00, 'pending', 'Quân - 0987654321 - kghjfdhgsfdghjgnbf', 'COD', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-06-07 21:38:22', '2026-06-07 21:38:22', NULL, NULL),
(55, 12, 48430000.00, 'pending', 'Phong - 0782583237 - 141/5 Phan Bội Châu, Phường Buôn Ma Thuột, Tỉnh Đắk Lắk', 'COD', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-06-11 09:28:33', '2026-06-11 09:28:33', NULL, NULL),
(56, 12, 46630000.00, 'refund_pending', 'Phong - 0782583237 - 141/5 Phan Bội Châu, Phường Buôn Ma Thuột, Tỉnh Đắk Lắk', 'COD', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-06-15 10:04:00', '2026-06-15 10:05:52', 'vsvdsvbdskhbvdskjvbdsnvfhb', 'refund_proofs/1781517951_Screen Recording 2026-05-20 210337.mp4'),
(57, 12, 23830000.00, 'pending', 'Phong - 0782583237 - 141/5 Phan Bội Châu, Phường Buôn Ma Thuột, Tỉnh Đắk Lắk', 'VNPay', 'paid', 'vnpay', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-18 17:42:10', '{\"checkout\":{\"promo_id\":null,\"freeship_promotion_id\":null,\"promo_code\":null,\"freeship_code\":null}}', NULL, 0.00, '2026-06-18 17:38:22', '2026-06-18 17:42:10', NULL, NULL),
(58, 12, 25830000.00, 'pending', 'Phong - 0782583237 - 141/5 Phan Bội Châu, Phường Buôn Ma Thuột, Tỉnh Đắk Lắk', 'VNPay', 'paid', 'vnpay', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-18 17:47:33', '{\"checkout\":{\"promo_id\":null,\"freeship_promotion_id\":null,\"promo_code\":null,\"freeship_code\":null}}', NULL, 0.00, '2026-06-18 17:46:39', '2026-06-18 17:47:33', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dathang_chitiet`
--

CREATE TABLE `dathang_chitiet` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_dathang` bigint(20) UNSIGNED NOT NULL,
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `soluong` int(11) NOT NULL,
  `gia` decimal(12,2) NOT NULL,
  `id_combo` bigint(20) UNSIGNED DEFAULT NULL,
  `id_nhom_combo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `hoantien` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dathang_chitiet`
--

INSERT INTO `dathang_chitiet` (`id`, `id_dathang`, `id_bienthe`, `soluong`, `gia`, `id_combo`, `id_nhom_combo`, `created_at`, `updated_at`, `hoantien`) VALUES
(56, 50, 13717, 1, 22800000.00, NULL, NULL, '2026-05-28 05:13:32', '2026-05-28 05:13:32', 0),
(57, 51, 13750, 1, 400000.00, NULL, NULL, '2026-05-28 06:22:19', '2026-05-28 06:22:19', 0),
(58, 52, 13750, 1, 340069.00, 1, 'combo_6a19a5300f75d7.75000574', '2026-05-29 07:58:10', '2026-05-29 07:58:10', 0),
(59, 52, 13751, 1, 2116931.00, 1, 'combo_6a19a5300f75d7.75000574', '2026-05-29 07:58:10', '2026-05-29 07:58:10', 0),
(60, 53, 13707, 1, 27800000.00, NULL, NULL, '2026-05-31 09:05:01', '2026-05-31 09:05:01', 0),
(61, 53, 13749, 1, 0.00, 1, 'combo_6a1c5c1ee1dff3.56365935', '2026-05-31 09:05:01', '2026-05-31 09:05:01', 0),
(62, 53, 13752, 1, 0.00, 1, 'combo_6a1c5c1ee1dff3.56365935', '2026-05-31 09:05:01', '2026-05-31 09:05:01', 0),
(63, 54, 13737, 1, 33800000.00, NULL, NULL, '2026-06-07 21:38:22', '2026-06-07 21:38:22', 0),
(64, 55, 13736, 2, 24200000.00, NULL, NULL, '2026-06-11 09:28:33', '2026-06-11 09:28:33', 0),
(65, 56, 13724, 2, 23300000.00, NULL, NULL, '2026-06-15 10:04:00', '2026-06-15 10:05:52', 1),
(66, 57, 13747, 1, 23800000.00, NULL, NULL, '2026-06-18 17:38:22', '2026-06-18 17:38:22', 0),
(67, 58, 13707, 1, 25800000.00, NULL, NULL, '2026-06-18 17:46:39', '2026-06-18 17:46:39', 0);

-- --------------------------------------------------------

--
-- Table structure for table `diachi`
--

CREATE TABLE `diachi` (
  `id_diachi` bigint(20) UNSIGNED NOT NULL,
  `id_user` bigint(20) UNSIGNED NOT NULL,
  `tinh_thanhpho` varchar(255) NOT NULL,
  `quan_huyen` varchar(255) NOT NULL,
  `phuong_xa` varchar(255) NOT NULL,
  `diachi_cuthe` text NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `loai_diachi` enum('home','company') NOT NULL,
  `mac_dinh` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diachi`
--

INSERT INTO `diachi` (`id_diachi`, `id_user`, `tinh_thanhpho`, `quan_huyen`, `phuong_xa`, `diachi_cuthe`, `latitude`, `longitude`, `loai_diachi`, `mac_dinh`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 14, 'Buôn Ma Thuột', 'Huyện cư mgar', 'xã eatul', 'buôn sah A', NULL, NULL, 'home', 0, '2026-05-19 08:57:01', '2026-05-24 02:35:03', NULL),
(2, 14, 'Biên Hòa', 'Tuy hòa', 'tuy hòa', 'tuy hòa', NULL, NULL, 'home', 0, '2026-05-21 06:10:07', '2026-05-24 01:25:50', '2026-05-24 01:25:50'),
(3, 14, 'Tỉnh Đắk Lắk', 'Thành phố Buôn Ma Thuột', 'Phường Tân Hòa', 'buon ma thuot 180 pham ngu lao', NULL, NULL, 'company', 0, '2026-05-21 06:17:51', '2026-05-24 02:35:03', NULL),
(4, 14, 'Tỉnh Đắk Lắk', 'Xã Ea Tul', 'Sah A', 'buôn sah a', NULL, NULL, 'home', 0, '2026-05-24 01:14:04', '2026-05-24 02:35:03', NULL),
(5, 14, 'Tỉnh Đắk Lắk', 'Không xác định', 'Phường Buôn Ma Thuột', 'Hà Huy Tập', 12.7077746, 108.0662495, 'home', 0, '2026-05-24 01:41:43', '2026-05-24 02:35:03', NULL),
(6, 14, 'Tỉnh Đắk Lắk', 'Xã Ea Tul', 'Xã Ea Tul', 'Buôn sah A', 12.8797515, 108.1591988, 'home', 1, '2026-05-24 02:35:03', '2026-05-24 02:35:03', NULL),
(7, 14, 'Tỉnh Đắk Lắk', 'Không xác định', 'Phường Buôn Ma Thuột', '99 Nguyễn Văn Linh', 12.6908309, 108.0606316, 'home', 0, '2026-05-24 03:06:34', '2026-05-24 03:06:34', NULL),
(8, 12, 'Tỉnh Đắk Lắk', 'Không xác định', 'Phường Buôn Ma Thuột', '141/5 Phan Bội Châu', NULL, NULL, 'home', 1, '2026-05-29 07:56:27', '2026-05-29 07:56:27', NULL),
(9, 12, 'Tỉnh Đắk Lắk', '', 'Phường Buôn Ma Thuột', '43 Nguyễn Lâm', NULL, NULL, 'home', 0, '2026-06-11 09:23:43', '2026-06-11 09:23:50', '2026-06-11 09:23:50');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `giatri_thuoctinh`
--

CREATE TABLE `giatri_thuoctinh` (
  `id_giatri` bigint(20) UNSIGNED NOT NULL,
  `id_thuoctinh` bigint(20) UNSIGNED NOT NULL,
  `giatri` varchar(255) NOT NULL,
  `gia_cong_them` decimal(15,2) NOT NULL DEFAULT 0.00,
  `danh_muc_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `trangthai` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `giatri_thuoctinh`
--

INSERT INTO `giatri_thuoctinh` (`id_giatri`, `id_thuoctinh`, `giatri`, `gia_cong_them`, `danh_muc_ids`, `trangthai`) VALUES
(1, 1, '4GB', 0.00, '[3, 7]', 1),
(2, 1, '́́8GB', 300000.00, '[3, 4, 7]', 1),
(3, 1, '16GB', 800000.00, '[2, 3, 4, 7]', 1),
(4, 1, '32GB', 1800000.00, '[2, 4]', 1),
(5, 1, '64GB', 4200000.00, '[2, 4]', 1),
(6, 1, '128GB', 9800000.00, '[2, 4]', 1),
(7, 2, 'Intel Core i3-14100', 0.00, '[3, 7]', 1),
(8, 2, 'Intel Core i5-13600K', 2500000.00, '[2]', 1),
(9, 2, 'Intel Core i5-14400', 1500000.00, '[2, 3, 7]', 1),
(10, 2, 'Intel Core i7-14700K', 6000000.00, '[2]', 1),
(11, 2, 'Intel Core i9-13900H', 5000000.00, '[2]', 1),
(12, 2, 'Intel Core i9-14900K', 9000000.00, '[2]', 1),
(13, 2, 'Ryzen 7 7800X3D', 5500000.00, '[2]', 1),
(14, 2, 'Ryzen 9 7950X', 8000000.00, '[2]', 1),
(15, 2, 'Apple M2 Ultra', 22000000.00, '[4]', 1),
(16, 2, 'Apple M3 Max', 14000000.00, '[4]', 1),
(17, 3, 'RTX 3080', 7000000.00, '[2]', 1),
(18, 3, 'RTX 4060', 0.00, '[2]', 1),
(19, 3, 'RTX 4080', 18000000.00, '[2]', 1),
(20, 3, 'RTX 4090', 32000000.00, '[2]', 1),
(21, 3, 'RX 7800 XT', 6000000.00, '[2]', 1),
(22, 3, 'RX 7900 XTX', 15000000.00, '[2]', 1),
(23, 3, 'Apple M3 GPU', 8000000.00, '[4]', 1),
(24, 4, '13.3 inch', 0.00, '[3, 4]', 1),
(25, 4, '14 inch', 300000.00, '[3, 4, 7]', 1),
(26, 4, '15.6 inch', 800000.00, '[2, 3, 7]', 1),
(27, 4, '16 inch', 1200000.00, '[2, 4]', 1),
(28, 5, 'FHD 1920×1080', 0.00, '[2, 3, 7]', 1),
(29, 5, '2K 2560×1440', 2200000.00, '[2, 3, 4]', 1),
(30, 5, '4K 3840×2160', 5000000.00, '[2]', 1),
(31, 6, 'AMOLED', 3500000.00, '[2, 3]', 1),
(32, 6, 'IPS', 0.00, '[2, 3, 4, 7]', 1),
(33, 6, 'OLED', 4500000.00, '[2, 3]', 1),
(34, 6, 'Mini-LED', 5500000.00, '[2, 4]', 1),
(36, 7, '60Wh', 0.00, '[3, 7]', 1),
(37, 7, '72Wh', 500000.00, '[2, 3, 4]', 1),
(38, 7, '100Wh', 1400000.00, '[2, 4]', 1),
(39, 8, '140W MagSafe', 900000.00, '[4]', 1),
(40, 8, '65W USB-C', 0.00, '[3, 4, 7]', 1),
(43, 11, '128GB', 0.00, '[7]', 1),
(44, 11, '256GB', 300000.00, '[3, 4, 7]', 1),
(45, 11, '512GB', 800000.00, '[2, 3, 4, 7]', 1),
(46, 11, '1TB', 1500000.00, '[2, 3, 4]', 1),
(47, 11, '2TB', 3500000.00, '[2, 4]', 1),
(48, 11, '4TB', 7000000.00, '[2, 4]', 1),
(49, 11, '8TB', 15000000.00, '[4]', 1),
(50, 11, '16TB', 35000000.00, '[4]', 1),
(51, 8, '230W AC Adapter', 1500000.00, '[2]', 1),
(52, 8, '330W AC Adapter', 2500000.00, '[2]', 1),
(53, 2, 'Intel Core i5-1240P', 0.00, '[3, 7]', 1),
(54, 2, 'Ryzen 5 7535HS', 0.00, '[2, 3]', 1),
(55, 2, 'Apple M1', 0.00, '[4]', 1),
(56, 3, 'Intel UHD Graphics', 0.00, '[3, 7]', 1),
(57, 3, 'Intel Iris Xe Graphics', 0.00, '[3, 7]', 1),
(58, 3, 'Intel Arc Graphics', 0.00, '[3, 7]', 1),
(59, 3, 'AMD Radeon Graphics', 0.00, '[3, 7]', 1),
(60, 3, 'NVIDIA GeForce MX550', 500000.00, '[3]', 1),
(61, 3, 'NVIDIA GeForce RTX 2050', 1500000.00, '[3]', 1),
(62, 3, 'NVIDIA GeForce RTX 3050', 2500000.00, '[2, 3]', 1),
(63, 12, 'Có dây (USB)', 0.00, '[10,11,12]', 1),
(64, 12, 'Không dây (Wireless 2.4GHz)', 0.00, '[10,11,12]', 1),
(65, 12, 'Bluetooth', 0.00, '[10,11,12]', 1),
(66, 13, '1000 DPI', 0.00, '\"[10]\"', 1),
(67, 13, '4000 DPI', 0.00, '\"[10]\"', 1),
(68, 13, '8000 DPI', 0.00, '\"[10]\"', 1),
(69, 13, '26000 DPI', 0.00, '\"[10]\"', 1),
(70, 14, 'Blue Switch (Clicky)', 0.00, '\"[11]\"', 1),
(71, 14, 'Red Switch (Linear)', 0.00, '\"[11]\"', 1),
(72, 14, 'Brown Switch (Tactile)', 0.00, '\"[11]\"', 1);

-- --------------------------------------------------------

--
-- Table structure for table `giohang`
--

CREATE TABLE `giohang` (
  `id_giohang` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang` bigint(20) UNSIGNED NOT NULL,
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `soluong` int(11) NOT NULL DEFAULT 1,
  `id_combo` bigint(20) UNSIGNED DEFAULT NULL,
  `id_nhom_combo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giohang`
--

INSERT INTO `giohang` (`id_giohang`, `id_khachhang`, `id_bienthe`, `soluong`, `id_combo`, `id_nhom_combo`, `created_at`, `updated_at`) VALUES
(74, 15, 13717, 1, NULL, NULL, '2026-06-18 17:01:00', '2026-06-18 17:01:00'),
(100, 4, 13690, 4, NULL, NULL, '2026-06-18 17:01:00', '2026-06-18 17:01:00');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(84, 'default', '{\"uuid\":\"1c3ec758-de42-42da-8594-de283ee8f7cf\",\"displayName\":\"App\\\\Mail\\\\RegisterSuccessMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:28:\\\"App\\\\Mail\\\\RegisterSuccessMail\\\":22:{s:6:\\\"locale\\\";N;s:4:\\\"from\\\";a:0:{}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"phongpoor2006@gmail.com\\\";}}s:2:\\\"cc\\\";a:0:{}s:3:\\\"bcc\\\";a:0:{}s:7:\\\"replyTo\\\";a:0:{}s:7:\\\"subject\\\";N;s:8:\\\"markdown\\\";N;s:7:\\\"\\u0000*\\u0000html\\\";N;s:4:\\\"view\\\";N;s:8:\\\"textView\\\";N;s:8:\\\"viewData\\\";a:0:{}s:11:\\\"attachments\\\";a:0:{}s:14:\\\"rawAttachments\\\";a:0:{}s:15:\\\"diskAttachments\\\";a:0:{}s:7:\\\"\\u0000*\\u0000tags\\\";a:0:{}s:11:\\\"\\u0000*\\u0000metadata\\\";a:0:{}s:9:\\\"callbacks\\\";a:0:{}s:5:\\\"theme\\\";N;s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";s:29:\\\"\\u0000*\\u0000assertionableRenderStrings\\\";N;s:4:\\\"user\\\";O:15:\\\"App\\\\Models\\\\User\\\":36:{s:13:\\\"\\u0000*\\u0000connection\\\";s:5:\\\"mysql\\\";s:8:\\\"\\u0000*\\u0000table\\\";s:5:\\\"users\\\";s:13:\\\"\\u0000*\\u0000primaryKey\\\";s:2:\\\"id\\\";s:10:\\\"\\u0000*\\u0000keyType\\\";s:3:\\\"int\\\";s:12:\\\"incrementing\\\";b:1;s:7:\\\"\\u0000*\\u0000with\\\";a:0:{}s:12:\\\"\\u0000*\\u0000withCount\\\";a:0:{}s:19:\\\"preventsLazyLoading\\\";b:0;s:10:\\\"\\u0000*\\u0000perPage\\\";i:15;s:6:\\\"exists\\\";b:1;s:18:\\\"wasRecentlyCreated\\\";b:1;s:28:\\\"\\u0000*\\u0000escapeWhenCastingToString\\\";b:0;s:13:\\\"\\u0000*\\u0000attributes\\\";a:7:{s:4:\\\"name\\\";s:15:\\\"TRAN QUOC PHONG\\\";s:5:\\\"email\\\";s:23:\\\"phongpoor2006@gmail.com\\\";s:5:\\\"phone\\\";s:10:\\\"0964832135\\\";s:8:\\\"password\\\";s:60:\\\"$2y$12$FW5Suxybo3rpHhAcPwUnGOiUiWtcwKRQ2o8tqQjjdBN.UwgGFYOKO\\\";s:10:\\\"updated_at\\\";s:19:\\\"2026-06-15 16:54:43\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-06-15 16:54:43\\\";s:2:\\\"id\\\";i:19;}s:11:\\\"\\u0000*\\u0000original\\\";a:7:{s:4:\\\"name\\\";s:15:\\\"TRAN QUOC PHONG\\\";s:5:\\\"email\\\";s:23:\\\"phongpoor2006@gmail.com\\\";s:5:\\\"phone\\\";s:10:\\\"0964832135\\\";s:8:\\\"password\\\";s:60:\\\"$2y$12$FW5Suxybo3rpHhAcPwUnGOiUiWtcwKRQ2o8tqQjjdBN.UwgGFYOKO\\\";s:10:\\\"updated_at\\\";s:19:\\\"2026-06-15 16:54:43\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-06-15 16:54:43\\\";s:2:\\\"id\\\";i:19;}s:10:\\\"\\u0000*\\u0000changes\\\";a:0:{}s:11:\\\"\\u0000*\\u0000previous\\\";a:0:{}s:8:\\\"\\u0000*\\u0000casts\\\";a:4:{s:17:\\\"email_verified_at\\\";s:8:\\\"datetime\\\";s:8:\\\"password\\\";s:6:\\\"hashed\\\";s:23:\\\"two_factor_confirmed_at\\\";s:8:\\\"datetime\\\";s:14:\\\"last_active_at\\\";s:8:\\\"datetime\\\";}s:17:\\\"\\u0000*\\u0000classCastCache\\\";a:0:{}s:21:\\\"\\u0000*\\u0000attributeCastCache\\\";a:0:{}s:13:\\\"\\u0000*\\u0000dateFormat\\\";N;s:10:\\\"\\u0000*\\u0000appends\\\";a:1:{i:0;s:6:\\\"online\\\";}s:19:\\\"\\u0000*\\u0000dispatchesEvents\\\";a:0:{}s:14:\\\"\\u0000*\\u0000observables\\\";a:0:{}s:12:\\\"\\u0000*\\u0000relations\\\";a:0:{}s:10:\\\"\\u0000*\\u0000touches\\\";a:0:{}s:27:\\\"\\u0000*\\u0000relationAutoloadCallback\\\";N;s:26:\\\"\\u0000*\\u0000relationAutoloadContext\\\";N;s:10:\\\"timestamps\\\";b:1;s:13:\\\"usesUniqueIds\\\";b:0;s:9:\\\"\\u0000*\\u0000hidden\\\";a:4:{i:0;s:8:\\\"password\\\";i:1;s:17:\\\"two_factor_secret\\\";i:2;s:25:\\\"two_factor_recovery_codes\\\";i:3;s:14:\\\"remember_token\\\";}s:10:\\\"\\u0000*\\u0000visible\\\";a:0:{}s:11:\\\"\\u0000*\\u0000fillable\\\";a:11:{i:0;s:4:\\\"name\\\";i:1;s:5:\\\"email\\\";i:2;s:5:\\\"phone\\\";i:3;s:13:\\\"date_of_birth\\\";i:4;s:6:\\\"gender\\\";i:5;s:6:\\\"avatar\\\";i:6;s:8:\\\"password\\\";i:7;s:4:\\\"role\\\";i:8;s:11:\\\"facebook_id\\\";i:9;s:6:\\\"status\\\";i:10;s:14:\\\"last_active_at\\\";}s:10:\\\"\\u0000*\\u0000guarded\\\";a:1:{i:0;s:1:\\\"*\\\";}s:19:\\\"\\u0000*\\u0000authPasswordName\\\";s:8:\\\"password\\\";s:20:\\\"\\u0000*\\u0000rememberTokenName\\\";s:14:\\\"remember_token\\\";s:14:\\\"\\u0000*\\u0000accessToken\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1781517288,\"delay\":null}', 0, NULL, 1781517288, 1781517288),
(85, 'default', '{\"uuid\":\"db74c970-a91a-4292-b656-a4f5ff32c11c\",\"displayName\":\"App\\\\Events\\\\OrderPlaced\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\OrderPlaced\\\":1:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\DatHang\\\";s:2:\\\"id\\\";i:56;s:9:\\\"relations\\\";a:4:{i:0;s:4:\\\"user\\\";i:1;s:9:\\\"chi_tiets\\\";i:2;s:17:\\\"chi_tiets.bienThe\\\";i:3;s:25:\\\"chi_tiets.bienThe.sanPham\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1781517840,\"delay\":null}', 0, NULL, 1781517840, 1781517840),
(86, 'default', '{\"uuid\":\"3fb0a835-94e3-4955-ac4c-cf4047805a9d\",\"displayName\":\"App\\\\Events\\\\OrderStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:29:\\\"App\\\\Events\\\\OrderStatusUpdated\\\":1:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\DatHang\\\";s:2:\\\"id\\\";i:56;s:9:\\\"relations\\\";a:2:{i:0;s:9:\\\"chi_tiets\\\";i:1;s:17:\\\"chi_tiets.bienThe\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1781517870,\"delay\":null}', 0, NULL, 1781517870, 1781517870),
(87, 'default', '{\"uuid\":\"99032cb5-8586-403d-bb4f-677dd0d9ea42\",\"displayName\":\"App\\\\Events\\\\OrderStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:29:\\\"App\\\\Events\\\\OrderStatusUpdated\\\":1:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\DatHang\\\";s:2:\\\"id\\\";i:56;s:9:\\\"relations\\\";a:2:{i:0;s:9:\\\"chi_tiets\\\";i:1;s:17:\\\"chi_tiets.bienThe\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1781517873,\"delay\":null}', 0, NULL, 1781517873, 1781517873),
(88, 'default', '{\"uuid\":\"b48bc556-c7f5-4f80-b7be-1e12c65d4fd1\",\"displayName\":\"App\\\\Events\\\\OrderStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:29:\\\"App\\\\Events\\\\OrderStatusUpdated\\\":1:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\DatHang\\\";s:2:\\\"id\\\";i:56;s:9:\\\"relations\\\";a:2:{i:0;s:9:\\\"chi_tiets\\\";i:1;s:17:\\\"chi_tiets.bienThe\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1781517876,\"delay\":null}', 0, NULL, 1781517876, 1781517876),
(89, 'default', '{\"uuid\":\"cbeeea4d-76c0-472c-afcc-13e20ac843b9\",\"displayName\":\"App\\\\Events\\\\OrderStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:29:\\\"App\\\\Events\\\\OrderStatusUpdated\\\":1:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\DatHang\\\";s:2:\\\"id\\\";i:56;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1781517952,\"delay\":null}', 0, NULL, 1781517952, 1781517952),
(90, 'default', '{\"uuid\":\"aabc6680-3c12-4c75-b7a8-3649a6944b7e\",\"displayName\":\"App\\\\Events\\\\OrderPlaced\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\OrderPlaced\\\":1:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\DatHang\\\";s:2:\\\"id\\\";i:57;s:9:\\\"relations\\\";a:4:{i:0;s:4:\\\"user\\\";i:1;s:9:\\\"chi_tiets\\\";i:2;s:17:\\\"chi_tiets.bienThe\\\";i:3;s:25:\\\"chi_tiets.bienThe.sanPham\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1781804304,\"delay\":null}', 0, NULL, 1781804304, 1781804304),
(91, 'default', '{\"uuid\":\"943caee8-07b4-4ff2-91fc-4bc592df8244\",\"displayName\":\"App\\\\Events\\\\OrderStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:29:\\\"App\\\\Events\\\\OrderStatusUpdated\\\":1:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\DatHang\\\";s:2:\\\"id\\\";i:57;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1781804530,\"delay\":null}', 0, NULL, 1781804530, 1781804530),
(92, 'default', '{\"uuid\":\"f502ea71-7ed7-407d-9b3c-94eebe17207b\",\"displayName\":\"App\\\\Events\\\\OrderPlaced\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\OrderPlaced\\\":1:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\DatHang\\\";s:2:\\\"id\\\";i:58;s:9:\\\"relations\\\";a:4:{i:0;s:4:\\\"user\\\";i:1;s:9:\\\"chi_tiets\\\";i:2;s:17:\\\"chi_tiets.bienThe\\\";i:3;s:25:\\\"chi_tiets.bienThe.sanPham\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1781804800,\"delay\":null}', 0, NULL, 1781804800, 1781804800),
(93, 'default', '{\"uuid\":\"7b36bb92-994b-4027-a42e-a4fc2707fbb4\",\"displayName\":\"App\\\\Events\\\\OrderStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:29:\\\"App\\\\Events\\\\OrderStatusUpdated\\\":1:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\DatHang\\\";s:2:\\\"id\\\";i:58;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1781804853,\"delay\":null}', 0, NULL, 1781804853, 1781804853);

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `khachhang`
--

CREATE TABLE `khachhang` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ten` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `anhdaidien` varchar(255) DEFAULT NULL,
  `sodienthoai` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `matkhau` varchar(255) NOT NULL,
  `vaitro` enum('user','admin') NOT NULL DEFAULT 'user',
  `id_facebook` varchar(255) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `api_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ngaysinh` date DEFAULT NULL,
  `gioitinh` varchar(10) DEFAULT NULL,
  `trangthai` enum('active','locked') NOT NULL DEFAULT 'active',
  `otp_khoiphuc` varchar(10) DEFAULT NULL,
  `otp_khoiphuc_hethan_luc` timestamp NULL DEFAULT NULL,
  `hoat_dong_cuoi_luc` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `khachhang`
--

INSERT INTO `khachhang` (`id`, `ten`, `email`, `anhdaidien`, `sodienthoai`, `email_verified_at`, `matkhau`, `vaitro`, `id_facebook`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `api_token`, `created_at`, `updated_at`, `ngaysinh`, `gioitinh`, `trangthai`, `otp_khoiphuc`, `otp_khoiphuc_hethan_luc`, `hoat_dong_cuoi_luc`) VALUES
(1, 'Lê Ngọc Tài', 'tantaile175@gmail.com', NULL, NULL, NULL, '$2y$12$S3Nfibi705ntzoKL9atckuxpY97GH6cbGw7AAov/mBHYFFBo2.BJ2', 'user', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-24 06:24:05', '2026-03-24 06:24:05', NULL, NULL, 'active', NULL, NULL, NULL),
(2, 'Quách Đức Thành', 'thanhquach123@gmail.com', NULL, NULL, NULL, '$2y$12$tupAE5XL6gbqmI0Z/DfhPevSL.PPyTUjIJFudlTEV/Yr79rIJt57W', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-24 06:25:48', '2026-06-18 17:02:07', NULL, NULL, 'locked', NULL, NULL, NULL),
(3, 'Trần Mạnh Quân', 'manhquan123@gmail.com', NULL, NULL, NULL, '$2y$12$SHGdkuQPMRuYG8M0qJ44o.gEPJ8tGnisRObPrbhqdExARLex2IXA.', 'user', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-24 07:34:24', '2026-03-24 07:34:24', NULL, NULL, 'active', NULL, NULL, NULL),
(4, 'NextGen', 'nextgenshop@gmail.com', NULL, '0235556789', NULL, '$2y$12$2xEvlIEAdN.BNJ3AEptxnu/8CuoC2esA8jlP7f76vtTKQUH450U0m', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-24 08:36:19', '2026-05-28 08:17:21', NULL, NULL, 'active', NULL, NULL, '2026-05-28 08:17:21'),
(7, 'Trần Quốc Phong', 'phongtqpk04300@gmail.com', NULL, '0782583237', NULL, '$2y$12$y1jlpKzUxyfZ3uqzYk40a.Py4TQGIYNse5U7r5psEZZFh1WdIYmWS', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-28 01:16:26', '2026-06-21 16:50:48', '2026-06-15', NULL, 'active', NULL, NULL, '2026-06-21 16:50:48'),
(12, 'Phong', 'phongpoor123@gmail.com', 'uploads/avatar/1776335397_12.png', '0782583237', NULL, '$2y$12$Run5y5di2OYuUmEbEgwaCOi/0SBSXOIV75gaOvgsTePN9J5u0EOMu', 'user', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-31 04:02:45', '2026-06-19 14:56:25', '2006-04-25', 'Nam', 'active', NULL, NULL, '2026-06-19 14:56:25'),
(13, 'Phongpoor', 'phongpoor236@gmail.com', NULL, '08563254785', NULL, '$2y$12$Z7xorAaYA2rBrbA/v5rTD.2lhGgOXGABOBZ7gN1rOO06VH/CKx3da', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-09 09:46:55', '2026-04-09 09:57:02', NULL, NULL, 'active', NULL, NULL, NULL),
(14, 'Test User', 'test@example.com', NULL, NULL, '2026-05-20 06:18:15', '$2y$12$yfpqaY2V9m9C65U39Gy/ZO3jeJb5vAxRc.eMVxuAx2FFZmxO042gW', 'user', NULL, NULL, NULL, NULL, 'yj6P2kSJCH', NULL, '2026-05-20 06:18:16', '2026-05-20 06:18:16', NULL, NULL, 'active', NULL, NULL, NULL),
(15, 'Đăng Niê', 'nieydpk04105@gmail.com', NULL, NULL, NULL, '$2y$12$0REJXjE.xA0CSNP45QicKO/hnrJWCPzeYKG8MLvugBmX5W7pObzBy', 'user', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-26 09:19:50', '2026-05-26 09:19:50', '2026-06-15', NULL, 'active', NULL, NULL, NULL),
(16, '10a6.lạc mạnh Quân Ê Ban', 'machquanlac5@gmail.com', NULL, NULL, NULL, '$2y$12$fUuN94UAMm/1PPfH81oSlOelEf.6Zg6pGnBvTQAimzRWW3trgyNhC', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-31 20:50:04', '2026-05-31 20:50:04', NULL, NULL, 'active', NULL, NULL, NULL),
(17, 'Quân', 'ebanlmqpk04165@gmail.com', NULL, '0987654321', NULL, '$2y$12$N2Lo3LwFJfF9RhFS8VwsOOONiSPbm/3EJqutR6fJkfoSzUoaZZGGe', 'user', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-07 21:18:12', '2026-06-07 21:38:21', NULL, NULL, 'active', NULL, NULL, NULL),
(18, 'Lac Quan', 'lquan1550@gmail.com', NULL, NULL, NULL, '$2y$12$HmrfLHzhtaCFAkLJpVl7Keor8iNUOwtdCw8tbHV2MhGicsGJcDL5W', 'user', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-09 06:27:34', '2026-06-09 06:27:34', NULL, NULL, 'active', NULL, NULL, NULL),
(19, 'TRAN QUOC PHONG', 'phongpoor2006@gmail.com', NULL, '0964832135', NULL, '$2y$12$FW5Suxybo3rpHhAcPwUnGOiUiWtcwKRQ2o8tqQjjdBN.UwgGFYOKO', 'user', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 09:54:43', '2026-06-15 09:54:43', NULL, NULL, 'active', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `khachhang_voucher`
--

CREATE TABLE `khachhang_voucher` (
  `id` int(11) NOT NULL,
  `id_user` bigint(20) UNSIGNED DEFAULT NULL,
  `id_voucher` int(11) DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT NULL,
  `ngay_nhan` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `khachhang_voucher`
--

INSERT INTO `khachhang_voucher` (`id`, `id_user`, `id_voucher`, `trang_thai`, `ngay_nhan`) VALUES
(12, 17, 28, '0', '2026-06-08 04:38:22'),
(13, 16, 26, '0', '2026-06-09 13:31:17'),
(14, 16, 27, '0', '2026-06-09 13:31:37'),
(15, 18, 29, '0', '2026-06-09 13:41:41'),
(16, 18, 27, '0', '2026-06-09 13:42:10'),
(17, 16, 29, '0', '2026-06-09 13:45:39'),
(18, 18, 26, '0', '2026-06-09 13:46:07'),
(19, 18, 31, '0', '2026-06-09 13:51:37'),
(20, 12, 28, '0', '2026-06-11 16:28:33'),
(21, 12, 33, '0', '2026-06-15 17:04:00');

-- --------------------------------------------------------

--
-- Table structure for table `khach_hang_affiliate`
--

CREATE TABLE `khach_hang_affiliate` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang` bigint(20) UNSIGNED NOT NULL,
  `ma_affiliate` varchar(255) NOT NULL,
  `ty_le_hoa_hong` decimal(5,2) NOT NULL DEFAULT 0.00,
  `trangthai` varchar(255) NOT NULL DEFAULT '''active''',
  `tong_thu_nhap` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tong_da_thanh_toan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lienhe`
--

CREATE TABLE `lienhe` (
  `id` int(11) NOT NULL,
  `hoten` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `sodienthoai` varchar(20) DEFAULT NULL,
  `noidung` text DEFAULT NULL,
  `trangthai` enum('new','processing','replied') DEFAULT 'new',
  `phanhoi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `danhmuc` varchar(100) DEFAULT 'Tư vấn',
  `phan_hoi_luc` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lienhe`
--

INSERT INTO `lienhe` (`id`, `hoten`, `email`, `sodienthoai`, `noidung`, `trangthai`, `phanhoi`, `created_at`, `updated_at`, `danhmuc`, `phan_hoi_luc`) VALUES
(2, 'lạc mạnh quân', 'ebanlmqpk04165@gmail.com', '0869734820', 'đẹp chất lượng', 'replied', 'xin cahof hehhe', '2026-04-06 11:58:33', '2026-04-08 11:37:13', 'Tư vấn', NULL),
(6, 'Trần Quốc Phong', 'phongtqpk04300@gmail.com', '0782583237', 'bgfhfnhndfghndfmghhf', 'replied', 'Kính gửi Quý khách,\n\nDựa trên nhu cầu của bạn, chúng tôi xin tư vấn một số dòng sản phẩm phù hợp:\n\n• Laptop Gaming: Asus ROG, MSI, Lenovo Legion\n• Laptop Văn phòng: Dell XPS, HP Spectre, MacBook Air\n• Laptop Sinh viên: Asus VivoBook, Acer Aspire\n\nQuý khách có thể ghé showroom hoặc đặt hàng online tại vinatech.vn với chính sách trả góp 0% lãi suất.', '2026-04-09 02:13:55', '2026-04-09 02:16:42', 'Tư vấn', NULL),
(7, 'Phong', 'phongpoor123@gmail.com', '0782583237', 'fhsdbvdhjd', 'replied', 'Kính gửi Quý khách,\n\nDựa trên nhu cầu của bạn, chúng tôi xin tư vấn một số dòng sản phẩm phù hợp:\n\n• Laptop Gaming: Asus ROG, MSI, Lenovo Legion\n• Laptop Văn phòng: Dell XPS, HP Spectre, MacBook Air\n• Laptop Sinh viên: Asus VivoBook, Acer Aspire\n\nQuý khách có thể ghé showroom hoặc đặt hàng online tại vinatech.vn với chính sách trả góp 0% lãi suất.', '2026-04-16 03:33:00', '2026-04-16 03:33:33', 'Tư vấn', NULL),
(8, 'Phong', 'phongpoor123@gmail.com', '0782583237', 'bhadsvdsvdsvds', 'replied', 'Kính gửi Quý khách,\n\nĐơn hàng của bạn đang được xử lý. Chúng tôi sẽ thông báo ngay khi hàng được giao cho đơn vị vận chuyển.\n\nThời gian dự kiến nhận hàng: 2-3 ngày làm việc.\n\nMọi thắc mắc xin liên hệ hotline 1800 9999 (miễn phí, 8:00 - 22:00 hàng ngày).', '2026-04-25 01:42:50', '2026-04-25 01:43:18', 'Tư vấn', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lien_ket_affiliate`
--

CREATE TABLE `lien_ket_affiliate` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_affiliate_khachhang` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang_duoc_gioithieu` bigint(20) UNSIGNED DEFAULT NULL,
  `id_donhang` bigint(20) UNSIGNED DEFAULT NULL,
  `so_tien` decimal(15,2) NOT NULL DEFAULT 0.00,
  `trangthai` varchar(255) NOT NULL DEFAULT '''pending''',
  `duoc_duyet_luc` timestamp NULL DEFAULT NULL,
  `duoc_thanh_toan_luc` timestamp NULL DEFAULT NULL,
  `ghichu` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mausac`
--

CREATE TABLE `mausac` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ten` varchar(100) NOT NULL,
  `mamau` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mausac`
--

INSERT INTO `mausac` (`id`, `ten`, `mamau`) VALUES
(1, 'Đen', '#000000'),
(2, 'Đỏ', '#FF0000'),
(3, 'Nâu', '#A62B2B'),
(4, 'Vàng', '#FBFF00'),
(5, 'Xanh lá', '#008001'),
(6, 'Trắng', '#FFFFFF');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_08_14_170933_add_two_factor_columns_to_users_table', 1),
(5, '2026_03_23_160000_add_auth_fields_to_users_table', 1),
(6, '2026_03_24_030503_create_personal_access_tokens_table', 1),
(7, '2026_03_23_132121_create_nhom_thuoctinh_table', 2),
(8, '2026_03_23_132132_create_thuoctinh_table', 2),
(9, '2026_03_23_132138_create_giatri_thuoctinh_table', 2),
(10, '2026_03_23_132333_create_danhmuc_table', 2),
(11, '2026_03_23_132339_create_thuonghieu_table', 2),
(12, '2026_03_23_132344_create_sanpham_table', 2),
(13, '2026_03_23_132348_create_dathang_table', 2),
(14, '2026_03_23_132349_create_bienthe_table', 2),
(15, '2026_03_23_132350_create_bienthe_thuoctinh_table', 2),
(16, '2026_03_23_132353_create_dathang_chitiet_table', 2),
(17, '2026_03_23_133711_create_giohang_table', 2),
(18, '2026_03_24_164451_add_fields_to_users_table', 3),
(19, '2026_03_26_082403_create_colors_table', 4),
(20, '2026_03_26_094456_create_bienthe_hinhanh_table', 5),
(21, '2026_03_28_011430_create_otps_table', 6),
(23, '2026_03_31_141212_create_yeu_thiches_table', 7),
(24, '2026_04_02_101454_add_delta_to_giatri_thuoctinh', 8),
(25, '2026_04_02_110924_add_delta_to_giatri_thuoctinh', 9),
(26, '2026_03_31_153200_add_ly_do_huy_to_dathang_table', 10),
(27, '2026_03_31_160421_make_lydo_nullable_on_dathang_table', 10),
(28, '2026_04_05_101106_migrate_ly_do_huy_to_lydo', 10),
(29, '2026_04_14_000000_add_promotion_and_discount_to_dathang', 10),
(30, '2026_04_20_062535_add_thong_so_ky_thuat_to_sanpham_table', 11),
(31, '2026_05_20_132821_add_danh_muc_ids_to_giatri_thuoctinh_table', 12),
(32, '2026_05_22_150000_add_parent_id_to_danhmuc_table', 12),
(33, '2026_05_22_150100_add_danh_muc_ids_to_thuonghieu_table', 12),
(34, '2026_05_22_155705_add_danh_muc_ids_to_nhom_thuoc_tinh_table', 13),
(35, '2026_05_24_100000_create_sanpham_daxem_table', 14),
(36, '2026_05_26_162908_add_last_active_at_to_users_table', 15),
(37, '2026_05_26_163218_create_admin_activity_logs_table', 16),
(38, '2026_05_26_163619_create_affiliate_tables', 17),
(39, '2026_05_27_144021_add_refund_proof_to_dathang_table', 18),
(40, '2026_05_27_145838_create_affiliate_commissions_table', 19),
(41, '2026_05_27_150321_split_danh_muc_table', 1),
(42, '2026_05_27_153652_add_last_active_at_to_users_table', 1),
(43, '2026_05_28_034210_create_combos_tables', 20),
(44, '2026_05_28_034220_add_combo_fields_to_cart_and_order_details', 20),
(45, '2026_05_30_150839_create_bienthe_combo_offers_table', 21),
(46, '2026_05_30_160000_add_limit_and_expiry_to_bienthe_combo_offers_table', 22),
(47, '2026_05_30_170000_drop_unique_from_giohang_table', 23),
(48, '2026_06_01_034240_change_foreign_key_in_users_voucher_table', 24),
(49, '2026_06_06_000001_upgrade_banners_for_home_hero', 25),
(50, '2026_06_06_000002_seed_default_home_hero_banners', 25),
(51, '2026_06_08_040059_add_conditional_fields_to_promotions_table', 25),
(52, '2026_06_01_120645_add_is_refund_to_dathang_chitiet_table', 25),
(53, '2026_06_04_000001_add_auth_profile_columns_to_users_table', 26),
(54, '2026_06_09_000003_create_birthday_coupon_logs_table', 29),
(55, '2026_06_09_000004_create_birthday_coupon_settings_table', 29),
(56, '2026_06_09_153913_update_birthday_coupon_tables_relation', 30),
(57, '2026_06_18_235400_create_flash_sale_sessions_table', 99),
(58, '2026_06_18_235401_create_flash_sale_products_table', 99),
(59, '2026_06_19_224147_rename_tables_to_vietnamese', 100),
(60, '2026_06_19_225617_rename_affiliate_gioi_thieu_columns', 101),
(61, '2026_06_19_230316_rename_all_remaining_affiliate_columns', 102),
(62, '2026_06_19_232103_rename_banners_columns', 103),
(63, '2026_06_19_233549_rename_birthday_coupon_columns', 104),
(64, '2026_06_19_234922_rename_chat_columns', 105),
(65, '2026_06_20_000524_rename_order_columns', 106),
(66, '2026_06_20_002437_rename_user_columns', 107),
(67, '2026_06_05_025304_add_dynamic_fields_to_banners_table', 107),
(68, '2026_06_05_135520_create_bienthe_combo_offers_table', 107),
(69, '2026_06_05_135531_create_chat_tables', 107),
(70, '2026_06_20_211248_rename_khachhang_voucher_columns', 108),
(71, '2026_06_20_213500_rename_lienhe_columns', 109),
(72, '2026_06_20_215500_rename_mausac_columns', 110),
(73, '2026_06_20_220500_rename_nhat_ky_admin_columns', 111),
(74, '2026_06_20_221500_rename_otps_columns', 112),
(75, '2026_06_20_222000_rename_sanpham_daxem_columns', 113),
(76, '2026_06_20_222500_rename_san_pham_flashsale_columns', 114),
(77, '2026_06_20_223500_rename_tintuc_columns', 115),
(78, '2026_06_20_224500_rename_vouchers_columns', 116),
(79, '2026_06_20_225951_rename_user_id_to_id_khachhang_in_yeuthich_table', 117);

-- --------------------------------------------------------

--
-- Table structure for table `nhat_ky_admin`
--

CREATE TABLE `nhat_ky_admin` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang` bigint(20) UNSIGNED NOT NULL,
  `hanhdong` varchar(255) DEFAULT NULL,
  `tenmodel` varchar(255) DEFAULT NULL,
  `id_doituong` varchar(255) DEFAULT NULL,
  `mota` text DEFAULT NULL,
  `diachi_ip` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nhat_ky_admin`
--

INSERT INTO `nhat_ky_admin` (`id`, `id_khachhang`, `hanhdong`, `tenmodel`, `id_doituong`, `mota`, `diachi_ip`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 4, 'Cập nhật', 'Đơn hàng', '46', 'Đã cập nhật Đơn hàng [46] (ID: 46). Thay đổi: [trangthai]: \'pending\' ➔ \'confirmed\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-26 09:34:25', '2026-05-26 09:34:25'),
(2, 4, 'Cập nhật', 'Đơn hàng', '46', 'Đã cập nhật Đơn hàng [46] (ID: 46). Thay đổi: [trangthai]: \'confirmed\' ➔ \'shipping\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-26 09:34:34', '2026-05-26 09:34:34'),
(5, 4, 'Cập nhật', 'Đơn hàng', '46', 'Đã cập nhật Đơn hàng [46] (ID: 46). Thay đổi: [trangthai]: \'shipping\' ➔ \'done\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-26 09:38:39', '2026-05-26 09:38:39'),
(6, 4, 'Cập nhật', 'Đơn hàng', '47', 'Đã cập nhật Đơn hàng [47] (ID: 47). Thay đổi: [trangthai]: \'pending\' ➔ \'confirmed\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 07:47:49', '2026-05-27 07:47:49'),
(7, 4, 'Cập nhật', 'Đơn hàng', '47', 'Đã cập nhật Đơn hàng [47] (ID: 47). Thay đổi: [trangthai]: \'confirmed\' ➔ \'shipping\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 07:47:55', '2026-05-27 07:47:55'),
(8, 4, 'Cập nhật', 'Đơn hàng', '47', 'Đã cập nhật Đơn hàng [47] (ID: 47). Thay đổi: [trangthai]: \'shipping\' ➔ \'done\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 07:48:02', '2026-05-27 07:48:02'),
(11, 4, 'Cập nhật', 'Đơn hàng', '46', 'Đã cập nhật Đơn hàng [46] (ID: 46). Thay đổi: [trangthai]: \'refund_pending\' ➔ \'refunded\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 08:00:47', '2026-05-27 08:00:47'),
(12, 4, 'Thêm mới', 'Đơn hàng', '48', 'Đã thêm mới Đơn hàng [48] (ID: 48)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 22:51:08', '2026-05-27 22:51:08'),
(13, 4, 'Cập nhật', 'Đơn hàng', '48', 'Đã cập nhật Đơn hàng [48] (ID: 48). Thay đổi: [trangthai]: \'pending\' ➔ \'confirmed\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 22:51:51', '2026-05-27 22:51:51'),
(14, 4, 'Cập nhật', 'Đơn hàng', '48', 'Đã cập nhật Đơn hàng [48] (ID: 48). Thay đổi: [trangthai]: \'confirmed\' ➔ \'shipping\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 22:51:54', '2026-05-27 22:51:54'),
(15, 4, 'Cập nhật', 'Đơn hàng', '48', 'Đã cập nhật Đơn hàng [48] (ID: 48). Thay đổi: [trangthai]: \'shipping\' ➔ \'done\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 22:51:57', '2026-05-27 22:51:57'),
(16, 4, 'Cập nhật', 'Đơn hàng', '48', 'Đã cập nhật Đơn hàng [48] (ID: 48). Thay đổi: [trangthai]: \'done\' ➔ \'refund_pending\', [lydo]: \'\' ➔ \'thrhrth\', [refund_proof]: \'\' ➔ \'refunds/1yQ5fYFbybNjHCikhoaqE7m4M6MWZ...\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 22:53:56', '2026-05-27 22:53:56'),
(17, 4, 'Cập nhật', 'Đơn hàng', '48', 'Đã cập nhật Đơn hàng [48] (ID: 48). Thay đổi: [trangthai]: \'refund_pending\' ➔ \'refunded\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 22:54:19', '2026-05-27 22:54:19'),
(18, 4, 'Cập nhật', 'Đơn hàng', '47', 'Đã cập nhật Đơn hàng [47] (ID: 47). Thay đổi: [trangthai]: \'refund_pending\' ➔ \'refund_pickup\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 23:05:36', '2026-05-27 23:05:36'),
(19, 4, 'Cập nhật', 'Đơn hàng', '47', 'Đã cập nhật Đơn hàng [47] (ID: 47). Thay đổi: [trangthai]: \'refund_pickup\' ➔ \'refund_delivering\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 23:05:41', '2026-05-27 23:05:41'),
(20, 4, 'Cập nhật', 'Đơn hàng', '47', 'Đã cập nhật Đơn hàng [47] (ID: 47). Thay đổi: [trangthai]: \'refund_delivering\' ➔ \'refund_received\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 23:06:07', '2026-05-27 23:06:07'),
(21, 4, 'Cập nhật', 'Đơn hàng', '47', 'Đã cập nhật Đơn hàng [47] (ID: 47). Thay đổi: [trangthai]: \'refund_received\' ➔ \'refunded\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-27 23:06:12', '2026-05-27 23:06:12'),
(22, 4, 'Cập nhật', 'Đơn hàng', '49', 'Đã cập nhật Đơn hàng [49] (ID: 49). Thay đổi: [trangthai]: \'pending\' ➔ \'confirmed\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 05:01:27', '2026-05-28 05:01:27'),
(23, 4, 'Cập nhật', 'Đơn hàng', '49', 'Đã cập nhật Đơn hàng [49] (ID: 49). Thay đổi: [trangthai]: \'confirmed\' ➔ \'shipping\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 05:01:31', '2026-05-28 05:01:31'),
(24, 4, 'Cập nhật', 'Đơn hàng', '49', 'Đã cập nhật Đơn hàng [49] (ID: 49). Thay đổi: [trangthai]: \'shipping\' ➔ \'done\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 05:01:34', '2026-05-28 05:01:34'),
(25, 4, 'Cập nhật', 'Đơn hàng', '49', 'Đã cập nhật Đơn hàng [49] (ID: 49). Thay đổi: [trangthai]: \'refund_pending\' ➔ \'refund_pickup\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 05:02:24', '2026-05-28 05:02:24'),
(26, 4, 'Cập nhật', 'Đơn hàng', '49', 'Đã cập nhật Đơn hàng [49] (ID: 49). Thay đổi: [trangthai]: \'refund_pickup\' ➔ \'refund_delivering\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 05:09:25', '2026-05-28 05:09:25'),
(27, 4, 'Xóa', 'Đơn hàng', '49', 'Đã xóa Đơn hàng [49] (ID: 49)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:02:47', '2026-05-28 06:02:47'),
(28, 4, 'Xóa', 'Đơn hàng', '48', 'Đã xóa Đơn hàng [48] (ID: 48)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:13:05', '2026-05-28 06:13:05'),
(29, 4, 'Xóa', 'Đơn hàng', '47', 'Đã xóa Đơn hàng [47] (ID: 47)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:13:11', '2026-05-28 06:13:11'),
(30, 4, 'Xóa', 'Đơn hàng', '46', 'Đã xóa Đơn hàng [46] (ID: 46)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:13:17', '2026-05-28 06:13:17'),
(31, 4, 'Cập nhật', 'Đơn hàng', '51', 'Đã cập nhật Đơn hàng [51] (ID: 51). Thay đổi: [trangthai]: \'pending\' ➔ \'confirmed\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:23:03', '2026-05-28 06:23:03'),
(32, 4, 'Cập nhật', 'Đơn hàng', '51', 'Đã cập nhật Đơn hàng [51] (ID: 51). Thay đổi: [trangthai]: \'confirmed\' ➔ \'shipping\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:23:07', '2026-05-28 06:23:07'),
(33, 4, 'Cập nhật', 'Đơn hàng', '51', 'Đã cập nhật Đơn hàng [51] (ID: 51). Thay đổi: [trangthai]: \'shipping\' ➔ \'done\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:23:12', '2026-05-28 06:23:12'),
(34, 4, 'Cập nhật', 'Đơn hàng', '51', 'Đã cập nhật Đơn hàng [51] (ID: 51). Thay đổi: [trangthai]: \'refund_pending\' ➔ \'refund_pickup\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:24:28', '2026-05-28 06:24:28'),
(35, 4, 'Cập nhật', 'Đơn hàng', '51', 'Đã cập nhật Đơn hàng [51] (ID: 51). Thay đổi: [trangthai]: \'refund_pickup\' ➔ \'refund_delivering\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:24:33', '2026-05-28 06:24:33'),
(36, 4, 'Cập nhật', 'Đơn hàng', '51', 'Đã cập nhật Đơn hàng [51] (ID: 51). Thay đổi: [trangthai]: \'refund_delivering\' ➔ \'refund_received\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 06:24:38', '2026-05-28 06:24:38'),
(37, 4, 'Cập nhật', 'Đơn hàng', '51', 'Đã cập nhật Đơn hàng [51] (ID: 51). Thay đổi: [trangthai]: \'refund_received\' ➔ \'refunded\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 08:17:12', '2026-05-28 08:17:12'),
(38, 7, 'Cập nhật', 'Đơn hàng', '52', 'Đã cập nhật Đơn hàng [52] (ID: 52). Thay đổi: [trangthai]: \'pending\' ➔ \'confirmed\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:58:51', '2026-05-29 07:58:51'),
(39, 7, 'Cập nhật', 'Đơn hàng', '52', 'Đã cập nhật Đơn hàng [52] (ID: 52). Thay đổi: [trangthai]: \'confirmed\' ➔ \'shipping\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:58:54', '2026-05-29 07:58:54'),
(40, 7, 'Cập nhật', 'Đơn hàng', '52', 'Đã cập nhật Đơn hàng [52] (ID: 52). Thay đổi: [trangthai]: \'shipping\' ➔ \'done\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:58:57', '2026-05-29 07:58:57'),
(41, 7, 'Cập nhật', 'Đơn hàng', '52', 'Đã cập nhật Đơn hàng [52] (ID: 52). Thay đổi: [trangthai]: \'refund_pending\' ➔ \'done\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 08:00:24', '2026-05-29 08:00:24'),
(42, 4, 'Xóa', 'Khuyến mãi', '22', 'Đã xóa Khuyến mãi [Giảm 5.000.000đ] (ID: 22)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-31 20:58:15', '2026-05-31 20:58:15'),
(43, 4, 'Xóa', 'Khuyến mãi', '21', 'Đã xóa Khuyến mãi [Giảm 3.000.000đ] (ID: 21)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-31 20:58:16', '2026-05-31 20:58:16'),
(44, 4, 'Xóa', 'Khuyến mãi', '20', 'Đã xóa Khuyến mãi [Giảm 5%] (ID: 20)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-31 20:58:16', '2026-05-31 20:58:16'),
(45, 4, 'Xóa', 'Khuyến mãi', '19', 'Đã xóa Khuyến mãi [Giảm 40000000đ] (ID: 19)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-31 20:58:17', '2026-05-31 20:58:17'),
(46, 4, 'Thêm mới', 'Khuyến mãi', '26', 'Đã thêm mới Khuyến mãi [miễn phí 20%] (ID: 26)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-31 20:59:45', '2026-05-31 20:59:45'),
(47, 4, 'Thêm mới', 'Khuyến mãi', '27', 'Đã thêm mới Khuyến mãi [miễn phí] (ID: 27)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-31 21:00:27', '2026-05-31 21:00:27'),
(48, 16, 'Thêm mới', 'Khuyến mãi', '28', 'Đã thêm mới Khuyến mãi [Giảm giá  2.000.000đ] (ID: 28)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-06-07 21:12:44', '2026-06-07 21:12:44'),
(49, 16, 'Thêm mới', 'Khuyến mãi', '29', 'Đã thêm mới Khuyến mãi [giảm giá] (ID: 29)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-06-09 06:41:13', '2026-06-09 06:41:13'),
(50, 16, 'Thêm mới', 'Khuyến mãi', '30', 'Đã thêm mới Khuyến mãi [giảm giá 20%] (ID: 30)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-06-09 06:49:56', '2026-06-09 06:49:56'),
(51, 16, 'Xóa', 'Khuyến mãi', '30', 'Đã xóa Khuyến mãi [giảm giá 20%] (ID: 30)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-06-09 06:50:19', '2026-06-09 06:50:19'),
(52, 16, 'Thêm mới', 'Khuyến mãi', '31', 'Đã thêm mới Khuyến mãi [giảm giá 20%] (ID: 31)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-06-09 06:50:57', '2026-06-09 06:50:57'),
(53, 7, 'Cập nhật', 'Thương hiệu', '1', 'Đã cập nhật Thương hiệu [] (ID: 1). Thay đổi: [danh_muc_ids]: \'[2,3,7]\' ➔ \'[\"2\",\"3\",\"7\"]\', [logo]: \'\' ➔ \'brands/MGlA4hKsRADKnAkTPmZ6LqUqFan9p1...\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 15:22:01', '2026-06-13 15:22:01'),
(54, 7, 'Cập nhật', 'Thương hiệu', '2', 'Đã cập nhật Thương hiệu [] (ID: 2). Thay đổi: [danh_muc_ids]: \'[2,3,7]\' ➔ \'[\"2\",\"3\",\"7\"]\', [logo]: \'\' ➔ \'brands/tL8iW6hGMrQGCMcyoyiV5neKVNAfex...\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 15:22:36', '2026-06-13 15:22:36'),
(55, 7, 'Thêm mới', 'Danh mục', '14', 'Đã thêm mới Danh mục [] (ID: 14)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 15:53:59', '2026-06-13 15:53:59'),
(56, 7, 'Xóa', 'Danh mục', '14', 'Đã xóa Danh mục [] (ID: 14)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-13 15:54:13', '2026-06-13 15:54:13'),
(57, 7, 'Thêm mới', 'Sản phẩm', '36', 'Đã thêm mới Sản phẩm [] (ID: 36)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 09:56:40', '2026-06-15 09:56:40'),
(58, 7, 'Thêm mới', 'Khuyến mãi', '33', 'Đã thêm mới Khuyến mãi [Giảm giá 20%] (ID: 33)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 09:58:39', '2026-06-15 09:58:39'),
(59, 7, 'Cập nhật', 'Đơn hàng', '56', 'Đã cập nhật Đơn hàng [56] (ID: 56). Thay đổi: [trangthai]: \'pending\' ➔ \'confirmed\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:04:30', '2026-06-15 10:04:30'),
(60, 7, 'Cập nhật', 'Đơn hàng', '56', 'Đã cập nhật Đơn hàng [56] (ID: 56). Thay đổi: [trangthai]: \'confirmed\' ➔ \'shipping\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:04:33', '2026-06-15 10:04:33'),
(61, 7, 'Cập nhật', 'Đơn hàng', '56', 'Đã cập nhật Đơn hàng [56] (ID: 56). Thay đổi: [trangthai]: \'shipping\' ➔ \'done\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:04:36', '2026-06-15 10:04:36'),
(62, 7, 'Xóa', 'Sản phẩm', '36', 'Đã xóa Sản phẩm [] (ID: 36)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:20:21', '2026-06-15 10:20:21'),
(63, 7, 'Cập nhật', 'Đơn hàng', '57', 'Đã cập nhật Đơn hàng [57] (ID: 57). Thay đổi: [payment_status]: \'pending\' ➔ \'paid\', [payment_paid_at]: \'\' ➔ \'2026-06-19 00:42:10\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 17:42:10', '2026-06-18 17:42:10'),
(64, 7, 'Cập nhật', 'Đơn hàng', '58', 'Đã cập nhật Đơn hàng [58] (ID: 58). Thay đổi: [payment_status]: \'pending\' ➔ \'paid\', [payment_paid_at]: \'\' ➔ \'2026-06-19 00:47:33\'', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 17:47:33', '2026-06-18 17:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `nhat_ky_gui_ma_sinh_nhat`
--

CREATE TABLE `nhat_ky_gui_ma_sinh_nhat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang` bigint(20) UNSIGNED NOT NULL,
  `id_voucher` int(11) DEFAULT NULL,
  `id_khachhang_voucher` int(11) DEFAULT NULL,
  `mavoucher` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ngaysinh` date NOT NULL,
  `guiluc` timestamp NULL DEFAULT NULL,
  `trangthai` varchar(255) NOT NULL DEFAULT 'pending',
  `thongbaoloi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nhat_ky_gui_ma_sinh_nhat`
--

INSERT INTO `nhat_ky_gui_ma_sinh_nhat` (`id`, `id_khachhang`, `id_voucher`, `id_khachhang_voucher`, `mavoucher`, `email`, `ngaysinh`, `guiluc`, `trangthai`, `thongbaoloi`, `created_at`, `updated_at`) VALUES
(1, 15, 23, NULL, 'BIRTHDAY_USER_15_2026', 'quachducthanh04@gmail.com', '2004-06-01', '2026-06-09 08:47:00', 'sent', NULL, '2026-06-09 08:47:00', '2026-06-09 08:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `nhom_thuoctinh`
--

CREATE TABLE `nhom_thuoctinh` (
  `id_nhom` bigint(20) UNSIGNED NOT NULL,
  `ten_nhom` varchar(255) NOT NULL,
  `danh_muc_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `trangthai` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nhom_thuoctinh`
--

INSERT INTO `nhom_thuoctinh` (`id_nhom`, `ten_nhom`, `danh_muc_ids`, `trangthai`) VALUES
(1, 'Cấu hình (Laptop)', '[8]', 1),
(2, 'Màn hình (Laptop)', '[8]', 1),
(3, 'Pin & Sạc (Laptop)', '[8]', 1),
(8, 'Thông số (Phụ kiện)', '\"[9]\"', 1);

-- --------------------------------------------------------

--
-- Table structure for table `noi_dung_tro_chuyen`
--

CREATE TABLE `noi_dung_tro_chuyen` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_cuoc_tro_chuyen` bigint(20) UNSIGNED NOT NULL,
  `id_nguoigui` bigint(20) UNSIGNED NOT NULL,
  `noidung` text DEFAULT NULL,
  `duongdan_dinhkem` varchar(255) DEFAULT NULL,
  `ten_dinhkem` varchar(255) DEFAULT NULL,
  `daxem` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `noi_dung_tro_chuyen`
--

INSERT INTO `noi_dung_tro_chuyen` (`id`, `id_cuoc_tro_chuyen`, `id_nguoigui`, `noidung`, `duongdan_dinhkem`, `ten_dinhkem`, `daxem`, `created_at`, `updated_at`) VALUES
(102, 3, 7, 'svsvdf', NULL, NULL, 0, '2026-06-19 17:03:59', '2026-06-19 17:03:59');

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(255) NOT NULL,
  `hethan_luc` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 7, 'auth_token', 'eb2cd84038fa9501665c44614f35e79e37235bd6e08bfa959787254e8c72dba8', '[\"*\"]', '2026-03-28 01:16:43', NULL, '2026-03-28 01:16:37', '2026-03-28 01:16:43'),
(2, 'App\\Models\\User', 8, 'auth_token', '310d7633d96c5332845b211e474fa79a269a0dd972eedb8df5854485d804f1ad', '[\"*\"]', '2026-03-28 01:41:24', NULL, '2026-03-28 01:25:45', '2026-03-28 01:41:24'),
(3, 'App\\Models\\User', 4, 'auth_token', 'a9c604a4c61cf8b4285f7dcf8f59a82569e37aad1f7039558bb206b0512817ff', '[\"*\"]', '2026-03-28 01:44:02', NULL, '2026-03-28 01:36:26', '2026-03-28 01:44:02'),
(4, 'App\\Models\\User', 7, 'auth_token', '06ca24b017014b89c2a4f87a9770dc9fcc6087ff04b7dc5d49ed1a3e4ad966d6', '[\"*\"]', '2026-03-28 02:43:22', NULL, '2026-03-28 02:42:45', '2026-03-28 02:43:22'),
(5, 'App\\Models\\User', 7, 'auth_token', 'adbf0691744d52693962a18bbc26a2c805b2118892530d736cfd6477b52f20ef', '[\"*\"]', '2026-03-28 03:09:24', NULL, '2026-03-28 02:43:32', '2026-03-28 03:09:24'),
(6, 'App\\Models\\User', 9, 'auth_token', 'e1c35f9590fe09fcda2caeb7ad2a6f7fc4386a3b5886a993b353e4f0a01f1ce6', '[\"*\"]', '2026-03-28 03:12:33', NULL, '2026-03-28 03:12:28', '2026-03-28 03:12:33'),
(7, 'App\\Models\\User', 7, 'auth_token', 'f3377a860ba6772a226d3c5ccb7911e8af452f84b04f82baf9918fa2714fefe9', '[\"*\"]', '2026-03-31 03:54:32', NULL, '2026-03-28 03:19:36', '2026-03-31 03:54:32'),
(8, 'App\\Models\\User', 12, 'auth_token', '075c1d3b5b8fc56e0cc3041c21eac00ebbbceb3b34431748fe70eddff400558b', '[\"*\"]', '2026-04-01 22:07:17', NULL, '2026-03-31 08:18:32', '2026-04-01 22:07:17'),
(9, 'App\\Models\\User', 7, 'auth_token', '06d3bfc25e0c4d326661ab1e69cb3d1228836c55e5951682b2d1ceb17c7df84c', '[\"*\"]', '2026-04-02 00:42:28', NULL, '2026-04-01 22:07:30', '2026-04-02 00:42:28'),
(10, 'App\\Models\\User', 12, 'auth_token', '8879a1f05ab32c5db4032eda3afcfab0282a2ff329144dde44ba997498a46f45', '[\"*\"]', '2026-04-02 00:43:21', NULL, '2026-04-02 00:43:16', '2026-04-02 00:43:21'),
(11, 'App\\Models\\User', 7, 'auth_token', 'a6cf92793f1b8fe7cabd0fb2f379e5e26f32c878cda3b38afc2df3249b2b121f', '[\"*\"]', '2026-04-02 02:16:27', NULL, '2026-04-02 00:43:32', '2026-04-02 02:16:27'),
(12, 'App\\Models\\User', 12, 'auth_token', '813d77ff6d049ead3724ac3551917efbecb2f1613b8018f6a3b277fe440eecfb', '[\"*\"]', '2026-05-17 01:57:00', NULL, '2026-04-02 01:31:25', '2026-05-17 01:57:00'),
(13, 'App\\Models\\User', 12, 'auth_token', 'f44bbc94a1efdc3536251c2ec91c36293f957c13fe1440b8a52467f100969161', '[\"*\"]', '2026-04-02 02:52:15', NULL, '2026-04-02 02:16:51', '2026-04-02 02:52:15'),
(14, 'App\\Models\\User', 7, 'auth_token', 'b175530b590bb1fd22fff69a632d59fd8fd01ebe5f51e31fdb9733134c034276', '[\"*\"]', '2026-04-04 03:12:35', NULL, '2026-04-02 02:55:47', '2026-04-04 03:12:35'),
(15, 'App\\Models\\User', 12, 'auth_token', '58e727d63d9d5dab620a6d3e5221b5f3479ea8f7a3d855bc293cb69b4cde1faa', '[\"*\"]', '2026-04-04 03:26:16', NULL, '2026-04-04 03:24:07', '2026-04-04 03:26:16'),
(16, 'App\\Models\\User', 7, 'auth_token', '376c7da2981c2c8850d39f676c33ceb8e0ed6b12d0b2f6e85a4607a68e2027e5', '[\"*\"]', '2026-04-04 07:52:34', NULL, '2026-04-04 03:31:15', '2026-04-04 07:52:34'),
(17, 'App\\Models\\User', 7, 'auth_token', '46938a09e7d7b9b07fbb89235660ce5cb7d5711e19841e7c490dd4640da06e75', '[\"*\"]', '2026-04-05 08:23:24', NULL, '2026-04-04 07:53:14', '2026-04-05 08:23:24'),
(18, 'App\\Models\\User', 12, 'auth_token', '0c5de676ac7a0e9935ec0e68e206816bdb37a3a194f3ea73cb12003f034e270a', '[\"*\"]', '2026-04-05 08:45:24', NULL, '2026-04-05 08:23:57', '2026-04-05 08:45:24'),
(19, 'App\\Models\\User', 7, 'auth_token', '093fbbd318a62af831d5c3d3ee15199b1aad111f9cd801e757d5facf95d75acd', '[\"*\"]', '2026-04-06 00:21:36', NULL, '2026-04-05 09:04:06', '2026-04-06 00:21:36'),
(20, 'App\\Models\\User', 7, 'auth_token', '4f52daca07b1cdd03e1597ef49d7a4302011a3219acb10fe21ece36e9ab8e61b', '[\"*\"]', '2026-04-06 00:27:28', NULL, '2026-04-06 00:27:19', '2026-04-06 00:27:28'),
(21, 'App\\Models\\User', 7, 'auth_token', '19e37400e9fa0baaf96fb19309fe70b0d7e4653c0990b21585235fd0494a81bb', '[\"*\"]', '2026-04-06 01:39:00', NULL, '2026-04-06 01:38:56', '2026-04-06 01:39:00'),
(22, 'App\\Models\\User', 12, 'auth_token', '57729cfbed184d6b4b48622ff825ffbafe955a783b1bd3cccbfe14e80ea842ff', '[\"*\"]', '2026-04-06 07:00:34', NULL, '2026-04-06 01:39:42', '2026-04-06 07:00:34'),
(23, 'App\\Models\\User', 7, 'auth_token', '4766d3053e0fc9dd07155a021b88a44c272cc2fe90a2f3c93488b1a0b391e24e', '[\"*\"]', '2026-04-09 02:19:58', NULL, '2026-04-06 07:02:24', '2026-04-09 02:19:58'),
(24, 'App\\Models\\User', 12, 'auth_token', 'e40dcdd371d1369abf90a36d81531d8f78599be5b16dd3be0b3a48de44693303', '[\"*\"]', '2026-04-09 03:57:08', NULL, '2026-04-06 07:05:10', '2026-04-09 03:57:08'),
(25, 'App\\Models\\User', 7, 'auth_token', 'ecb347b68f0f57beb70c799f2ccc19c3e88ac9dbbaed0cac118aeb7b7f77b40a', '[\"*\"]', '2026-04-09 03:29:49', NULL, '2026-04-09 02:20:10', '2026-04-09 03:29:49'),
(26, 'App\\Models\\User', 7, 'auth_token', 'ef7b59119cc373f774a2a52b8ba5e022926070cc6a19453d7883d9ff48048097', '[\"*\"]', '2026-04-09 03:54:18', NULL, '2026-04-09 03:54:12', '2026-04-09 03:54:18'),
(27, 'App\\Models\\User', 7, 'auth_token', '0fe155991510442e3dcf67fc8d315cd8484434c898073f9d24914a31332b7078', '[\"*\"]', '2026-04-13 02:26:08', NULL, '2026-04-09 03:54:26', '2026-04-13 02:26:08'),
(28, 'App\\Models\\User', 12, 'auth_token', 'ac255f1ff06d4c31df93b7176a1f60daec160eb2c2f2d70cf6abdb42dca1dda9', '[\"*\"]', '2026-04-09 09:33:21', NULL, '2026-04-09 03:58:39', '2026-04-09 09:33:21'),
(29, 'App\\Models\\User', 13, 'auth_token', '684622641585d231494a3351a280e0799c10f30938fdbeef1c3c2d58133f28be', '[\"*\"]', '2026-04-09 09:47:35', NULL, '2026-04-09 09:47:26', '2026-04-09 09:47:35'),
(30, 'App\\Models\\User', 12, 'auth_token', '2ac9682e524a0a468bbdb1d91be80f6e549da44aa8b275aba8d81aeda8af1908', '[\"*\"]', '2026-04-09 09:48:06', NULL, '2026-04-09 09:48:02', '2026-04-09 09:48:06'),
(31, 'App\\Models\\User', 12, 'auth_token', 'cddd77d7fb8adaf0aa80ab2f92a86970131dae1327cfb0e86b564a6aea4be86d', '[\"*\"]', '2026-04-16 03:04:59', NULL, '2026-04-09 09:49:13', '2026-04-16 03:04:59'),
(32, 'App\\Models\\User', 7, 'session_token', 'c3ac1cffe2640f7c7b8542ac85ef5603f24609515285a606f8452f6fc8a2256f', '[\"*\"]', NULL, NULL, '2026-04-13 02:27:11', '2026-04-13 02:27:11'),
(33, 'App\\Models\\User', 7, 'session_token', '165fa46862faa1cb2599c274366e31ef46803e7e61ee76e154eb76bf1d49d4c8', '[\"*\"]', '2026-04-13 20:45:53', NULL, '2026-04-13 20:38:55', '2026-04-13 20:45:53'),
(34, 'App\\Models\\User', 7, 'remember_token', 'aacc6b6302f21038e6c3f63f12d52ed1dc10d18d31c415fb2011f54d8e7edded', '[\"*\"]', '2026-04-16 02:46:16', NULL, '2026-04-14 02:14:42', '2026-04-16 02:46:16'),
(35, 'App\\Models\\User', 4, 'remember_token', 'f3c8b4902d9f02fab243c11e162da874137bacc51cb3ff58baa9cd48e78c6883', '[\"*\"]', '2026-04-14 03:41:17', NULL, '2026-04-14 03:25:33', '2026-04-14 03:41:17'),
(36, 'App\\Models\\User', 7, 'remember_token', '2fcb31348850d6d1922a5f36412ccdf6f47f0174cdc5e53d76453de39c36b3dc', '[\"*\"]', '2026-05-31 09:06:45', NULL, '2026-04-16 02:55:07', '2026-05-31 09:06:45'),
(37, 'App\\Models\\User', 12, 'session_token', '8a7ee5bf09648d10ce76e481366d0ea8525023125b40674ca669a512acf47739', '[\"*\"]', '2026-04-16 03:14:42', NULL, '2026-04-16 03:08:31', '2026-04-16 03:14:42'),
(38, 'App\\Models\\User', 12, 'session_token', '19c64a0cff9897e211c9a2451076eee472d5bbbb5feec26f4287b110a2817aa6', '[\"*\"]', '2026-04-16 03:39:14', NULL, '2026-04-16 03:17:46', '2026-04-16 03:39:14'),
(39, 'App\\Models\\User', 12, 'session_token', 'fad5108cb05c7c14ca85c20e3bdaaf9a9889eeb5860ce4eb281d3bfd70b4d744', '[\"*\"]', '2026-04-20 03:10:06', NULL, '2026-04-20 02:51:17', '2026-04-20 03:10:06'),
(40, 'App\\Models\\User', 12, 'session_token', '926b47894ecc8fe2dbc3ce2b6c81f901ea1e9c21d2e4415117de1f145ca5dd50', '[\"*\"]', '2026-04-20 20:41:23', NULL, '2026-04-20 20:22:13', '2026-04-20 20:41:23'),
(41, 'App\\Models\\User', 12, 'session_token', '7b7762f4b6714407d8d2553e32969f7e1801635748d9118add2487014e3cd12d', '[\"*\"]', '2026-04-21 23:18:30', NULL, '2026-04-21 20:36:46', '2026-04-21 23:18:30'),
(42, 'App\\Models\\User', 12, 'session_token', '26a07616ff3cf7224cdfd9a7c5a82b5b87f98a77baab99123d6eb2d6d31f971e', '[\"*\"]', '2026-04-25 01:07:52', NULL, '2026-04-24 23:18:06', '2026-04-25 01:07:52'),
(43, 'App\\Models\\User', 12, 'auth_token', 'd37695c9f4a809006cfe25f2600bea981524c63ebee39920740e0be75305a155', '[\"*\"]', '2026-04-25 01:50:36', NULL, '2026-04-25 01:23:17', '2026-04-25 01:50:36'),
(44, 'App\\Models\\User', 12, 'auth_token', 'b21952339a76c9cc7d2b804d39859b53c1da801ad47338a094ed39ed8f5b4d28', '[\"*\"]', '2026-05-20 09:53:36', NULL, '2026-05-20 09:53:17', '2026-05-20 09:53:36'),
(45, 'App\\Models\\User', 12, 'auth_token', 'e56609b957530d1d1323533b531cf437add3013d7496f50d7d16e0dbf7714a27', '[\"*\"]', '2026-05-24 00:51:47', NULL, '2026-05-22 08:51:09', '2026-05-24 00:51:47'),
(46, 'App\\Models\\User', 12, 'auth_token', 'b40bc9d23ccb8cfb6f38668ecceee42c9565b86163d4b6ae8452f28177df2ec4', '[\"*\"]', '2026-05-24 01:17:09', NULL, '2026-05-24 01:16:58', '2026-05-24 01:17:09'),
(48, 'App\\Models\\User', 15, 'auth_token', 'f68bce479ff715d8576226d54f2c9e0269c77ada7ac2c50e8d18a672b7e7b3e8', '[\"*\"]', '2026-05-26 09:39:15', NULL, '2026-05-26 09:19:50', '2026-05-26 09:39:15'),
(49, 'App\\Models\\User', 4, 'remember_token', '444c637e7a9cee8d71d30535c179938f07062a8437aea6d1448e2556950d0d4d', '[\"*\"]', NULL, NULL, '2026-05-26 09:21:46', '2026-05-26 09:21:46'),
(50, 'App\\Models\\User', 4, 'remember_token', '76a6a2a7edcc4949b264891b85f9479bf64fe83128fc9b2d4af4e66760b45410', '[\"*\"]', '2026-05-26 09:38:39', NULL, '2026-05-26 09:22:41', '2026-05-26 09:38:39'),
(51, 'App\\Models\\User', 4, 'remember_token', 'c540f63f8b833ae809d0dd87bebc82210181aa589dad6f569338bec4008f3bfb', '[\"*\"]', '2026-05-27 08:14:17', NULL, '2026-05-27 07:15:26', '2026-05-27 08:14:17'),
(52, 'App\\Models\\User', 15, 'auth_token', '6ef4b7c5baa7c261fea9dfd17c6c45537637697f1c271896c4facdf6b70a06db', '[\"*\"]', '2026-05-27 08:15:12', NULL, '2026-05-27 07:30:03', '2026-05-27 08:15:12'),
(53, 'App\\Models\\User', 4, 'remember_token', '770516220861d4a72bf1e68f55558cf982ff6311b05c58aba190f7faf44eeb46', '[\"*\"]', NULL, NULL, '2026-05-27 22:49:25', '2026-05-27 22:49:25'),
(54, 'App\\Models\\User', 15, 'auth_token', '213888496cbda7455abd695370374cf553f79a895b7bf6d5b7343c0c404861db', '[\"*\"]', '2026-05-27 23:04:37', NULL, '2026-05-27 22:49:31', '2026-05-27 23:04:37'),
(55, 'App\\Models\\User', 4, 'remember_token', '0243799b2bc0aff828e06a054a7caec9e31ed5d1e6cd936ea675baf10fa523aa', '[\"*\"]', '2026-05-27 23:06:16', NULL, '2026-05-27 22:50:42', '2026-05-27 23:06:16'),
(56, 'App\\Models\\User', 4, 'remember_token', '069f5faa373bef2d519f3c137ff41ff56b04426cc21e72a863002478e3396c6f', '[\"*\"]', '2026-05-28 08:17:21', NULL, '2026-05-28 04:24:49', '2026-05-28 08:17:21'),
(57, 'App\\Models\\User', 15, 'auth_token', '967d9c8b290e697160a9b1e764c79e078c87473b3e8e31aae8a51bb2a8d3ee70', '[\"*\"]', NULL, NULL, '2026-05-28 04:25:26', '2026-05-28 04:25:26'),
(58, 'App\\Models\\User', 15, 'auth_token', '67b673fa21dc208667ad2bb50e97d76cb9dd956d935a56270b04ba17e2ddc05a', '[\"*\"]', '2026-05-28 08:17:40', NULL, '2026-05-28 04:25:27', '2026-05-28 08:17:40'),
(59, 'App\\Models\\User', 12, 'auth_token', 'ba479cfdfa771d6e80deaced2cc7f87716b4cf30ec85e20a606ba09cbcb5457e', '[\"*\"]', '2026-05-29 07:39:47', NULL, '2026-05-28 09:02:29', '2026-05-29 07:39:47'),
(60, 'App\\Models\\User', 12, 'auth_token', '8af45dbbe85c08ad3ced8d57a88d258b62f2992478b27396888a4a184685f0b4', '[\"*\"]', '2026-05-29 08:10:08', NULL, '2026-05-29 07:40:03', '2026-05-29 08:10:08'),
(61, 'App\\Models\\User', 12, 'session_token', 'a285c3083ef31475c7bc9a159548b0a5a9bb81e060e5842fdd37879cbda7a41f', '[\"*\"]', NULL, NULL, '2026-05-31 09:03:52', '2026-05-31 09:03:52'),
(62, 'App\\Models\\User', 12, 'session_token', '7b5d9d1d906a5d526103777c0aa92c65f49c734f469f008f3136f751652fb0dd', '[\"*\"]', '2026-05-31 09:05:41', NULL, '2026-05-31 09:04:00', '2026-05-31 09:05:41'),
(63, 'App\\Models\\User', 4, 'session_token', '28566adde2a4e6e73d1110a9484935e5cb021e3216f18acf014610a3ce5fde45', '[\"*\"]', '2026-05-31 20:44:08', NULL, '2026-05-31 20:43:58', '2026-05-31 20:44:08'),
(64, 'App\\Models\\User', 16, 'auth_token', '08ae0a4bf104f69a6143dca3f5abe3fa942079194d90a3e5a88cee73d35b46e8', '[\"*\"]', '2026-06-01 01:32:25', NULL, '2026-05-31 20:50:04', '2026-06-01 01:32:25'),
(65, 'App\\Models\\User', 4, 'session_token', '645dfe4b5560942d7b6d51069cf67d0e3f8986fc416744eb770239cf5efca409', '[\"*\"]', NULL, NULL, '2026-05-31 20:57:47', '2026-05-31 20:57:47'),
(66, 'App\\Models\\User', 4, 'session_token', 'e4162ec2f9e5b6d0918f9cfe972049a6d67c0f8987997ad261c0701d7497c145', '[\"*\"]', '2026-06-01 03:22:51', NULL, '2026-05-31 20:57:54', '2026-06-01 03:22:51'),
(67, 'App\\Models\\User', 4, 'session_token', '0af5613c706b954dd643a2f4e715d894ba47c224d808b4bef455821fbce9f9a5', '[\"*\"]', '2026-06-01 01:32:40', NULL, '2026-06-01 01:32:32', '2026-06-01 01:32:40'),
(68, 'App\\Models\\User', 16, 'auth_token', '2be414d493b8fd0a75c0ceb0ea39057fd6a83abdbb8b07d5f13a978e7c637724', '[\"*\"]', '2026-06-01 04:35:28', NULL, '2026-06-01 03:23:52', '2026-06-01 04:35:28'),
(69, 'App\\Models\\User', 4, 'session_token', 'a8de3e099f5adc5264ee47d0abe25766a290f2574a3d19d073790b991a4c320b', '[\"*\"]', NULL, NULL, '2026-06-07 04:58:04', '2026-06-07 04:58:04'),
(70, 'App\\Models\\User', 4, 'session_token', 'b40301a6b228d4e6b2d46d2a363168e0fba5721294baedc49e95755cec4a1b2a', '[\"*\"]', NULL, NULL, '2026-06-07 19:14:10', '2026-06-07 19:14:10'),
(71, 'App\\Models\\User', 16, 'auth_token', '58579dc3fe8c0873cec7bc34a44e06e4c618bc391d357f621c497810f6e3c9a5', '[\"*\"]', '2026-06-10 01:20:03', NULL, '2026-06-07 19:15:18', '2026-06-10 01:20:03'),
(72, 'App\\Models\\User', 16, 'auth_token', '4d033bfa4e69b21fa569b3b251197aad28998e8f69454aa12709f0f1dcef993e', '[\"*\"]', '2026-06-10 01:51:34', NULL, '2026-06-07 20:35:55', '2026-06-10 01:51:34'),
(73, 'App\\Models\\User', 17, 'auth_token', 'dfe1ded89612f650597e5a06c919e0976406e76f2ca254164194acedaf04cb92', '[\"*\"]', '2026-06-09 06:26:40', NULL, '2026-06-07 21:18:12', '2026-06-09 06:26:40'),
(74, 'App\\Models\\User', 18, 'auth_token', 'c65fb0d1adcfb1ed84a82a5b8db03657104788f502d94739a3f4060d644cfac1', '[\"*\"]', '2026-06-09 06:51:46', NULL, '2026-06-09 06:27:35', '2026-06-09 06:51:46'),
(75, 'App\\Models\\User', 7, 'auth_token', '2c64e48bf779065f7451635126c23e2d4562ff68f8ce9392840969c3b7e2089c', '[\"*\"]', '2026-06-11 09:49:53', NULL, '2026-06-11 09:06:43', '2026-06-11 09:49:53'),
(76, 'App\\Models\\User', 12, 'session_token', '688e6aec1d1259c86a774e49be82cb74f174e88f4701339ec073a32f34f2ca59', '[\"*\"]', '2026-06-15 10:00:42', NULL, '2026-06-11 09:13:15', '2026-06-15 10:00:42'),
(77, 'App\\Models\\User', 7, 'auth_token', 'c36f8ebb4d0dd0b9d9f78aa292191c0da1df82722f94a0ef71fd0dfc6b3dce8e', '[\"*\"]', '2026-06-12 03:03:05', NULL, '2026-06-12 02:34:59', '2026-06-12 03:03:05'),
(78, 'App\\Models\\User', 7, 'auth_token', '1795b6c623b94507f896a71feefa1489a4d9ebcbf31a77a74d826f3e33b307bb', '[\"*\"]', '2026-06-13 16:56:21', NULL, '2026-06-13 15:10:41', '2026-06-13 16:56:21'),
(79, 'App\\Models\\User', 7, 'auth_token', '61184ca34c2d7f754c8efa3af260186ff4958b2c0f321392b279dc0fb3f67212', '[\"*\"]', '2026-06-15 04:15:25', NULL, '2026-06-15 02:25:45', '2026-06-15 04:15:25'),
(80, 'App\\Models\\User', 7, 'auth_token', '5b06166efbf10844ba9fe3b6ae06a4b618d4d7bd69f4f5587f0c85b54498c64d', '[\"*\"]', '2026-06-15 10:20:52', NULL, '2026-06-15 09:55:01', '2026-06-15 10:20:52'),
(81, 'App\\Models\\User', 12, 'auth_token', '922c03e65f6d4bb1c6419ba8d85a8a3cd38d7f39eea2e25d2f599cc264e51c87', '[\"*\"]', '2026-06-15 10:22:40', NULL, '2026-06-15 10:00:49', '2026-06-15 10:22:40'),
(82, 'App\\Models\\User', 7, 'auth_token', '110ddeeb9667afc5dfbc19b60f02c8a75da71045d8efda89edb26d2f2ddfa1ed', '[\"*\"]', '2026-06-18 17:47:33', NULL, '2026-06-18 16:34:04', '2026-06-18 17:47:33'),
(83, 'App\\Models\\User', 12, 'auth_token', '1ea728b29e1886042faaa6a4bd81f368754e5ae5243dba00a5dfc64e18cca59f', '[\"*\"]', '2026-06-19 14:50:56', NULL, '2026-06-18 17:33:30', '2026-06-19 14:50:56'),
(84, 'App\\Models\\User', 7, 'auth_token', '55724f2b65279b6898392339599695daa595ae992f598cb25ac29ad8b21bd99d', '[\"*\"]', '2026-06-19 17:15:46', NULL, '2026-06-19 14:50:08', '2026-06-19 17:15:46'),
(85, 'App\\Models\\User', 12, 'auth_token', '86d342bf27af367368436ee1a097aa672ff6cee9c313cf7ebb27e2e9ed2cdf94', '[\"*\"]', '2026-06-19 14:57:15', NULL, '2026-06-19 14:51:01', '2026-06-19 14:57:15'),
(86, 'App\\Models\\User', 7, 'auth_token', '429186f884bdabbb3a45ec105c4ce230d56cf54061f2d0ae9b8e07538a195ac7', '[\"*\"]', '2026-06-20 16:06:42', NULL, '2026-06-20 16:06:29', '2026-06-20 16:06:42'),
(87, 'App\\Models\\User', 7, 'auth_token', 'a98f643ed3050c62652057517f18851c832a3acfa105e26bee0194737d5c2166', '[\"*\"]', '2026-06-20 16:12:46', NULL, '2026-06-20 16:12:40', '2026-06-20 16:12:46'),
(88, 'App\\Models\\User', 7, 'auth_token', '08cffc252184d23e1e55a6b1cb87ddf6081e8ff4129d9cdc26bd0389eb87d5b4', '[\"*\"]', '2026-06-20 16:13:17', NULL, '2026-06-20 16:13:12', '2026-06-20 16:13:17'),
(89, 'App\\Models\\User', 7, 'session_token', 'ea2409d68499c99e6143215eb4ac6e82574cf978889701ccf9103f64eda9ad63', '[\"*\"]', '2026-06-20 16:32:50', NULL, '2026-06-20 16:15:52', '2026-06-20 16:32:50'),
(90, 'App\\Models\\User', 7, 'auth_token', '6f0f5544a08d5b885ecec536faf93466afac9f6b83bb58b91aea36ab097c9aea', '[\"*\"]', '2026-06-20 16:33:59', NULL, '2026-06-20 16:33:54', '2026-06-20 16:33:59'),
(91, 'App\\Models\\User', 7, 'session_token', '368416414fd16a4291f88239f31b2f32c78ab38e149e6fd4bb418cb7421f206d', '[\"*\"]', '2026-06-21 16:37:40', NULL, '2026-06-21 16:28:44', '2026-06-21 16:37:40'),
(92, 'App\\Models\\User', 7, 'session_token', '427c658d2b549c0dc207c61c14821f721b236cb2a8662f86633bc662acc8a36c', '[\"*\"]', '2026-06-21 16:51:21', NULL, '2026-06-21 16:40:03', '2026-06-21 16:51:21');

-- --------------------------------------------------------

--
-- Table structure for table `sanpham`
--

CREATE TABLE `sanpham` (
  `id_sanpham` bigint(20) UNSIGNED NOT NULL,
  `id_danhmuc` bigint(20) UNSIGNED NOT NULL,
  `id_thuonghieu` bigint(20) UNSIGNED NOT NULL,
  `thong_so_ky_thuat` longtext DEFAULT NULL COMMENT 'Lưu trữ thông số kỹ thuật dưới dạng JSON',
  `tenSP` varchar(255) NOT NULL,
  `SKU` varchar(255) DEFAULT NULL,
  `trangthai` varchar(225) NOT NULL DEFAULT '0.00',
  `hinhanh` varchar(1000) DEFAULT NULL,
  `khoiluong` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sanpham`
--

INSERT INTO `sanpham` (`id_sanpham`, `id_danhmuc`, `id_thuonghieu`, `thong_so_ky_thuat`, `tenSP`, `SKU`, `trangthai`, `hinhanh`, `khoiluong`) VALUES
(25, 4, 7, '[{\"id_thuoctinh\":\"3\",\"ten_thuoctinh\":\"GPU\",\"giatri\":\"RX 7800 XT\"},{\"id_thuoctinh\":\"11\",\"ten_thuoctinh\":\"SSD\",\"giatri\":\"512GB\"},{\"id_thuoctinh\":\"4\",\"ten_thuoctinh\":\"K\\u00edch th\\u01b0\\u1edbc\",\"giatri\":\"16 inch\"},{\"id_thuoctinh\":\"5\",\"ten_thuoctinh\":\"\\u0110\\u1ed9 ph\\u00e2n gi\\u1ea3i\",\"giatri\":\"2K 2560\\u00d71440\"},{\"id_thuoctinh\":\"6\",\"ten_thuoctinh\":\"T\\u1ea5m n\\u1ec1n\",\"giatri\":\"Mini-LED\"},{\"id_thuoctinh\":\"7\",\"ten_thuoctinh\":\"Pin\",\"giatri\":\"100Wh\"},{\"id_thuoctinh\":\"8\",\"ten_thuoctinh\":\"S\\u1ea1c\",\"giatri\":\"65W USB-C\"}]', 'MacBook Air M4', 'SP-2-Y8M5C4', '1', 'uploads/sanpham/92a2975e4a6674a453e8c9d7fb128e13.webp', '2.07'),
(26, 2, 3, '[{\"id_thuoctinh\":\"3\",\"ten_thuoctinh\":\"GPU\",\"giatri\":\"RTX 4080\"},{\"id_thuoctinh\":\"11\",\"ten_thuoctinh\":\"SSD\",\"giatri\":\"8TB\"},{\"id_thuoctinh\":\"4\",\"ten_thuoctinh\":\"K\\u00edch th\\u01b0\\u1edbc\",\"giatri\":\"15.6 inch\"},{\"id_thuoctinh\":\"5\",\"ten_thuoctinh\":\"\\u0110\\u1ed9 ph\\u00e2n gi\\u1ea3i\",\"giatri\":\"2K 2560\\u00d71440\"},{\"id_thuoctinh\":\"6\",\"ten_thuoctinh\":\"T\\u1ea5m n\\u1ec1n\",\"giatri\":\"OLED\"},{\"id_thuoctinh\":\"7\",\"ten_thuoctinh\":\"Pin\",\"giatri\":\"72Wh\"},{\"id_thuoctinh\":\"8\",\"ten_thuoctinh\":\"S\\u1ea1c\",\"giatri\":\"140W MagSafe\"}]', 'Laptop Lenovo Gaming LOQ 15IRX9', 'SP-1-IWCIVM', '1', 'uploads/sanpham/382cd5ada4ca9b1cad94463f877d9fdf.webp', '2.49'),
(27, 3, 2, '[{\"id_thuoctinh\":\"3\",\"ten_thuoctinh\":\"GPU\",\"giatri\":\"RTX 4080\"},{\"id_thuoctinh\":\"11\",\"ten_thuoctinh\":\"SSD\",\"giatri\":\"512GB\"},{\"id_thuoctinh\":\"4\",\"ten_thuoctinh\":\"K\\u00edch th\\u01b0\\u1edbc\",\"giatri\":\"14 inch\"},{\"id_thuoctinh\":\"5\",\"ten_thuoctinh\":\"\\u0110\\u1ed9 ph\\u00e2n gi\\u1ea3i\",\"giatri\":\"2K 2560\\u00d71440\"},{\"id_thuoctinh\":\"6\",\"ten_thuoctinh\":\"T\\u1ea5m n\\u1ec1n\",\"giatri\":\"OLED\"},{\"id_thuoctinh\":\"7\",\"ten_thuoctinh\":\"Pin\",\"giatri\":\"100Wh\"},{\"id_thuoctinh\":\"8\",\"ten_thuoctinh\":\"S\\u1ea1c\",\"giatri\":\"65W USB-C\"}]', 'Laptop HP 15', 'SP-2-ZPYYEO', '1', 'uploads/sanpham/9c65ae8282a281c9735e567dc0e63384.png', '2.5'),
(28, 4, 7, '[{\"id_thuoctinh\":\"3\",\"ten_thuoctinh\":\"GPU\",\"giatri\":\"Apple M3 GPU\"},{\"id_thuoctinh\":\"11\",\"ten_thuoctinh\":\"SSD\",\"giatri\":\"1TB\"},{\"id_thuoctinh\":\"4\",\"ten_thuoctinh\":\"K\\u00edch th\\u01b0\\u1edbc\",\"giatri\":\"14 inch\"},{\"id_thuoctinh\":\"5\",\"ten_thuoctinh\":\"\\u0110\\u1ed9 ph\\u00e2n gi\\u1ea3i\",\"giatri\":\"4K 3840\\u00d72160\"},{\"id_thuoctinh\":\"6\",\"ten_thuoctinh\":\"T\\u1ea5m n\\u1ec1n\",\"giatri\":\"AMOLED\"},{\"id_thuoctinh\":\"7\",\"ten_thuoctinh\":\"Pin\",\"giatri\":\"72Wh\"},{\"id_thuoctinh\":\"8\",\"ten_thuoctinh\":\"S\\u1ea1c\",\"giatri\":\"140W MagSafe\"}]', 'MacBook Pro 14 M5 Pro', 'SP-7-2L3OBU', '1', 'uploads/sanpham/cb8585ee381e66860f02485d9cb556f6.webp', '2.5'),
(29, 2, 1, '[{\"id_thuoctinh\":\"3\",\"ten_thuoctinh\":\"GPU\",\"giatri\":\"RTX 4090\"},{\"id_thuoctinh\":\"11\",\"ten_thuoctinh\":\"SSD\",\"giatri\":\"1TB\"},{\"id_thuoctinh\":\"4\",\"ten_thuoctinh\":\"K\\u00edch th\\u01b0\\u1edbc\",\"giatri\":\"15.6 inch\"},{\"id_thuoctinh\":\"5\",\"ten_thuoctinh\":\"\\u0110\\u1ed9 ph\\u00e2n gi\\u1ea3i\",\"giatri\":\"2K 2560\\u00d71440\"},{\"id_thuoctinh\":\"6\",\"ten_thuoctinh\":\"T\\u1ea5m n\\u1ec1n\",\"giatri\":\"OLED\"},{\"id_thuoctinh\":\"7\",\"ten_thuoctinh\":\"Pin\",\"giatri\":\"100Wh\"},{\"id_thuoctinh\":\"8\",\"ten_thuoctinh\":\"S\\u1ea1c\",\"giatri\":\"140W MagSafe\"}]', 'Laptop ASUS TUF Gaming F16 FX608JHR-RV037W', 'SP-1-GLRO8P', '1', 'uploads/sanpham/a83f0a34f2d3db606a5d335711aa906a.webp', '2.5'),
(30, 3, 1, '[{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\"},{\"id_thuoctinh\":\"3\",\"ten_thuoctinh\":\"GPU\",\"giatri\":\"RTX 4090\"},{\"id_thuoctinh\":\"11\",\"ten_thuoctinh\":\"SSD\",\"giatri\":\"128GB\"},{\"id_thuoctinh\":\"4\",\"ten_thuoctinh\":\"K\\u00edch th\\u01b0\\u1edbc\",\"giatri\":\"15.6 inch\"},{\"id_thuoctinh\":\"5\",\"ten_thuoctinh\":\"\\u0110\\u1ed9 ph\\u00e2n gi\\u1ea3i\",\"giatri\":\"2K 2560\\u00d71440\"},{\"id_thuoctinh\":\"6\",\"ten_thuoctinh\":\"T\\u1ea5m n\\u1ec1n\",\"giatri\":\"Mini-LED\"},{\"id_thuoctinh\":\"7\",\"ten_thuoctinh\":\"Pin\",\"giatri\":\"100Wh\"},{\"id_thuoctinh\":\"8\",\"ten_thuoctinh\":\"S\\u1ea1c\",\"giatri\":\"65W USB-C\"},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"M\\u00e0u s\\u1eafc\",\"giatri\":\"N\\u00e2u\"}]', 'Laptop ASUS Vivobook S16 S3607VA-RP056W', 'SP-1-DRMOZW', '1', 'uploads/sanpham/35559b5146c6711362579ea30180e389.webp', '2.5'),
(34, 10, 8, '[{\"id_thuoctinh\":\"12\",\"ten_thuoctinh\":\"Ki\\u1ec3u k\\u1ebft n\\u1ed1i\",\"giatri\":\"C\\u00f3 d\\u00e2y (USB)\"},{\"id_thuoctinh\":\"13\",\"ten_thuoctinh\":\"DPI\",\"giatri\":\"8000 DPI\"}]', 'Chuột Gaming Logitech G102 LightSync Gen 2', 'SP-8-KQAYA3', '1', 'uploads/sanpham/a1213af2d6ffebdf5af98a5a75f2fc1f.png', '0.5'),
(35, 11, 8, '[{\"id_thuoctinh\":\"12\",\"ten_thuoctinh\":\"Kiểu kết nối\",\"giatri\":\"Không dây (Wireless 2.4GHz)\"},{\"id_thuoctinh\":\"14\",\"ten_thuoctinh\":\"Loại Switch\",\"giatri\":\"Brown Switch (Tactile)\"}]', 'Bàn phím Logitech G Pro X TKL Light Speed', 'SP-8-UUHADM', '1', 'uploads/sanpham/64f80e6cdf1a37be0cf34b5865c5ee79.jpeg', '1.1');

-- --------------------------------------------------------

--
-- Table structure for table `sanpham_daxem`
--

CREATE TABLE `sanpham_daxem` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang` bigint(20) UNSIGNED NOT NULL,
  `id_sanpham` bigint(20) UNSIGNED NOT NULL,
  `xem_luc` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sanpham_daxem`
--

INSERT INTO `sanpham_daxem` (`id`, `id_khachhang`, `id_sanpham`, `xem_luc`, `created_at`, `updated_at`) VALUES
(1, 4, 30, '2026-05-26 08:49:15', '2026-05-24 01:32:41', '2026-05-26 08:49:15'),
(2, 4, 29, '2026-05-26 09:11:26', '2026-05-24 01:34:23', '2026-05-26 09:11:26'),
(3, 4, 28, '2026-05-24 01:39:50', '2026-05-24 01:39:29', '2026-05-24 01:39:50'),
(4, 4, 26, '2026-05-24 01:53:54', '2026-05-24 01:45:14', '2026-05-24 01:53:54'),
(5, 4, 27, '2026-05-26 08:49:37', '2026-05-24 01:51:14', '2026-05-26 08:49:37'),
(6, 15, 27, '2026-05-26 09:20:36', '2026-05-26 09:20:36', '2026-05-26 09:20:36'),
(7, 7, 35, '2026-05-29 07:32:48', '2026-05-29 07:32:40', '2026-05-29 07:32:48'),
(8, 7, 34, '2026-05-29 07:32:53', '2026-05-29 07:32:53', '2026-05-29 07:32:53'),
(9, 12, 34, '2026-06-15 10:01:15', '2026-05-29 07:39:07', '2026-06-15 10:01:15'),
(10, 12, 35, '2026-05-29 08:02:27', '2026-05-29 07:39:29', '2026-05-29 08:02:27'),
(11, 7, 29, '2026-05-30 09:42:29', '2026-05-30 09:00:29', '2026-05-30 09:42:29'),
(12, 12, 29, '2026-06-15 10:22:37', '2026-05-31 09:04:27', '2026-06-15 10:22:37'),
(13, 16, 29, '2026-06-01 04:12:17', '2026-06-01 03:51:24', '2026-06-01 04:12:17'),
(14, 12, 26, '2026-06-11 09:26:36', '2026-06-11 09:26:36', '2026-06-11 09:26:36'),
(15, 12, 27, '2026-06-15 10:01:24', '2026-06-15 10:01:24', '2026-06-15 10:01:24'),
(16, 7, 30, '2026-06-15 10:18:07', '2026-06-15 10:18:07', '2026-06-15 10:18:07');

-- --------------------------------------------------------

--
-- Table structure for table `san_pham_flashsale`
--

CREATE TABLE `san_pham_flashsale` (
  `id_sanpham_flashsale` bigint(20) UNSIGNED NOT NULL,
  `id_danhsach` bigint(20) UNSIGNED NOT NULL,
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `gia_flash_sale` decimal(12,2) NOT NULL,
  `so_luong_gioi_han` int(11) NOT NULL,
  `so_luong_da_ban` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `san_pham_flashsale`
--

INSERT INTO `san_pham_flashsale` (`id_sanpham_flashsale`, `id_danhsach`, `id_bienthe`, `gia_flash_sale`, `so_luong_gioi_han`, `so_luong_da_ban`, `created_at`, `updated_at`) VALUES
(1, 1, 13748, 23800000.00, 5, 0, '2026-06-18 17:24:22', '2026-06-18 17:24:22'),
(2, 1, 13747, 23800000.00, 5, 1, '2026-06-18 17:24:22', '2026-06-18 17:38:22'),
(5, 1, 13708, 25800000.00, 5, 0, '2026-06-18 17:25:24', '2026-06-18 17:25:24'),
(6, 1, 13707, 25800000.00, 5, 1, '2026-06-18 17:25:24', '2026-06-18 17:46:39');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('1mXWQljbyTs97FsfzYfefY2QNt7T0iWAZnOshMsk', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUDFlUThSZWV0QTFVMDd2TDBRSlY5aklBV293V2VWcWtQWlBXNmRrZSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774511002),
('6ydbctNvjvNN7BsDhO0gF8IalVivACxO8uIDleOC', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTlV6SzJRd2lJcGp1Sm1IWE4yZXVrYXJjM2NCbDhub2xQdExMODFhdyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774370707),
('7qgWc9P8iLBF3tJzpIQi2zvQQtrkTh7hhI2zKKWj', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVThjWlBzWDE2RlRlRGJuTjVsektlNEdHQXVZdHpYc1Y1VjZJNjdTbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774373759),
('blerbylxNaGqiE7Lv1TMqNaTZYQbD1K5F3tRmU7Z', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidzg5SDM3QWRFMFpjMnFDUzRGSThtQTJaQzlsSkFzSldHRUhoTXRCUiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1775127067),
('bU0LbyPpzHddBGv8P3rXGBhypjh52HLpaJVMHv0M', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNUdmSG5ianNjS1BBVjZEcm5PMVhLTjlUNkFjb0tWYmMwbmJ5RnNuMyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774460992),
('cQOaEOWEw6flKk7Dl2UHaAhHxmnomAsfpau5xQE6', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMUNzblVWRU5Ud2hnQTJxemgxQUlzUEdoTk9VYjJnazM3S3lMQmJ2biI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1775556397),
('FvuOKjmBZgwYUy9DUIuZIOCj34DJMR5mDbHeDnIc', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMWVwSG9NNTdxeDFRMnNiR3F0MjJrcUQwMUlLcURxZWdJYXFXdjlZeiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774347174),
('hBcaVuoR3fEiSyU7IXNEsQjGqLljnwxHx87I9eXz', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiREJieUhScEpXTUVFdGI0WVFMMDNwV0pCWEdUeDEwWHFNV2JjcXZkQSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1775837114),
('HeTEupeMrUmbZQR1PgWHpOB41iinGdD5otCnRE9J', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZjVvT0tNZ3BtR0FTelI3cFVnQmNSNmxYdFQ3Wm9UMlF4RmtSaEFFSiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774547629),
('ItJ8AJBDGU7XtwlBkxvVZslHO5HhKEtj28cd91Tz', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSkF1NENONjJSWXB5YjZ0Rk5ua2xnYWJYcU9mdkJTbWlHelVWRTR6RCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774487509),
('mz9RZi7ph85v2CpvLBvA1qAwYDSbc5D9mdAjkLPb', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWW9kRmlOUFJMa01naFJvM0hTR1B5RGMyVXpKMHZvNkYzb1B0RFdaUCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774620221),
('yzf1FY4NPie48JcRH6aY74o9ysjxFx7p5j8tZCoa', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZTRnSHk1UmNWSkV5cVVORXVvMWJLQ1J0SnFvTlVwcWt0SlQwMERmNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774521140),
('Z1kh4XRSbN64vQ42tu3FPS1Yk4USM7UPBJiqq2l1', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNGxDaENTMlZOT0FBdVZpWGc5SkR5Um52anVvRVRXdHNjV0xYZm1CRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1774500178);

-- --------------------------------------------------------

--
-- Table structure for table `thuoctinh`
--

CREATE TABLE `thuoctinh` (
  `id_thuoctinh` bigint(20) UNSIGNED NOT NULL,
  `ten_thuoctinh` varchar(255) NOT NULL,
  `id_nhom` bigint(20) UNSIGNED NOT NULL,
  `trangthai` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thuoctinh`
--

INSERT INTO `thuoctinh` (`id_thuoctinh`, `ten_thuoctinh`, `id_nhom`, `trangthai`) VALUES
(1, 'RAM', 1, 1),
(2, 'CPU', 1, 1),
(3, 'GPU', 1, 1),
(4, 'Kích thước', 2, 1),
(5, 'Độ phân giải', 2, 1),
(6, 'Tấm nền', 2, 1),
(7, 'Pin', 3, 1),
(8, 'Sạc', 3, 1),
(11, 'SSD', 1, 1),
(12, 'Kiểu kết nối', 8, 1),
(13, 'DPI', 8, 1),
(14, 'Loại Switch', 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `thuonghieu`
--

CREATE TABLE `thuonghieu` (
  `id_thuonghieu` bigint(20) UNSIGNED NOT NULL,
  `ten_thuonghieu` varchar(255) NOT NULL,
  `danh_muc_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thuonghieu`
--

INSERT INTO `thuonghieu` (`id_thuonghieu`, `ten_thuonghieu`, `danh_muc_ids`, `created_at`, `updated_at`, `logo`) VALUES
(1, 'Asus', '[\"2\",\"3\",\"7\"]', NULL, NULL, 'brands/MGlA4hKsRADKnAkTPmZ6LqUqFan9p1yg32nLbW5y.png'),
(2, 'HP', '[\"2\",\"3\",\"7\"]', NULL, NULL, 'brands/tL8iW6hGMrQGCMcyoyiV5neKVNAfex0diOUV7dc1.png'),
(3, 'Lenovo', '[2,3,7]', NULL, NULL, NULL),
(4, 'MSI', '[2,3]', NULL, NULL, NULL),
(7, 'Apple', '[4,12]', NULL, NULL, NULL),
(8, 'Logitech', '[10,11,12,13]', NULL, NULL, NULL),
(9, 'Razer', '[2,10,11,12,13]', NULL, NULL, NULL),
(10, 'Akko', '[10,11,13]', NULL, NULL, NULL),
(11, 'DareU', '[10,11,12]', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tintuc`
--

CREATE TABLE `tintuc` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tieude` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `danhmuc` varchar(255) NOT NULL DEFAULT 'Tin tức',
  `tacgia` varchar(255) NOT NULL DEFAULT 'Admin',
  `hinhanh` varchar(255) DEFAULT NULL,
  `mota_hinhanh` varchar(255) DEFAULT NULL,
  `trangthai` enum('draft','scheduled','published') NOT NULL DEFAULT 'draft',
  `dang_luc` timestamp NULL DEFAULT NULL,
  `luotxem` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tomtat` text DEFAULT NULL,
  `noidung` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tintuc`
--

INSERT INTO `tintuc` (`id`, `tieude`, `slug`, `danhmuc`, `tacgia`, `hinhanh`, `mota_hinhanh`, `trangthai`, `dang_luc`, `luotxem`, `tomtat`, `noidung`, `created_at`, `updated_at`) VALUES
(2, 'Cách chọn laptop gaming 2026: CPU, GPU, màn hình và tản nhiệt cần ưu tiên', 'cach-chon-laptop-gaming-2026-cpu-gpu-man-hinh-va-tan-nhiet-can-uu-tien', 'Công nghệ', 'VinaTech Editorial', 'https://images.unsplash.com/photo-1593640495253-23196b27a87f?w=1200', 'Laptop gaming 2026 với bàn phím RGB và màn hình hiệu năng cao', 'published', '2026-05-18 00:56:32', 428, 'Hướng dẫn chọn laptop gaming 2026 theo GPU, TGP, CPU, màn hình, RAM và hệ thống tản nhiệt để mua đúng cấu hình, tránh lãng phí.', '## Vì sao không nên chỉ nhìn tên card đồ họa\nKhi chọn laptop gaming, nhiều người chỉ nhìn vào tên GPU như RTX 4050, RTX 4060 hoặc RTX 4070 rồi kết luận máy mạnh hay yếu. Cách đánh giá này chưa đủ chính xác vì cùng một dòng GPU có thể được cấu hình mức điện khác nhau. Thông số TGP càng cao thì GPU càng có nhiều không gian để duy trì hiệu năng, nhưng máy cũng cần hệ thống tản nhiệt đủ tốt.\n\nMột laptop gaming tốt nên cân bằng giữa hiệu năng, nhiệt độ và độ ồn. Nếu máy quá mỏng nhưng dùng GPU mạnh, hiệu năng thực tế có thể bị giới hạn sau vài phút chơi game hoặc render. Vì vậy, hãy xem thêm bài đánh giá nhiệt độ, mức điện GPU và thiết kế khe thoát gió trước khi quyết định.\n\n![Laptop gaming đang chạy game với bàn phím RGB và màn hình tần số quét cao](https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1200)\n\n## CPU phù hợp cho game và công việc\nVới game thủ phổ thông, CPU Intel Core i5/i7 hoặc AMD Ryzen 5/7 thế hệ mới đã đủ cho hầu hết tựa game eSports và AAA. Nếu bạn vừa chơi game vừa livestream, chỉnh video hoặc làm 3D, nên ưu tiên CPU nhiều nhân hơn để hệ thống xử lý đa nhiệm ổn định.\n\nKhông nên dồn toàn bộ ngân sách vào CPU nếu mục tiêu chính là chơi game. Trong phần lớn trường hợp, GPU và màn hình ảnh hưởng trực tiếp đến trải nghiệm hơn. Cấu hình cân bằng sẽ giúp máy dùng bền hơn và ít phải nâng cấp sớm.\n\n## Màn hình quyết định trải nghiệm hằng ngày\nMàn hình laptop gaming nên có tần số quét tối thiểu 144Hz, độ phủ màu tốt và độ sáng đủ dùng. Nếu bạn chơi game bắn súng, tần số quét cao giúp thao tác mượt hơn. Nếu bạn làm thêm thiết kế hoặc chỉnh ảnh, hãy chú ý độ phủ màu sRGB hoặc DCI-P3.\n\n![Màn hình laptop gaming hiển thị hình ảnh sắc nét cho trải nghiệm chơi game](https://images.unsplash.com/photo-1598550476439-6847785fcea6?w=1200)\n\nKích thước 15 đến 16 inch là lựa chọn cân bằng giữa không gian hiển thị và tính di động. Màn hình 17 inch phù hợp người ít di chuyển, còn máy 14 inch gaming hợp với người cần sự gọn nhẹ nhưng phải chấp nhận nhiệt độ cao hơn.\n\n## RAM, SSD và khả năng nâng cấp\nLaptop gaming năm 2026 nên bắt đầu từ 16GB RAM. Nếu bạn chơi game nặng, mở nhiều tab trình duyệt hoặc dùng phần mềm dựng video, 32GB RAM sẽ thoải mái hơn. SSD tối thiểu nên là 512GB, nhưng 1TB sẽ thực tế hơn vì nhiều game hiện nay có dung lượng rất lớn.\n\nTrước khi mua, hãy kiểm tra máy còn khe RAM hoặc khe SSD trống không. Một thiết kế dễ nâng cấp sẽ giúp bạn tiết kiệm chi phí trong dài hạn.\n\n## Kết luận\nLaptop gaming đáng mua không phải là máy có thông số cao nhất trên giấy, mà là máy giữ được hiệu năng ổn định trong thời gian dài. Hãy ưu tiên GPU có TGP rõ ràng, tản nhiệt tốt, màn hình phù hợp và khả năng nâng cấp. Nếu ngân sách có giới hạn, chọn cấu hình cân bằng sẽ an toàn hơn chạy theo một linh kiện quá mạnh nhưng bị bóp hiệu năng.\n\n## Liên kết nội bộ và tham khảo\nBạn có thể xem thêm [trang tin tức công nghệ VinaTech](/news), tham khảo [danh sách laptop gaming và laptop hiệu năng cao](/products) hoặc [liên hệ VinaTech để được tư vấn cấu hình](/contact) trước khi mua.\n\nNguồn tham khảo thêm: [NVIDIA GeForce Laptop GPU](https://www.nvidia.com/en-us/geforce/laptops/) và [Intel hướng dẫn chọn laptop gaming](https://www.intel.com/content/www/us/en/gaming/resources/gaming-laptop-vs-desktop.html).', '2026-05-19 00:35:19', '2026-05-24 19:31:24'),
(3, 'Laptop văn phòng mỏng nhẹ: 7 tiêu chí giúp làm việc bền bỉ cả ngày', 'laptop-van-phong-mong-nhe-7-tieu-chi-giup-lam-viec-ben-bi-ca-ngay', 'Sản phẩm', 'Minh Khôi', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=1200', 'Laptop văn phòng mỏng nhẹ đặt trên bàn làm việc tối giản', 'published', '2026-05-17 00:56:32', 381, 'Danh sách tiêu chí chọn laptop văn phòng mỏng nhẹ: pin, bàn phím, màn hình, cổng kết nối, webcam và độ bền để làm việc hiệu quả.', '## Trọng lượng và thời lượng pin nên đặt lên hàng đầu\nLaptop văn phòng tốt không cần cấu hình quá mạnh, nhưng phải đủ nhẹ để mang theo mỗi ngày và đủ pin để làm việc nhiều giờ. Trọng lượng lý tưởng thường nằm trong khoảng 1 đến 1,4 kg. Máy nhẹ giúp giảm áp lực khi di chuyển, đặc biệt với người thường xuyên gặp khách hàng hoặc làm việc ngoài văn phòng.\n\nThời lượng pin thực tế nên đạt ít nhất 7 đến 9 giờ với tác vụ văn phòng như trình duyệt, email, họp trực tuyến và soạn thảo. Không nên chỉ nhìn thông số pin do hãng công bố vì điều kiện thử nghiệm thường nhẹ hơn thực tế.\n\n![Laptop văn phòng mỏng nhẹ phục vụ làm việc di động cả ngày](https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=1200)\n\n## Bàn phím và touchpad ảnh hưởng trực tiếp đến năng suất\nVới người nhập liệu nhiều, bàn phím quan trọng không kém CPU. Hành trình phím vừa đủ, layout rõ ràng và đèn nền sẽ giúp làm việc thoải mái hơn. Touchpad nên rộng, bề mặt mượt và nhận diện thao tác chính xác để bạn không phải luôn mang chuột rời.\n\nNếu có thể, hãy gõ thử trước khi mua. Một chiếc laptop cấu hình cao nhưng bàn phím khó dùng sẽ gây mệt mỏi sau vài giờ làm việc.\n\n## Màn hình cần dễ nhìn, không chỉ sắc nét\nĐộ phân giải Full HD vẫn đủ cho hầu hết công việc văn phòng, nhưng tấm nền IPS hoặc OLED chất lượng tốt sẽ giúp mắt dễ chịu hơn. Độ sáng nên từ 300 nits trở lên nếu bạn thường làm việc ở quán cà phê hoặc khu vực nhiều ánh sáng.\n\nMàn hình tỷ lệ 16:10 là điểm cộng vì hiển thị được nhiều dòng nội dung hơn khi đọc tài liệu, làm bảng tính hoặc viết báo cáo.\n\n![Không gian làm việc văn phòng với laptop, sổ ghi chú và ánh sáng tự nhiên](https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=1200)\n\n## Cổng kết nối và webcam không nên bị bỏ qua\nMột laptop văn phòng thực dụng nên có USB-C, USB-A, HDMI hoặc ít nhất hỗ trợ xuất hình qua USB-C. Nếu công việc thường xuyên họp online, webcam rõ, micro lọc ồn và loa đủ nghe sẽ tạo khác biệt lớn.\n\nNhững yếu tố này ít được chú ý khi xem cấu hình, nhưng lại ảnh hưởng trực tiếp đến trải nghiệm hằng ngày.\n\n## Cấu hình khuyến nghị\nVới nhu cầu văn phòng, CPU Core i5 hoặc Ryzen 5 đời mới, RAM 16GB và SSD 512GB là mức hợp lý. Nếu bạn làm dữ liệu nặng, mở nhiều file Excel hoặc chạy phần mềm kế toán, RAM 16GB nên là tiêu chuẩn tối thiểu.\n\n## Kết luận\nLaptop văn phòng tốt là thiết bị giúp bạn làm việc ổn định, nhẹ nhàng và ít bị gián đoạn. Hãy ưu tiên pin, bàn phím, màn hình, webcam và độ bền trước khi chạy theo cấu hình quá cao. Một lựa chọn cân bằng sẽ đem lại hiệu quả tốt hơn trong nhiều năm sử dụng.\n\n## Liên kết nội bộ và tham khảo\nNếu bạn đang cân nhắc mua máy, hãy xem [các mẫu laptop văn phòng tại VinaTech](/products), đọc thêm [tin tức và kinh nghiệm chọn laptop](/news) hoặc [gửi yêu cầu tư vấn laptop phù hợp](/contact).\n\nNguồn tham khảo thêm: [Microsoft hướng dẫn tối ưu pin Windows](https://support.microsoft.com/windows/caring-for-your-battery-in-windows-2db3e37f-5e7d-488e-9086-ed15320519e4) và [Intel Evo laptop experience](https://www.intel.com/content/www/us/en/products/systems-devices/laptops/evo.html).', '2026-05-19 00:35:19', '2026-05-21 21:48:54'),
(4, 'Laptop AI là gì? Khi nào nên mua laptop có NPU riêng', 'laptop-ai-la-gi-khi-nao-nen-mua-laptop-co-npu-rieng', 'Công nghệ', 'Hoàng Nam', 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200', 'Laptop AI xử lý tác vụ thông minh với giao diện công nghệ', 'published', '2026-05-16 00:56:32', 514, 'Giải thích laptop AI, vai trò của NPU, lợi ích khi xử lý tác vụ thông minh và nhóm người dùng nên mua laptop AI trong năm 2026.', '## Laptop AI khác gì laptop thông thường\nLaptop AI là cách gọi các mẫu laptop được tối ưu cho tác vụ trí tuệ nhân tạo, thường có thêm NPU. NPU là bộ xử lý chuyên dụng cho các tác vụ AI nhẹ và liên tục như lọc ồn, làm mờ nền, nhận diện hình ảnh, tóm tắt nội dung hoặc hỗ trợ trợ lý thông minh.\n\nKhác với CPU và GPU, NPU được thiết kế để xử lý các tác vụ AI với mức điện thấp hơn. Điều này giúp máy tiết kiệm pin hơn khi chạy các tính năng thông minh trong thời gian dài.\n\n![Chip xử lý AI và bo mạch laptop minh họa cho NPU trên laptop AI](https://images.unsplash.com/photo-1518779578993-ec3579fee39f?w=1200)\n\n## NPU có thay thế CPU và GPU không\nNPU không thay thế CPU hoặc GPU. CPU vẫn xử lý tác vụ hệ thống và ứng dụng thông thường. GPU vẫn quan trọng với game, render, dựng video và các mô hình AI lớn. NPU phù hợp với tác vụ AI cục bộ vừa và nhỏ, đặc biệt là những tính năng chạy nền.\n\nNếu bạn dùng laptop để chơi game hoặc dựng 3D nặng, GPU vẫn là yếu tố cần ưu tiên. Nếu bạn làm việc văn phòng, họp online, xử lý tài liệu và muốn tận dụng các tính năng AI mới, NPU là điểm cộng đáng giá.\n\n## Ai nên mua laptop AI\nNgười thường xuyên họp trực tuyến sẽ thấy lợi ích rõ qua tính năng khử ồn, chỉnh ánh mắt, làm mờ nền và tối ưu webcam. Người làm nội dung có thể dùng AI để tóm tắt, gợi ý văn bản hoặc phân loại hình ảnh nhanh hơn.\n\n![Người dùng laptop AI trong cuộc họp trực tuyến với webcam và khử ồn](https://images.unsplash.com/photo-1553877522-43269d4ea984?w=1200)\n\nDoanh nghiệp cũng có lý do để quan tâm laptop AI vì nhiều tác vụ có thể xử lý cục bộ trên máy, giảm phụ thuộc vào cloud và cải thiện quyền riêng tư.\n\n## Khi nào chưa cần laptop AI\nNếu bạn chỉ dùng máy để học online, lướt web, soạn thảo nhẹ và ngân sách hạn chế, laptop chưa có NPU vẫn đáp ứng tốt. AI là xu hướng quan trọng, nhưng không phải mọi người dùng đều cần nâng cấp ngay.\n\nƯu tiên vẫn là cấu hình tổng thể: CPU đủ mới, RAM từ 16GB, SSD nhanh, màn hình tốt và pin ổn định. NPU nên được xem là một lợi thế cộng thêm, không phải tiêu chí duy nhất.\n\n## Kết luận\nLaptop AI đáng mua khi bạn cần các tính năng thông minh chạy ổn định, tiết kiệm pin và riêng tư hơn. Tuy nhiên, hãy chọn máy dựa trên nhu cầu thực tế. Một chiếc laptop cân bằng giữa CPU, RAM, màn hình, pin và NPU sẽ có giá trị sử dụng lâu dài hơn.\n\n## Liên kết nội bộ và tham khảo\nBạn có thể theo dõi thêm [chuyên mục tin tức công nghệ VinaTech](/news), xem [các mẫu laptop thế hệ mới](/products) hoặc [liên hệ tư vấn laptop AI](/contact) nếu cần cấu hình phù hợp.\n\nNguồn tham khảo thêm: [Microsoft Copilot+ PC](https://www.microsoft.com/windows/copilot-plus-pcs) và [Intel AI PC](https://www.intel.com/content/www/us/en/products/docs/processors/core-ultra/ai-pc.html).', '2026-05-19 00:35:19', '2026-05-19 02:20:23'),
(5, 'Cách chọn laptop đồ họa cho designer: màn hình, RAM và GPU cần biết', 'cach-chon-laptop-do-hoa-cho-designer-man-hinh-ram-va-gpu-can-biet', 'Sản phẩm', 'Thanh Hồng', 'https://images.unsplash.com/photo-1492724441997-5dc865305da7?w=1200', 'Designer làm việc trên laptop đồ họa với màn hình màu sắc chính xác', 'published', '2026-05-15 00:56:32', 298, 'Tư vấn chọn laptop đồ họa cho designer theo màn hình, độ phủ màu, RAM, GPU, SSD và khả năng tản nhiệt để làm việc ổn định.', '## Màn hình là yếu tố quan trọng nhất với designer\nVới designer, màn hình không chỉ cần đẹp mà còn phải chính xác. Hãy ưu tiên laptop có tấm nền IPS hoặc OLED chất lượng tốt, độ phủ màu cao và khả năng hiển thị ổn định ở nhiều góc nhìn. Nếu làm thiết kế in ấn, độ chính xác màu càng quan trọng.\n\nĐộ phân giải Full HD có thể đủ với người mới bắt đầu, nhưng màn hình 2K hoặc 3K sẽ cho không gian làm việc rộng hơn. Tỷ lệ 16:10 cũng hữu ích khi thao tác với timeline, layer và thanh công cụ.\n\n![Designer chỉnh màu trên laptop đồ họa với màn hình độ phủ màu cao](https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1200)\n\n## RAM và SSD ảnh hưởng đến tốc độ làm việc\nCác phần mềm như Photoshop, Illustrator, Lightroom, Premiere hoặc Figma có thể dùng nhiều RAM khi mở file lớn. RAM 16GB là mức khởi điểm hợp lý, còn 32GB phù hợp hơn nếu bạn làm video, ảnh RAW hoặc nhiều dự án cùng lúc.\n\nSSD nên từ 512GB, tốt hơn là 1TB. File thiết kế, ảnh gốc và video thường chiếm dung lượng rất nhanh. SSD nhanh giúp mở project, xuất file và cache mượt hơn.\n\n![Bàn làm việc sáng tạo với laptop, bảng vẽ và phụ kiện thiết kế](https://images.unsplash.com/photo-1518005020951-eccb494ad742?w=1200)\n\n## GPU có cần quá mạnh không\nNếu bạn chủ yếu làm thiết kế 2D, chỉnh ảnh và UI, GPU rời tầm trung đã đủ. Nếu làm dựng video, motion graphic, 3D hoặc AI hình ảnh, GPU mạnh sẽ giúp tiết kiệm nhiều thời gian. Tuy nhiên, không nên chọn GPU mạnh nhưng máy tản nhiệt yếu.\n\nMột laptop đồ họa tốt phải giữ được hiệu năng ổn định trong phiên làm việc dài. Vì vậy, hãy xem đánh giá nhiệt độ và tiếng ồn thay vì chỉ nhìn thông số.\n\n## Bàn phím, cổng kết nối và độ bền\nDesigner thường dùng màn hình ngoài, bảng vẽ, ổ cứng rời và phụ kiện. Laptop nên có đủ USB-C, USB-A, HDMI hoặc hỗ trợ dock tốt. Bàn phím chắc, touchpad rộng và vỏ máy cứng cáp cũng giúp trải nghiệm làm việc chuyên nghiệp hơn.\n\n## Kết luận\nLaptop đồ họa nên được chọn theo màn hình, RAM, SSD và khả năng tản nhiệt trước khi nhìn đến thiết kế bên ngoài. Một chiếc máy hiển thị màu tốt, chạy ổn định và dễ kết nối phụ kiện sẽ giúp designer làm việc hiệu quả hơn nhiều so với một cấu hình lệch trọng tâm.\n\n## Liên kết nội bộ và tham khảo\nDesigner có thể xem thêm [laptop đồ họa và laptop creator tại VinaTech](/products), đọc [các bài tư vấn laptop mới nhất](/news) hoặc [liên hệ để được tư vấn cấu hình thiết kế](/contact).\n\nNguồn tham khảo thêm: [Adobe khuyến nghị cấu hình Photoshop](https://helpx.adobe.com/photoshop/system-requirements.html) và [NVIDIA Studio laptops](https://www.nvidia.com/en-us/studio/laptops-desktops/).', '2026-05-19 00:35:19', '2026-05-21 01:53:04'),
(6, 'Bảo quản pin laptop đúng cách: sạc, nhiệt độ và thói quen sử dụng', 'bao-quan-pin-laptop-dung-cach-sac-nhiet-do-va-thoi-quen-su-dung', 'Công nghệ', 'VinaTech Editorial', 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=1200', 'Laptop đang sạc pin trên bàn làm việc gọn gàng', 'published', '2026-05-14 00:56:32', 610, 'Các thói quen bảo quản pin laptop giúp giảm chai pin: cách sạc, kiểm soát nhiệt độ, dùng chế độ giới hạn sạc và vệ sinh máy.', '## Pin laptop chai vì những nguyên nhân nào\nPin laptop xuống cấp theo thời gian là điều bình thường, nhưng thói quen sử dụng có thể làm quá trình này nhanh hoặc chậm hơn. Nhiệt độ cao, vừa sạc vừa dùng tác vụ nặng trong thời gian dài, để pin cạn liên tục hoặc luôn giữ pin ở 100% đều có thể làm pin giảm tuổi thọ nhanh hơn.\n\nĐiều quan trọng là giữ máy trong điều kiện nhiệt độ ổn định và tránh các chu kỳ sạc xả cực đoan. Pin lithium-ion hoạt động tốt nhất khi không bị nóng quá lâu.\n\n![Laptop đang được sạc đúng cách với bộ sạc đặt trên bàn làm việc](https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=1200)\n\n## Có nên cắm sạc laptop liên tục không\nVới laptop hiện đại, cắm sạc liên tục không còn nguy hiểm như trước vì máy có mạch quản lý pin. Tuy nhiên, nếu máy luôn ở mức 100% và nhiệt độ cao, pin vẫn có thể xuống cấp nhanh hơn. Nhiều hãng cung cấp chế độ giới hạn sạc ở 60 đến 80%, rất phù hợp khi bạn dùng máy cố định tại bàn.\n\nNếu thường xuyên di chuyển, bạn có thể sạc đầy trước khi ra ngoài. Nếu chủ yếu dùng ở văn phòng, hãy bật chế độ bảo vệ pin nếu máy hỗ trợ.\n\n## Nhiệt độ là kẻ thù lớn nhất của pin\nPin không thích nhiệt. Khi chơi game, render video hoặc chạy phần mềm nặng, nhiệt độ trong máy tăng cao và ảnh hưởng đến pin. Hãy đặt laptop trên bề mặt thoáng, tránh dùng trên chăn, nệm hoặc nơi bị bịt khe gió.\n\nVệ sinh quạt, thay keo tản nhiệt định kỳ và dùng đế kê hợp lý cũng giúp giảm nhiệt. Một hệ thống mát hơn không chỉ tốt cho pin mà còn giúp CPU và GPU giữ hiệu năng ổn định.\n\n![Khe tản nhiệt laptop giúp kiểm soát nhiệt độ và bảo vệ pin](https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=1200)\n\n## Thói quen sạc xả nên áp dụng\nBạn không cần xả pin về 0% thường xuyên. Ngược lại, để pin cạn kiệt nhiều lần có thể không tốt. Mức sử dụng an toàn thường là sạc khi pin còn khoảng 20% và rút hoặc giới hạn khi đạt 80 đến 90% nếu không cần dùng pin tối đa.\n\nHãy kiểm tra sức khỏe pin định kỳ bằng công cụ của hệ điều hành hoặc phần mềm từ hãng. Khi pin xuống cấp rõ rệt, thay pin chính hãng sẽ an toàn hơn dùng pin kém chất lượng.\n\n## Kết luận\nBảo quản pin laptop không phức tạp, nhưng cần thói quen nhất quán. Hãy kiểm soát nhiệt độ, tránh để pin cạn thường xuyên, bật giới hạn sạc khi dùng cố định và vệ sinh máy định kỳ. Những việc nhỏ này giúp laptop bền hơn và giữ trải nghiệm di động tốt hơn.\n\n## Liên kết nội bộ và tham khảo\nBạn có thể xem [các bài hướng dẫn sử dụng laptop trên VinaTech](/news), tham khảo [laptop pin tốt đang bán](/products) hoặc [liên hệ VinaTech để kiểm tra và tư vấn bảo dưỡng](/contact).\n\nNguồn tham khảo thêm: [Microsoft chăm sóc pin trong Windows](https://support.microsoft.com/windows/caring-for-your-battery-in-windows-2db3e37f-5e7d-488e-9086-ed15320519e4) và [Battery University về pin lithium-ion](https://batteryuniversity.com/article/bu-808-how-to-prolong-lithium-based-batteries).', '2026-05-19 00:35:19', '2026-05-19 00:56:32'),
(7, 'claude model AI mạnh nhất hiện tại', 'claude-model-ai-manh-nhat-hien-tai', 'Công nghệ', 'Admin', 'uploads/news/d9eb2a0434528840a6c05d4e8fdf0aff.png', 'Ảnh đại diện công nghệ về claude model AI mạnh nhất', 'published', '2026-04-25 10:00:00', 2, 'ảgasasdSD', 'èawef', '2026-05-19 12:05:16', '2026-05-21 23:00:00'),
(8, 'web bán giày có 1 0 2trên thế giới', 'web-ban-giay-co-1-0-2tren-the-gioi', 'Công nghệ', 'Admin', 'uploads/news/ef22eaf944fa85bd9a4776bc2baa4b12.png', 'Ảnh đại diện công nghệ về web giày có 1 0', 'published', '2026-04-25 10:00:00', 0, 'ừefeewfwe', 'ewfƯEFưegwf\n\n![Ảnh minh họa công nghệ về web giày có 1 0 trong bài web bán giày có 1 0 2trên thế giới](uploads/news/content/d9eb2a0434528840a6c05d4e8fdf0aff.png)', '2026-05-19 12:12:51', '2026-05-21 22:20:42'),
(9, 'vdsjhvdsjvhds', 'vdsjhvdsjvhds', 'Sản phẩm', 'Trần Quốc Phong', 'uploads/news/f8d00476f62175a0d573305430ce8238.jpeg', 'Ảnh đại diện sản phẩm về vdsjhvdsjvhds', 'published', '2026-05-24 20:35:44', 1, 'dsjvdsvdsjvds', 'bjvdsjvdsjcv dsbv dsbvdsvdvsdsv\n\n![Ảnh minh họa sản phẩm về vdsjhvdsjvhds trong bài vdsjhvdsjvhds](uploads/news/content/7f0b807c5e116a7247cc9e1b6c5a1049.webp)\n\nsdvdsbvdsvds', '2026-05-24 20:35:44', '2026-05-24 20:36:06');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `ten` varchar(255) DEFAULT NULL,
  `danhmuc` varchar(50) NOT NULL DEFAULT 'product',
  `code` varchar(50) DEFAULT NULL,
  `loai` varchar(20) DEFAULT NULL,
  `giatri` int(11) DEFAULT NULL,
  `mota` longtext NOT NULL,
  `ngaybatdau` date DEFAULT NULL,
  `ngayketthuc` date DEFAULT NULL,
  `trangthai` varchar(20) DEFAULT NULL,
  `congkhai` tinyint(1) NOT NULL DEFAULT 1,
  `dieu_kien_tang` decimal(15,2) DEFAULT NULL,
  `so_luong_phat` int(11) DEFAULT NULL,
  `loai_dieu_kien` varchar(50) DEFAULT NULL,
  `dieu_kien` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `ten`, `danhmuc`, `code`, `loai`, `giatri`, `mota`, `ngaybatdau`, `ngayketthuc`, `trangthai`, `congkhai`, `dieu_kien_tang`, `so_luong_phat`, `loai_dieu_kien`, `dieu_kien`) VALUES
(23, 'HAPPYBIRTHDAY', 'birthday', 'BIRTHDAY', 'fixed', 5000000, 'Chúc bạn sinh nhật vui vẻ', NULL, NULL, 'open', 1, NULL, NULL, NULL, NULL),
(26, 'miễn phí 20%', 'product', 'DSY9U56K', 'percent', 20, 'xcvbnm', '2026-06-01', '2026-06-21', 'running', 1, NULL, NULL, '>=', 20000000.00),
(27, 'miễn phí', 'freeship', '1TJFX1LW', 'percent', 100, 'sdfgbh', '2026-06-01', '2026-06-21', 'running', 1, NULL, NULL, NULL, 30000000.00),
(28, 'Giảm giá  2.000.000đ', 'product', 'RH036HFO', 'fixed', 2000000, 'Giảm giá 2.000.000đ cho những đơn hàng có giá trên 25.000.000đ', '2026-06-08', '2026-06-14', 'running', 0, 30000000.00, 10, '>=', 25000000.00),
(29, 'giảm giá', 'product', 'GIAM-GIA-9653', 'percent', 20, 'fghjk', '2026-06-09', '2026-06-30', 'running', 1, NULL, NULL, '>=', 30000000.00),
(31, 'giảm giá 20%', 'product', 'X5RFO4JA', 'percent', 20, 'fvgbhnjm,', '2026-06-09', '2026-06-30', 'running', 1, NULL, NULL, '>=', 30000000.00),
(32, 'Sinh Nhß║¡t V├áng VinaTech', 'birthday', 'HAPPYBDAY100', 'fixed', 100000, 'M├ú giß║úm gi├í qu├á tß║Àng sinh nhß║¡t kh├ích h├áng th├ón thiß║┐t cß╗ºa Predator Group.', NULL, NULL, 'open', 1, NULL, NULL, NULL, NULL),
(33, 'Giảm giá 20%', 'product', 'OAVPEAAL', 'maxprice', 20, 'fbvdfhbvdkbvdfkbvdkjbvdsjlvds', '2026-06-15', '2026-06-21', 'running', 0, 35000000.00, 10, '>=', 35000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `yeuthich`
--

CREATE TABLE `yeuthich` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_khachhang` bigint(20) UNSIGNED NOT NULL,
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `soluong` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `yeuthich`
--

INSERT INTO `yeuthich` (`id`, `id_khachhang`, `id_bienthe`, `soluong`, `created_at`, `updated_at`) VALUES
(14, 12, 13709, 1, '2026-05-20 09:53:34', '2026-05-20 09:53:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `affiliate_gioi_thieu`
--
ALTER TABLE `affiliate_gioi_thieu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `affiliate_referrals_affiliate_user_id_foreign` (`id_affiliate_khachhang`),
  ADD KEY `affiliate_referrals_referred_user_id_foreign` (`id_khachhang_duoc_gioithieu`);

--
-- Indexes for table `affiliate_yeu_cau_rut_tien`
--
ALTER TABLE `affiliate_yeu_cau_rut_tien`
  ADD PRIMARY KEY (`id`),
  ADD KEY `affiliate_withdraw_requests_affiliate_user_id_foreign` (`id_affiliate_khachhang`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bienthe`
--
ALTER TABLE `bienthe`
  ADD PRIMARY KEY (`id_bienthe`),
  ADD KEY `bienthe_id_sanpham_foreign` (`id_sanpham`);

--
-- Indexes for table `bienthe_combo_offers`
--
ALTER TABLE `bienthe_combo_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bienthe_combo_offers_id_bienthe_foreign` (`id_bienthe`),
  ADD KEY `bienthe_combo_offers_id_combo_foreign` (`id_combo`);

--
-- Indexes for table `bienthe_hinhanh`
--
ALTER TABLE `bienthe_hinhanh`
  ADD PRIMARY KEY (`id_bienthe_hinhanh`),
  ADD KEY `idx_bienthe_hinhanh_id_sanpham` (`id_sanpham`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `cai_dat_ma_sinh_nhat`
--
ALTER TABLE `cai_dat_ma_sinh_nhat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `birthday_coupon_settings_promotion_id_foreign` (`id_voucher`);

--
-- Indexes for table `combos`
--
ALTER TABLE `combos`
  ADD PRIMARY KEY (`id_combo`);

--
-- Indexes for table `combo_sanpham`
--
ALTER TABLE `combo_sanpham`
  ADD PRIMARY KEY (`id`),
  ADD KEY `combo_sanpham_id_combo_foreign` (`id_combo`),
  ADD KEY `combo_sanpham_id_sanpham_foreign` (`id_sanpham`);

--
-- Indexes for table `cuoc_tro_chuyen`
--
ALTER TABLE `cuoc_tro_chuyen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_conversations_user_id_foreign` (`id_khachhang`);

--
-- Indexes for table `danhgia`
--
ALTER TABLE `danhgia`
  ADD PRIMARY KEY (`id_danhgia`),
  ADD UNIQUE KEY `unique_review_per_item` (`id_dathang`,`id_bienthe`,`user_id`),
  ADD KEY `danhgia_id_bienthe_foreign` (`id_bienthe`),
  ADD KEY `danhgia_user_id_foreign` (`user_id`);

--
-- Indexes for table `danhmuc`
--
ALTER TABLE `danhmuc`
  ADD PRIMARY KEY (`id_danhmuc`),
  ADD KEY `danhmuc_id_danhmuc_cha_foreign` (`id_danhmuc_cha`);

--
-- Indexes for table `danhmuc_cha`
--
ALTER TABLE `danhmuc_cha`
  ADD PRIMARY KEY (`id_danhmuc_cha`),
  ADD UNIQUE KEY `danhmuc_cha_ten_danhmuc_unique` (`ten_danhmuc`);

--
-- Indexes for table `danh_sach_flashsale`
--
ALTER TABLE `danh_sach_flashsale`
  ADD PRIMARY KEY (`id_session`);

--
-- Indexes for table `dathang`
--
ALTER TABLE `dathang`
  ADD PRIMARY KEY (`id_dathang`),
  ADD KEY `dathang_user_id_foreign` (`id_khachhang`);

--
-- Indexes for table `dathang_chitiet`
--
ALTER TABLE `dathang_chitiet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dathang_chitiet_id_dathang_foreign` (`id_dathang`),
  ADD KEY `dathang_chitiet_id_bienthe_foreign` (`id_bienthe`),
  ADD KEY `dathang_chitiet_id_combo_foreign` (`id_combo`);

--
-- Indexes for table `diachi`
--
ALTER TABLE `diachi`
  ADD PRIMARY KEY (`id_diachi`),
  ADD KEY `diachi_id_user_mac_dinh_index` (`id_user`,`mac_dinh`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `giatri_thuoctinh`
--
ALTER TABLE `giatri_thuoctinh`
  ADD PRIMARY KEY (`id_giatri`),
  ADD KEY `giatri_thuoctinh_id_thuoctinh_foreign` (`id_thuoctinh`);

--
-- Indexes for table `giohang`
--
ALTER TABLE `giohang`
  ADD PRIMARY KEY (`id_giohang`),
  ADD KEY `giohang_id_bienthe_foreign` (`id_bienthe`),
  ADD KEY `giohang_id_combo_foreign` (`id_combo`),
  ADD KEY `giohang_user_id_index` (`id_khachhang`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `khachhang`
--
ALTER TABLE `khachhang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_api_token_unique` (`api_token`);

--
-- Indexes for table `khachhang_voucher`
--
ALTER TABLE `khachhang_voucher`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_promotion` (`id_voucher`);

--
-- Indexes for table `khach_hang_affiliate`
--
ALTER TABLE `khach_hang_affiliate`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `affiliate_profiles_affiliate_code_unique` (`ma_affiliate`),
  ADD KEY `affiliate_profiles_user_id_foreign` (`id_khachhang`);

--
-- Indexes for table `lienhe`
--
ALTER TABLE `lienhe`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lien_ket_affiliate`
--
ALTER TABLE `lien_ket_affiliate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mausac`
--
ALTER TABLE `mausac`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `colors_name_unique` (`ten`),
  ADD UNIQUE KEY `colors_hex_code_unique` (`mamau`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nhat_ky_admin`
--
ALTER TABLE `nhat_ky_admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_activity_logs_user_id_foreign` (`id_khachhang`);

--
-- Indexes for table `nhat_ky_gui_ma_sinh_nhat`
--
ALTER TABLE `nhat_ky_gui_ma_sinh_nhat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `birthday_coupon_logs_user_id_foreign` (`id_khachhang`),
  ADD KEY `birthday_coupon_logs_promotion_id_foreign` (`id_voucher`),
  ADD KEY `birthday_coupon_logs_user_voucher_id_foreign` (`id_khachhang_voucher`);

--
-- Indexes for table `nhom_thuoctinh`
--
ALTER TABLE `nhom_thuoctinh`
  ADD PRIMARY KEY (`id_nhom`);

--
-- Indexes for table `noi_dung_tro_chuyen`
--
ALTER TABLE `noi_dung_tro_chuyen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_messages_conversation_id_foreign` (`id_cuoc_tro_chuyen`),
  ADD KEY `chat_messages_sender_id_foreign` (`id_nguoigui`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`id_sanpham`),
  ADD KEY `sanpham_id_danhmuc_foreign` (`id_danhmuc`),
  ADD KEY `sanpham_id_thuonghieu_foreign` (`id_thuonghieu`);

--
-- Indexes for table `sanpham_daxem`
--
ALTER TABLE `sanpham_daxem`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sanpham_daxem_id_user_id_sanpham_unique` (`id_khachhang`,`id_sanpham`),
  ADD KEY `sanpham_daxem_id_sanpham_foreign` (`id_sanpham`);

--
-- Indexes for table `san_pham_flashsale`
--
ALTER TABLE `san_pham_flashsale`
  ADD PRIMARY KEY (`id_sanpham_flashsale`),
  ADD KEY `flash_sale_products_session_id_foreign` (`id_danhsach`),
  ADD KEY `flash_sale_products_id_bienthe_foreign` (`id_bienthe`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `thuoctinh`
--
ALTER TABLE `thuoctinh`
  ADD PRIMARY KEY (`id_thuoctinh`),
  ADD KEY `thuoctinh_id_nhom_foreign` (`id_nhom`);

--
-- Indexes for table `thuonghieu`
--
ALTER TABLE `thuonghieu`
  ADD PRIMARY KEY (`id_thuonghieu`);

--
-- Indexes for table `tintuc`
--
ALTER TABLE `tintuc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `news_slug_unique` (`slug`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `yeuthich`
--
ALTER TABLE `yeuthich`
  ADD PRIMARY KEY (`id`),
  ADD KEY `yeuthich_user_id_foreign` (`id_khachhang`),
  ADD KEY `yeuthich_id_bienthe_foreign` (`id_bienthe`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `affiliate_gioi_thieu`
--
ALTER TABLE `affiliate_gioi_thieu`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `affiliate_yeu_cau_rut_tien`
--
ALTER TABLE `affiliate_yeu_cau_rut_tien`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bienthe`
--
ALTER TABLE `bienthe`
  MODIFY `id_bienthe` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13761;

--
-- AUTO_INCREMENT for table `bienthe_combo_offers`
--
ALTER TABLE `bienthe_combo_offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bienthe_hinhanh`
--
ALTER TABLE `bienthe_hinhanh`
  MODIFY `id_bienthe_hinhanh` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=334;

--
-- AUTO_INCREMENT for table `cai_dat_ma_sinh_nhat`
--
ALTER TABLE `cai_dat_ma_sinh_nhat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `combos`
--
ALTER TABLE `combos`
  MODIFY `id_combo` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `combo_sanpham`
--
ALTER TABLE `combo_sanpham`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cuoc_tro_chuyen`
--
ALTER TABLE `cuoc_tro_chuyen`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `danhgia`
--
ALTER TABLE `danhgia`
  MODIFY `id_danhgia` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `danhmuc`
--
ALTER TABLE `danhmuc`
  MODIFY `id_danhmuc` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `danhmuc_cha`
--
ALTER TABLE `danhmuc_cha`
  MODIFY `id_danhmuc_cha` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `danh_sach_flashsale`
--
ALTER TABLE `danh_sach_flashsale`
  MODIFY `id_session` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dathang`
--
ALTER TABLE `dathang`
  MODIFY `id_dathang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `dathang_chitiet`
--
ALTER TABLE `dathang_chitiet`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `diachi`
--
ALTER TABLE `diachi`
  MODIFY `id_diachi` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `giatri_thuoctinh`
--
ALTER TABLE `giatri_thuoctinh`
  MODIFY `id_giatri` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `giohang`
--
ALTER TABLE `giohang`
  MODIFY `id_giohang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `khachhang`
--
ALTER TABLE `khachhang`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `khachhang_voucher`
--
ALTER TABLE `khachhang_voucher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `khach_hang_affiliate`
--
ALTER TABLE `khach_hang_affiliate`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lienhe`
--
ALTER TABLE `lienhe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lien_ket_affiliate`
--
ALTER TABLE `lien_ket_affiliate`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mausac`
--
ALTER TABLE `mausac`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `nhat_ky_admin`
--
ALTER TABLE `nhat_ky_admin`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `nhat_ky_gui_ma_sinh_nhat`
--
ALTER TABLE `nhat_ky_gui_ma_sinh_nhat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nhom_thuoctinh`
--
ALTER TABLE `nhom_thuoctinh`
  MODIFY `id_nhom` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `noi_dung_tro_chuyen`
--
ALTER TABLE `noi_dung_tro_chuyen`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `id_sanpham` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `sanpham_daxem`
--
ALTER TABLE `sanpham_daxem`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `san_pham_flashsale`
--
ALTER TABLE `san_pham_flashsale`
  MODIFY `id_sanpham_flashsale` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `thuoctinh`
--
ALTER TABLE `thuoctinh`
  MODIFY `id_thuoctinh` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `thuonghieu`
--
ALTER TABLE `thuonghieu`
  MODIFY `id_thuonghieu` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tintuc`
--
ALTER TABLE `tintuc`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `yeuthich`
--
ALTER TABLE `yeuthich`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `affiliate_gioi_thieu`
--
ALTER TABLE `affiliate_gioi_thieu`
  ADD CONSTRAINT `affiliate_referrals_affiliate_user_id_foreign` FOREIGN KEY (`id_affiliate_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `affiliate_referrals_referred_user_id_foreign` FOREIGN KEY (`id_khachhang_duoc_gioithieu`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `affiliate_yeu_cau_rut_tien`
--
ALTER TABLE `affiliate_yeu_cau_rut_tien`
  ADD CONSTRAINT `affiliate_withdraw_requests_affiliate_user_id_foreign` FOREIGN KEY (`id_affiliate_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bienthe`
--
ALTER TABLE `bienthe`
  ADD CONSTRAINT `bienthe_id_sanpham_foreign` FOREIGN KEY (`id_sanpham`) REFERENCES `sanpham` (`id_sanpham`) ON DELETE CASCADE;

--
-- Constraints for table `bienthe_combo_offers`
--
ALTER TABLE `bienthe_combo_offers`
  ADD CONSTRAINT `bienthe_combo_offers_id_bienthe_foreign` FOREIGN KEY (`id_bienthe`) REFERENCES `bienthe` (`id_bienthe`) ON DELETE CASCADE,
  ADD CONSTRAINT `bienthe_combo_offers_id_combo_foreign` FOREIGN KEY (`id_combo`) REFERENCES `combos` (`id_combo`) ON DELETE CASCADE;

--
-- Constraints for table `bienthe_hinhanh`
--
ALTER TABLE `bienthe_hinhanh`
  ADD CONSTRAINT `fk_bienthe_hinhanh_sanpham` FOREIGN KEY (`id_sanpham`) REFERENCES `sanpham` (`id_sanpham`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cai_dat_ma_sinh_nhat`
--
ALTER TABLE `cai_dat_ma_sinh_nhat`
  ADD CONSTRAINT `birthday_coupon_settings_promotion_id_foreign` FOREIGN KEY (`id_voucher`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `combo_sanpham`
--
ALTER TABLE `combo_sanpham`
  ADD CONSTRAINT `combo_sanpham_id_combo_foreign` FOREIGN KEY (`id_combo`) REFERENCES `combos` (`id_combo`) ON DELETE CASCADE,
  ADD CONSTRAINT `combo_sanpham_id_sanpham_foreign` FOREIGN KEY (`id_sanpham`) REFERENCES `sanpham` (`id_sanpham`) ON DELETE CASCADE;

--
-- Constraints for table `cuoc_tro_chuyen`
--
ALTER TABLE `cuoc_tro_chuyen`
  ADD CONSTRAINT `chat_conversations_user_id_foreign` FOREIGN KEY (`id_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `danhgia`
--
ALTER TABLE `danhgia`
  ADD CONSTRAINT `danhgia_id_bienthe_foreign` FOREIGN KEY (`id_bienthe`) REFERENCES `bienthe` (`id_bienthe`) ON DELETE CASCADE,
  ADD CONSTRAINT `danhgia_id_dathang_foreign` FOREIGN KEY (`id_dathang`) REFERENCES `dathang` (`id_dathang`) ON DELETE CASCADE,
  ADD CONSTRAINT `danhgia_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `danhmuc`
--
ALTER TABLE `danhmuc`
  ADD CONSTRAINT `danhmuc_id_danhmuc_cha_foreign` FOREIGN KEY (`id_danhmuc_cha`) REFERENCES `danhmuc_cha` (`id_danhmuc_cha`) ON DELETE CASCADE;

--
-- Constraints for table `dathang`
--
ALTER TABLE `dathang`
  ADD CONSTRAINT `dathang_user_id_foreign` FOREIGN KEY (`id_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dathang_chitiet`
--
ALTER TABLE `dathang_chitiet`
  ADD CONSTRAINT `dathang_chitiet_id_bienthe_foreign` FOREIGN KEY (`id_bienthe`) REFERENCES `bienthe` (`id_bienthe`),
  ADD CONSTRAINT `dathang_chitiet_id_combo_foreign` FOREIGN KEY (`id_combo`) REFERENCES `combos` (`id_combo`) ON DELETE SET NULL,
  ADD CONSTRAINT `dathang_chitiet_id_dathang_foreign` FOREIGN KEY (`id_dathang`) REFERENCES `dathang` (`id_dathang`) ON DELETE CASCADE;

--
-- Constraints for table `diachi`
--
ALTER TABLE `diachi`
  ADD CONSTRAINT `diachi_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `giatri_thuoctinh`
--
ALTER TABLE `giatri_thuoctinh`
  ADD CONSTRAINT `giatri_thuoctinh_id_thuoctinh_foreign` FOREIGN KEY (`id_thuoctinh`) REFERENCES `thuoctinh` (`id_thuoctinh`) ON DELETE CASCADE;

--
-- Constraints for table `giohang`
--
ALTER TABLE `giohang`
  ADD CONSTRAINT `giohang_id_bienthe_foreign` FOREIGN KEY (`id_bienthe`) REFERENCES `bienthe` (`id_bienthe`) ON DELETE CASCADE,
  ADD CONSTRAINT `giohang_id_combo_foreign` FOREIGN KEY (`id_combo`) REFERENCES `combos` (`id_combo`) ON DELETE SET NULL,
  ADD CONSTRAINT `giohang_user_id_foreign` FOREIGN KEY (`id_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `khachhang_voucher`
--
ALTER TABLE `khachhang_voucher`
  ADD CONSTRAINT `khachhang_voucher_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `khachhang` (`id`),
  ADD CONSTRAINT `users_voucher_id_promotion_foreign` FOREIGN KEY (`id_voucher`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `khach_hang_affiliate`
--
ALTER TABLE `khach_hang_affiliate`
  ADD CONSTRAINT `affiliate_profiles_user_id_foreign` FOREIGN KEY (`id_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nhat_ky_admin`
--
ALTER TABLE `nhat_ky_admin`
  ADD CONSTRAINT `admin_activity_logs_user_id_foreign` FOREIGN KEY (`id_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nhat_ky_gui_ma_sinh_nhat`
--
ALTER TABLE `nhat_ky_gui_ma_sinh_nhat`
  ADD CONSTRAINT `birthday_coupon_logs_promotion_id_foreign` FOREIGN KEY (`id_voucher`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `birthday_coupon_logs_user_id_foreign` FOREIGN KEY (`id_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `birthday_coupon_logs_user_voucher_id_foreign` FOREIGN KEY (`id_khachhang_voucher`) REFERENCES `khachhang_voucher` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `noi_dung_tro_chuyen`
--
ALTER TABLE `noi_dung_tro_chuyen`
  ADD CONSTRAINT `chat_messages_conversation_id_foreign` FOREIGN KEY (`id_cuoc_tro_chuyen`) REFERENCES `cuoc_tro_chuyen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_sender_id_foreign` FOREIGN KEY (`id_nguoigui`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sanpham`
--
ALTER TABLE `sanpham`
  ADD CONSTRAINT `sanpham_id_danhmuc_foreign` FOREIGN KEY (`id_danhmuc`) REFERENCES `danhmuc` (`id_danhmuc`) ON DELETE CASCADE,
  ADD CONSTRAINT `sanpham_id_thuonghieu_foreign` FOREIGN KEY (`id_thuonghieu`) REFERENCES `thuonghieu` (`id_thuonghieu`) ON DELETE CASCADE;

--
-- Constraints for table `sanpham_daxem`
--
ALTER TABLE `sanpham_daxem`
  ADD CONSTRAINT `sanpham_daxem_id_sanpham_foreign` FOREIGN KEY (`id_sanpham`) REFERENCES `sanpham` (`id_sanpham`) ON DELETE CASCADE,
  ADD CONSTRAINT `sanpham_daxem_id_user_foreign` FOREIGN KEY (`id_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `san_pham_flashsale`
--
ALTER TABLE `san_pham_flashsale`
  ADD CONSTRAINT `flash_sale_products_id_bienthe_foreign` FOREIGN KEY (`id_bienthe`) REFERENCES `bienthe` (`id_bienthe`) ON DELETE CASCADE,
  ADD CONSTRAINT `flash_sale_products_session_id_foreign` FOREIGN KEY (`id_danhsach`) REFERENCES `danh_sach_flashsale` (`id_session`) ON DELETE CASCADE;

--
-- Constraints for table `thuoctinh`
--
ALTER TABLE `thuoctinh`
  ADD CONSTRAINT `thuoctinh_id_nhom_foreign` FOREIGN KEY (`id_nhom`) REFERENCES `nhom_thuoctinh` (`id_nhom`) ON DELETE CASCADE;

--
-- Constraints for table `yeuthich`
--
ALTER TABLE `yeuthich`
  ADD CONSTRAINT `yeuthich_id_bienthe_foreign` FOREIGN KEY (`id_bienthe`) REFERENCES `bienthe` (`id_bienthe`) ON DELETE CASCADE,
  ADD CONSTRAINT `yeuthich_id_khachhang_foreign` FOREIGN KEY (`id_khachhang`) REFERENCES `khachhang` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
