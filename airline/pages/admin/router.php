<?php
// pages/routes.php — Quản lý tuyến bay
if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config.php';
}

// Cho phép ADMIN và STAFF vào trang (nhưng chỉ ADMIN mới được thay đổi dữ liệu)
require_login(['ADMIN', 'STAFF']);

$pdo = db();

function flash_ok($m)
{
    flash_set('ok', $m);
}
function flash_err($m)
{
    flash_set('err', $m);
}

// Lấy role user hiện tại để kiểm tra quyền ở server-side
// Giả định hệ thống lưu thông tin user trong $_SESSION['user']['role']
$user_role = $_SESSION['user']['role'] ?? '';
$is_admin = ($user_role === 'ADMIN');

// Lấy danh sách sân bay
$airports = $pdo->query("SELECT ma, ten, thanh_pho FROM san_bay ORDER BY ma")->fetchAll(PDO::FETCH_ASSOC);

// ====== Xử lý POST ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Chặn nếu không phải admin
    if (!$is_admin) {
        flash_err('Bạn không có quyền thực hiện hành động này.');
        redirect('index.php?p=router');
    }

    require_post_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create' || $action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $ma_tuyen = trim($_POST['ma_tuyen'] ?? '');
            $di = trim($_POST['di'] ?? '');
            $den = trim($_POST['den'] ?? '');
            $khoang_cach_km = (int)($_POST['khoang_cach_km'] ?? 0);

            if ($ma_tuyen === '' || !$di || !$den) throw new RuntimeException('Vui lòng nhập đầy đủ thông tin.');
            if ($di === $den) throw new RuntimeException('Sân bay đi và đến không được trùng nhau.');

            // --- Kiểm tra tồn tại trong bảng san_bay ---
            $check = $pdo->prepare("SELECT COUNT(*) FROM san_bay WHERE ma IN (?, ?)");
            $check->execute([$di, $den]);
            if ($check->fetchColumn() < 2) {
                throw new RuntimeException('Sân bay đi/đến chưa tồn tại trong hệ thống.');
            }

            if ($action === 'create') {
                $sql = "INSERT INTO tuyen_bay(ma_tuyen, di, den, khoang_cach_km)
                        VALUES (?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$ma_tuyen, $di, $den, $khoang_cach_km]);
                flash_ok("Đã thêm tuyến bay mới.");
            } else {
                $sql = "UPDATE tuyen_bay SET ma_tuyen=?, di=?, den=?, khoang_cach_km=? WHERE id=?";
                $pdo->prepare($sql)->execute([$ma_tuyen, $di, $den, $khoang_cach_km, $id]);
                flash_ok("Đã cập nhật tuyến bay.");
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            // Kiểm tra xem có chuyến bay nào đang dùng tuyến này không
            $check = $pdo->prepare("SELECT COUNT(*) FROM chuyen_bay WHERE tuyen_bay_id=?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                throw new RuntimeException('Không thể xóa: Tuyến này đang được sử dụng.');
            }
            $pdo->prepare("DELETE FROM tuyen_bay WHERE id=?")->execute([$id]);
            flash_ok("Đã xóa tuyến bay.");
        } else {
            throw new RuntimeException('Hành động không hợp lệ.');
        }
    } catch (Throwable $e) {
        flash_err($e->getMessage());
    }

    redirect('index.php?p=router');
}

// ====== Danh sách tuyến ======
$rows = $pdo->query("SELECT * FROM tuyen_bay ORDER BY ma_tuyen")->fetchAll(PDO::FETCH_ASSOC);

// ====== Dữ liệu để sửa ======
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id) {
    // Chặn nếu STAFF cố gắng truy cập trang edit
    if (!$is_admin) {
        flash_err('Bạn không có quyền truy cập trang chỉnh sửa.');
        redirect('index.php?p=router');
    }

    foreach ($rows as $r) {
        if ((int)$r['id'] === $edit_id) {
            $edit_row = $r;
            break;
        }
    }
}

// include template; truyền $is_admin để template có thể ẩn/hiện nút theo quyền
include dirname(__DIR__) . '/../templates/router_view.php';
