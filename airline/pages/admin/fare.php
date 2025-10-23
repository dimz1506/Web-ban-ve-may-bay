<?php
// page.admin.fare.php - quản lý giá vé
if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config.php';
}

// Cho phép cả ADMIN và STAFF vào trang (quyền thay đổi chỉ dành ADMIN)
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
// Giả định hệ thống lưu thông tin user trong $_SESSION['user']
// Nếu hệ thống của bạn dùng helper khác, thay đổi cho phù hợp.
$user_role = $_SESSION['user']['role'] ?? '';
$is_admin = ($user_role === 'ADMIN');

// Danh mục tuyến và hạng ghế
$routes = $pdo->query("SELECT id, ma_tuyen, di, den FROM tuyen_bay ORDER BY ma_tuyen")->fetchAll();
$classes = $pdo->query("SELECT id, ma, ten FROM hang_ghe ORDER BY id")->fetchAll();

// xử lý POST (thêm, sửa, xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bảo đảm chỉ ADMIN mới thực hiện hành động thay đổi dữ liệu
    if (!$is_admin) {
        flash_set('err', 'Bạn không có quyền thực hiện hành động này.');
        redirect('index.php?p=fare');
    }

    require_post_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create' || $action === 'update') {
            $id         = (int)($_POST['id'] ?? 0);
            $route_id   = (int)($_POST['tuyen_bay_id'] ?? 0);
            $class_id   = (int)($_POST['hang_ghe_id'] ?? 0);
            $gia_co_ban = (float)($_POST['gia_co_ban'] ?? 0);
            $hanh_ly_kg = (int)($_POST['hanh_ly_kg'] ?? 0);
            $phi_doi    = (float)($_POST['phi_doi'] ?? 0);
            $duoc_hoan  = isset($_POST['duoc_hoan']) ? 1 : 0;

            if (!$route_id || !$class_id) throw new RuntimeException('Vui lòng chọn tuyến bay và hạng ghế.');
            if ($gia_co_ban <= 0) throw new RuntimeException('Giá cơ bản phải lớn hơn 0.');

            if ($action === 'create') {
                $sql = "INSERT INTO gia_ve_mac_dinh (tuyen_bay_id, hang_ghe_id, gia_co_ban, hanh_ly_kg, duoc_hoan, phi_doi)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$route_id, $class_id, $gia_co_ban, $hanh_ly_kg, $duoc_hoan, $phi_doi]);
                flash_set('ok', 'Đã thêm giá vé mặc định.');
            } else {
                $sql = "UPDATE gia_ve_mac_dinh SET tuyen_bay_id=?, hang_ghe_id=?, gia_co_ban=?, hanh_ly_kg=?, duoc_hoan=?, phi_doi=? WHERE id=?";
                $pdo->prepare($sql)->execute([$route_id, $class_id, $gia_co_ban, $hanh_ly_kg, $duoc_hoan, $phi_doi, $id]);
                flash_set('ok', 'Đã cập nhật giá vé mặc định.');
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM gia_ve_mac_dinh WHERE id=?")->execute([$id]);
            flash_set('ok', 'Đã xóa giá vé mặc định.');
        } else {
            throw new RuntimeException('Hành động không hợp lệ.');
        }
    } catch (Throwable $e) {
        flash_set('err', $e->getMessage());
    }
    redirect('index.php?p=fare');
}

// danh sách giá vé
$fares = $pdo->query("SELECT gv.id, gv.gia_co_ban, gv.hanh_ly_kg, gv.duoc_hoan, gv.phi_doi,
                             tb.ma_tuyen, tb.di, tb.den,
                             hg.ma AS hang_ma, hg.ten AS hang_ten
                      FROM gia_ve_mac_dinh gv
                      JOIN tuyen_bay tb ON gv.tuyen_bay_id = tb.id
                      JOIN hang_ghe hg ON gv.hang_ghe_id = hg.id
                      ORDER BY tb.ma_tuyen, hg.id")->fetchAll();

// dữ liệu để sửa (chỉ ADMIN mới được phép vào trang sửa)
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id) {
    if (!$is_admin) {
        // Nếu STAFF cố gắng truy cập ?edit=..., chặn lại
        flash_set('err', 'Bạn không có quyền truy cập trang chỉnh sửa.');
        redirect('index.php?p=fare');
    }

    $stmt = $pdo->prepare("SELECT * FROM gia_ve_mac_dinh WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_row = $stmt->fetch();
    if (!$edit_row) {
        flash_err('Giá vé mặc định không tồn tại.');
        redirect('index.php?p=fare');
    }
}

// Khi include template, truyền biến $is_admin để template ẩn/hiện nút theo quyền
include dirname(__DIR__) . '/../templates/fare_view.php';
?>
