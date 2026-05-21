-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2026 at 12:36 PM
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
-- Table structure for table `bienthe`
--

CREATE TABLE `bienthe` (
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `id_sanpham` bigint(20) UNSIGNED NOT NULL,
  `ten_bienthe` varchar(255) DEFAULT NULL,
  `gia` decimal(12,2) NOT NULL,
  `soluong` int(11) NOT NULL,
  `thuoc_tinh_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`thuoc_tinh_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bienthe`
--

INSERT INTO `bienthe` (`id_bienthe`, `id_sanpham`, `ten_bienthe`, `gia`, `soluong`, `thuoc_tinh_json`) VALUES
(13690, 30, '32GB', 22800000.00, 19, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null}]'),
(13691, 30, '64GB', 25200000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null}]'),
(13692, 30, '16GB', 21800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null}]'),
(13701, 29, '64GB - Ryzen 7 7800X3D - Đen', 19700000.00, 9, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 7 7800X3D\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đen\",\"hex\":\"#000000\"}]'),
(13702, 29, '64GB - Ryzen 7 7800X3D - Xanh lá', 19700000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 7 7800X3D\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Xanh lá\",\"hex\":\"#008001\"}]'),
(13703, 29, '64GB - Ryzen 9 7950X - Đen', 22200000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 9 7950X\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đen\",\"hex\":\"#000000\"}]'),
(13704, 29, '64GB - Ryzen 9 7950X - Xanh lá', 22200000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 9 7950X\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Xanh lá\",\"hex\":\"#008001\"}]'),
(13705, 29, '128GB - Ryzen 7 7800X3D - Đen', 25300000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 7 7800X3D\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đen\",\"hex\":\"#000000\"}]'),
(13706, 29, '128GB - Ryzen 7 7800X3D - Xanh lá', 25300000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 7 7800X3D\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Xanh lá\",\"hex\":\"#008001\"}]'),
(13707, 29, '128GB - Ryzen 9 7950X - Đen', 27800000.00, 6, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 9 7950X\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đen\",\"hex\":\"#000000\"}]'),
(13708, 29, '128GB - Ryzen 9 7950X - Xanh lá', 27800000.00, 10, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Ryzen 9 7950X\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Xanh lá\",\"hex\":\"#008001\"}]'),
(13709, 28, '32GB - Apple M2 Ultra - Vàng', 33800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13710, 28, '32GB - Apple M2 Ultra - Nâu', 33800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13711, 28, '32GB - Apple M3 Max - Vàng', 25800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13712, 28, '32GB - Apple M3 Max - Nâu', 25800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13713, 28, '16GB - Apple M2 Ultra - Vàng', 32800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13714, 28, '16GB - Apple M2 Ultra - Nâu', 32800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13715, 28, '16GB - Apple M3 Max - Vàng', 24800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13716, 28, '16GB - Apple M3 Max - Nâu', 24800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13717, 27, '́́8GB - Intel Core i5-13600K - Vàng', 22800000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"́́8GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13718, 27, '́́8GB - Intel Core i5-13600K - Nâu', 22800000.00, 29, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"́́8GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13719, 27, '́́8GB - Intel Core i5-14400 - Vàng', 21800000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"́́8GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13720, 27, '́́8GB - Intel Core i5-14400 - Nâu', 21800000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"́́8GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13721, 27, '32GB - Intel Core i5-13600K - Vàng', 24300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13722, 27, '32GB - Intel Core i5-13600K - Nâu', 24300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13723, 27, '32GB - Intel Core i5-14400 - Vàng', 23300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13724, 27, '32GB - Intel Core i5-14400 - Nâu', 23300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13725, 27, '16GB - Intel Core i5-13600K - Vàng', 23300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13726, 27, '16GB - Intel Core i5-13600K - Nâu', 23300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-13600K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13727, 27, '16GB - Intel Core i5-14400 - Vàng', 22300000.00, 30, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13728, 27, '16GB - Intel Core i5-14400 - Nâu', 22300000.00, 0, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13729, 26, '32GB - Intel Core i9-14900K - Vàng', 30800000.00, 50, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13730, 26, '32GB - Intel Core i9-14900K - Đỏ', 30800000.00, 50, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13731, 26, '32GB - Intel Core i9-13900H - Vàng', 21800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13732, 26, '32GB - Intel Core i9-13900H - Đỏ', 21800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13733, 26, '64GB - Intel Core i9-14900K - Vàng', 33200000.00, 55, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13734, 26, '64GB - Intel Core i9-14900K - Đỏ', 33200000.00, 50, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13735, 26, '64GB - Intel Core i9-13900H - Vàng', 24200000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13736, 26, '64GB - Intel Core i9-13900H - Đỏ', 24200000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"64GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13737, 26, '128GB - Intel Core i9-14900K - Vàng', 33800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13738, 26, '128GB - Intel Core i9-14900K - Đỏ', 33800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-14900K\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13739, 26, '128GB - Intel Core i9-13900H - Vàng', 29800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13740, 26, '128GB - Intel Core i9-13900H - Đỏ', 29800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"128GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i9-13900H\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Đỏ\",\"hex\":\"#FF0000\"}]'),
(13741, 25, '16GB - Apple M2 Ultra - Nâu', 32800000.00, 49, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13742, 25, '16GB - Apple M2 Ultra - Vàng', 32800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13743, 25, '16GB - Apple M3 Max - Nâu', 24800000.00, 49, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13744, 25, '16GB - Apple M3 Max - Vàng', 24800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"16GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13745, 25, '32GB - Apple M2 Ultra - Nâu', 33800000.00, 50, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13746, 25, '32GB - Apple M2 Ultra - Vàng', 33800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M2 Ultra\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]'),
(13747, 25, '32GB - Apple M3 Max - Nâu', 25800000.00, 50, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Nâu\",\"hex\":\"#A62B2B\"}]'),
(13748, 25, '32GB - Apple M3 Max - Vàng', 25800000.00, 20, '[{\"id_thuoctinh\":\"1\",\"ten_thuoctinh\":\"RAM\",\"giatri\":\"32GB\",\"hex\":null},{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Apple M3 Max\",\"hex\":null},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"Màu sắc\",\"giatri\":\"Vàng\",\"hex\":\"#FBFF00\"}]');

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
(314, 25, 'uploads/sanpham/1fd801ef4e5694aedd118e26748a2371.webp', 2);

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
-- Table structure for table `colors`
--

CREATE TABLE `colors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `hex_code` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `colors`
--

INSERT INTO `colors` (`id`, `name`, `hex_code`) VALUES
(1, 'Đen', '#000000'),
(2, 'Đỏ', '#FF0000'),
(3, 'Nâu', '#A62B2B'),
(4, 'Vàng', '#FBFF00'),
(5, 'Xanh lá', '#008001');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','processing','replied') DEFAULT 'new',
  `reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category` varchar(100) DEFAULT 'Tư vấn',
  `replied_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `phone`, `message`, `status`, `reply`, `created_at`, `updated_at`, `category`, `replied_at`) VALUES
(2, 'lạc mạnh quân', 'ebanlmqpk04165@gmail.com', '0869734820', 'đẹp chất lượng', 'replied', 'xin cahof hehhe', '2026-04-06 11:58:33', '2026-04-08 11:37:13', 'Tư vấn', NULL),
(6, 'Trần Quốc Phong', 'phongtqpk04300@gmail.com', '0782583237', 'bgfhfnhndfghndfmghhf', 'replied', 'Kính gửi Quý khách,\n\nDựa trên nhu cầu của bạn, chúng tôi xin tư vấn một số dòng sản phẩm phù hợp:\n\n• Laptop Gaming: Asus ROG, MSI, Lenovo Legion\n• Laptop Văn phòng: Dell XPS, HP Spectre, MacBook Air\n• Laptop Sinh viên: Asus VivoBook, Acer Aspire\n\nQuý khách có thể ghé showroom hoặc đặt hàng online tại vinatech.vn với chính sách trả góp 0% lãi suất.', '2026-04-09 02:13:55', '2026-04-09 02:16:42', 'Tư vấn', NULL),
(7, 'Phong', 'phongpoor123@gmail.com', '0782583237', 'fhsdbvdhjd', 'replied', 'Kính gửi Quý khách,\n\nDựa trên nhu cầu của bạn, chúng tôi xin tư vấn một số dòng sản phẩm phù hợp:\n\n• Laptop Gaming: Asus ROG, MSI, Lenovo Legion\n• Laptop Văn phòng: Dell XPS, HP Spectre, MacBook Air\n• Laptop Sinh viên: Asus VivoBook, Acer Aspire\n\nQuý khách có thể ghé showroom hoặc đặt hàng online tại vinatech.vn với chính sách trả góp 0% lãi suất.', '2026-04-16 03:33:00', '2026-04-16 03:33:33', 'Tư vấn', NULL),
(8, 'Phong', 'phongpoor123@gmail.com', '0782583237', 'bhadsvdsvdsvds', 'replied', 'Kính gửi Quý khách,\n\nĐơn hàng của bạn đang được xử lý. Chúng tôi sẽ thông báo ngay khi hàng được giao cho đơn vị vận chuyển.\n\nThời gian dự kiến nhận hàng: 2-3 ngày làm việc.\n\nMọi thắc mắc xin liên hệ hotline 1800 9999 (miễn phí, 8:00 - 22:00 hàng ngày).', '2026-04-25 01:42:50', '2026-04-25 01:43:18', 'Tư vấn', NULL);

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
  `ten_danhmuc` varchar(255) NOT NULL,
  `trangthai` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `danhmuc`
--

INSERT INTO `danhmuc` (`id_danhmuc`, `ten_danhmuc`, `trangthai`) VALUES
(2, 'Laptop Gaming', 'active'),
(3, 'Laptop văn phòng', 'active'),
(4, 'Macbook', 'active'),
(7, 'Laptop học sinh', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `dathang`
--

CREATE TABLE `dathang` (
  `id_dathang` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tongtien` decimal(12,2) NOT NULL,
  `trangthai` varchar(255) NOT NULL DEFAULT 'pending',
  `diachi` varchar(255) DEFAULT NULL,
  `PTTT` varchar(255) DEFAULT NULL,
  `promotion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `giam_gia` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lydo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dathang`
--

INSERT INTO `dathang` (`id_dathang`, `user_id`, `tongtien`, `trangthai`, `diachi`, `PTTT`, `promotion_id`, `giam_gia`, `created_at`, `updated_at`, `lydo`) VALUES
(35, 12, 32800000.00, 'done', 'gihdfvihdfvhdchvdbvd', 'COD', NULL, 30000.00, '2026-04-20 19:25:37', '2026-04-20 19:25:57', NULL),
(36, 7, 46600000.00, 'pending', 'dfhv dchvbdfhbvdshuvbdu', 'Ví điện tử', NULL, 30000.00, '2026-04-20 19:30:25', '2026-04-20 19:30:25', NULL),
(37, 12, 46100000.00, 'done', 'ghfvgvgcvdgsvdsucvdsu', 'Ví điện tử', NULL, 30000.00, '2026-04-20 19:32:33', '2026-04-20 19:34:28', NULL),
(38, 12, 48100000.00, 'pending', 'hdfbdfbdfbdfbdfbet', 'Ví điện tử', NULL, 30000.00, '2026-04-20 19:38:38', '2026-04-20 19:38:38', NULL),
(39, 12, 18745000.00, 'cancelled', 'gdfvdbdfbdf', 'Ví điện tử', 20, 985000.00, '2026-04-24 23:47:50', '2026-04-25 00:09:17', 'hfshjvdshjvsdhvsdyvsd'),
(40, 12, 39400000.00, 'done', 'fbcxdfvbdsvdsvs', 'Ví điện tử', NULL, 30000.00, '2026-04-24 23:49:09', '2026-04-25 00:28:30', NULL),
(41, 12, 108200000.00, 'done', 'bvbdhvdjvdveru', 'Ví điện tử', 19, 3030000.00, '2026-04-25 01:07:22', '2026-04-25 01:37:54', NULL),
(42, 12, 157700000.00, 'cancelled', 'vdshvdsjvdssvs', 'Ví điện tử', 20, 8330000.00, '2026-04-25 01:27:12', '2026-04-25 01:38:50', NULL),
(43, 12, 36400000.00, 'cancelled', 'FBFVDSVDSVSDVD', 'COD', 25, 3030000.00, '2026-04-25 01:46:47', '2026-04-25 01:47:11', 'B VDBSJVDSUGVDSU'),
(44, 7, 19730000.00, 'pending', 'hjbigbjkdfbndfjb v', 'Ví điện tử', NULL, 0.00, '2026-05-04 01:43:44', '2026-05-04 01:43:44', NULL),
(45, 7, 669030000.00, 'pending', 'phppmgg', 'Ví điện tử', NULL, 0.00, '2026-05-04 01:45:55', '2026-05-04 01:45:55', NULL);

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `trangthai` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giatri_thuoctinh`
--

INSERT INTO `giatri_thuoctinh` (`id_giatri`, `id_thuoctinh`, `giatri`, `gia_cong_them`, `trangthai`) VALUES
(1, 1, '4GB', 0.00, 1),
(2, 1, '́́8GB', 300000.00, 1),
(3, 1, '16GB', 800000.00, 1),
(4, 1, '32GB', 1800000.00, 1),
(5, 1, '64GB', 4200000.00, 1),
(6, 1, '128GB', 9800000.00, 1),
(7, 2, 'Intel Core i3-14100', 0.00, 1),
(8, 2, 'Intel Core i5-13600K', 2500000.00, 1),
(9, 2, 'Intel Core i5-14400', 1500000.00, 1),
(10, 2, 'Intel Core i7-14700K', 6000000.00, 1),
(11, 2, 'Intel Core i9-13900H', 5000000.00, 1),
(12, 2, 'Intel Core i9-14900K', 9000000.00, 1),
(13, 2, 'Ryzen 7 7800X3D', 5500000.00, 1),
(14, 2, 'Ryzen 9 7950X', 8000000.00, 1),
(15, 2, 'Apple M2 Ultra', 22000000.00, 1),
(16, 2, 'Apple M3 Max', 14000000.00, 1),
(17, 3, 'RTX 3080', 7000000.00, 1),
(18, 3, 'RTX 4060', 0.00, 1),
(19, 3, 'RTX 4080', 18000000.00, 1),
(20, 3, 'RTX 4090', 32000000.00, 1),
(21, 3, 'RX 7800 XT', 6000000.00, 1),
(22, 3, 'RX 7900 XTX', 15000000.00, 1),
(23, 3, 'Apple M3 GPU', 8000000.00, 1),
(24, 4, '13.3 inch', 0.00, 1),
(25, 4, '14 inch', 300000.00, 1),
(26, 4, '15.6 inch', 800000.00, 1),
(27, 4, '16 inch', 1200000.00, 1),
(28, 5, 'FHD 1920×1080', 0.00, 1),
(29, 5, '2K 2560×1440', 2200000.00, 1),
(30, 5, '4K 3840×2160', 5000000.00, 1),
(31, 6, 'AMOLED', 3500000.00, 1),
(32, 6, 'IPS', 0.00, 1),
(33, 6, 'OLED', 4500000.00, 1),
(34, 6, 'Mini-LED', 5500000.00, 1),
(36, 7, '60Wh', 0.00, 1),
(37, 7, '72Wh', 500000.00, 1),
(38, 7, '100Wh', 1400000.00, 1),
(39, 8, '140W MagSafe', 900000.00, 1),
(40, 8, '65W USB-C', 0.00, 1),
(43, 11, '128GB', 0.00, 1),
(44, 11, '256GB', 300000.00, 1),
(45, 11, '512GB', 800000.00, 1),
(46, 11, '1TB', 1500000.00, 1),
(47, 11, '2TB', 3500000.00, 1),
(48, 11, '4TB', 7000000.00, 1),
(49, 11, '8TB', 15000000.00, 1),
(50, 11, '16TB', 35000000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `giohang`
--

CREATE TABLE `giohang` (
  `id_giohang` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `soluong` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(30, '2026_04_20_062535_add_thong_so_ky_thuat_to_sanpham_table', 11);

-- --------------------------------------------------------

--
-- Table structure for table `nhom_thuoctinh`
--

CREATE TABLE `nhom_thuoctinh` (
  `id_nhom` bigint(20) UNSIGNED NOT NULL,
  `ten_nhom` varchar(255) NOT NULL,
  `trangthai` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nhom_thuoctinh`
--

INSERT INTO `nhom_thuoctinh` (`id_nhom`, `ten_nhom`, `trangthai`) VALUES
(1, 'Cấu hình', 1),
(2, 'Màn hình', 1),
(3, 'Pin & Sạc', 1);

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
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
(36, 'App\\Models\\User', 7, 'remember_token', '2fcb31348850d6d1922a5f36412ccdf6f47f0174cdc5e53d76453de39c36b3dc', '[\"*\"]', '2026-05-18 03:11:02', NULL, '2026-04-16 02:55:07', '2026-05-18 03:11:02'),
(37, 'App\\Models\\User', 12, 'session_token', '8a7ee5bf09648d10ce76e481366d0ea8525023125b40674ca669a512acf47739', '[\"*\"]', '2026-04-16 03:14:42', NULL, '2026-04-16 03:08:31', '2026-04-16 03:14:42'),
(38, 'App\\Models\\User', 12, 'session_token', '19c64a0cff9897e211c9a2451076eee472d5bbbb5feec26f4287b110a2817aa6', '[\"*\"]', '2026-04-16 03:39:14', NULL, '2026-04-16 03:17:46', '2026-04-16 03:39:14'),
(39, 'App\\Models\\User', 12, 'session_token', 'fad5108cb05c7c14ca85c20e3bdaaf9a9889eeb5860ce4eb281d3bfd70b4d744', '[\"*\"]', '2026-04-20 03:10:06', NULL, '2026-04-20 02:51:17', '2026-04-20 03:10:06'),
(40, 'App\\Models\\User', 12, 'session_token', '926b47894ecc8fe2dbc3ce2b6c81f901ea1e9c21d2e4415117de1f145ca5dd50', '[\"*\"]', '2026-04-20 20:41:23', NULL, '2026-04-20 20:22:13', '2026-04-20 20:41:23'),
(41, 'App\\Models\\User', 12, 'session_token', '7b7762f4b6714407d8d2553e32969f7e1801635748d9118add2487014e3cd12d', '[\"*\"]', '2026-04-21 23:18:30', NULL, '2026-04-21 20:36:46', '2026-04-21 23:18:30'),
(42, 'App\\Models\\User', 12, 'session_token', '26a07616ff3cf7224cdfd9a7c5a82b5b87f98a77baab99123d6eb2d6d31f971e', '[\"*\"]', '2026-04-25 01:07:52', NULL, '2026-04-24 23:18:06', '2026-04-25 01:07:52'),
(43, 'App\\Models\\User', 12, 'auth_token', 'd37695c9f4a809006cfe25f2600bea981524c63ebee39920740e0be75305a155', '[\"*\"]', '2026-04-25 01:50:36', NULL, '2026-04-25 01:23:17', '2026-04-25 01:50:36');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'product',
  `code` varchar(50) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `value` int(11) DEFAULT NULL,
  `mota` longtext NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `loai_dieu_kien` varchar(50) DEFAULT NULL,
  `dieu_kien` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `name`, `category`, `code`, `type`, `value`, `mota`, `start_date`, `end_date`, `status`, `loai_dieu_kien`, `dieu_kien`) VALUES
