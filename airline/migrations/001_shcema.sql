-- CREATE DATABASE IF NOT EXISTS dat_ve_may_bay
--   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE dat_ve_may_bay;

-- CREATE TABLE vai_tro (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   ma  VARCHAR(32)  NOT NULL UNIQUE,     -- ADMIN, STAFF, CUSTOMER
--   ten VARCHAR(64)  NOT NULL
-- );

-- CREATE TABLE nguoi_dung (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   email VARCHAR(190) NOT NULL UNIQUE,
--   sdt VARCHAR(32),
--   mat_khau_ma_hoa VARCHAR(255) NOT NULL,
--   ho_ten VARCHAR(120) NOT NULL,
--   trang_thai ENUM('HOAT_DONG','KHOA','XOA') NOT NULL DEFAULT 'HOAT_DONG',
--   vai_tro_id BIGINT UNSIGNED NOT NULL,
--   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--   dang_nhap_gan_nhat TIMESTAMP NULL,
--   FOREIGN KEY (vai_tro_id) REFERENCES vai_tro(id)
-- );

-- CREATE TABLE san_bay (
--   ma CHAR(3) PRIMARY KEY,
--   ten VARCHAR(120) NOT NULL,
--   thanh_pho VARCHAR(80),
--   quoc_gia VARCHAR(80),
--   mui_gio VARCHAR(64) NOT NULL
-- );

-- CREATE TABLE tau_bay (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   so_dang_ba VARCHAR(16) UNIQUE, 
--   dong_may_bay VARCHAR(64) NOT NULL
-- );

-- CREATE TABLE hang_ghe (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   ma  VARCHAR(16) NOT NULL UNIQUE,       -- ECON, PREM, BUSI
--   ten VARCHAR(64) NOT NULL
-- );

-- CREATE TABLE tuyen_bay (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   ma_tuyen VARCHAR(16) UNIQUE,
--   di  CHAR(3) NOT NULL,
--   den CHAR(3) NOT NULL,
--   khoang_cach_km INT UNSIGNED NULL,
--   UNIQUE (di, den, ma_tuyen),
--   FOREIGN KEY (di)  REFERENCES san_bay(ma),
--   FOREIGN KEY (den) REFERENCES san_bay(ma)
-- );

-- CREATE TABLE chuyen_bay (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   so_hieu VARCHAR(10) NOT NULL,
--   tuyen_bay_id BIGINT UNSIGNED NOT NULL,
--   tau_bay_id BIGINT UNSIGNED NULL,
--   gio_di DATETIME NOT NULL,
--   gio_den DATETIME NOT NULL,
--   trang_thai ENUM('LEN_KE_HOACH','HUY','TRE','DA_CAT_CANH','DA_HA_CANH')
--             NOT NULL DEFAULT 'LEN_KE_HOACH',
--   tien_te CHAR(3) NOT NULL DEFAULT 'VND',
--   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--   INDEX (so_hieu, gio_di),
--   FOREIGN KEY (tuyen_bay_id) REFERENCES tuyen_bay(id),
--   FOREIGN KEY (tau_bay_id)   REFERENCES tau_bay(id)
-- );

-- CREATE TABLE chuyen_bay_gia_hang (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   chuyen_bay_id BIGINT UNSIGNED NOT NULL,
--   hang_ghe_id BIGINT UNSIGNED NOT NULL,
--   gia_co_ban DECIMAL(12,2) NOT NULL,
--   so_ghe_con INT UNSIGNED NOT NULL,
--   hanh_ly_kg INT UNSIGNED DEFAULT 0,
--   duoc_hoan TINYINT(1) NOT NULL DEFAULT 0,
--   phi_doi DECIMAL(12,2) DEFAULT 0.00,
--   UNIQUE (chuyen_bay_id, hang_ghe_id),
--   FOREIGN KEY (chuyen_bay_id) REFERENCES chuyen_bay(id) ON DELETE CASCADE,
--   FOREIGN KEY (hang_ghe_id)    REFERENCES hang_ghe(id)
-- );

