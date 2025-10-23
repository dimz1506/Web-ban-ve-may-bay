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
    <link rel="stylesheet" href="assets/profile.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">✈</div>
                    <span>VNAir Ticket</span>
                </div>
                
                <!-- <nav class="nav-menu">
                    <a href="index.php?p=customer">Trang chủ</a>
                    <a href="index.php?p=book_search">Tìm chuyến</a>
                    <a href="index.php?p=my_tickets">Vé của tôi</a>
                    <a href="index.php?p=profile" class="active">Thông tin cá nhân</a>
                    <a href="index.php?p=contact">Hỗ trợ</a>
                </nav> -->
                
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
