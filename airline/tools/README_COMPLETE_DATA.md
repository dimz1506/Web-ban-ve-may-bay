# COMPLETE DATA EXPORT - VNAir Ticket System

## 📋 Mô tả
File SQL này chứa **TẤT CẢ** dữ liệu hiện tại của hệ thống VNAir Ticket System, bao gồm:

## 📊 Nội dung dữ liệu

### 🏢 **Dữ liệu cơ bản:**
- **3 vai trò**: Admin, Staff, Customer
- **3 hạng ghế**: Economy, Premium, Business
- **5 sân bay**: HAN, SGN, DAD, CXR, PQC
- **10 tuyến bay**: Tất cả các tuyến nội địa
- **5 tàu bay**: Airbus A320, A321, Boeing 737, 777, A350

### 🛫 **Chuyến bay:**
- **61 chuyến bay** từ 25/10/2025 đến 31/12/2025
- **9 ngày** có chuyến bay
- **Tất cả trạng thái**: LÊN KẾ HOẠCH

### 👥 **Người dùng:**
- **2 Admin**: admin@vnairticket.com, admin@vnair.com
- **3 Staff**: staff@vnairticket.com, staff1@vnair.com, staff2@vnair.com
- **8 Customer**: customer1-5@example.com, testuser3@example.com, luutientam1@gmail.com, luutientam@gmail.com

### 🎫 **Booking mẫu:**
- **5 đặt chỗ** với PNR: VNA001, VNA002, VNA003, VNABC123, B105CE
- **5 vé** tương ứng
- **4 hành khách** mẫu

### 💰 **Giá vé:**
- **ECON**: 2,500,000 VND
- **PREM**: 3,500,000 VND  
- **BUSI**: 5,000,000 VND

### 🎁 **Khuyến mãi:**
- **3 mã giảm giá**: WELCOME10, SUMMER20, VIP15

## 🚀 Cách sử dụng

### 1. **Khôi phục dữ liệu hoàn chỉnh:**
```bash
mysql -u root dat_ve_may_bay < complete_data_export.sql
```

### 2. **Kiểm tra dữ liệu:**
```sql
-- Kiểm tra số lượng chuyến bay
SELECT COUNT(*) as total_flights FROM chuyen_bay;

-- Kiểm tra số lượng người dùng
SELECT COUNT(*) as total_users FROM nguoi_dung;

-- Kiểm tra số lượng booking
SELECT COUNT(*) as total_bookings FROM dat_cho;
```

### 3. **Test đăng nhập:**
- **Admin**: admin@vnairticket.com / password
- **Staff**: staff@vnairticket.com / password  
- **Customer**: customer4@example.com / password

## 📅 Lịch chuyến bay

| Ngày | Số chuyến | Ghi chú |
|------|-----------|---------|
| 25/10/2025 | 8 chuyến | VN201-VN208 |
| 26/10/2025 | 18 chuyến | VN301, VN401, VN501, VN701, VN901, VN601, VN801, VN1001, VN302, VN402, VN502, VN702, VN902, VN602, VN802, VN303, VN1002, VN403 |
| 01/11/2025 | 5 chuyến | VN301-VN305 |
| 02/11/2025 | 5 chuyến | VN306-VN310 |
| 15/11/2025 | 5 chuyến | VN311-VN315 |
| 01/12/2025 | 5 chuyến | VN401-VN405 |
| 15/12/2025 | 5 chuyến | VN406-VN410 |
| 25/12/2025 | 5 chuyến | VN411-VN415 (Giáng sinh) |
| 31/12/2025 | 5 chuyến | VN416-VN420 (Tết dương lịch) |

## 🔗 URL Test

### Đăng nhập:
```
http://localhost/Web-ban-ve-may-bay/airline/public/index.php?p=login
```

### Tìm chuyến bay:
```
http://localhost/Web-ban-ve-may-bay/airline/public/index.php?p=search_results&from=HAN&to=SGN&depart=2025-10-25&pax=1&cabin=ECON&trip_type=oneway
```

### Xem vé đã đặt:
```
http://localhost/Web-ban-ve-may-bay/airline/public/index.php?p=my_tickets
```

### Tra cứu booking:
```
http://localhost/Web-ban-ve-may-bay/airline/public/index.php?p=my_bookings&pnr=B105CE
```

## ⚠️ Lưu ý quan trọng

1. **Mật khẩu**: Tất cả tài khoản đều có mật khẩu `password`
2. **INSERT IGNORE**: File sử dụng INSERT IGNORE để tránh lỗi duplicate
3. **Foreign Key**: Đã sửa tất cả tuyen_bay_id để khớp với database
4. **Cấu trúc bảng**: Đã cập nhật theo schema thực tế (thanh_toan, etc.)
5. **Test thành công**: PNR B105CE đã được đặt thành công
6. **Hệ thống hoạt động**: Đặt vé, thanh toán, tạo booking đều OK

## 📝 Ghi chú

- File này được tạo tự động từ dữ liệu hiện tại
- **INSERT IGNORE**: Có thể chạy nhiều lần mà không gặp lỗi duplicate
- **Schema đúng**: Đã cập nhật theo cấu trúc database thực tế
- Có thể sử dụng để khôi phục hệ thống về trạng thái hiện tại
- Bao gồm tất cả dữ liệu cần thiết để test đầy đủ các chức năng

---
**Tạo lúc**: 2025-10-24  
**Phiên bản**: 2.0 (Đã sửa lỗi)  
**Trạng thái**: Hoàn thành ✅
