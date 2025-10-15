<?php
if (!function_exists('db')) { require_once dirname(__DIR__,2).'/config.php'; }
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $flight_id   = (int)$_POST['flight_id'];
    $cabin       = trim($_POST['cabin']);
    $price_per   = (float)$_POST['price_per'];
    $seats       = (array)($_POST['seats'] ?? []);
    $passengers  = $_POST['passengers'] ?? [];
} else {
    die("Truy cập không hợp lệ.");
}

// Lấy thông tin chuyến bay
$st = db()->prepare(
  "SELECT cb.so_hieu, s1.ten as san_bay_di, s2.ten as san_bay_den, cb.gio_di, cb.gio_den, tb.id as tuyen_id
   FROM chuyen_bay cb
   JOIN tuyen_bay tb ON cb.tuyen_bay_id=tb.id
   JOIN san_bay s1 ON tb.di=s1.ma
   JOIN san_bay s2 ON tb.den=s2.ma
   WHERE cb.id=?"
);
$st->execute([$flight_id]);
$flight = $st->fetch();

$pnr   = strtoupper(substr(bin2hex(random_bytes(6)),0,6));
$total = count($seats) * $price_per;

// Giả sử có session user (test tạm id=1)
$khach_hang_id = $_SESSION['user_id'] ?? 1;

if (isset($_POST['confirm_booking'])) {
    // Tạo record trong dat_cho (booking)
    $st = db()->prepare("INSERT INTO dat_cho (khach_hang_id, pnr, trang_thai) VALUES (?, ?, 'DA_XUAT')");
    $st->execute([$khach_hang_id, $pnr]);
    $dat_cho_id = db()->lastInsertId();

    // Lấy id hạng ghế
    $stHg = db()->prepare("SELECT id FROM hang_ghe WHERE ma=? LIMIT 1");
    $stHg->execute([$cabin]);
    $hang_ghe_id = $stHg->fetchColumn();

    foreach ($passengers as $i=>$p) {
        $seat = $seats[$i] ?? null;

        // 1. Lưu hành khách
        $st1 = db()->prepare("INSERT INTO hanh_khach 
            (dat_cho_id, loai, ho_ten, gioi_tinh, ngay_sinh, loai_giay_to, so_giay_to, quoc_tich) 
            VALUES (?, 'NGUOI_LON', ?, ?, ?, 'CMND', ?, 'VN')");
        $st1->execute([$dat_cho_id, $p['ho_ten'], $p['gioi_tinh'], $p['ngay_sinh'], $p['giay_to']]);
        $hanh_khach_id = db()->lastInsertId();

        // Sinh số vé duy nhất
        $so_ve = strtoupper("VE".time().rand(1000,9999));

        // 2. Lưu vé
        $st2 = db()->prepare("INSERT INTO ve 
            (dat_cho_id, hanh_khach_id, chuyen_bay_id, hang_ghe_id, so_ghe, so_ve, trang_thai) 
            VALUES (?, ?, ?, ?, ?, ?, 'DA_XUAT')");
        $st2->execute([$dat_cho_id, $hanh_khach_id, $flight_id, $hang_ghe_id, $seat, $so_ve]);
    }

    header("Location: index.php?p=my_bookings&pnr=".$pnr);
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Xác nhận vé</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    .ticket {background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px;margin:16px 0}
    .ticket-header {display:flex;justify-content:space-between;align-items:center}
    .ticket h2 {margin:0;}
    .passenger {margin:.5rem 0;padding:.5rem;border-bottom:1px dashed #ccc}
    .btn-row {margin-top:2rem;display:flex;gap:1rem;}
    .btn {background:#1e40af;color:#fff;padding:.5rem 1.2rem;
          border-radius:8px;text-decoration:none;text-align:center;}
    .btn:hover {background:#1e3a8a;}
    .btn.outline {background:transparent;border:1px solid #1e40af;color:#1e40af;}
    .btn.outline:hover {background:#e0e7ff;}
  </style>
</head>
<body>
  <header class="topbar">
  <div class="container nav">
    <div class="brand">
      <div class="logo">✈</div>
      <div>VNAir Ticket</div>
    </div>
    <nav>
      <a href="index.php#uu-dai">Ưu đãi</a>
      <a href="index.php#quy-trinh">Quy trình</a>
      <a href="index.php#lien-he">Liên hệ</a>
    </nav>
  </div>
</header>
<main class="container">
  <div class="ticket">
    <div class="ticket-header">
      <h2>Xác nhận vé - PNR: <?=$pnr?></h2>
      <button onclick="window.print()" class="btn outline">🖨 In vé</button>
    </div>

    <?php if ($flight): ?>
      <p>
        Chuyến: <strong><?=htmlspecialchars($flight['so_hieu'])?></strong><br>
        Hành trình: <?=htmlspecialchars($flight['san_bay_di'])?> → <?=htmlspecialchars($flight['san_bay_den'])?><br>
        Giờ đi: <?=$flight['gio_di']?> — Giờ đến: <?=$flight['gio_den']?><br>
        Hạng: <?=$cabin?><br>
        Tổng tiền: <strong><?=number_format($total)?> VND</strong>
      </p>
    <?php endif; ?>

    <h3>Danh sách hành khách</h3>
    <?php foreach ($passengers as $i=>$p): ?>
      <div class="passenger">
        <strong><?=$i+1?>. <?=htmlspecialchars($p['ho_ten'])?></strong><br>
        Giới tính: <?=htmlspecialchars($p['gioi_tinh'])?><br>
        Ngày sinh: <?=htmlspecialchars($p['ngay_sinh'])?><br>
        CMND/Hộ chiếu: <?=htmlspecialchars($p['giay_to'])?><br>
        Ghế: <?=htmlspecialchars($seats[$i]??'')?>
      </div>
    <?php endforeach; ?>

    <form method="post">
      <?php foreach ($_POST as $k=>$v): 
        if ($k==='confirm_booking') continue;
        if (is_array($v)) {
          foreach ($v as $sk=>$sv) {
            if (is_array($sv)) {
              foreach ($sv as $kk=>$vv) {
                echo '<input type="hidden" name="passengers['.$sk.']['.$kk.']" value="'.htmlspecialchars($vv).'">';
              }
            } else {
              echo '<input type="hidden" name="'.$k.'[]" value="'.htmlspecialchars($sv).'">';
            }
          }
        } else {
          echo '<input type="hidden" name="'.$k.'" value="'.htmlspecialchars($v).'">';
        }
      endforeach; ?>
      <div class="btn-row">
        <button type="submit" name="confirm_booking" class="btn">Xác nhận đặt vé</button>
        <button type="submit" name="go_back" formaction="index.php?p=add_passengers" class="btn outline">
          ← Quay lại nhập thông tin khách
        </button>
      </div>
    </form>
  </div>
</main>
<footer class="footer">
  <div class="container">&copy; <?=date('Y')?> VNAir Ticket</div>
</footer>
</body>
</html>
