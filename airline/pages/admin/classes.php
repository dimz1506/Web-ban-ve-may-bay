<?php
// page.admin.classes.php - quản lý hạng ghế (classes)
// Cho phép ADMIN và STAFF vào trang, nhưng chỉ ADMIN được thêm/sửa/xóa
if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config.php';
}

// allow both ADMIN and STAFF to view the page
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

// handle POST (chỉ ADMIN được phép)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$is_admin) {
        flash_err('Bạn không có quyền thực hiện hành động này.');
        redirect("index.php?p=classes");
    }

    require_post_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create' || $action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $ma = trim($_POST['ma'] ?? '');
            $ten = trim($_POST['ten'] ?? '');
            $mo_ta = trim($_POST['mo_ta'] ?? '');
            $tien_ich = trim($_POST['tien_ich'] ?? '');
            $mau_sac = trim($_POST['mau_sac'] ?? '#cccccc');
            $thu_tu = (int)($_POST['thu_tu'] ?? 0);

            if ($ma === '' || $ten === '') {
                throw new RuntimeException("Thiếu dữ liệu: mã và tên là bắt buộc.");
            }

            if ($action === 'create') {
                $pdo->prepare("INSERT INTO hang_ghe (ma, ten, mo_ta, tien_ich, mau_sac, thu_tu) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$ma, $ten, $mo_ta, $tien_ich, $mau_sac, $thu_tu]);
                flash_ok("Đã thêm hạng ghế.");
            } else {
                $pdo->prepare("UPDATE hang_ghe SET ma=?, ten=?, mo_ta=?, tien_ich=?, mau_sac=?, thu_tu=? WHERE id=?")
                    ->execute([$ma, $ten, $mo_ta, $tien_ich, $mau_sac, $thu_tu, $id]);
                flash_ok("Đã cập nhật hạng ghế.");
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM hang_ghe WHERE id=?")->execute([$id]);
            flash_ok("Đã xóa hạng ghế.");
        } else {
            throw new RuntimeException('Hành động không hợp lệ.');
        }
    } catch (Throwable $e) {
        flash_err($e->getMessage());
    }
    redirect("index.php?p=classes");
}

// danh sách
$rows = $pdo->query("SELECT * FROM hang_ghe ORDER BY id")->fetchAll();

// edit (chỉ ADMIN được truy cập trang edit)
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id) {
    if (!$is_admin) {
        // STAFF không được quyền truy cập trang chỉnh sửa
        flash_err('Bạn không có quyền truy cập trang chỉnh sửa.');
        redirect('index.php?p=classes');
    }

    foreach ($rows as $r) {
        if ((int)$r['id'] === $edit_id) {
            $edit_row = $r;
            break;
        }
    }
}

// include template; truyền $is_admin để template có thể ẩn/hiện nút theo quyền
include dirname(__DIR__) . '/../templates/classes_view.php';
