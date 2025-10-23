<?php
// pages/sanbay.php — Quản lý sân bay (và phân quyền: STAFF chỉ được xem)
if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config.php';
}

// Cho phép cả ADMIN và STAFF vào trang (nhưng chỉ ADMIN mới được thay đổi dữ liệu)
require_login(['ADMIN', 'STAFF']);

$pdo = db();

function flash_ok($m){ flash_set('ok', $m); }
function flash_err($m){ flash_set('err', $m); }

// Lấy role user hiện tại để kiểm tra quyền ở server-side
// Giả định hệ thống lưu thông tin user trong $_SESSION['user']['role']
$user_role = $_SESSION['user']['role'] ?? '';
$is_admin = ($user_role === 'ADMIN');

// Xử lý POST (chỉ ADMIN được phép)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        flash_err('Bạn không có quyền thực hiện hành động này.');
        redirect('index.php?p=sanbay');
    }

    require_post_csrf();
    $action = $_POST['action'] ?? '';
    try {
        $ma = strtoupper(trim($_POST['ma'] ?? ''));
        $ten = trim($_POST['ten'] ?? '');
        $thanh_pho = trim($_POST['thanh_pho'] ?? '');
        $quoc_gia = trim($_POST['quoc_gia'] ?? 'Việt Nam');
        $mui_gio = trim($_POST['mui_gio'] ?? 'Asia/Ho_Chi_Minh');

        if ($ma === '' || $ten === '') throw new RuntimeException('Thiếu dữ liệu.');

        if ($action === 'create') {
            // Có thể thêm kiểm tra tồn tại nếu cần
            $pdo->prepare("INSERT INTO san_bay(ma, ten, thanh_pho, quoc_gia, mui_gio) VALUES (?, ?, ?, ?, ?)")
                ->execute([$ma, $ten, $thanh_pho, $quoc_gia, $mui_gio]);
            flash_ok('Đã thêm sân bay.');
        } elseif ($action === 'delete') {
            // Xoá theo mã sân bay (ma)
            $ma_del = strtoupper(trim($_POST['id'] ?? ''));
            if ($ma_del === '') throw new RuntimeException('Thiếu mã sân bay để xóa.');
            $pdo->prepare("DELETE FROM san_bay WHERE ma=?")->execute([$ma_del]);
            flash_ok('Đã xóa sân bay.');
        } else {
            throw new RuntimeException('Hành động không hợp lệ.');
        }
    } catch (Throwable $e) {
        flash_err($e->getMessage());
    }
    redirect('index.php?p=sanbay');



    
}

// Lấy danh sách sân bay (dùng cho cả ADMIN và STAFF)
$rows = $pdo->query("SELECT * FROM san_bay ORDER BY ma")->fetchAll(PDO::FETCH_ASSOC);

// Khi include template, biến $is_admin sẽ được dùng để ẩn/hiện các nút thao tác
include dirname(__DIR__) . '/../templates/sanbay_view.php';