(18, 'miễn phí vận chuyển', 'freeship', '9XND6U45', 'percent', 100, 'đơn hàng phải trên 30000000', NULL, NULL, 'open', NULL, NULL),
(19, 'Giảm 40000000đ', 'product', 'GIAM-40000000D-2128', 'fixed', 3000000, 'Giảm 3000000đ với những đơn hàng có giá trên 40000000đ', NULL, '2026-04-30', 'running', '>=', 40000000.00),
(20, 'Giảm 5%', 'product', 'VOGYGTZL', 'percent', 5, 'Giảm 5% giá tiền với đơn hàng trên 35000000', NULL, '2026-04-30', 'running', '>=', 35000000.00),
(21, 'Giảm 3.000.000đ', 'product', 'GGULN0K6', 'fixed', 3000000, 'Nhân dịp lễ 30/4 - 1/5 giảm giá 40000000', NULL, '2026-05-01', 'running', '>=', 40000000.00),
(22, 'Giảm 5.000.000đ', 'product', '9DVF43K8', 'fixed', 5000000, 'dfhydbvhdbvhbvr', NULL, '2026-04-20', 'running', '>=', 40000000.00),
(23, 'HAPPYBIRTHDAY', 'birthday', 'BIRTHDAY', 'fixed', 5000000, 'Chúc bạn sinh nhật vui vẻ', NULL, NULL, 'open', NULL, NULL),
(25, 'sdbvsjvb', 'product', 'XURG6EIM', 'fixed', 3000000, 'vhdsbvsdvsd', NULL, '2026-04-30', 'running', '>=', 20000000.00);

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
(30, 3, 1, '[{\"id_thuoctinh\":\"2\",\"ten_thuoctinh\":\"CPU\",\"giatri\":\"Intel Core i5-14400\"},{\"id_thuoctinh\":\"3\",\"ten_thuoctinh\":\"GPU\",\"giatri\":\"RTX 4090\"},{\"id_thuoctinh\":\"11\",\"ten_thuoctinh\":\"SSD\",\"giatri\":\"128GB\"},{\"id_thuoctinh\":\"4\",\"ten_thuoctinh\":\"K\\u00edch th\\u01b0\\u1edbc\",\"giatri\":\"15.6 inch\"},{\"id_thuoctinh\":\"5\",\"ten_thuoctinh\":\"\\u0110\\u1ed9 ph\\u00e2n gi\\u1ea3i\",\"giatri\":\"2K 2560\\u00d71440\"},{\"id_thuoctinh\":\"6\",\"ten_thuoctinh\":\"T\\u1ea5m n\\u1ec1n\",\"giatri\":\"Mini-LED\"},{\"id_thuoctinh\":\"7\",\"ten_thuoctinh\":\"Pin\",\"giatri\":\"100Wh\"},{\"id_thuoctinh\":\"8\",\"ten_thuoctinh\":\"S\\u1ea1c\",\"giatri\":\"65W USB-C\"},{\"id_thuoctinh\":\"color-type\",\"ten_thuoctinh\":\"M\\u00e0u s\\u1eafc\",\"giatri\":\"N\\u00e2u\"}]', 'Laptop ASUS Vivobook S16 S3607VA-RP056W', 'SP-1-DRMOZW', '1', 'uploads/sanpham/35559b5146c6711362579ea30180e389.webp', '2.5');

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
(11, 'SSD', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `thuonghieu`
--

CREATE TABLE `thuonghieu` (
  `id_thuonghieu` bigint(20) UNSIGNED NOT NULL,
  `ten_thuonghieu` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thuonghieu`
--

INSERT INTO `thuonghieu` (`id_thuonghieu`, `ten_thuonghieu`, `created_at`, `updated_at`) VALUES
(1, 'Asus', NULL, NULL),
(2, 'HP', NULL, NULL),
(3, 'Levono', NULL, NULL),
(4, 'MSI', NULL, NULL),
(7, 'Apple', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `api_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `status` enum('active','locked') NOT NULL DEFAULT 'active',
  `reset_otp` varchar(10) DEFAULT NULL,
  `reset_otp_expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `avatar`, `phone`, `email_verified_at`, `password`, `role`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `api_token`, `created_at`, `updated_at`, `date_of_birth`, `gender`, `status`, `reset_otp`, `reset_otp_expires_at`) VALUES
(1, 'Lê Ngọc Tài', 'tantaile175@gmail.com', NULL, NULL, NULL, '$2y$12$S3Nfibi705ntzoKL9atckuxpY97GH6cbGw7AAov/mBHYFFBo2.BJ2', 'user', NULL, NULL, NULL, NULL, NULL, '2026-03-24 06:24:05', '2026-03-24 06:24:05', NULL, NULL, 'active', NULL, NULL),
(2, 'Quách Đức Thành', 'thanhquach123@gmail.com', NULL, NULL, NULL, '$2y$12$EYx5/JnreNJyYchAAQkqbuIKOi3fO2kSH.03tPq5dtXRE7Z/.QiEK', 'admin', NULL, NULL, NULL, NULL, NULL, '2026-03-24 06:25:48', '2026-04-16 03:27:40', NULL, NULL, 'locked', NULL, NULL),
(3, 'Trần Mạnh Quân', 'manhquan123@gmail.com', NULL, NULL, NULL, '$2y$12$SHGdkuQPMRuYG8M0qJ44o.gEPJ8tGnisRObPrbhqdExARLex2IXA.', 'user', NULL, NULL, NULL, NULL, NULL, '2026-03-24 07:34:24', '2026-03-24 07:34:24', NULL, NULL, 'active', NULL, NULL),
(4, 'NextGen', 'nextgenshop@gmail.com', NULL, '0235556789', NULL, '$2y$12$2xEvlIEAdN.BNJ3AEptxnu/8CuoC2esA8jlP7f76vtTKQUH450U0m', 'admin', NULL, NULL, NULL, NULL, NULL, '2026-03-24 08:36:19', '2026-03-24 08:36:19', NULL, NULL, 'active', NULL, NULL),
(7, 'Trần Quốc Phong', 'phongtqpk04300@gmail.com', NULL, '0782583237', NULL, '$2y$12$Q8LmL.5faVjo7YK27vUIb.SmO3i/8CDKrqav3aWnQTMXOccIUxs56', 'admin', NULL, NULL, NULL, NULL, NULL, '2026-03-28 01:16:26', '2026-04-16 02:49:09', NULL, NULL, 'active', '220934', '2026-04-16 02:54:09'),
(12, 'Phong', 'phongpoor123@gmail.com', 'uploads/avatar/1776335397_12.png', '0782583237', NULL, '$2y$12$Run5y5di2OYuUmEbEgwaCOi/0SBSXOIV75gaOvgsTePN9J5u0EOMu', 'user', NULL, NULL, NULL, NULL, NULL, '2026-03-31 04:02:45', '2026-04-25 00:32:09', '2006-04-25', 'Nam', 'active', NULL, NULL),
(13, 'Phongpoor', 'phongpoor236@gmail.com', NULL, '08563254785', NULL, '$2y$12$Z7xorAaYA2rBrbA/v5rTD.2lhGgOXGABOBZ7gN1rOO06VH/CKx3da', 'admin', NULL, NULL, NULL, NULL, NULL, '2026-04-09 09:46:55', '2026-04-09 09:57:02', NULL, NULL, 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_voucher`
--

CREATE TABLE `users_voucher` (
  `id` int(11) NOT NULL,
  `id_user` bigint(20) UNSIGNED DEFAULT NULL,
  `id_promotion` int(11) DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT NULL,
  `ngay_nhan` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_voucher`
--

INSERT INTO `users_voucher` (`id`, `id_user`, `id_promotion`, `trang_thai`, `ngay_nhan`) VALUES
(3, 7, 19, '0', '2026-04-16 09:07:02'),
(4, 7, 18, '1', '2026-04-16 09:07:04'),
(5, 12, 19, '1', '2026-04-16 09:08:24'),
(6, 12, 18, '1', '2026-04-16 09:08:26'),
(7, 12, 20, '1', '2026-04-16 10:39:14'),
(8, 12, 21, '0', '2026-04-22 03:39:20'),
(9, 12, 22, '0', '2026-04-22 03:43:12'),
(10, 7, 20, '0', '2026-04-24 00:52:21'),
(11, 7, 25, '0', '2026-05-04 08:42:41');

-- --------------------------------------------------------

--
-- Table structure for table `yeuthich`
--

CREATE TABLE `yeuthich` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `id_bienthe` bigint(20) UNSIGNED NOT NULL,
  `soluong` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bienthe`
--
ALTER TABLE `bienthe`
  ADD PRIMARY KEY (`id_bienthe`),
  ADD KEY `bienthe_id_sanpham_foreign` (`id_sanpham`);

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
-- Indexes for table `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `colors_name_unique` (`name`),
  ADD UNIQUE KEY `colors_hex_code_unique` (`hex_code`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id_danhmuc`);

--
-- Indexes for table `dathang`
--
ALTER TABLE `dathang`
  ADD PRIMARY KEY (`id_dathang`),
  ADD KEY `dathang_user_id_foreign` (`user_id`);

--
-- Indexes for table `dathang_chitiet`
--
ALTER TABLE `dathang_chitiet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dathang_chitiet_id_dathang_foreign` (`id_dathang`),
  ADD KEY `dathang_chitiet_id_bienthe_foreign` (`id_bienthe`);

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
  ADD UNIQUE KEY `giohang_user_id_id_bienthe_unique` (`user_id`,`id_bienthe`),
  ADD KEY `giohang_id_bienthe_foreign` (`id_bienthe`);

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
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nhom_thuoctinh`
--
ALTER TABLE `nhom_thuoctinh`
  ADD PRIMARY KEY (`id_nhom`);

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
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`id_sanpham`),
  ADD KEY `sanpham_id_danhmuc_foreign` (`id_danhmuc`),
  ADD KEY `sanpham_id_thuonghieu_foreign` (`id_thuonghieu`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_api_token_unique` (`api_token`);

--
-- Indexes for table `users_voucher`
--
ALTER TABLE `users_voucher`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_promotion` (`id_promotion`);

--
-- Indexes for table `yeuthich`
--
ALTER TABLE `yeuthich`
  ADD PRIMARY KEY (`id`),
  ADD KEY `yeuthich_user_id_foreign` (`user_id`),
  ADD KEY `yeuthich_id_bienthe_foreign` (`id_bienthe`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bienthe`
--
ALTER TABLE `bienthe`
  MODIFY `id_bienthe` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13749;

--
-- AUTO_INCREMENT for table `bienthe_hinhanh`
--
ALTER TABLE `bienthe_hinhanh`
  MODIFY `id_bienthe_hinhanh` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=315;

--
-- AUTO_INCREMENT for table `colors`
--
ALTER TABLE `colors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `danhgia`
--
ALTER TABLE `danhgia`
  MODIFY `id_danhgia` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `danhmuc`
--
ALTER TABLE `danhmuc`
  MODIFY `id_danhmuc` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `dathang`
--
ALTER TABLE `dathang`
  MODIFY `id_dathang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `dathang_chitiet`
--
ALTER TABLE `dathang_chitiet`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `giatri_thuoctinh`
--
ALTER TABLE `giatri_thuoctinh`
  MODIFY `id_giatri` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `giohang`
--
ALTER TABLE `giohang`
  MODIFY `id_giohang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `nhom_thuoctinh`
--
ALTER TABLE `nhom_thuoctinh`
  MODIFY `id_nhom` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `id_sanpham` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `thuoctinh`
--
ALTER TABLE `thuoctinh`
  MODIFY `id_thuoctinh` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `thuonghieu`
--
ALTER TABLE `thuonghieu`
  MODIFY `id_thuonghieu` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users_voucher`
--
ALTER TABLE `users_voucher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `yeuthich`
--
ALTER TABLE `yeuthich`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bienthe`
--
ALTER TABLE `bienthe`
  ADD CONSTRAINT `bienthe_id_sanpham_foreign` FOREIGN KEY (`id_sanpham`) REFERENCES `sanpham` (`id_sanpham`) ON DELETE CASCADE;

--
-- Constraints for table `bienthe_hinhanh`
--
ALTER TABLE `bienthe_hinhanh`
  ADD CONSTRAINT `fk_bienthe_hinhanh_sanpham` FOREIGN KEY (`id_sanpham`) REFERENCES `sanpham` (`id_sanpham`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `danhgia`
--
ALTER TABLE `danhgia`
  ADD CONSTRAINT `danhgia_id_bienthe_foreign` FOREIGN KEY (`id_bienthe`) REFERENCES `bienthe` (`id_bienthe`) ON DELETE CASCADE,
  ADD CONSTRAINT `danhgia_id_dathang_foreign` FOREIGN KEY (`id_dathang`) REFERENCES `dathang` (`id_dathang`) ON DELETE CASCADE,
  ADD CONSTRAINT `danhgia_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dathang`
--
ALTER TABLE `dathang`
  ADD CONSTRAINT `dathang_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dathang_chitiet`
--
ALTER TABLE `dathang_chitiet`
  ADD CONSTRAINT `dathang_chitiet_id_bienthe_foreign` FOREIGN KEY (`id_bienthe`) REFERENCES `bienthe` (`id_bienthe`) ON DELETE CASCADE,
  ADD CONSTRAINT `dathang_chitiet_id_dathang_foreign` FOREIGN KEY (`id_dathang`) REFERENCES `dathang` (`id_dathang`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `giohang_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sanpham`
--
ALTER TABLE `sanpham`
  ADD CONSTRAINT `sanpham_id_danhmuc_foreign` FOREIGN KEY (`id_danhmuc`) REFERENCES `danhmuc` (`id_danhmuc`) ON DELETE CASCADE,
  ADD CONSTRAINT `sanpham_id_thuonghieu_foreign` FOREIGN KEY (`id_thuonghieu`) REFERENCES `thuonghieu` (`id_thuonghieu`) ON DELETE CASCADE;

--
-- Constraints for table `thuoctinh`
--
ALTER TABLE `thuoctinh`
  ADD CONSTRAINT `thuoctinh_id_nhom_foreign` FOREIGN KEY (`id_nhom`) REFERENCES `nhom_thuoctinh` (`id_nhom`) ON DELETE CASCADE;

--
-- Constraints for table `users_voucher`
--
ALTER TABLE `users_voucher`
  ADD CONSTRAINT `users_voucher_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `users_voucher_ibfk_2` FOREIGN KEY (`id_promotion`) REFERENCES `promotions` (`id`);

--
-- Constraints for table `yeuthich`
--
ALTER TABLE `yeuthich`
  ADD CONSTRAINT `yeuthich_id_bienthe_foreign` FOREIGN KEY (`id_bienthe`) REFERENCES `bienthe` (`id_bienthe`) ON DELETE CASCADE,
  ADD CONSTRAINT `yeuthich_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
