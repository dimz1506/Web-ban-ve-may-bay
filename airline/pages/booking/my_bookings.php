<?php
// pages/booking/my_bookings.php
if (!function_exists('db')) { 
    require_once dirname(__DIR__,2).'/config.php'; 
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy PNR từ URL
$pnr = $_GET['pnr'] ?? null;
if (!$pnr) {
    echo "<h2>Đặt chỗ của tôi</h2><p>Không tìm thấy PNR.</p>";
    exit;
}

// ===== XỬ LÝ HỦY VÉ =====
if (isset($_POST['cancel_ticket_id'])) {
    $ticket_id = (int)$_POST['cancel_ticket_id'];
    $stCancel = db()->prepare("UPDATE ve SET trang_thai='HUY' WHERE id=?");
    $stCancel->execute([$ticket_id]);
    $_SESSION['flash'] = "Hủy vé thành công!";
    header("Location: index.php?p=my_bookings&pnr=" . urlencode($pnr));
    exit;
}

// ===== LẤY THÔNG TIN ĐẶT CHỖ =====
$st = db()->prepare(
  "SELECT d.*, cb.so_hieu, cb.gio_di, cb.gio_den, 
          s1.ten AS san_bay_di, s2.ten AS san_bay_den
   FROM dat_cho d
   JOIN ve v ON v.dat_cho_id = d.id
   JOIN chuyen_bay cb ON v.chuyen_bay_id = cb.id
   JOIN tuyen_bay tb ON cb.tuyen_bay_id = tb.id
   JOIN san_bay s1 ON tb.di=s1.ma
   JOIN san_bay s2 ON tb.den=s2.ma
   WHERE d.pnr = ?
   LIMIT 1"
);
$st->execute([$pnr]);
$booking = $st->fetch();

if (!$booking) {
    echo "<h2>Đặt chỗ của tôi</h2><p>Không tìm thấy thông tin đặt chỗ.</p>";
    exit;
}

// ===== LẤY DANH SÁCH VÉ CÒN HIỆU LỰC =====
$st2 = db()->prepare(
  "SELECT v.*, h.ho_ten, h.gioi_tinh, h.ngay_sinh, h.so_giay_to
   FROM ve v
   JOIN hanh_khach h ON v.hanh_khach_id = h.id
   WHERE v.dat_cho_id = ? AND v.trang_thai != 'HUY'"
);
$st2->execute([$booking['id']]);
$passengers = $st2->fetchAll();
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Đặt chỗ của tôi</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    .booking-card {background:#fff;border:1px solid #ddd;border-radius:12px;padding:20px;margin:20px auto;max-width:800px;}
    .booking-header {display:flex;justify-content:space-between;align-items:center;}
    .booking-header h2{margin:0;}
    .alert {padding:10px 15px;margin:10px 0;border-radius:8px;}
    .alert.success {background:#dcfce7;color:#166534;}
    .passenger {display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px dashed #ccc;padding:12px 0;}
    .passenger-info {flex:1;}
    .passenger-qr {margin-left:20px;}
    .passenger-qr img {border:1px solid #ddd;border-radius:8px;padding:4px;background:#fff;}
    .btn-row {margin-top:1rem;display:flex;gap:1rem;}
    .btn {background:#1e40af;color:#fff;padding:.4rem 1rem;border-radius:8px;text-decoration:none;}
    .btn:hover {background:#1e3a8a;}
    .btn.outline {background:transparent;border:1px solid #1e40af;color:#1e40af;}
    .btn.outline:hover {background:#e0e7ff;}
  </style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    <nav>
      <a href="index.php">Trang chủ</a>
      <a href="index.php?p=my_bookings">Vé đã đặt</a>
    </nav>
  </div>
</header>

<main class="container">
  <h2>Đặt chỗ của tôi</h2>

  <?php if(isset($_SESSION['flash'])): ?>
    <div class="alert success"><?= $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
  <?php endif; ?>

  <div class="booking-card">
    <div class="booking-header">
      <h2>PNR: <?=htmlspecialchars($pnr)?></h2>
      <button onclick="window.print()" class="btn outline">🖨 In vé</button>
    </div>
    <p>
      <strong>Chuyến:</strong> <?=htmlspecialchars($booking['so_hieu'])?><br>
      <strong>Hành trình:</strong> <?=htmlspecialchars($booking['san_bay_di'])?> → <?=htmlspecialchars($booking['san_bay_den'])?><br>
      <strong>Giờ đi:</strong> <?=$booking['gio_di']?> — 
      <strong>Giờ đến:</strong> <?=$booking['gio_den']?><br>
      <strong>Trạng thái:</strong> <?=htmlspecialchars($booking['trang_thai'])?>
    </p>

    <p style="font-size:14px;color:#555;margin-bottom:1rem;">
      * Khách hàng có thể <strong>hủy hoặc sửa thông tin vé</strong> 
      trong vòng <strong>24–48 giờ sau khi đặt vé</strong>.  
      Quá thời hạn, vui lòng liên hệ tổng đài hỗ trợ.
    </p>

    <?php if (empty($passengers)): ?>
      <p style="color:#d00;font-weight:500;margin-top:1rem;">
        Tất cả vé trong đặt chỗ này đã bị hủy. Vui lòng liên hệ tổng đài để được hỗ trợ đặt lại.
      </p>
    <?php else: ?>
      <h3>Danh sách hành khách</h3>
      <?php foreach ($passengers as $i=>$p): ?>
        <div class="passenger">
          <div class="passenger-info">
            <strong><?=$i+1?>. <?=htmlspecialchars($p['ho_ten'])?></strong><br>
            Giới tính: <?=htmlspecialchars($p['gioi_tinh'])?><br>
            Ngày sinh: <?=htmlspecialchars($p['ngay_sinh'])?><br>
            CMND/Hộ chiếu: <?=htmlspecialchars($p['so_giay_to'])?><br>
            Ghế: <?=htmlspecialchars($p['so_ghe'])?><br>
            Số vé: <?=htmlspecialchars($p['so_ve'])?><br>

            <div class="btn-row">
              <?php
                $dat_cho_time = strtotime($booking['created_at'] ?? ''); 
                $now = time();
                $hours_passed = ($now - $dat_cho_time)/3600;
                if ($hours_passed <= 48): 
              ?>
                <a href="index.php?p=edit_ticket&id=<?=$p['id']?>" class="btn outline">✏ Sửa</a>
                <form method="post" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn hủy vé này?')">
                  <input type="hidden" name="cancel_ticket_id" value="<?=$p['id']?>">
                  <button type="submit" class="btn outline">❌ Hủy</button>
                </form>
              <?php else: ?>
                <span style="color:#999;font-size:13px;">(Hết hạn hủy/sửa)</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="passenger-qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?=urlencode($p['so_ve'])?>" alt="QR vé">
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="btn-row" style="margin-top:1.5rem;">
    <a href="index.php?p=customer" class="btn">← Quay lại Trang chủ</a>
  </div>
  <div class="btn-row" style="margin-top:1rem;">
  <a href="index.php?p=my_tickets" class="btn outline">📋 Xem tất cả vé của tôi</a>
</div>

</main>

<footer class="footer">
  <div class="container">&copy; <?=date('Y')?> VNAir Ticket</div>
</footer>
</body>
</html>
