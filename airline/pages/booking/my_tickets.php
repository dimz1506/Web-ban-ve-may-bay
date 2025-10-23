<?php
// pages/booking/my_tickets.php
if (!function_exists('db')) { 
    require_once dirname(__DIR__,2).'/config.php'; 
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy filter trên URL
$filter = $_GET['filter'] ?? 'all';

// Lấy danh sách tất cả đặt chỗ (PNR) kèm trạng thái từng vé
$st = db()->query(
  "SELECT d.id, d.pnr, d.created_at,
          cb.so_hieu, cb.gio_di, cb.gio_den, 
          s1.ten AS san_bay_di, s2.ten AS san_bay_den,
          GROUP_CONCAT(v.trang_thai SEPARATOR ', ') AS trang_thai_ve,
          COUNT(v.id) AS tong_ve
   FROM dat_cho d
   JOIN ve v ON v.dat_cho_id = d.id
   JOIN chuyen_bay cb ON v.chuyen_bay_id = cb.id
   JOIN tuyen_bay tb ON cb.tuyen_bay_id = tb.id
   JOIN san_bay s1 ON tb.di=s1.ma
   JOIN san_bay s2 ON tb.den=s2.ma
   GROUP BY d.id
   ORDER BY d.created_at DESC"
);
$bookings_all = $st->fetchAll();

// Lọc theo trạng thái
$bookings = [];
foreach ($bookings_all as $b) {
    $statuses = array_map('trim', explode(',', $b['trang_thai_ve']));
    if ($filter === 'confirmed' && !in_array('CONFIRMED', $statuses) && !in_array('DA_XUAT', $statuses)) continue;
    if ($filter === 'huy' && !in_array('HUY', $statuses)) continue;
    $bookings[] = $b;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>Vé của tôi</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    .ticket-list {max-width:900px;margin:20px auto;}
    .ticket-card {background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0;}
    .ticket-card h3 {margin:0 0 8px;}
    .btn {background:#1e40af;color:#fff;padding:.4rem .8rem;border-radius:6px;text-decoration:none;}
    .btn:hover {background:#1e3a8a;}
    .status {font-weight:bold;}
    .status span {margin-right:6px; padding:2px 6px; border-radius:4px;}
    .status .CONFIRMED, .status .DA_XUAT {color:#166534; background:#dcfce7;}
    .status .HUY {color:#991b1b; background:#fee2e2;}
    .status .PENDING {color:#92400e; background:#fef3c7;}
    .stats {font-size:14px;color:#444;margin-top:6px;}
    .filters {margin:1rem 0;}
    .filters a {margin-right:10px; text-decoration:none; font-weight:600;}
  </style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    
</header>

<main class="container ticket-list">
  <h2>Danh sách vé đã đặt</h2>

  <div class="filters">
    <a href="index.php?p=my_tickets" <?= $filter==='all'?'style="color:#1e40af"':''?>>Tất cả</a>
    <a href="index.php?p=my_tickets&filter=confirmed" <?= $filter==='confirmed'?'style="color:#1e40af"':''?>>Vé còn hiệu lực</a>
    <a href="index.php?p=my_tickets&filter=huy" <?= $filter==='huy'?'style="color:#1e40af"':''?>>Vé đã hủy</a>
  </div>

  <?php if(!$bookings): ?>
    <p>Không tìm thấy vé nào phù hợp.</p>
  <?php else: ?>
    <?php foreach($bookings as $b): ?>
      <?php 
        $statuses = array_map('trim', explode(',', $b['trang_thai_ve']));
        $count_confirmed = count(array_filter($statuses, fn($s) => $s === 'CONFIRMED' || $s==='DA_XUAT'));
        $count_huy = count(array_filter($statuses, fn($s) => $s === 'HUY'));
        $count_pending = count(array_filter($statuses, fn($s) => $s === 'PENDING'));
      ?>
      <div class="ticket-card">
        <h3>PNR: <?=htmlspecialchars($b['pnr'])?></h3>
        <p>
          <strong>Chuyến:</strong> <?=htmlspecialchars($b['so_hieu'])?><br>
          <strong>Hành trình:</strong> <?=htmlspecialchars($b['san_bay_di'])?> → <?=htmlspecialchars($b['san_bay_den'])?><br>
          <strong>Giờ đi:</strong> <?=$b['gio_di']?> — 
          <strong>Giờ đến:</strong> <?=$b['gio_den']?><br>
          <strong>Trạng thái vé:</strong> 
            <span class="status">
              <?php foreach($statuses as $st): ?>
                <span class="<?=htmlspecialchars($st)?>"><?=htmlspecialchars($st)?></span>
              <?php endforeach; ?>
            </span><br>
          <div class="stats">
            Tổng vé: <?=$b['tong_ve']?> | 
            ✅ Hiệu lực: <?=$count_confirmed?> | 
            ❌ Đã hủy: <?=$count_huy?> 
            <?php if($count_pending>0): ?>| ⏳ Chờ xử lý: <?=$count_pending?><?php endif; ?>
          </div>
          <small>Ngày đặt: <?=$b['created_at']?></small>
        </p>
        <a href="index.php?p=my_bookings&pnr=<?=$b['pnr']?>" class="btn">Xem chi tiết</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div style="margin-top:1.5rem; display:flex; gap:1rem;">
    <a href="javascript:history.back()" class="btn outline">← Quay lại</a>
    <a href="index.php?p=customer" class="btn">🏠 Trang chủ</a>
  </div>
</main>

<footer class="footer">
  <div class="container">&copy; <?=date('Y')?> VNAir Ticket</div>
</footer>
</body>
</html>
