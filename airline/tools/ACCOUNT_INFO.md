# Thông tin tài khoản test - VNAir Ticket System

## 🔐 Thông tin đăng nhập

**Mật khẩu cho tất cả tài khoản: `password`**

### 👨‍💼 ADMIN (Quản trị)
| Email | Họ tên | Vai trò | Số đặt chỗ | Số vé |
|-------|--------|---------|------------|-------|
| admin@vnairticket.com | Administrator | Quản trị | 0 | 0 |
| admin@vnair.com | Nguyễn Văn Admin | Quản trị | 0 | 0 |

### 👨‍💻 STAFF (Nhân viên)
| Email | Họ tên | SĐT | Vai trò | Số đặt chỗ | Số vé |
|-------|--------|-----|---------|------------|-------|
| staff@vnairticket.com | Nguyễn Văn Staff | 0987654321 | Nhân viên | 1 | 1 |
| staff1@vnair.com | Trần Thị Staff | 0987654321 | Nhân viên | 0 | 0 |
| staff2@vnair.com | Lê Văn Staff | 0369258147 | Nhân viên | 0 | 0 |

### 👤 CUSTOMER (Khách hàng)
| Email | Họ tên | SĐT | Vai trò | Số đặt chỗ | Số vé |
|-------|--------|-----|---------|------------|-------|
| customer1@example.com | Phạm Văn A | 0123456789 | Khách hàng | 2 | 2 |
| customer2@example.com | Nguyễn Thị B | 0987654321 | Khách hàng | 1 | 1 |
| customer3@example.com | Trần Văn C | 0369258147 | Khách hàng | 1 | 1 |
| customer4@example.com | Lê Thị D | 0912345678 | Khách hàng | 0 | 0 |
| customer5@example.com | Hoàng Văn E | 0923456789 | Khách hàng | 0 | 0 |
| testuser3@example.com | Test User | 0123456789 | Khách hàng | 0 | 0 |
| luutientam1@gmail.com | lmao | 0337993739 | Khách hàng | 0 | 0 |
| luutientam@gmail.com | sd | - | Khách hàng | 0 | 0 |

## 🎯 Các tài khoản có dữ liệu để test:

### Customer có vé:
- **customer1@example.com** - 2 vé (PNR: VNA001)
- **customer2@example.com** - 1 vé (PNR: VNA002) 
- **customer3@example.com** - 1 vé (PNR: VNA003)
- **customer4@example.com** - 1 vé (PNR: B105CE) - ✅ **Mới đặt thành công**

### Customer không có vé (để test đăng ký mới):
- **customer5@example.com**
- **testuser3@example.com**
- **luutientam1@gmail.com**
- **luutientam@gmail.com**

## 🛫 Chuyến bay có sẵn (từ 24/10/2025):

### 📅 **Theo ngày:**
- **25/10/2025**: 8 chuyến bay (VN201-VN208)
- **26/10/2025**: 18 chuyến bay (VN301, VN401, VN501, VN701, VN901, VN601, VN801, VN1001, VN302, VN402, VN502, VN702, VN902, VN602, VN802, VN303, VN1002, VN403)
- **01/11/2025**: 5 chuyến bay (VN301-VN305)
- **02/11/2025**: 5 chuyến bay (VN306-VN310)
- **15/11/2025**: 5 chuyến bay (VN311-VN315)
- **01/12/2025**: 5 chuyến bay (VN401-VN405)
- **15/12/2025**: 5 chuyến bay (VN406-VN410)
- **25/12/2025**: 5 chuyến bay (VN411-VN415) - Giáng sinh
- **31/12/2025**: 5 chuyến bay (VN416-VN420) - Tết dương lịch

### 🎯 **Tổng cộng: 61 chuyến bay**

### 🛫 **Tuyến bay:**
- **HAN ↔ SGN**: Nhiều chuyến bay
- **HAN ↔ DAD**: Nhiều chuyến bay
- **SGN ↔ CXR**: Nhiều chuyến bay  
- **SGN ↔ PQC**: Nhiều chuyến bay

## 💰 Giá vé:
- **ECON**: 2,500,000 VND
- **PREM**: 3,500,000 VND
- **BUSI**: 5,000,000 VND

## 🔗 URL quan trọng:

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

## 📝 Ghi chú quan trọng:

### ✅ **Đã hoàn thành:**
- **Xóa dữ liệu cũ**: Tất cả chuyến bay trước 24/10/2025 đã được xóa
- **Thêm dữ liệu mới**: 61 chuyến bay từ 25/10/2025 đến 31/12/2025
- **Test đặt vé thành công**: PNR B105CE cho customer4@example.com
- **Hệ thống hoạt động**: Đặt vé, thanh toán, tạo booking đều OK

### 🔐 **Thông tin đăng nhập:**
- Tất cả tài khoản đều có mật khẩu: `password`
- Tài khoản có dữ liệu để test các chức năng xem vé, thanh toán
- Tài khoản không có dữ liệu để test đăng ký mới và đặt vé từ đầu

### 🎯 **Để test:**
1. **Đăng nhập** với customer4@example.com / password
2. **Xem vé đã đặt** tại my_tickets
3. **Tra cứu booking** với PNR: B105CE
4. **Đặt vé mới** từ ngày 25/10/2025 trở đi
