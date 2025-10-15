<?php
// pages/customer/profile.php — Quản lý thông tin khách hàng
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

$user = me();
$errors = [];
$success = '';

// Lấy thông tin chi tiết khách hàng
$stmt = db()->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
$stmt->execute([$user['id']]);
$userInfo = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($ho_ten)) $errors[] = 'Họ tên không được để trống';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    
    // Kiểm tra email trùng (nếu thay đổi)
    if ($email !== $userInfo['email']) {
        $checkEmail = db()->prepare("SELECT id FROM nguoi_dung WHERE email = ? AND id != ?");
        $checkEmail->execute([$email, $user['id']]);
        if ($checkEmail->fetch()) $errors[] = 'Email đã được sử dụng';
    }
    
    // Kiểm tra mật khẩu hiện tại nếu muốn đổi
    if (!empty($new_password)) {
        if (empty($password)) {
            $errors[] = 'Vui lòng nhập mật khẩu hiện tại';
        } elseif (!password_verify($password, $userInfo['mat_khau_ma_hoa'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Mật khẩu mới và xác nhận không khớp';
        }
    }
    
    if (empty($errors)) {
        try {
            $updateData = [$ho_ten, $sdt ?: null, $email, $user['id']];
            $sql = "UPDATE nguoi_dung SET ho_ten = ?, sdt = ?, email = ?";
            
            if (!empty($new_password)) {
                $sql .= ", mat_khau_ma_hoa = ?";
                $updateData[] = password_hash($new_password, PASSWORD_BCRYPT);
            }
            
            $sql .= " WHERE id = ?";
            $updateData[] = $user['id'];
            
            $stmt = db()->prepare($sql);
            $stmt->execute($updateData);
            
            // Cập nhật session
            $_SESSION['user']['ho_ten'] = $ho_ten;
            $_SESSION['user']['email'] = $email;
            
            $success = 'Cập nhật thông tin thành công';
            
            // Refresh user info
            $stmt = db()->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
            $stmt->execute([$user['id']]);
            $userInfo = $stmt->fetch();
            
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
    <title>Thông tin cá nhân | VNAir Ticket</title>
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            padding: 40px 0;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 10px 0;
        }

        .page-subtitle {
            color: var(--gray-600);
            font-size: 16px;
            margin: 0;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        /* Sidebar */
        .sidebar {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--gray-600);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .sidebar-menu a:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .sidebar-menu a.active {
            background: var(--primary);
            color: white;
        }

        .sidebar-menu .icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Content Area */
        .content-area {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }

        /* Form Styles */
        .form-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gray-100);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input {
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--white);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(11, 79, 125, 0.1);
        }

        .form-input:disabled {
            background: var(--gray-100);
            color: var(--gray-500);
        }

        .form-help {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 5px;
        }

        /* Buttons */
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 25px;
            border-radius: var(--border-radius);
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-menu {
                display: none;
            }
            
            .page-title {
                font-size: 24px;
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
                    <a href="index.php?p=my_tickets">Vé của tôi</a>
                    <a href="index.php?p=profile" class="active">Thông tin cá nhân</a>
                    <a href="index.php?p=contact">Hỗ trợ</a>
                </nav>
                
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['ho_ten'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($user['ho_ten']) ?></div>
                        <div style="font-size: 12px; opacity: 0.8;">Khách hàng</div>
                    </div>
                    <a href="index.php?p=logout" class="btn btn-secondary" style="padding: 8px 16px; font-size: 12px;">Đăng xuất</a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Thông tin cá nhân</h1>
                <p class="page-subtitle">Quản lý thông tin tài khoản và cài đặt bảo mật</p>
            </div>

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
                <aside class="sidebar">
                    <ul class="sidebar-menu">
                        <li><a href="index.php?p=profile" class="active"><span class="icon">👤</span>Thông tin cá nhân</a></li>
                        <li><a href="index.php?p=my_tickets"><span class="icon">🎫</span>Vé của tôi</a></li>
                        <li><a href="index.php?p=notifications"><span class="icon">🔔</span>Thông báo</a></li>
                        <li><a href="index.php?p=contact"><span class="icon">📞</span>Hỗ trợ</a></li>
                    </ul>
                </aside>

                <div class="content-area">
                    <!-- Thống kê nhanh -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $userInfo['id'] ?></div>
                            <div class="stat-label">Mã khách hàng</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= date('d/m/Y', strtotime($userInfo['created_at'])) ?></div>
                            <div class="stat-label">Ngày tham gia</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $userInfo['dang_nhap_gan_nhat'] ? date('d/m/Y', strtotime($userInfo['dang_nhap_gan_nhat'])) : 'Chưa có' ?></div>
                            <div class="stat-label">Đăng nhập cuối</div>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                        
                        <!-- Thông tin cơ bản -->
                        <div class="form-section">
                            <h2 class="section-title">Thông tin cơ bản</h2>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="ho_ten">Họ và tên *</label>
                                    <input type="text" id="ho_ten" name="ho_ten" class="form-input" 
                                           value="<?= htmlspecialchars($userInfo['ho_ten']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="email">Email *</label>
                                    <input type="email" id="email" name="email" class="form-input" 
                                           value="<?= htmlspecialchars($userInfo['email']) ?>" required>
                                    <div class="form-help">Email này sẽ được sử dụng để đăng nhập và nhận thông báo</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="sdt">Số điện thoại</label>
                                    <input type="tel" id="sdt" name="sdt" class="form-input" 
                                           value="<?= htmlspecialchars($userInfo['sdt'] ?? '') ?>" 
                                           placeholder="Nhập số điện thoại">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Trạng thái tài khoản</label>
                                    <input type="text" class="form-input" value="<?= $userInfo['trang_thai'] === 'HOAT_DONG' ? 'Hoạt động' : 'Bị khóa' ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <!-- Bảo mật -->
                        <div class="form-section">
                            <h2 class="section-title">Bảo mật</h2>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="password">Mật khẩu hiện tại</label>
                                    <input type="password" id="password" name="password" class="form-input" 
                                           placeholder="Nhập mật khẩu hiện tại">
                                    <div class="form-help">Chỉ cần nhập khi muốn đổi mật khẩu</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="new_password">Mật khẩu mới</label>
                                    <input type="password" id="new_password" name="new_password" class="form-input" 
                                           placeholder="Nhập mật khẩu mới">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="confirm_password">Xác nhận mật khẩu mới</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                           placeholder="Nhập lại mật khẩu mới">
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">Hủy</button>
                            <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>