<?php
if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_login(['ADMIN']);
$pdo = db();

function flash_ok($m){ flash_set('ok', $m); }
function flash_err($m){ flash_set('err', $m); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $pdo->prepare("INSERT INTO san_bay(ma, ten, thanh_pho, quoc_gia, mui_gio) VALUES (?, ?, ?, ?, ?)")
                ->execute([$ma, $ten, $thanh_pho, $quoc_gia, $mui_gio]);
            flash_ok('Đã thêm sân bay.');
        } elseif ($action === 'delete') {
            $ma = $_POST['id'] ?? '';
            $pdo->prepare("DELETE FROM san_bay WHERE ma=?")->execute([$ma]);
            flash_ok('Đã xóa sân bay.');
        }
    } catch (Throwable $e) {
        flash_err($e->getMessage());
    }
    redirect('index.php?p=sanbay');
}

$rows = $pdo->query("SELECT * FROM san_bay ORDER BY ma")->fetchAll();
include dirname(__DIR__) . '/../templates/sanbay_view.php';
