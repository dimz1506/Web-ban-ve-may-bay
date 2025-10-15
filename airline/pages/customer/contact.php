<?php
// pages/customer/contact.php — Hỗ trợ khách hàng và liên hệ
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

$user = me();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    
    $loai_ho_tro = $_POST['loai_ho_tro'] ?? '';
    $tieu_de = trim($_POST['tieu_de'] ?? '');
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $muc_do_uu_tien = $_POST['muc_do_uu_tien'] ?? 'TRUNG_BINH';
    
    // Validation
    if (empty($loai_ho_tro)) $errors[] = 'Vui lòng chọn loại hỗ trợ';
    if (empty($tieu_de)) $errors[] = 'Vui lòng nhập tiêu đề';
    if (empty($noi_dung)) $errors[] = 'Vui lòng nhập nội dung';
    if (strlen($tieu_de) < 5) $errors[] = 'Tiêu đề phải có ít nhất 5 ký tự';
    if (strlen($noi_dung) < 20) $errors[] = 'Nội dung phải có ít nhất 20 ký tự';
    
    if (empty($errors)) {
        try {
            // Tạo bảng hỗ trợ nếu chưa có
            $createTable = "
                CREATE TABLE IF NOT EXISTS ho_tro_khach_hang (
                    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    khach_hang_id BIGINT UNSIGNED NOT NULL,
                    loai_ho_tro VARCHAR(50) NOT NULL,
                    tieu_de VARCHAR(200) NOT NULL,
                    noi_dung TEXT NOT NULL,
                    muc_do_uu_tien ENUM('THAP','TRUNG_BINH','CAO','KHAN_CAP') NOT NULL DEFAULT 'TRUNG_BINH',
                    trang_thai ENUM('MOI','DANG_XU_LY','DA_TRA_LOI','DONG') NOT NULL DEFAULT 'MOI',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (khach_hang_id) REFERENCES nguoi_dung(id)
                )
            ";
            db()->exec($createTable);
            
            // Lưu yêu cầu hỗ trợ
            $stmt = db()->prepare("
                INSERT INTO ho_tro_khach_hang 
                (khach_hang_id, loai_ho_tro, tieu_de, noi_dung, muc_do_uu_tien) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $loai_ho_tro, $tieu_de, $noi_dung, $muc_do_uu_tien]);
            
            $success = 'Yêu cầu hỗ trợ đã được gửi thành công! Chúng tôi sẽ phản hồi trong vòng 24 giờ.';
            
        } catch (Exception $e) {
            $errors[] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách yêu cầu hỗ trợ của khách hàng
$supportRequests = [];
try {
    $stmt = db()->prepare("
        SELECT * FROM ho_tro_khach_hang 
        WHERE khach_hang_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user['id']]);
    $supportRequests = $stmt->fetchAll();
} catch (Exception $e) {
    // Bảng chưa tồn tại, bỏ qua
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Hỗ trợ khách hàng | VNAir Ticket</title>
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
            text-align: center;
            margin-bottom: 50px;
        }

        .page-title {
            font-size: 36px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 15px 0;
        }

        .page-subtitle {
            font-size: 18px;
            color: var(--gray-600);
            margin: 0;
            max-width: 600px;
            margin: 0 auto;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 50px;
        }

        /* Contact Methods */
        .contact-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .contact-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 20px;
        }

        .contact-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 10px 0;
        }

        .contact-desc {
            color: var(--gray-600);
            margin: 0 0 15px 0;
        }

        .contact-info {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
        }

        /* Support Form */
        .support-form {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow);
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 30px 0;
            text-align: center;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(11, 79, 125, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-help {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 5px;
        }

        /* Support History */
        .support-history {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .history-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-200);
        }

        .support-item {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: border-color 0.2s;
        }

        .support-item:hover {
            border-color: var(--primary);
        }

        .support-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .support-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .support-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-moi {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-dang_xu_ly {
            background: #fef3c7;
            color: #d97706;
        }

        .status-da_tra_loi {
            background: #d1fae5;
            color: #065f46;
        }

        .status-dong {
            background: #f3f4f6;
            color: #6b7280;
        }

        .support-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--gray-600);
        }

        .support-content {
            color: var(--gray-700);
            line-height: 1.5;
        }

        /* FAQ Section */
        .faq-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .faq-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 30px 0;
            text-align: center;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .faq-item {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 20px;
            transition: border-color 0.2s;
        }

        .faq-item:hover {
            border-color: var(--primary);
        }

        .faq-question {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 10px 0;
        }

        .faq-answer {
            color: var(--gray-700);
            font-size: 14px;
            line-height: 1.5;
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

        /* Responsive */
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-methods {
                grid-template-columns: 1fr;
            }
            
            .faq-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 28px;
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
                    <a href="index.php?p=contact" class="active">Hỗ trợ</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Hỗ trợ khách hàng</h1>
                <p class="page-subtitle">Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7. Liên hệ với chúng tôi qua các kênh sau hoặc gửi yêu cầu hỗ trợ trực tiếp.</p>
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

            <!-- Contact Methods -->
            <div class="contact-methods">
                <div class="contact-card">
                    <div class="contact-icon">📞</div>
                    <h3 class="contact-title">Hotline</h3>
                    <p class="contact-desc">Hỗ trợ 24/7</p>
                    <div class="contact-info">1900 1234</div>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">📧</div>
                    <h3 class="contact-title">Email</h3>
                    <p class="contact-desc">Phản hồi trong 24h</p>
                    <div class="contact-info">support@vnairticket.com</div>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">💬</div>
                    <h3 class="contact-title">Live Chat</h3>
                    <p class="contact-desc">Trò chuyện trực tiếp</p>
                    <div class="contact-info">Có sẵn 8:00 - 22:00</div>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">🏢</div>
                    <h3 class="contact-title">Văn phòng</h3>
                    <p class="contact-desc">Tại các sân bay</p>
                    <div class="contact-info">HAN, SGN, DAD</div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <h2 class="faq-title">Câu hỏi thường gặp</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h4 class="faq-question">Làm thế nào để đổi/hủy vé?</h4>
                        <p class="faq-answer">Bạn có thể đổi/hủy vé trực tuyến trong phần "Vé của tôi" hoặc liên hệ hotline 1900 1234. Phí đổi/hủy tùy thuộc vào loại vé và thời gian.</p>
                    </div>

                    <div class="faq-item">
                        <h4 class="faq-question">Tôi có thể mang bao nhiều hành lý?</h4>
                        <p class="faq-answer">Hành lý xách tay: 7kg, hành lý ký gửi: 20kg (phổ thông), 30kg (phổ thông đặc biệt), 40kg (thương gia).</p>
                    </div>

                    <div class="faq-item">
                        <h4 class="faq-question">Làm sao để check-in online?</h4>
                        <p class="faq-answer">Bạn có thể check-in online từ 24h trước giờ bay tại website hoặc ứng dụng di động. Check-in tại sân bay từ 2h trước giờ bay.</p>
                    </div>

                    <div class="faq-item">
                        <h4 class="faq-question">Tôi quên mã đặt chỗ (PNR) thì sao?</h4>
                        <p class="faq-answer">Bạn có thể tra cứu bằng số điện thoại hoặc email đã đăng ký. Hoặc liên hệ hotline để được hỗ trợ tra cứu.</p>
                    </div>

                    <div class="faq-item">
                        <h4 class="faq-question">Có thể đặt vé cho trẻ em không?</h4>
                        <p class="faq-answer">Có, trẻ em từ 2-12 tuổi được giảm giá 25%, em bé dưới 2 tuổi được giảm 90% (ngồi cùng người lớn).</p>
                    </div>

                    <div class="faq-item">
                        <h4 class="faq-question">Làm thế nào để tích điểm thành viên?</h4>
                    <p class="faq-answer">Mỗi chuyến bay bạn sẽ tích được điểm tương ứng với giá vé. Điểm có thể dùng để đổi vé miễn phí hoặc nâng cấp hạng ghế.</p>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <!-- Support Form -->
                <div class="support-form">
                    <h2 class="form-title">Gửi yêu cầu hỗ trợ</h2>
                    
                    <form method="POST">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                        
                        <div class="form-group">
                            <label class="form-label" for="loai_ho_tro">Loại hỗ trợ *</label>
                            <select id="loai_ho_tro" name="loai_ho_tro" class="form-select" required>
                                <option value="">Chọn loại hỗ trợ</option>
                                <option value="DAT_VE">Đặt vé</option>
                                <option value="DOI_HUY_VE">Đổi/hủy vé</option>
                                <option value="HANH_LY">Hành lý</option>
                                <option value="CHECK_IN">Check-in</option>
                                <option value="THANH_TOAN">Thanh toán</option>
                                <option value="TICH_DIEM">Tích điểm thành viên</option>
                                <option value="KHAC">Khác</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="muc_do_uu_tien">Mức độ ưu tiên</label>
                            <select id="muc_do_uu_tien" name="muc_do_uu_tien" class="form-select">
                                <option value="TRUNG_BINH">Trung bình</option>
                                <option value="CAO">Cao</option>
                                <option value="KHAN_CAP">Khẩn cấp</option>
                            </select>
                            <div class="form-help">Chọn "Khẩn cấp" chỉ khi có vấn đề cần giải quyết ngay lập tức</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="tieu_de">Tiêu đề *</label>
                            <input type="text" id="tieu_de" name="tieu_de" class="form-input" 
                                   placeholder="Mô tả ngắn gọn vấn đề của bạn" required>
                            <div class="form-help">Tối thiểu 5 ký tự</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="noi_dung">Nội dung chi tiết *</label>
                            <textarea id="noi_dung" name="noi_dung" class="form-textarea" 
                                      placeholder="Mô tả chi tiết vấn đề, bao gồm mã đặt chỗ (nếu có), thời gian xảy ra sự cố..." required></textarea>
                            <div class="form-help">Tối thiểu 20 ký tự</div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <span>📤</span>
                            Gửi yêu cầu hỗ trợ
                        </button>
                    </form>
                </div>

                <!-- Support History -->
                <div class="support-history">
                    <h3 class="history-title">Lịch sử yêu cầu hỗ trợ</h3>
                    
                    <?php if (empty($supportRequests)): ?>
                        <p style="text-align: center; color: var(--gray-500); padding: 40px 0;">
                            Bạn chưa có yêu cầu hỗ trợ nào
                        </p>
                    <?php else: ?>
                        <?php foreach ($supportRequests as $request): ?>
                            <div class="support-item">
                                <div class="support-header">
                                    <h4 class="support-title"><?= htmlspecialchars($request['tieu_de']) ?></h4>
                                    <span class="support-status status-<?= strtolower($request['trang_thai']) ?>">
                                        <?= $request['trang_thai'] === 'MOI' ? 'Mới' : 
                                            ($request['trang_thai'] === 'DANG_XU_LY' ? 'Đang xử lý' : 
                                            ($request['trang_thai'] === 'DA_TRA_LOI' ? 'Đã trả lời' : 'Đóng')) ?>
                                    </span>
                                </div>
                                
                                <div class="support-meta">
                                    <span>Loại: <?= htmlspecialchars($request['loai_ho_tro']) ?></span>
                                    <span>Ngày: <?= date('d/m/Y H:i', strtotime($request['created_at'])) ?></span>
                                    <span>Ưu tiên: <?= $request['muc_do_uu_tien'] === 'KHAN_CAP' ? 'Khẩn cấp' : 
                                        ($request['muc_do_uu_tien'] === 'CAO' ? 'Cao' : 'Trung bình') ?></span>
                                </div>
                                
                                <div class="support-content">
                                    <?= nl2br(htmlspecialchars($request['noi_dung'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
