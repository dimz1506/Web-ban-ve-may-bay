<?php
// pages/customer/notifications.php — Thông báo cho khách hàng
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

$user = me();

// Tạo bảng thông báo nếu chưa có
try {
    $createTable = "
        CREATE TABLE IF NOT EXISTS thong_bao (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            khach_hang_id BIGINT UNSIGNED NULL,
            loai ENUM('CHUNG','CA_NHAN','KHUYEN_MAI','CHUYEN_BAY','THANH_TOAN') NOT NULL DEFAULT 'CHUNG',
            tieu_de VARCHAR(200) NOT NULL,
            noi_dung TEXT NOT NULL,
            trang_thai ENUM('CHUA_DOC','DA_DOC') NOT NULL DEFAULT 'CHUA_DOC',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (khach_hang_id) REFERENCES nguoi_dung(id)
        )
    ";
    db()->exec($createTable);
    
    
} catch (Exception $e) {
    // Bỏ qua lỗi nếu bảng đã tồn tại
}

// Xử lý đánh dấu đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    require_post_csrf();
    $notificationId = $_POST['notification_id'] ?? '';
    
    if ($notificationId) {
        $stmt = db()->prepare("UPDATE thong_bao SET trang_thai = 'DA_DOC' WHERE id = ? AND khach_hang_id = ?");
        $stmt->execute([$notificationId, $user['id']]);
    }
}

// Xử lý đánh dấu tất cả đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    require_post_csrf();
    $stmt = db()->prepare("UPDATE thong_bao SET trang_thai = 'DA_DOC' WHERE khach_hang_id = ?");
    $stmt->execute([$user['id']]);
}

// Lấy danh sách thông báo
$stmt = db()->prepare("
    SELECT * FROM thong_bao 
    WHERE khach_hang_id = ? OR khach_hang_id IS NULL
    ORDER BY created_at DESC
");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

// Đếm thông báo chưa đọc
$unreadCount = 0;
foreach ($notifications as $notif) {
    if ($notif['trang_thai'] === 'CHUA_DOC') {
        $unreadCount++;
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Thông báo | VNAir Ticket</title>
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

        .unread-badge {
            background: var(--danger);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        /* Notification Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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

        .stat-icon.unread {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: white;
        }

        .stat-icon.read {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
        }

        .stat-icon.total {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
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

        /* Notifications List */
        .notifications-container {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .notifications-header {
            background: var(--gray-50);
            padding: 20px 30px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notifications-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .notifications-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .notification-item {
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 30px;
            transition: background-color 0.2s;
            position: relative;
        }

        .notification-item:hover {
            background: var(--gray-50);
        }

        .notification-item.unread {
            background: rgba(11, 79, 125, 0.05);
            border-left: 4px solid var(--primary);
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            top: 20px;
            right: 20px;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .notification-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
            flex: 1;
        }

        .notification-meta {
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 12px;
            color: var(--gray-500);
        }

        .notification-type {
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 500;
        }

        .type-chung {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .type-ca_nhan {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-khuyen_mai {
            background: #fef3c7;
            color: #d97706;
        }

        .type-chuyen_bay {
            background: #d1fae5;
            color: #065f46;
        }

        .type-thanh_toan {
            background: #fce7f3;
            color: #be185d;
        }

        .notification-content {
            color: var(--gray-700);
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 12px;
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
            margin: 0;
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
            
            .notification-item {
                padding: 15px 20px;
            }
            
            .notifications-header {
                padding: 15px 20px;
            }
            
            .notification-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .notification-meta {
                flex-wrap: wrap;
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
                    <a href="index.php?p=my_tickets">Vé của tôi</a>
                    <a href="index.php?p=profile">Thông tin cá nhân</a>
                    <a href="index.php?p=notifications" class="active">Thông báo</a>
                    <a href="index.php?p=contact">Hỗ trợ</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Thông báo</h1>
                    <?php if ($unreadCount > 0): ?>
                        <div class="unread-badge"><?= $unreadCount ?> thông báo chưa đọc</div>
                    <?php endif; ?>
                </div>
                
                <?php if ($unreadCount > 0): ?>
                <div class="action-buttons">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                        <button type="submit" name="mark_all_read" class="btn btn-success">
                            <span>✓</span>
                            Đánh dấu tất cả đã đọc
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon unread">
                        <span>🔔</span>
                    </div>
                    <div class="stat-number"><?= $unreadCount ?></div>
                    <div class="stat-label">Chưa đọc</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon read">
                        <span>✓</span>
                    </div>
                    <div class="stat-number"><?= count($notifications) - $unreadCount ?></div>
                    <div class="stat-label">Đã đọc</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon total">
                        <span>📋</span>
                    </div>
                    <div class="stat-number"><?= count($notifications) ?></div>
                    <div class="stat-label">Tổng cộng</div>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="notifications-container">
                <div class="notifications-header">
                    <h2 class="notifications-title">Danh sách thông báo</h2>
                </div>

                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <h3 class="empty-title">Chưa có thông báo nào</h3>
                            <p class="empty-desc">Các thông báo quan trọng sẽ xuất hiện ở đây</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?= $notification['trang_thai'] === 'CHUA_DOC' ? 'unread' : '' ?>">
                                <div class="notification-header">
                                    <h3 class="notification-title"><?= htmlspecialchars($notification['tieu_de']) ?></h3>
                                    <div class="notification-meta">
                                        <span class="notification-type type-<?= strtolower($notification['loai']) ?>">
                                            <?= $notification['loai'] === 'CHUNG' ? 'Chung' : 
                                                ($notification['loai'] === 'CA_NHAN' ? 'Cá nhân' : 
                                                ($notification['loai'] === 'KHUYEN_MAI' ? 'Khuyến mãi' : 
                                                ($notification['loai'] === 'CHUYEN_BAY' ? 'Chuyến bay' : 'Thanh toán'))) ?>
                                        </span>
                                        <span><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></span>
                                    </div>
                                </div>

                                <div class="notification-content">
                                    <?= nl2br(htmlspecialchars($notification['noi_dung'])) ?>
                                </div>

                                <?php if ($notification['trang_thai'] === 'CHUA_DOC'): ?>
                                <div class="notification-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                        <button type="submit" name="mark_read" class="btn btn-primary">
                                            <span>✓</span>
                                            Đánh dấu đã đọc
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>