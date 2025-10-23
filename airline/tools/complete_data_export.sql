-- =====================================================
-- COMPLETE DATA EXPORT - VNAir Ticket System
-- Generated: 2025-10-24
-- Description: Chỉ INSERT dữ liệu (không xóa)
-- =====================================================

-- =====================================================
-- 1. VAI TRÒ (ROLES)
-- =====================================================
INSERT IGNORE INTO vai_tro (id, ma, ten) VALUES (1, 'ADMIN', 'Quản trị viên'),(2, 'STAFF', 'Nhân viên'),(3, 'CUSTOMER', 'Khách hàng');

-- =====================================================
-- 2. HẠNG GHẾ (SEAT CLASSES)
-- =====================================================
INSERT IGNORE INTO hang_ghe (id, ma, ten) VALUES (1, 'ECON', 'Pho thong'),(2, 'PREM', 'Pho thong dac biet'),(3, 'BUSI', 'Thuong gia');

-- =====================================================
-- 3. SÂN BAY (AIRPORTS)
-- =====================================================
INSERT IGNORE INTO san_bay (ma, ten, thanh_pho, quoc_gia, mui_gio) VALUES ('HAN', 'Noi Bai', 'Ha Noi', 'Viet Nam', 'UTC+7'),('SGN', 'Tan Son Nhat', 'Ho Chi Minh', 'Viet Nam', 'UTC+7'),('DAD', 'Da Nang', 'Da Nang', 'Viet Nam', 'UTC+7'),('CXR', 'Cam Ranh', 'Nha Trang', 'Viet Nam', 'UTC+7'),('PQC', 'Phu Quoc', 'Phu Quoc', 'Viet Nam', 'UTC+7');

-- =====================================================
-- 4. TUYẾN BAY (ROUTES)
-- =====================================================
INSERT IGNORE INTO tuyen_bay (id, ma_tuyen, di, den, khoang_cach_km) VALUES (1, 'HAN-SGN', 'HAN', 'SGN', 1130),(2, 'HAN-DAD', 'HAN', 'DAD', 600),(3, 'SGN-DAD', 'SGN', 'DAD', 600),(4, 'DAD-HAN', 'DAD', 'HAN', 600),(6, 'DAD-SGN', 'DAD', 'SGN', 600),(7, 'SGN-CXR', 'SGN', 'CXR', 300),(8, 'CXR-SGN', 'CXR', 'SGN', 300),(9, 'SGN-PQC', 'SGN', 'PQC', 200),(10, 'PQC-SGN', 'PQC', 'SGN', 200);

-- =====================================================
-- 5. TÀU BAY (AIRCRAFT)
-- =====================================================
INSERT IGNORE INTO tau_bay (id, so_dang_ba, dong_may_bay) VALUES (1, 'VN-A001', 'Airbus A320'),(2, 'VN-A002', 'Airbus A321'),(3, 'VN-A003', 'Boeing 737'),(4, 'VN-A004', 'Boeing 777'),(5, 'VN-A005', 'Airbus A350');

-- =====================================================
-- 6. CHUYẾN BAY (FLIGHTS) - Từ 25/10/2025 trở đi
-- =====================================================
INSERT IGNORE INTO chuyen_bay (id, so_hieu, tuyen_bay_id, tau_bay_id, gio_di, gio_den, trang_thai) VALUES 
-- Ngày 25/10/2025
(41, 'VN201', 1, 1, '2025-10-25 08:00:00', '2025-10-25 10:00:00', 'LEN_KE_HOACH'),
(42, 'VN202', 1, 2, '2025-10-25 09:30:00', '2025-10-25 11:30:00', 'LEN_KE_HOACH'),
(43, 'VN203', 2, 3, '2025-10-25 11:00:00', '2025-10-25 13:00:00', 'LEN_KE_HOACH'),
(44, 'VN204', 3, 4, '2025-10-25 14:00:00', '2025-10-25 16:00:00', 'LEN_KE_HOACH'),
(45, 'VN205', 4, 5, '2025-10-25 16:30:00', '2025-10-25 18:30:00', 'LEN_KE_HOACH'),
(46, 'VN206', 6, 1, '2025-10-25 19:00:00', '2025-10-25 21:00:00', 'LEN_KE_HOACH'),
(47, 'VN207', 7, 2, '2025-10-25 20:30:00', '2025-10-25 22:30:00', 'LEN_KE_HOACH'),
(48, 'VN208', 8, 3, '2025-10-25 22:00:00', '2025-10-26 00:00:00', 'LEN_KE_HOACH'),