-- CREATE TABLE khuyen_mai (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   ma VARCHAR(40) NOT NULL UNIQUE,
--   ten VARCHAR(120) NOT NULL,
--   kieu ENUM('PHAN_TRAM','SO_TIEN') NOT NULL,
--   gia_tri DECIMAL(12,2) NOT NULL,
--   bat_dau DATETIME NOT NULL,
--   ket_thuc DATETIME NOT NULL,
--   don_toi_thieu DECIMAL(12,2) DEFAULT 0.00,
--   giam_toi_da DECIMAL(12,2) DEFAULT NULL,
--   gioi_han_luot INT UNSIGNED DEFAULT NULL,
--   da_su_dung INT UNSIGNED NOT NULL DEFAULT 0,
--   kich_hoat TINYINT(1) NOT NULL DEFAULT 1
-- );

-- CREATE TABLE dat_cho (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   pnr VARCHAR(10) NOT NULL UNIQUE,
--   khach_hang_id BIGINT UNSIGNED NOT NULL,
--   trang_thai ENUM('CHO_THANH_TOAN','XAC_NHAN','HUY','DA_DOI','HET_HAN')
--             NOT NULL DEFAULT 'CHO_THANH_TOAN',
--   kenh ENUM('WEB','MOBILE','NHAN_VIEN') NOT NULL DEFAULT 'WEB',
--   tong_tien DECIMAL(12,2) NOT NULL DEFAULT 0.00,
--   tien_te CHAR(3) NOT NULL DEFAULT 'VND',
--   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--   xac_nhan_luc TIMESTAMP NULL,
--   FOREIGN KEY (khach_hang_id) REFERENCES nguoi_dung(id)
-- );

-- CREATE TABLE dat_cho_khuyen_mai (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   dat_cho_id BIGINT UNSIGNED NOT NULL,
--   khuyen_mai_id BIGINT UNSIGNED NOT NULL,
--   so_tien_giam DECIMAL(12,2) NOT NULL,
--   UNIQUE (dat_cho_id, khuyen_mai_id),
--   FOREIGN KEY (dat_cho_id) REFERENCES dat_cho(id) ON DELETE CASCADE,
--   FOREIGN KEY (khuyen_mai_id) REFERENCES khuyen_mai(id)
-- );

-- CREATE TABLE hanh_khach (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   dat_cho_id BIGINT UNSIGNED NOT NULL,
--   loai ENUM('ADT','CHD','INF') NOT NULL,
--   ho_ten VARCHAR(120) NOT NULL,
--   gioi_tinh ENUM('M','F','X') DEFAULT 'X',
--   ngay_sinh DATE NULL,
--   loai_giay_to VARCHAR(32),
--   so_giay_to VARCHAR(64),
--   quoc_tich VARCHAR(64),
--   FOREIGN KEY (dat_cho_id) REFERENCES dat_cho(id) ON DELETE CASCADE
-- );

-- CREATE TABLE ve (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   so_ve VARCHAR(20) NOT NULL UNIQUE,
--   dat_cho_id BIGINT UNSIGNED NOT NULL,
--   hanh_khach_id BIGINT UNSIGNED NOT NULL,
--   chuyen_bay_id BIGINT UNSIGNED NOT NULL,
--   hang_ghe_id BIGINT UNSIGNED NOT NULL,
--   so_ghe VARCHAR(6) NULL,
--   trang_thai ENUM('DA_XUAT','DA_DI','HUY','HOAN_TIEN','DA_DOI')
--             NOT NULL DEFAULT 'DA_XUAT',
--   url_ve VARCHAR(255) NULL,
--   phat_hanh_boi BIGINT UNSIGNED NULL,
--   phat_hanh_luc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--   UNIQUE (hanh_khach_id, chuyen_bay_id),
--   FOREIGN KEY (dat_cho_id)   REFERENCES dat_cho(id) ON DELETE CASCADE,
--   FOREIGN KEY (hanh_khach_id) REFERENCES hanh_khach(id) ON DELETE CASCADE,
--   FOREIGN KEY (chuyen_bay_id) REFERENCES chuyen_bay(id),
--   FOREIGN KEY (hang_ghe_id)   REFERENCES hang_ghe(id),
--   FOREIGN KEY (phat_hanh_boi) REFERENCES nguoi_dung(id)
-- );

