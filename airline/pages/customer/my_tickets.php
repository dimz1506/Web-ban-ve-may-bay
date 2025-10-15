<?php
// pages/customer/my_tickets.php — Vé của tôi
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

$user = me();

// Lấy danh sách đặt chỗ của khách hàng
$bookings = [];
try {
    $stmt = db()->prepare("
        SELECT dc.*, 
               COUNT(v.id) as so_ve,
               GROUP_CONCAT(v.so_ve) as danh_sach_ve
        FROM dat_cho dc
        LEFT JOIN ve v ON v.dat_cho_id = dc.id
        WHERE dc.khach_hang_id = ?
        GROUP BY dc.id
        ORDER BY dc.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
    // Bảng chưa tồn tại hoặc có lỗi
}

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Vé của tôi | VNAir Ticket</title>
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

        .nav-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-menu a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Main Content */
        .main-content {
            padding: 40px 0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin: 0 auto 15px;
        }

        .stat-icon.total {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .stat-icon.confirmed {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
        }

        .stat-icon.pending {
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
            color: white;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray-600);
        }

        /* Bookings List */
        .bookings-container {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .bookings-header {
            background: var(--gray-50);
            padding: 20px 30px;
            border-bottom: 1px solid var(--gray-200);
        }

        .bookings-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .booking-card {
            border-bottom: 1px solid var(--gray-200);
            padding: 25px 30px;
            transition: background-color 0.2s;
        }

        .booking-card:hover {
            background: var(--gray-50);
        }

        .booking-card:last-child {
            border-bottom: none;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .pnr-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .pnr-code {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .pnr-label {
            font-size: 12px;
            color: var(--gray-500);
        }

        .booking-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-xac_nhan {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cho_thanh_toan {
            background: #fef3c7;
            color: #d97706;
        }

        .status-huy {
            background: #fee2e2;
            color: #991b1b;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .detail-value {
            font-size: 14px;
            color: var(--gray-800);
            font-weight: 500;
        }

        .booking-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
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

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: var(--gray-500);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 10px 0;
        }

        .empty-desc {
            font-size: 14px;
            margin: 0 0 20px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .booking-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .booking-actions {
                justify-content: stretch;
            }
            
            .btn {
                flex: 1;
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
                
                <nav class="nav-menu">
                    <a href="index.php?p=customer">Trang chủ</a>
                    <a href="index.php?p=book_search">Tìm chuyến</a>
                    <a href="index.php?p=my_tickets" class="active">Vé của tôi</a>
                    <a href="index.php?p=profile">Thông tin cá nhân</a>
                    <a href="index.php?p=contact">Hỗ trợ</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Vé của tôi</h1>
                <div class="action-buttons">
                    <a href="index.php?p=book_search" class="btn btn-primary">
                        <span>✈️</span>
                        Đặt vé mới
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <span>🎫</span>
                    </div>
                    <div class="stat-number"><?= count($bookings) ?></div>
                    <div class="stat-label">Tổng số đặt chỗ</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon confirmed">
                        <span>✓</span>
                    </div>
                    <div class="stat-number"><?= count(array_filter($bookings, function($b) { return $b['trang_thai'] === 'XAC_NHAN'; })) ?></div>
                    <div class="stat-label">Đã xác nhận</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon pending">
                        <span>⏳</span>
                    </div>
                    <div class="stat-number"><?= count(array_filter($bookings, function($b) { return $b['trang_thai'] === 'CHO_THANH_TOAN'; })) ?></div>
                    <div class="stat-label">Chờ thanh toán</div>
                </div>
            </div>

            <!-- Bookings List -->
            <div class="bookings-container">
                <div class="bookings-header">
                    <h2 class="bookings-title">Lịch sử đặt vé</h2>
                </div>

                <?php if (empty($bookings)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🎫</div>
                        <h3 class="empty-title">Chưa có vé nào</h3>
                        <p class="empty-desc">Bạn chưa đặt vé nào. Hãy tìm và đặt chuyến bay đầu tiên của bạn!</p>
                        <a href="index.php?p=book_search" class="btn btn-primary">
                            <span>✈️</span>
                            Đặt vé ngay
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                            <div class="booking-header">
                                <div class="pnr-info">
                                    <div>
                                        <div class="pnr-code"><?= $booking['pnr'] ?></div>
                                        <div class="pnr-label">Mã đặt chỗ</div>
                                    </div>
                                </div>
                                
                                <span class="booking-status status-<?= strtolower($booking['trang_thai']) ?>">
                                    <?= $booking['trang_thai'] === 'XAC_NHAN' ? 'Đã xác nhận' : 
                                        ($booking['trang_thai'] === 'CHO_THANH_TOAN' ? 'Chờ thanh toán' : 'Đã hủy') ?>
                                </span>
                            </div>

                            <div class="booking-details">
                                <div class="detail-item">
                                    <div class="detail-label">Ngày đặt</div>
                                    <div class="detail-value"><?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Số vé</div>
                                    <div class="detail-value"><?= $booking['so_ve'] ?> vé</div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Tổng tiền</div>
                                    <div class="detail-value"><?= number_format($booking['tong_tien']) ?> VND</div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Mã vé</div>
                                    <div class="detail-value"><?= htmlspecialchars($booking['danh_sach_ve']) ?></div>
                                </div>
                            </div>

                            <div class="booking-actions">
                                <?php if ($booking['trang_thai'] === 'XAC_NHAN'): ?>
                                    <a href="index.php?p=invoice&pnr=<?= $booking['pnr'] ?>" class="btn btn-primary">
                                        <span>📄</span>
                                        Xem hóa đơn
                                    </a>
                                    <button class="btn btn-warning" onclick="cancelBooking('<?= $booking['pnr'] ?>')">
                                        <span>❌</span>
                                        Hủy vé
                                    </button>
                                <?php elseif ($booking['trang_thai'] === 'CHO_THANH_TOAN'): ?>
                                    <a href="index.php?p=payment&pnr=<?= $booking['pnr'] ?>" class="btn btn-success">
                                        <span>💳</span>
                                        Thanh toán
                                    </a>
                                    <button class="btn btn-danger" onclick="cancelBooking('<?= $booking['pnr'] ?>')">
                                        <span>❌</span>
                                        Hủy đặt chỗ
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-secondary" onclick="viewDetails('<?= $booking['pnr'] ?>')">
                                    <span>ℹ️</span>
                                    Chi tiết
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function cancelBooking(pnr) {
            if (confirm('Bạn có chắc chắn muốn hủy đặt chỗ ' + pnr + '?')) {
                // Trong thực tế sẽ gọi API để hủy đặt chỗ
                alert('Đặt chỗ đã được hủy thành công!');
                location.reload();
            }
        }

        function viewDetails(pnr) {
            // Trong thực tế sẽ hiển thị modal hoặc chuyển trang chi tiết
            alert('Chi tiết đặt chỗ ' + pnr + ' sẽ được hiển thị ở đây');
        }
    </script>
</body>
</html>