-- Ngày 26/10/2025
(23, 'VN301', 1, 1, '2025-10-26 08:00:00', '2025-10-26 10:30:00', 'LEN_KE_HOACH'),
(24, 'VN302', 2, 2, '2025-10-26 14:30:00', '2025-10-26 17:00:00', 'LEN_KE_HOACH'),
(25, 'VN303', 3, 3, '2025-10-26 19:15:00', '2025-10-26 21:45:00', 'LEN_KE_HOACH'),
(26, 'VN401', 4, 4, '2025-10-26 09:00:00', '2025-10-26 11:30:00', 'LEN_KE_HOACH'),
(27, 'VN402', 6, 5, '2025-10-26 15:30:00', '2025-10-26 18:00:00', 'LEN_KE_HOACH'),
(28, 'VN403', 7, 1, '2025-10-26 20:15:00', '2025-10-26 22:45:00', 'LEN_KE_HOACH'),
(29, 'VN501', 8, 2, '2025-10-26 10:00:00', '2025-10-26 11:30:00', 'LEN_KE_HOACH'),
(30, 'VN502', 9, 3, '2025-10-26 16:00:00', '2025-10-26 17:30:00', 'LEN_KE_HOACH'),
(31, 'VN601', 10, 4, '2025-10-26 12:30:00', '2025-10-26 14:00:00', 'LEN_KE_HOACH'),
(32, 'VN602', 1, 5, '2025-10-26 18:30:00', '2025-10-26 20:00:00', 'LEN_KE_HOACH'),
(33, 'VN701', 2, 1, '2025-10-26 11:00:00', '2025-10-26 12:00:00', 'LEN_KE_HOACH'),
(34, 'VN702', 3, 2, '2025-10-26 17:00:00', '2025-10-26 18:00:00', 'LEN_KE_HOACH'),
(35, 'VN801', 4, 3, '2025-10-26 13:00:00', '2025-10-26 14:00:00', 'LEN_KE_HOACH'),
(36, 'VN802', 6, 4, '2025-10-26 19:00:00', '2025-10-26 20:00:00', 'LEN_KE_HOACH'),
(37, 'VN901', 7, 5, '2025-10-26 12:00:00', '2025-10-26 13:00:00', 'LEN_KE_HOACH'),
(38, 'VN902', 8, 1, '2025-10-26 18:00:00', '2025-10-26 19:00:00', 'LEN_KE_HOACH'),
(39, 'VN1001', 9, 2, '2025-10-26 14:00:00', '2025-10-26 15:00:00', 'LEN_KE_HOACH'),
(40, 'VN1002', 10, 3, '2025-10-26 20:00:00', '2025-10-26 21:00:00', 'LEN_KE_HOACH'),

-- Ngày 01/11/2025
(49, 'VN301', 1, 1, '2025-11-01 08:00:00', '2025-11-01 10:00:00', 'LEN_KE_HOACH'),
(50, 'VN302', 2, 2, '2025-11-01 10:30:00', '2025-11-01 12:30:00', 'LEN_KE_HOACH'),
(51, 'VN303', 3, 3, '2025-11-01 13:00:00', '2025-11-01 15:00:00', 'LEN_KE_HOACH'),
(52, 'VN304', 4, 4, '2025-11-01 15:30:00', '2025-11-01 17:30:00', 'LEN_KE_HOACH'),
(53, 'VN305', 6, 5, '2025-11-01 18:00:00', '2025-11-01 20:00:00', 'LEN_KE_HOACH'),

-- Ngày 02/11/2025
(54, 'VN306', 7, 1, '2025-11-02 07:30:00', '2025-11-02 09:30:00', 'LEN_KE_HOACH'),
(55, 'VN307', 8, 2, '2025-11-02 09:00:00', '2025-11-02 11:00:00', 'LEN_KE_HOACH'),
(56, 'VN308', 9, 3, '2025-11-02 11:30:00', '2025-11-02 13:30:00', 'LEN_KE_HOACH'),
(57, 'VN309', 10, 4, '2025-11-02 14:00:00', '2025-11-02 16:00:00', 'LEN_KE_HOACH'),
(58, 'VN310', 1, 5, '2025-11-02 16:30:00', '2025-11-02 18:30:00', 'LEN_KE_HOACH'),

