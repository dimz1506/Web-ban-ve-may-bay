<?php
// session_start();
// if (!isset($_SESSION['user_id'])) {
//   header('Location: index.php?p=login');
//   exit;
// }
if (!function_exists('db')) require_once dirname(__DIR__,2).'/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$ve_id = (int)($_GET['ve_id'] ?? 0);
if ($ve_id <= 0) die("Thiếu ID vé.");

// Kiểm tra cột giới tính
$colCheck = db()->query("SHOW COLUMNS FROM hanh_khach LIKE 'gioi_tinh'")->fetch();
$hasGenderCol = !!$colCheck;

// 🔹 Lấy thông tin vé + hành khách
$sql = "
  SELECT v.id AS ve_id, v.so_ve, v.trang_thai, v.so_ghe,
         d.pnr, d.id AS dat_cho_id, d.created_at,
         cb.so_hieu, cb.gio_di, cb.gio_den,
         s1.ten AS san_bay_di, s2.ten AS san_bay_den,
         hg.ten AS hang_ghe,
         hk.ho_ten, hk.ngay_sinh, hk.so_giay_to
         ".($hasGenderCol ? ", hk.gioi_tinh" : "")."
  FROM ve v
  JOIN dat_cho d   ON d.id = v.dat_cho_id
  JOIN chuyen_bay cb ON cb.id = v.chuyen_bay_id
  JOIN tuyen_bay tb  ON tb.id = cb.tuyen_bay_id
  JOIN san_bay s1 ON s1.ma = tb.di
  JOIN san_bay s2 ON s2.ma = tb.den
  JOIN hang_ghe hg ON hg.id = v.hang_ghe_id
  JOIN hanh_khach hk ON hk.id = v.hanh_khach_id
  WHERE v.id=?
";
$st = db()->prepare($sql);
$st->execute([$ve_id]);
$ve = $st->fetch();

if (!$ve) die("Không tìm thấy vé.");

$dat_cho_id = (int)$ve['dat_cho_id'];
$hk_name = $ve['ho_ten'];
$hk_doc  = $ve['so_giay_to'];

// 🔹 Lấy tất cả vé khứ hồi của hành khách trong cùng đặt chỗ
$sql2 = "
  SELECT 
    v.id AS ve_id, v.so_ve, v.so_ghe, v.trang_thai,
    cb.so_hieu, cb.gio_di, cb.gio_den,
    s1.ten AS san_bay_di, s2.ten AS san_bay_den,
    hg.ten AS hang_ghe
  FROM ve v
  JOIN chuyen_bay cb ON cb.id = v.chuyen_bay_id
  JOIN tuyen_bay tb ON tb.id = cb.tuyen_bay_id
  JOIN san_bay s1 ON s1.ma = tb.di
  JOIN san_bay s2 ON s2.ma = tb.den
  JOIN hang_ghe hg ON hg.id = v.hang_ghe_id
  JOIN hanh_khach hk ON hk.id = v.hanh_khach_id
  WHERE v.dat_cho_id = ? AND hk.ho_ten = ? AND hk.so_giay_to = ?
  ORDER BY cb.gio_di ASC
";
$st2 = db()->prepare($sql2);
$st2->execute([$dat_cho_id, $hk_name, $hk_doc]);
$flights = $st2->fetchAll();

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function fmtDT($d){ return $d ? date('d/m/Y H:i', strtotime($d)) : ''; }

$gioi_tinh = isset($ve['gioi_tinh'])
  ? ($ve['gioi_tinh'] === 'M' ? 'Nam' : ($ve['gioi_tinh'] === 'F' ? 'Nữ' : 'Khác'))
  : 'Không rõ';
