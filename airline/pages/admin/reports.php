<?php
// pages/admin/reports.php
if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_login(['ADMIN', 'STAFF']);
$pdo = db();

// nhận khoảng thời gian lọc
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

// Vé bán & doanh thu theo tháng
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(v.phat_hanh_luc, '%Y-%m') as thang,
           COUNT(*) as so_ve,
           SUM(g.gia_co_ban) as doanh_thu
    FROM ve v
    JOIN chuyen_bay_gia_hang g 
         ON v.chuyen_bay_id = g.chuyen_bay_id 
        AND v.hang_ghe_id = g.hang_ghe_id
    WHERE v.phat_hanh_luc BETWEEN ? AND ?
    GROUP BY thang
    ORDER BY thang
");
$stmt->execute([$from . " 00:00:00", $to . " 23:59:59"]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê chuyến bay theo trạng thái
$stmt = $pdo->prepare("
    SELECT trang_thai, COUNT(*) as so_luong
    FROM chuyen_bay
    WHERE gio_di BETWEEN ? AND ?
    GROUP BY trang_thai
");
$stmt->execute([$from . " 00:00:00", $to . " 23:59:59"]);
$flights = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt->execute([$from . " 00:00:00", $to . " 23:59:59"]);
$flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Hạng ghế được chọn
$stmt = $pdo->prepare("
    SELECT h.ten, COUNT(*) as so_ve
    FROM ve v
    JOIN hang_ghe h ON v.hang_ghe_id = h.id
    WHERE v.phat_hanh_luc BETWEEN ? AND ?
    GROUP BY h.ten
");
$stmt->execute([$from . " 00:00:00", $to . " 23:59:59"]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include dirname(__DIR__) . '/../templates/reports_view.php';
?>