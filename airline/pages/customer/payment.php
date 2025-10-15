<?php
// pages/customer/payment.php — Trang thanh toán
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

$user = me();
$errors = [];
$success = '';

// Lấy dữ liệu đặt chỗ từ database hoặc session
$bookingData = null;
$pnr = $_GET['pnr'] ?? '';

if ($pnr) {
    // Lấy thông tin đặt chỗ từ database
    $stmt = db()->prepare("SELECT * FROM dat_cho WHERE pnr = ? AND khach_hang_id = ?");
    $stmt->execute([$pnr, $user['id']]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        // Lấy thông tin vé và hành khách
        $stmt = db()->prepare("
            SELECT v.*, hk.ho_ten, hk.loai,
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
        ");
        $stmt->execute([$booking['id']]);
        $tickets = $stmt->fetchAll();
        
        if ($tickets) {
            $bookingData = [
                'pnr' => $booking['pnr'],
                'flights' => [],
                'tong_tien' => $booking['tong_tien'],
                'khuyen_mai' => 0,
                'thanh_toan' => $booking['tong_tien']
            ];
            
            foreach ($tickets as $ticket) {
                $bookingData['flights'][] = [
                    'so_hieu' => $ticket['so_hieu'],
                    'di' => substr($ticket['di_ten'], 0, 3),
                    'den' => substr($ticket['den_ten'], 0, 3),
                    'gio_di' => $ticket['gio_di'],
                    'gio_den' => $ticket['gio_den'],
                    'hang_ghe' => $ticket['hang_ghe_ten'],
                    'gia' => 2500000, // Giá mẫu
                    'hanh_khach' => [
                        ['ho_ten' => $ticket['ho_ten'], 'loai' => $ticket['loai']]
                    ]
                ];
            }
        }
    }
}

// Nếu không có dữ liệu, redirect về trang tìm kiếm
if (!$bookingData) {
    header('Location: index.php?p=book_search');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    
    $phuong_thuc = $_POST['phuong_thuc'] ?? '';
    $nha_cung_cap = $_POST['nha_cung_cap'] ?? '';
    $so_the = $_POST['so_the'] ?? '';
    $ngay_het_han = $_POST['ngay_het_han'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    $ten_chu_the = $_POST['ten_chu_the'] ?? '';
    
    // Validation
    if (empty($phuong_thuc)) $errors[] = 'Vui lòng chọn phương thức thanh toán';
    if (empty($nha_cung_cap)) $errors[] = 'Vui lòng chọn nhà cung cấp';
    
    if ($phuong_thuc === 'THE') {
        if (empty($so_the)) $errors[] = 'Vui lòng nhập số thẻ';
        if (empty($ngay_het_han)) $errors[] = 'Vui lòng nhập ngày hết hạn';
        if (empty($cvv)) $errors[] = 'Vui lòng nhập mã CVV';
        if (empty($ten_chu_the)) $errors[] = 'Vui lòng nhập tên chủ thẻ';
        
        // Validate card number (basic)
        if (!empty($so_the) && !preg_match('/^\d{16}$/', str_replace(' ', '', $so_the))) {
            $errors[] = 'Số thẻ không hợp lệ';
        }
    }
    
    if (empty($errors)) {
        try {
            // Tạo đặt chỗ trong database
            $stmt = db()->prepare("INSERT INTO dat_cho (pnr, khach_hang_id, trang_thai, tong_tien, tien_te) VALUES (?, ?, 'XAC_NHAN', ?, 'VND')");
            $stmt->execute([$bookingData['pnr'], $user['id'], $bookingData['thanh_toan']]);
            $dat_cho_id = db()->lastInsertId();
            
            // Tạo hành khách
            foreach ($bookingData['flights'] as $flight) {
                foreach ($flight['hanh_khach'] as $passenger) {
                    $stmt = db()->prepare("INSERT INTO hanh_khach (dat_cho_id, loai, ho_ten, gioi_tinh) VALUES (?, ?, ?, 'X')");
                    $stmt->execute([$dat_cho_id, $passenger['loai'], $passenger['ho_ten']]);
                    $hanh_khach_id = db()->lastInsertId();
                    
                    // Tạo vé
                    $so_ve = 'VN' . strtoupper(substr(md5(time() . $hanh_khach_id), 0, 8));
                    $stmt = db()->prepare("INSERT INTO ve (so_ve, dat_cho_id, hanh_khach_id, chuyen_bay_id, hang_ghe_id, trang_thai) VALUES (?, ?, ?, 1, 1, 'DA_XUAT')");
                    $stmt->execute([$so_ve, $dat_cho_id, $hanh_khach_id, 1]);
                }
            }
            
            // Tạo thanh toán
            $ma_giao_dich = 'TXN' . strtoupper(substr(md5(time()), 0, 10));
            $stmt = db()->prepare("INSERT INTO thanh_toan (dat_cho_id, nha_cung_cap, phuong_thuc, so_tien, tien_te, trang_thai, ma_giao_dich, thanh_toan_luc) VALUES (?, ?, ?, ?, 'VND', 'THANH_CONG', ?, NOW())");
            $stmt->execute([$dat_cho_id, $nha_cung_cap, $phuong_thuc, $bookingData['thanh_toan'], $ma_giao_dich]);
            
            // Gửi email xác nhận (giả lập)
            send_confirmation_email(
                $user['email'],
                'Xác nhận đặt vé thành công - ' . $bookingData['pnr'],
                "Chào " . $user['ho_ten'] . ",\n\nBạn đã đặt vé thành công!\nPNR: " . $bookingData['pnr'] . "\nTổng tiền: " . number_format($bookingData['thanh_toan']) . " VND\n\nCảm ơn bạn đã sử dụng dịch vụ VNAir Ticket!"
            );
            
            $success = 'Thanh toán thành công! Vé đã được gửi về email của bạn.';
            
        } catch (Exception $e) {
            $errors[] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Thanh toán | VNAir Ticket</title>
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
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--gray-800);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 20px 0;
            box-shadow: var(--shadow-lg);
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
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Progress Steps */
        .progress-steps {
            background: var(--white);
            padding: 20px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .step.active {
            background: var(--primary);
            color: white;
        }

        .step.completed {
            background: var(--success);
            color: white;
        }

        .step-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--gray-300);
            color: var(--gray-600);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        .step.active .step-number,
        .step.completed .step-number {
            background: white;
            color: var(--primary);
        }

        .step.completed .step-number {
            color: var(--success);
        }

        /* Main Content */
        .main-content {
            padding: 40px 0;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        /* Booking Summary */
        .booking-summary {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-100);
        }

        .pnr-info {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .pnr-code {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .pnr-label {
            font-size: 12px;
            opacity: 0.9;
        }

        .flight-item {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .flight-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .flight-number {
            font-weight: 600;
            color: var(--primary);
        }

        .flight-route {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .airport {
            text-align: center;
        }

        .airport-code {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .airport-name {
            font-size: 12px;
            color: var(--gray-600);
        }

        .flight-time {
            font-size: 14px;
            color: var(--gray-700);
            font-weight: 500;
        }

        .flight-duration {
            text-align: center;
            font-size: 12px;
            color: var(--gray-500);
        }

        .passenger-list {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-200);
        }

        .passenger-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .passenger-name {
            font-weight: 500;
        }

        .passenger-type {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        /* Payment Summary */
        .payment-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid var(--gray-200);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-row.total {
            font-weight: 700;
            font-size: 18px;
            color: var(--primary);
            padding-top: 10px;
            border-top: 1px solid var(--gray-200);
        }

        /* Payment Form */
        .payment-form {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .form-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 30px 0;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .payment-method {
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .payment-method:hover {
            border-color: var(--primary);
            background: rgba(11, 79, 125, 0.05);
        }

        .payment-method.selected {
            border-color: var(--primary);
            background: rgba(11, 79, 125, 0.1);
        }

        .payment-method input[type="radio"] {
            margin-bottom: 10px;
        }

        .method-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .method-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .method-desc {
            font-size: 12px;
            color: var(--gray-600);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(11, 79, 125, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            justify-content: center;
            width: 100%;
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

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }

        /* Security Badge */
        .security-badge {
            background: var(--gray-100);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
        }

        .security-badge .icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .security-badge .text {
            font-size: 12px;
            color: var(--gray-600);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
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
            </div>
        </div>
    </header>

    <div class="progress-steps">
        <div class="container">
            <div class="steps">
                <div class="step completed">
                    <div class="step-number">✓</div>
                    <span>Tìm chuyến</span>
                </div>
                <div class="step completed">
                    <div class="step-number">✓</div>
                    <span>Chọn vé</span>
                </div>
                <div class="step active">
                    <div class="step-number">3</div>
                    <span>Thanh toán</span>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <span>Xác nhận</span>
                </div>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="content-grid">
                <div class="payment-form">
                    <h1 class="form-title">Thanh toán</h1>
                    
                    <form method="POST">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                        
                        <!-- Phương thức thanh toán -->
                        <div class="form-group">
                            <label class="form-label">Phương thức thanh toán</label>
                            <div class="payment-methods">
                                <label class="payment-method" onclick="selectPaymentMethod('THE')">
                                    <input type="radio" name="phuong_thuc" value="THE" required>
                                    <div class="method-icon">💳</div>
                                    <div class="method-name">Thẻ tín dụng</div>
                                    <div class="method-desc">Visa, Mastercard</div>
                                </label>
                                
                                <label class="payment-method" onclick="selectPaymentMethod('VI')">
                                    <input type="radio" name="phuong_thuc" value="VI" required>
                                    <div class="method-icon">📱</div>
                                    <div class="method-name">Ví điện tử</div>
                                    <div class="method-desc">MoMo, ZaloPay</div>
                                </label>
                                
                                <label class="payment-method" onclick="selectPaymentMethod('CK')">
                                    <input type="radio" name="phuong_thuc" value="CK" required>
                                    <div class="method-icon">🏦</div>
                                    <div class="method-name">Chuyển khoản</div>
                                    <div class="method-desc">Ngân hàng</div>
                                </label>
                            </div>
                        </div>

                        <!-- Nhà cung cấp -->
                        <div class="form-group">
                            <label class="form-label" for="nha_cung_cap">Nhà cung cấp</label>
                            <select id="nha_cung_cap" name="nha_cung_cap" class="form-input" required>
                                <option value="">Chọn nhà cung cấp</option>
                                <option value="VISA">Visa</option>
                                <option value="MASTERCARD">Mastercard</option>
                                <option value="MOMO">MoMo</option>
                                <option value="ZALOPAY">ZaloPay</option>
                                <option value="VIETCOMBANK">Vietcombank</option>
                                <option value="TECHCOMBANK">Techcombank</option>
                            </select>
                        </div>

                        <!-- Thông tin thẻ -->
                        <div id="card-info" style="display: none;">
                            <div class="form-group">
                                <label class="form-label" for="so_the">Số thẻ</label>
                                <input type="text" id="so_the" name="so_the" class="form-input" 
                                       placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="ngay_het_han">Ngày hết hạn</label>
                                    <input type="text" id="ngay_het_han" name="ngay_het_han" class="form-input" 
                                           placeholder="MM/YY" maxlength="5">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="cvv">CVV</label>
                                    <input type="text" id="cvv" name="cvv" class="form-input" 
                                           placeholder="123" maxlength="4">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="ten_chu_the">Tên chủ thẻ</label>
                                <input type="text" id="ten_chu_the" name="ten_chu_the" class="form-input" 
                                       placeholder="NGUYEN VAN A">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <span>💳</span>
                            Thanh toán <?= number_format($bookingData['thanh_toan']) ?> VND
                        </button>
                    </form>

                    <div class="security-badge">
                        <div class="icon">🔒</div>
                        <div class="text">Thông tin thanh toán được mã hóa và bảo mật</div>
                    </div>
                </div>

                <div class="booking-summary">
                    <h2 class="summary-title">Tóm tắt đặt chỗ</h2>
                    
                    <div class="pnr-info">
                        <div class="pnr-code"><?= $bookingData['pnr'] ?></div>
                        <div class="pnr-label">Mã đặt chỗ (PNR)</div>
                    </div>

                    <?php foreach ($bookingData['flights'] as $flight): ?>
                        <div class="flight-item">
                            <div class="flight-header">
                                <span class="flight-number"><?= $flight['so_hieu'] ?></span>
                                <span class="flight-time"><?= date('H:i', strtotime($flight['gio_di'])) ?> - <?= date('H:i', strtotime($flight['gio_den'])) ?></span>
                            </div>
                            
                            <div class="flight-route">
                                <div class="airport">
                                    <div class="airport-code"><?= $flight['di'] ?></div>
                                    <div class="airport-name">Hà Nội</div>
                                </div>
                                
                                <div class="flight-duration">
                                    <div>✈</div>
                                    <div>2h 30m</div>
                                </div>
                                
                                <div class="airport">
                                    <div class="airport-code"><?= $flight['den'] ?></div>
                                    <div class="airport-name">TP.HCM</div>
                                </div>
                            </div>
                            
                            <div class="passenger-list">
                                <?php foreach ($flight['hanh_khach'] as $passenger): ?>
                                    <div class="passenger-item">
                                        <span class="passenger-name"><?= htmlspecialchars($passenger['ho_ten']) ?></span>
                                        <span class="passenger-type"><?= $passenger['loai'] === 'ADT' ? 'Người lớn' : ($passenger['loai'] === 'CHD' ? 'Trẻ em' : 'Em bé') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Giá vé</span>
                            <span><?= number_format($bookingData['tong_tien']) ?> VND</span>
                        </div>
                        <div class="summary-row">
                            <span>Khuyến mãi</span>
                            <span>-<?= number_format($bookingData['khuyen_mai']) ?> VND</span>
                        </div>
                        <div class="summary-row">
                            <span>Thuế và phí</span>
                            <span>0 VND</span>
                        </div>
                        <div class="summary-row total">
                            <span>Tổng cộng</span>
                            <span><?= number_format($bookingData['thanh_toan']) ?> VND</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function selectPaymentMethod(method) {
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            event.currentTarget.classList.add('selected');
            
            // Show/hide card info
            const cardInfo = document.getElementById('card-info');
            if (method === 'THE') {
                cardInfo.style.display = 'block';
            } else {
                cardInfo.style.display = 'none';
            }
        }

        // Format card number
        document.getElementById('so_the').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Format expiry date
        document.getElementById('ngay_het_han').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>