$isRoundTrip = count($flights) > 1;
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Vé điện tử - <?=h($ve['ho_ten'])?></title>
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: #f1f5f9;
  margin: 0;
  padding: 30px;
}
.ticket {
  max-width: 850px;
  margin: auto;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
  padding: 24px 30px;
}
header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #1e3a8a;
  padding-bottom: 10px;
  margin-bottom: 18px;
}
.logo {display: flex;align-items: center;gap: 8px;}
.logo div:first-child {font-size: 28px;}
h1 {font-size: 20px;color: #1e3a8a;margin: 0;}
.section {margin-bottom: 18px;}
.section h2 {font-size: 16px;color: #1e3a8a;border-bottom: 1px solid #cbd5e1;padding-bottom: 4px;}
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
  font-size: 14px;
  color: #334155;
}
.label {font-weight: 600;}
.status {
  display:inline-block;padding: 4px 8px;
  border-radius: 6px;font-weight: 600;font-size: 12px;
}
.status.DA_XUAT,.status.CONFIRMED {background:#dcfce7;color:#166534;}
.status.HUY {background:#fee2e2;color:#991b1b;}
.status.PENDING {background:#fef3c7;color:#92400e;}
.flight-box {
  border:1px solid #e2e8f0;
  border-radius:10px;
  padding:12px 16px;
  margin-top:10px;
}
.flight-go {background:#eff6ff;} /* Xanh nhạt cho chiều đi */
.flight-return {background:#f9fafb;} /* Xám nhạt cho chiều về */
.summary-box {
  background:#eef2ff;
  border:1px solid #c7d2fe;
  border-radius:12px;
  padding:12px 14px;
  margin:16px 0;
  font-size:15px;
}
footer {text-align:center;font-size:13px;color:#64748b;margin-top:20px;}
@media print {body {background:#fff;} .btn {display:none;}}
.btn {
  display:inline-block;
  background:#1e40af;
  color:white;
  padding:8px 14px;
  border-radius:8px;
  text-decoration:none;
  margin-top:10px;
}
.btn:hover {background:#1e3a8a;}
.btn.outline {
  background:transparent;
  color:#1e40af;
  border:1px solid #1e40af;
}
.btn.outline:hover {background:#e0e7ff;}
</style>
</head>
<body>

<div class="ticket">
  <header>
    <div class="logo">
      <div>✈</div>
      <div><h1>VNAir Ticket</h1></div>
    </div>
    <div>
      <div style="font-size:14px;">PNR: <strong><?=h($ve['pnr'])?></strong></div>
      <div style="font-size:13px;color:#475569;">Số vé: <?=h($ve['so_ve'])?></div>
    </div>
  </header>

  <div class="section">
    <h2>Thông tin hành khách</h2>
    <div class="info-grid">
      <div><span class="label">Họ tên:</span> <?=h($ve['ho_ten'])?></div>
      <div><span class="label">Giới tính:</span> <?=h($gioi_tinh)?></div>
      <div><span class="label">Ngày sinh:</span> <?=h($ve['ngay_sinh'])?></div>
      <div><span class="label">CMND/Hộ chiếu:</span> <?=h($ve['so_giay_to'])?></div>
    </div>
  </div>

  <div class="section">
    <h2>Thông tin chuyến bay<?= $isRoundTrip ? ' (Khứ hồi)' : '' ?></h2>
    <?php foreach ($flights as $i => $f): ?>
      <div class="flight-box <?=$i==0?'flight-go':'flight-return'?>">
        <div style="font-weight:600;color:#1e3a8a;">
          <?= $i==0 ? '✈ Chiều đi:' : '↩ Chiều về:' ?>
          <?=$f['san_bay_di']?> → <?=$f['san_bay_den']?> (<?=$f['so_hieu']?>)
        </div>
        <div class="info-grid" style="margin-top:6px;">
          <div><span class="label">Ghế:</span> <?=h($f['so_ghe'])?></div>
          <div><span class="label">Hạng:</span> <?=h($f['hang_ghe'])?></div>
          <div><span class="label">Trạng thái:</span> <span class="status <?=$f['trang_thai']?>"><?=h($f['trang_thai'])?></span></div>
          <div><span class="label">Giờ đi:</span> <?=fmtDT($f['gio_di'])?></div>
          <div><span class="label">Giờ đến:</span> <?=fmtDT($f['gio_den'])?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($isRoundTrip): ?>
    <div class="summary-box">
      Loại vé: <strong>Khứ hồi</strong> • Ưu đãi: <strong>Giảm giá 10%</strong><br>
      Tổng số chặng: <?=count($flights)?> • Ngày đặt: <?=date('d/m/Y', strtotime($ve['created_at']))?>
    </div>
  <?php else: ?>
    <div class="summary-box">
      Loại vé: <strong>Một chiều</strong> • Ngày đặt: <?=date('d/m/Y', strtotime($ve['created_at']))?>
    </div>
  <?php endif; ?>

  <div style="text-align:center;margin-top:20px;display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
    <a href="#" class="btn" onclick="window.print()">🖨 In vé</a>
    <a href="index.php?p=my_bookings&pnr=<?=urlencode($ve['pnr'] ?? '')?>" class="btn outline">← Quay lại</a>
  </div>
</div>

<footer>
  © <?=date('Y')?> VNAir Ticket — Vé điện tử hợp lệ cho <?=$isRoundTrip ? 'cả chiều đi và chiều về' : 'chuyến bay đã chọn'?>.
</footer>

</body>
</html>