-- Ngày 15/11/2025
(59, 'VN311', 2, 1, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 'LEN_KE_HOACH'),
(60, 'VN312', 3, 2, '2025-11-15 10:30:00', '2025-11-15 12:30:00', 'LEN_KE_HOACH'),
(61, 'VN313', 4, 3, '2025-11-15 13:00:00', '2025-11-15 15:00:00', 'LEN_KE_HOACH'),
(62, 'VN314', 6, 4, '2025-11-15 15:30:00', '2025-11-15 17:30:00', 'LEN_KE_HOACH'),
(63, 'VN315', 7, 5, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 'LEN_KE_HOACH'),

-- Ngày 01/12/2025
(64, 'VN401', 8, 1, '2025-12-01 08:00:00', '2025-12-01 10:00:00', 'LEN_KE_HOACH'),
(65, 'VN402', 9, 2, '2025-12-01 10:30:00', '2025-12-01 12:30:00', 'LEN_KE_HOACH'),
(66, 'VN403', 10, 3, '2025-12-01 13:00:00', '2025-12-01 15:00:00', 'LEN_KE_HOACH'),
(67, 'VN404', 1, 4, '2025-12-01 15:30:00', '2025-12-01 17:30:00', 'LEN_KE_HOACH'),
(68, 'VN405', 2, 5, '2025-12-01 18:00:00', '2025-12-01 20:00:00', 'LEN_KE_HOACH'),

-- Ngày 15/12/2025
(69, 'VN406', 3, 1, '2025-12-15 07:30:00', '2025-12-15 09:30:00', 'LEN_KE_HOACH'),
(70, 'VN407', 4, 2, '2025-12-15 09:00:00', '2025-12-15 11:00:00', 'LEN_KE_HOACH'),
(71, 'VN408', 6, 3, '2025-12-15 11:30:00', '2025-12-15 13:30:00', 'LEN_KE_HOACH'),
(72, 'VN409', 7, 4, '2025-12-15 14:00:00', '2025-12-15 16:00:00', 'LEN_KE_HOACH'),
(73, 'VN410', 8, 5, '2025-12-15 16:30:00', '2025-12-15 18:30:00', 'LEN_KE_HOACH'),

-- Ngày 25/12/2025 (Giáng sinh)
(74, 'VN411', 9, 1, '2025-12-25 08:00:00', '2025-12-25 10:00:00', 'LEN_KE_HOACH'),
(75, 'VN412', 10, 2, '2025-12-25 10:30:00', '2025-12-25 12:30:00', 'LEN_KE_HOACH'),
(76, 'VN413', 1, 3, '2025-12-25 13:00:00', '2025-12-25 15:00:00', 'LEN_KE_HOACH'),
(77, 'VN414', 2, 4, '2025-12-25 15:30:00', '2025-12-25 17:30:00', 'LEN_KE_HOACH'),
(78, 'VN415', 3, 5, '2025-12-25 18:00:00', '2025-12-25 20:00:00', 'LEN_KE_HOACH'),

-- Ngày 31/12/2025 (Tết dương lịch)
(79, 'VN416', 4, 1, '2025-12-31 08:00:00', '2025-12-31 10:00:00', 'LEN_KE_HOACH'),
(80, 'VN417', 6, 2, '2025-12-31 10:30:00', '2025-12-31 12:30:00', 'LEN_KE_HOACH'),
(81, 'VN418', 7, 3, '2025-12-31 13:00:00', '2025-12-31 15:00:00', 'LEN_KE_HOACH'),
(82, 'VN419', 8, 4, '2025-12-31 15:30:00', '2025-12-31 17:30:00', 'LEN_KE_HOACH'),
(83, 'VN420', 9, 5, '2025-12-31 18:00:00', '2025-12-31 20:00:00', 'LEN_KE_HOACH');

