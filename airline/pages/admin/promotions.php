<?php
// pages/promotions.php
if (!function_exists('db')) {
         require_once dirname(__DIR__) . '/../config.php';
}
require_login(['ADMIN']);
$pdo = db();

// Flash helpers
function flash_ok($m)
{
         flash_set('ok', $m);
}
function flash_err($m)
{
         flash_set('err', $m);
}

// Load danh mục
$routes  = $pdo->query("SELECT id,ma_tuyen,di,den FROM tuyen_bay ORDER BY ma_tuyen")->fetchAll();
$flights = $pdo->query("SELECT id,so_hieu FROM chuyen_bay ORDER BY gio_di DESC LIMIT 100")->fetchAll();
$classes = $pdo->query("SELECT id,ma FROM hang_ghe ORDER BY id")->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         require_post_csrf();
         $action = $_POST['action'] ?? '';
         try {
                  if ($action === 'create' || $action === 'update') {
                           $id   = (int)($_POST['id'] ?? 0);
                           $ma   = trim($_POST['ma'] ?? '');
                           $ten = trim($_POST['ten'] ?? '');
                           $kieu = $_POST['kieu'] ?? 'PHAN_TRAM';
                           $gia  = (float)($_POST['gia_tri'] ?? 0);
                           $batdau = $_POST['bat_dau'] ?? '';
                           $ketthuc = $_POST['ket_thuc'] ?? '';
                           $scope  = $_POST['scope'] ?? 'ALL';
                           $scope_id = $_POST['scope_id'] ?? null;
                           $kichhoat = isset($_POST['kich_hoat']) ? 1 : 0;

                           if ($ma === '' || $ten == '') throw new RuntimeException("Thiếu mã.");
                           if (!in_array($kieu, ['PHAN_TRAM', 'SO_TIEN'], true)) $kieu = 'PHAN_TRAM';
                           if ($kieu === 'PHAN_TRAM' && ($gia <= 0 || $gia > 100)) throw new RuntimeException('Giảm % phải từ 1–100.');
                           if ($kieu === 'SO_TIEN' && $gia <= 0) throw new RuntimeException('Giảm số tiền phải > 0.');
                           if (!$batdau || !$ketthuc) throw new RuntimeException("Chọn thời gian.");

                           if ($action === 'create') {
                                    $sql = "INSERT INTO khuyen_mai(ma,ten,kieu,gia_tri,bat_dau,ket_thuc,don_toi_thieu,giam_toi_da,gioi_han_luot,da_su_dung,kich_hoat)
        VALUES(?,?,?,?,?,?,?,?,?,?)";
                                    $pdo->prepare($sql)->execute([
                                             $ma,
                                             $ten,
                                             $kieu,
                                             $gia,
                                             $batdau,
                                             $ketthuc,
                                             0.00,
                                             null,
                                             null,
                                             0,
                                             $kichhoat
                                    ]);
                                    flash_ok("Đã thêm khuyến mãi.");
                           } else {
                                    if ($id <= 0) throw new RuntimeException("Thiếu ID.");
                                    $sql = "UPDATE khuyen_mai 
        SET ma=?,ten=?, kieu=?, gia_tri=?, bat_dau=?, ket_thuc=?, kich_hoat=? 
        WHERE id=?";
                                    $pdo->prepare($sql)->execute([$ma,$ten, $kieu, $gia, $batdau, $ketthuc, $kichhoat, $id]);
                                    flash_ok("Đã cập nhật.");
                           }
                  } elseif ($action === 'delete') {
                           $id = (int)($_POST['id'] ?? 0);
                           if ($id <= 0) throw new RuntimeException("Thiếu ID.");
                           $pdo->prepare("DELETE FROM khuyen_mai WHERE id=?")->execute([$id]);
                           flash_ok("Đã xóa khuyến mãi #$id");
                  }
         } catch (Throwable $e) {
                  flash_err($e->getMessage());
         }
         redirect("index.php?p=promotions");
         exit;
}

// Danh sách
$rows = $pdo->query("SELECT * FROM khuyen_mai ORDER BY bat_dau DESC")->fetchAll();

// Edit
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id > 0) {
         foreach ($rows as $r) {
                  if ((int)$r['id'] === $edit_id) {
                           $edit_row = $r;
                           break;
                  }
         }
}
//goi view
include dirname(__DIR__) . '/../templates/promotions_view.php';
