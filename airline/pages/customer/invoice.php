<?php
// pages/customer/invoice.php — Xuất hóa đơn và xác nhận vé
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

$user = me();
$pnr = $_GET['pnr'] ?? '';

if (empty($pnr)) {
    header('Location: index.php?p=my_tickets');
    exit;
}

// Lấy thông tin đặt chỗ
$stmt = db()->prepare("
    SELECT dc.*, u.ho_ten, u.email, u.sdt 
    FROM dat_cho dc 
    JOIN nguoi_dung u ON u.id = dc.khach_hang_id 
    WHERE dc.pnr = ? AND dc.khach_hang_id = ?
");
$stmt->execute([$pnr, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: index.php?p=my_tickets');
    exit;
}

// Lấy thông tin vé và hành khách
$stmt = db()->prepare("
    SELECT v.*, hk.ho_ten, hk.loai, hk.gioi_tinh, hk.ngay_sinh,
           cb.so_hieu, cb.gio_di, cb.gio_den,
           sb1.ten as di_ten, sb2.ten as den_ten,
           hg.ten as hang_ghe_ten
    FROM ve v
    JOIN hanh_khach hk ON hk.id = v.hanh_khach_id
    JOIN chuyen_bay cb ON cb.id = v.chuyen_bay_id
    JOIN tuyen_bay tb ON tb.id = cb.tuyen_bay_id
    JOIN san_bay sb1 ON sb1.ma = tb.di
    JOIN san_bay sb2 ON sb2.ma = tb.den
    JOIN hang_ghe hg ON hg.id = v.hang_ghe_id
    WHERE v.dat_cho_id = ?
    ORDER BY v.id
");
$stmt->execute([$booking['id']]);
$tickets = $stmt->fetchAll();

// Lấy thông tin thanh toán
$stmt = db()->prepare("
    SELECT * FROM thanh_toan 
    WHERE dat_cho_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$booking['id']]);
$payment = $stmt->fetch();

// Tính tổng tiền
$totalAmount = 0;
foreach ($tickets as $ticket) {
    // Giả lập giá vé (trong thực tế sẽ lấy từ bảng chuyen_bay_gia_hang)
    $ticketPrice = 2500000; // Giá mẫu
    $totalAmount += $ticketPrice;
}

// Nếu có tham số print, hiển thị để in
$isPrint = isset($_GET['print']);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $isPrint ? 'Hóa đơn' : 'Xác nhận vé' ?> | VNAir Ticket</title>
    <link rel="stylesheet" href="assets/home.css">
    <style>
        :root {
            --primary: #0b4f7d;
            --primary-light: #0a6aa7;
            --accent: #f5c242;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --white: #ffffff;
            --border-radius: 12px;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?= $isPrint ? 'white' : 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)' ?>;
            color: var(--gray-800);
            line-height: 1.6;
        }

        .container {
            max-width: <?= $isPrint ? '800px' : '1200px' ?>;
            margin: 0 auto;
            padding: <?= $isPrint ? '0' : '0 20px' ?>;
        }

        /* Header */
        .header {
            background: <?= $isPrint ? 'white' : 'linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%)' ?>;
            color: <?= $isPrint ? 'var(--gray-900)' : 'white' ?>;
            padding: 20px 0;
            <?= $isPrint ? '' : 'box-shadow: var(--shadow-lg);' ?>
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 700;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: <?= $isPrint ? 'var(--primary)' : 'rgba(255, 255, 255, 0.2)' ?>;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .nav-menu {
            display: <?= $isPrint ? 'none' : 'flex' ?>;
            gap: 30px;
            align-items: center;
        }

        .nav-menu a {
            color: <?= $isPrint ? 'var(--gray-700)' : 'white' ?>;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .nav-menu a:hover {
            background: <?= $isPrint ? 'var(--gray-100)' : 'rgba(255, 255, 255, 0.1)' ?>;
        }

        /* Main Content */
        .main-content {
            padding: <?= $isPrint ? '0' : '40px 0' ?>;
        }

        .invoice-container {
            background: var(--white);
            border-radius: <?= $isPrint ? '0' : 'var(--border-radius)' ?>;
            padding: 40px;
            box-shadow: <?= $isPrint ? 'none' : 'var(--shadow)' ?>;
            margin: <?= $isPrint ? '0' : '20px 0' ?>;
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid var(--primary);
        }

        .invoice-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin: 0 0 10px 0;
        }

        .invoice-subtitle {
            font-size: 16px;
            color: var(--gray-600);
            margin: 0;
        }

        .booking-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .info-section {
            background: var(--gray-50);
            padding: 25px;
            border-radius: 8px;
        }

        .info-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 600;
            color: var(--gray-700);
        }

        .info-value {
            color: var(--gray-800);
        }

        .pnr-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }

        .pnr-code {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .pnr-label {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Tickets */
        .tickets-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-200);
        }

        .ticket {
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
            background: var(--white);
        }

        .ticket-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ticket-number {
            font-size: 20px;
            font-weight: 700;
        }

        .ticket-status {
            background: var(--success);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .ticket-body {
            padding: 25px;
        }

        .flight-info {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 30px;
            align-items: center;
            margin-bottom: 25px;
        }

        .airport-info {
            text-align: center;
        }

        .airport-code {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .airport-name {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 5px;
        }

        .flight-time {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .flight-details {
            text-align: center;
            padding: 15px;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .flight-number {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .flight-duration {
            font-size: 14px;
            color: var(--gray-600);
        }

        .passenger-info {
            background: var(--gray-50);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .passenger-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 10px;
        }

        .passenger-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .passenger-detail {
            font-size: 14px;
        }

        .passenger-detail .label {
            font-weight: 600;
            color: var(--gray-700);
        }

        .passenger-detail .value {
            color: var(--gray-800);
        }

        /* Payment Summary */
        .payment-summary {
            background: var(--gray-50);
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .summary-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 20px 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
        }

        .summary-row.total {
            font-weight: 700;
            font-size: 18px;
            color: var(--primary);
            padding-top: 15px;
            border-top: 2px solid var(--primary);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }
            
            .header {
                background: white;
                color: var(--gray-900);
                box-shadow: none;
            }
            
            .action-buttons {
                display: none;
            }
            
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .booking-info {
                grid-template-columns: 1fr;
            }
            
            .flight-info {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">✈</div>
                    <span>VNAir Ticket</span>
                </div>
                
                <?php if (!$isPrint): ?>
                <nav class="nav-menu">
                    <a href="index.php?p=customer">Trang chủ</a>
                    <a href="index.php?p=my_tickets">Vé của tôi</a>
                    <a href="index.php?p=contact">Hỗ trợ</a>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="invoice-container">
                <div class="invoice-header">
                    <h1 class="invoice-title"><?= $isPrint ? 'HÓA ĐƠN' : 'XÁC NHẬN ĐẶT VÉ' ?></h1>
                    <p class="invoice-subtitle">Vietnam Airlines - VNAir Ticket</p>
                </div>

                <div class="pnr-badge">
                    <div class="pnr-code"><?= $booking['pnr'] ?></div>
                    <div class="pnr-label">Mã đặt chỗ (PNR)</div>
                </div>

                <div class="booking-info">
                    <div class="info-section">
                        <h3 class="info-title">Thông tin khách hàng</h3>
                        <div class="info-row">
                            <span class="info-label">Họ tên:</span>
                            <span class="info-value"><?= htmlspecialchars($booking['ho_ten']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?= htmlspecialchars($booking['email']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Số điện thoại:</span>
                            <span class="info-value"><?= htmlspecialchars($booking['sdt'] ?? 'Chưa cập nhật') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Mã khách hàng:</span>
                            <span class="info-value"><?= $user['id'] ?></span>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3 class="info-title">Thông tin đặt chỗ</h3>
                        <div class="info-row">
                            <span class="info-label">Ngày đặt:</span>
                            <span class="info-value"><?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Trạng thái:</span>
                            <span class="info-value"><?= $booking['trang_thai'] === 'XAC_NHAN' ? 'Đã xác nhận' : 'Chờ thanh toán' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kênh đặt:</span>
                            <span class="info-value">Website</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tổng tiền:</span>
                            <span class="info-value"><?= number_format($booking['tong_tien']) ?> VND</span>
                        </div>
                    </div>
                </div>

                <div class="tickets-section">
                    <h2 class="section-title">Thông tin vé</h2>
                    
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket">
                            <div class="ticket-header">
                                <span class="ticket-number"><?= $ticket['so_ve'] ?></span>
                                <span class="ticket-status">Đã xuất vé</span>
                            </div>
                            
                            <div class="ticket-body">
                                <div class="flight-info">
                                    <div class="airport-info">
                                        <div class="airport-code"><?= substr($ticket['di_ten'], 0, 3) ?></div>
                                        <div class="airport-name"><?= $ticket['di_ten'] ?></div>
                                        <div class="flight-time"><?= date('H:i', strtotime($ticket['gio_di'])) ?></div>
                                    </div>
                                    
                                    <div class="flight-details">
                                        <div class="flight-number"><?= $ticket['so_hieu'] ?></div>
                                        <div class="flight-duration">2h 30m</div>
                                    </div>
                                    
                                    <div class="airport-info">
                                        <div class="airport-code"><?= substr($ticket['den_ten'], 0, 3) ?></div>
                                        <div class="airport-name"><?= $ticket['den_ten'] ?></div>
                                        <div class="flight-time"><?= date('H:i', strtotime($ticket['gio_den'])) ?></div>
                                    </div>
                                </div>
                                
                                <div class="passenger-info">
                                    <div class="passenger-name"><?= htmlspecialchars($ticket['ho_ten']) ?></div>
                                    <div class="passenger-details">
                                        <div class="passenger-detail">
                                            <div class="label">Loại hành khách:</div>
                                            <div class="value"><?= $ticket['loai'] === 'ADT' ? 'Người lớn' : ($ticket['loai'] === 'CHD' ? 'Trẻ em' : 'Em bé') ?></div>
                                        </div>
                                        <div class="passenger-detail">
                                            <div class="label">Hạng ghế:</div>
                                            <div class="value"><?= $ticket['hang_ghe_ten'] ?></div>
                                        </div>
                                        <div class="passenger-detail">
                                            <div class="label">Số ghế:</div>
                                            <div class="value"><?= $ticket['so_ghe'] ?? 'Chưa phân' ?></div>
                                        </div>
                                        <div class="passenger-detail">
                                            <div class="label">Giới tính:</div>
                                            <div class="value"><?= $ticket['gioi_tinh'] === 'M' ? 'Nam' : ($ticket['gioi_tinh'] === 'F' ? 'Nữ' : 'Khác') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($payment): ?>
                <div class="payment-summary">
                    <h3 class="summary-title">Thông tin thanh toán</h3>
                    <div class="info-row">
                        <span class="info-label">Phương thức:</span>
                        <span class="info-value"><?= $payment['phuong_thuc'] === 'THE' ? 'Thẻ tín dụng' : ($payment['phuong_thuc'] === 'VI' ? 'Ví điện tử' : 'Chuyển khoản') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nhà cung cấp:</span>
                        <span class="info-value"><?= htmlspecialchars($payment['nha_cung_cap']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mã giao dịch:</span>
                        <span class="info-value"><?= htmlspecialchars($payment['ma_giao_dich']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Thời gian thanh toán:</span>
                        <span class="info-value"><?= date('d/m/Y H:i', strtotime($payment['thanh_toan_luc'])) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Tổng thanh toán:</span>
                        <span><?= number_format($payment['so_tien']) ?> VND</span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$isPrint): ?>
                <div class="action-buttons">
                    <a href="?pnr=<?= $pnr ?>&print=1" class="btn btn-primary" target="_blank">
                        <span>🖨️</span>
                        In hóa đơn
                    </a>
                    <a href="index.php?p=my_tickets" class="btn btn-secondary">
                        <span>📋</span>
                        Quay lại danh sách vé
                    </a>
                    <button onclick="sendEmail()" class="btn btn-success">
                        <span>📧</span>
                        Gửi email
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php if (!$isPrint): ?>
    <script>
        function sendEmail() {
            if (confirm('Gửi lại email xác nhận vé?')) {
                // Trong thực tế sẽ gọi API để gửi email
                alert('Email đã được gửi thành công!');
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>