<?php
require_once dirname(__DIR__,2).'/includes/db.php';
require_once dirname(__DIR__,2).'/includes/auth.php';
require_login(['CUSTOMER']);
$user = me();
$pdo = db();
// Thông báo trạng thái đặt chỗ
$stmt = $pdo->prepare("SELECT pnr, trang_thai, created_at, xac_nhan_luc FROM dat_cho WHERE khach_hang_id=? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();
// Thông báo khuyến mãi
$now = date('Y-m-d H:i:s');
$promoStmt = $pdo->prepare("SELECT ma, kieu, gia_tri, bat_dau, ket_thuc FROM khuyen_mai WHERE kich_hoat=1 AND bat_dau<=? AND ket_thuc>=? ORDER BY bat_dau DESC LIMIT 5");
$promoStmt->execute([$now, $now]);
$promos = $promoStmt->fetchAll();
?>
<h1>Thông báo của bạn</h1>
<h2>Trạng thái đặt chỗ gần đây</h2>
<?php if (!$bookings): ?>
    <p>Bạn chưa có đặt chỗ nào.</p>
<?php else: ?>
    <ul>
    <?php foreach ($bookings as $b): ?>
        <li>
            <b>Mã đặt chỗ:</b> <?=htmlspecialchars($b['pnr'])?> -
            <b>Trạng thái:</b> <?=htmlspecialchars($b['trang_thai'])?>
            <i>(<?=htmlspecialchars($b['xac_nhan_luc'] ?: $b['created_at'])?>)</i>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
<h2>Khuyến mãi đang diễn ra</h2>
<?php if (!$promos): ?>
    <p>Hiện không có khuyến mãi nào.</p>
<?php else: ?>
    <ul>
    <?php foreach ($promos as $p): ?>
        <li>
            <b>Mã:</b> <?=htmlspecialchars($p['ma'])?> -
            <b>Kiểu:</b> <?=htmlspecialchars($p['kieu'])?> -
            <b>Giá trị:</b> <?=number_format($p['gia_tri'])?> -
            <b>Thời gian:</b> <?=htmlspecialchars($p['bat_dau'])?> đến <?=htmlspecialchars($p['ket_thuc'])?>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