-- CREATE TABLE thanh_toan (
--   id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--   dat_cho_id BIGINT UNSIGNED NOT NULL,
--   nha_cung_cap VARCHAR(40) NOT NULL,
--   phuong_thuc VARCHAR(40) NOT NULL,
--   so_tien DECIMAL(12,2) NOT NULL,
--   tien_te CHAR(3) NOT NULL DEFAULT 'VND',
--   trang_thai ENUM('CHO','THANH_CONG','THAT_BAI','DA_HOAN','HOAN_MOT_PHAN')
--             NOT NULL DEFAULT 'CHO',
--   ma_giao_dich VARCHAR(80) UNIQUE,
--   thanh_toan_luc TIMESTAMP NULL,
--   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--   FOREIGN KEY (dat_cho_id) REFERENCES dat_cho(id) ON DELETE CASCADE
-- );

-- -- Seed co ban
-- INSERT INTO vai_tro(ma,ten) VALUES ('ADMIN','Quan tri'),('STAFF','Nhan vien'),('CUSTOMER','Khach hang');
-- INSERT INTO hang_ghe(ma,ten) VALUES ('ECON','Pho thong'),('PREM','Pho thong dac biet'),('BUSI','Thuong gia');

-- -- Mot so san bay pho bien
-- INSERT INTO san_bay(ma,ten,thanh_pho,quoc_gia,mui_gio) VALUES
-- ('HAN','Noi Bai','Ha Noi','Viet Nam','Asia/Ho_Chi_Minh'),
-- ('SGN','Tan Son Nhat','Ho Chi Minh City','Viet Nam','Asia/Ho_Chi_Minh'),
-- ('DAD','Da Nang','Da Nang','Viet Nam','Asia/Ho_Chi_Minh');

-- -- Tuyen mau
-- INSERT INTO tuyen_bay(ma_tuyen,di,den,khoang_cach_km) VALUES
-- ('HAN-SGN','HAN','SGN',1150),
-- ('HAN-DAD','HAN','DAD',600),
-- ('SGN-DAD','SGN','DAD',610);


-- ALTER TABLE khuyen_mai
-- DROP COLUMN ten;

-- ALTER TABLE hang_ghe 
-- ADD COLUMN mo_ta VARCHAR(255) NULL,
-- ADD COLUMN tien_ich VARCHAR(255) NULL,
-- ADD COLUMN mau_sac VARCHAR(20) NULL,
-- ADD COLUMN thu_tu INT DEFAULT 0;

-- CREATE TABLE gia_ve_mac_dinh (
--  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--  tuyen_bay_id BIGINT UNSIGNED NOT NULL,
--  hang_ghe_id BIGINT UNSIGNED NOT NULL,
--  gia_co_ban DECIMAL(12,2) NOT NULL,
--  hanh_ly_kg INT UNSIGNED DEFAULT 0,
--  duoc_hoan TINYINT(1) NOT NULL DEFAULT 0,
--  phi_doi DECIMAL(12,2) DEFAULT 0.00,
--  UNIQUE (tuyen_bay_id, hang_ghe_id),
--  FOREIGN KEY (tuyen_bay_id) REFERENCES tuyen_bay(id) ON DELETE CASCADE,
--  FOREIGN KEY (hang_ghe_id) REFERENCES hang_ghe(id)
-- );