-- =====================================================
-- 7. GIÁ VÉ (FLIGHT PRICES)
-- =====================================================
INSERT IGNORE INTO chuyen_bay_gia_hang (chuyen_bay_id, hang_ghe_id, gia_co_ban, so_ghe_con, hanh_ly_kg, duoc_hoan, phi_doi)
SELECT cb.id as chuyen_bay_id, hg.id as hang_ghe_id, CASE hg.ma WHEN 'ECON' THEN 2500000.00 WHEN 'PREM' THEN 3500000.00 WHEN 'BUSI' THEN 5000000.00 ELSE 2500000.00 END as gia_co_ban, CASE hg.ma WHEN 'ECON' THEN 150 WHEN 'PREM' THEN 50 WHEN 'BUSI' THEN 30 ELSE 150 END as so_ghe_con, CASE hg.ma WHEN 'ECON' THEN 20 WHEN 'PREM' THEN 25 WHEN 'BUSI' THEN 30 ELSE 20 END as hanh_ly_kg, 1 as duoc_hoan, 500000.00 as phi_doi FROM chuyen_bay cb CROSS JOIN hang_ghe hg;

-- =====================================================
-- 8. NGƯỜI DÙNG (USERS)
-- =====================================================
INSERT IGNORE INTO nguoi_dung (id, email, sdt, mat_khau_ma_hoa, ho_ten, trang_thai, vai_tro_id) VALUES 
(1, 'admin@vnairticket.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'HOAT_DONG', 1),
(2, 'admin@vnair.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Admin', 'HOAT_DONG', 1),
(3, 'staff@vnairticket.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Staff', 'HOAT_DONG', 2),
(4, 'staff1@vnair.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị Staff', 'HOAT_DONG', 2),
(5, 'staff2@vnair.com', '0369258147', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Văn Staff', 'HOAT_DONG', 2),
(6, 'customer1@example.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Phạm Văn A', 'HOAT_DONG', 3),
(7, 'customer2@example.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Thị B', 'HOAT_DONG', 3),
(8, 'customer3@example.com', '0369258147', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Văn C', 'HOAT_DONG', 3),
(9, 'customer4@example.com', '0912345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Thị D', 'HOAT_DONG', 3),
(10, 'customer5@example.com', '0923456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hoàng Văn E', 'HOAT_DONG', 3),
(11, 'testuser3@example.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'HOAT_DONG', 3),
(12, 'luutientam1@gmail.com', '0337993739', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lmao', 'HOAT_DONG', 3),
(13, 'luutientam@gmail.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sd', 'HOAT_DONG', 3);

-- =====================================================
-- 9. ĐẶT CHỖ (BOOKINGS) - Dữ liệu mẫu
-- =====================================================
INSERT IGNORE INTO dat_cho (id, pnr, khach_hang_id, trang_thai, tong_tien, created_at) VALUES 
(1, 'VNA001', 6, 'XAC_NHAN', 5000000.00, '2025-10-24 00:44:57'),
(2, 'VNA002', 7, 'XAC_NHAN', 2500000.00, '2025-10-24 00:44:57'),
(3, 'VNA003', 8, 'HUY', 2500000.00, '2025-10-24 00:44:57'),
(4, 'VNABC123', 6, 'HUY', 2500000.00, '2025-10-24 00:37:05'),
(5, 'B105CE', 9, 'XAC_NHAN', 2500000.00, '2025-10-24 01:02:50');

-- =====================================================
-- 10. HÀNH KHÁCH (PASSENGERS)
-- =====================================================
INSERT IGNORE INTO hanh_khach (id, dat_cho_id, loai, ho_ten, gioi_tinh, ngay_sinh, loai_giay_to, so_giay_to, quoc_tich) VALUES 
(1, 1, 'ADT', 'Phạm Văn A', 'M', '1990-01-01', 'CCCD', '123456789', 'Viet Nam'),
(2, 2, 'ADT', 'Nguyễn Thị B', 'F', '1992-02-02', 'CCCD', '987654321', 'Viet Nam'),
(3, 3, 'ADT', 'Trần Văn C', 'M', '1988-03-03', 'CCCD', '456789123', 'Viet Nam'),
(4, 5, 'ADT', 'Lê Thị D', 'F', '1995-04-04', 'CCCD', '789123456', 'Viet Nam');

-- =====================================================
-- 11. VÉ (TICKETS)
-- =====================================================
INSERT IGNORE INTO ve (id, so_ve, dat_cho_id, hanh_khach_id, chuyen_bay_id, hang_ghe_id, trang_thai) VALUES 
(1, 'VNA001-001', 1, 1, 41, 1, 'DA_XUAT'),
(2, 'VNA001-002', 1, 1, 42, 1, 'DA_XUAT'),
(3, 'VNA002-001', 2, 2, 43, 1, 'DA_XUAT'),
(4, 'VNA003-001', 3, 3, 44, 1, 'HUY'),
(5, 'B105CE-001', 5, 4, 41, 1, 'DA_XUAT');

-- =====================================================
-- 12. KHUYẾN MÃI (PROMOTIONS) - Dữ liệu mẫu
-- =====================================================
INSERT IGNORE INTO khuyen_mai (id, ma, kieu, gia_tri, bat_dau, ket_thuc, don_toi_thieu, giam_toi_da, gioi_han_luot, kich_hoat) VALUES 
(1, 'WELCOME10', 'PHAN_TRAM', 10.00, '2025-01-01 00:00:00', '2025-12-31 23:59:59', 0.00, 500000.00, 1000, 1),
(2, 'SUMMER20', 'PHAN_TRAM', 20.00, '2025-06-01 00:00:00', '2025-08-31 23:59:59', 0.00, 1000000.00, 500, 1),
(3, 'VIP15', 'PHAN_TRAM', 15.00, '2025-01-01 00:00:00', '2025-12-31 23:59:59', 0.00, 750000.00, 200, 1);

-- =====================================================
-- 13. GIÁ VÉ MẶC ĐỊNH (DEFAULT PRICES)
-- =====================================================
INSERT IGNORE INTO gia_ve_mac_dinh (hang_ghe_id, gia_co_ban, hanh_ly_kg, duoc_hoan, phi_doi) VALUES 
(1, 2500000.00, 20, 1, 500000.00),
(2, 3500000.00, 25, 1, 500000.00),
(3, 5000000.00, 30, 1, 500000.00);

-- =====================================================
-- 14. THANH TOÁN (PAYMENTS)
-- =====================================================
INSERT IGNORE INTO thanh_toan (id, dat_cho_id, nha_cung_cap, phuong_thuc, so_tien, tien_te, trang_thai, ma_giao_dich, thanh_toan_luc) VALUES 
(1, 1, 'Vietcombank', 'CHUYEN_KHOAN', 5000000.00, 'VND', 'THANH_CONG', 'VCB001', '2025-10-24 00:45:00'),
(2, 2, 'Vietcombank', 'CHUYEN_KHOAN', 2500000.00, 'VND', 'THANH_CONG', 'VCB002', '2025-10-24 00:45:00'),
(3, 3, 'Vietcombank', 'CHUYEN_KHOAN', 2500000.00, 'VND', 'THAT_BAI', 'VCB003', '2025-10-24 00:45:00'),
(4, 4, 'Vietcombank', 'CHUYEN_KHOAN', 2500000.00, 'VND', 'THAT_BAI', 'VCB004', '2025-10-24 00:37:30'),
(5, 5, 'Vietcombank', 'CHUYEN_KHOAN', 2500000.00, 'VND', 'THANH_CONG', 'VCB005', '2025-10-24 01:03:00');

-- =====================================================
-- 15. ĐẶT CHỖ KHUYẾN MÃI (BOOKING PROMOTIONS)
-- =====================================================
INSERT IGNORE INTO dat_cho_khuyen_mai (dat_cho_id, khuyen_mai_id, so_tien_giam) VALUES 
(1, 1, 500000.00),
(2, 1, 250000.00),
(5, 1, 250000.00);

-- =====================================================
-- HOÀN THÀNH
-- =====================================================
-- Tổng cộng: 61 chuyến bay từ 25/10/2025 đến 31/12/2025
-- 2 Admin, 3 Staff, 8 Customer
-- 5 booking mẫu với vé tương ứng
-- 3 khuyến mãi mẫu
-- 5 thanh toán mẫu
-- 3 áp dụng khuyến mãi
-- =====================================================