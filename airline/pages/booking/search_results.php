<?php

if (!function_exists('db')) { 
    require_once dirname(__DIR__,2).'/config.php'; 
}

$from = strtoupper(trim($_GET['from'] ?? ''));
$to   = strtoupper(trim($_GET['to'] ?? ''));
$depart = trim($_GET['depart'] ?? ($_GET['date'] ?? ''));
$return = trim($_GET['return'] ?? '');
$cab  = trim($_GET['cabin'] ?? 'ECON');

// Loại hành khách
$adults = (int)($_GET['adults'] ?? 1);
$children = (int)($_GET['children'] ?? 0);
$infants = (int)($_GET['infants'] ?? 0);

// ===== Lấy id hạng ghế =====
$hgId = db()->prepare("SELECT id FROM hang_ghe WHERE ma=? LIMIT 1");
$hgId->execute([$cab]);
$hang_ghe_id = $hgId->fetchColumn();

// ===== Lấy danh sách chuyến đi =====
$st = db()->prepare("
   SELECT cb.id as chuyen_id, cb.so_hieu, cb.gio_di, cb.gio_den,
          s1.ten as ten_di, s2.ten as ten_den,
          cgh.gia_co_ban, cgh.so_ghe_con
   FROM tuyen_bay tb
   JOIN san_bay s1 ON s1.ma=tb.di
   JOIN san_bay s2 ON s2.ma=tb.den
   JOIN chuyen_bay cb ON cb.tuyen_bay_id=tb.id
   JOIN chuyen_bay_gia_hang cgh 
     ON cgh.chuyen_bay_id=cb.id AND cgh.hang_ghe_id=?
   WHERE tb.di=? AND tb.den=? 
     AND DATE(cb.gio_di)=? 
     AND cb.trang_thai='LEN_KE_HOACH'
   ORDER BY cb.gio_di ASC
");
$st->execute([$hang_ghe_id, $from, $to, $depart]);
$rows_go = $st->fetchAll();

// ===== Nếu có ngày về -> tìm chuyến về =====
$rows_back = [];
if (!empty($return)) {
    $st2 = db()->prepare("
       SELECT cb.id as chuyen_id, cb.so_hieu, cb.gio_di, cb.gio_den,
              s1.ten as ten_di, s2.ten as ten_den,
              cgh.gia_co_ban, cgh.so_ghe_con
       FROM tuyen_bay tb
       JOIN san_bay s1 ON s1.ma=tb.di
       JOIN san_bay s2 ON s2.ma=tb.den
       JOIN chuyen_bay cb ON cb.tuyen_bay_id=tb.id
       JOIN chuyen_bay_gia_hang cgh 
         ON cgh.chuyen_bay_id=cb.id AND cgh.hang_ghe_id=?
       WHERE tb.di=? AND tb.den=? 
         AND DATE(cb.gio_di)=? 
         AND cb.trang_thai='LEN_KE_HOACH'
       ORDER BY cb.gio_di ASC
    ");
    $st2->execute([$hang_ghe_id, $to, $from, $return]);
    $rows_back = $st2->fetchAll();
}

// ===== Hàm tiện ích =====
function fmtDate($d) { return date('d/m/Y', strtotime($d)); }
function fmtTime($d) { return date('H:i', strtotime($d)); }
function diffMinutes($start, $end) {
  $diff = abs(strtotime($end) - strtotime($start));
  $hours = floor($diff / 3600);
  $mins = floor(($diff % 3600) / 60);
  return sprintf("%dh%02dm", $hours, $mins);
}
function calcTotalPrice($base, $adults, $children, $infants) {
  return $adults*$base + $children*$base*0.75 + $infants*$base*0.1;
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Kết quả tìm kiếm</title>
<link rel="stylesheet" href="assets/home.css">
<style>
  input, select {
  width:100%;
  padding:.55rem .75rem;
  border:1px solid #cbd5e1;
  border-radius:10px;       /* ✅ BO GÓC ở đây */
  background-color:#f8fafc;
  font-size:0.95rem;
  transition:border-color .2s, box-shadow .2s;
}

input:focus, select:focus {
  outline:none;
  border-color:#1e40af;
  box-shadow:0 0 0 3px rgba(30,64,175,0.2); /* ✅ hiệu ứng sáng khi focus */
  background:#fff;
}

body {
  background:#f1f5f9;
  margin:0;
  font-family:'Segoe UI', sans-serif;
  display:flex;
  flex-direction:column;
  min-height:100vh;
}
header, footer { flex-shrink:0; }
footer { text-align:center; color:#64748b; margin:1rem 0; }
main.container {
  flex:1; display:flex; flex-direction:column; align-items:center;
  width:95%; max-width:950px;
  background:#fff; border-radius:16px;
  box-shadow:0 4px 10px rgba(0,0,0,.08);
  padding:2rem 2.5rem; margin:2rem auto;
}
.trip-summary {
  background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;
  padding:1rem 2rem; margin-bottom:1.5rem; width:85%;
  max-width:750px; text-align:center;
  box-shadow:0 1px 3px rgba(0,0,0,.05);
}
.section-title {
  text-align:center; margin-top:2rem;
  border-bottom:2px solid #1e40af; padding-bottom:4px;
  color:#1e40af; width:100%;
}
.card {
  width:80%; max-width:700px; background:#fff;
  border:1px solid #e2e8f0; border-radius:12px;
  padding:20px; margin:16px 0;
  box-shadow:0 2px 4px rgba(0,0,0,.05);
  display:flex; justify-content:space-between; gap:20px;
  transition:.25s;
}
.card:hover { transform:translateY(-3px); background:#f8fafc; }
.card.best-price { border:2px solid #1e40af; background:#f0f7ff; }
.card-left { flex:3; }
.card-right { flex:1; text-align:right; }
.card h3 { margin-bottom:6px; }
.card p { font-size:0.9rem; margin:3px 0; }
.price { font-size:1rem; font-weight:bold; color:#1e40af; }
.btn { background:#1e40af; color:#fff; padding:.5rem 1.5rem;
  border-radius:8px; text-decoration:none; font-size:0.9rem; transition:.3s;}
.btn:hover { background:#1e3a8a; transform:translateY(-2px);}
.btn.outline {background:#fff;border:1px solid #1e40af;color:#1e40af;}
.btn.outline:hover {background:#e0e7ff;}

</style>
</head>
<body>

<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    
  </div>
</header>

<main class="container">
  <h2 style="text-align:center;">Kết quả tìm chuyến</h2>

  <div class="trip-summary">
    <h3 style="margin:0 0 .5rem 0; color:#1e3a8a;">
      ✈ <?=htmlspecialchars($from)?> → <?=htmlspecialchars($to)?>
      <?php if (!empty($return)): ?>
        <span style="color:#64748b;">⇄ <?=htmlspecialchars($to)?> → <?=htmlspecialchars($from)?></span>
      <?php endif; ?>
    </h3>
    <p>
      <strong>Ngày đi:</strong> <?=fmtDate($depart)?>
      <?php if (!empty($return)): ?> | <strong>Ngày về:</strong> <?=fmtDate($return)?><?php endif; ?>
      | <strong>Hạng:</strong> <?=htmlspecialchars($cab)?><br>
      <!-- 👤 Người lớn: <?=$adults?> | 👦 Trẻ em: <?=$children?> | 👶 Em bé: <?=$infants?> -->
    </p>
  </div>

  <h3 class="section-title">✈ Chuyến đi (<?=$from?> → <?=$to?>)</h3>
  <?php if (!$rows_go): ?>
    <p>Không tìm thấy chuyến bay phù hợp cho chiều đi.</p>
  <?php else: ?>
    <?php foreach ($rows_go as $r): 
      $time = diffMinutes($r['gio_di'], $r['gio_den']);
      $totalPrice = calcTotalPrice($r['gia_co_ban'], $adults, $children, $infants);
    ?>
      <div class="card <?=($r['gia_co_ban']==min(array_column($rows_go,'gia_co_ban')))?'best-price':''?>">
        <div class="card-left">
          <h3><?=$r['so_hieu']?> <?=($r['gia_co_ban']==min(array_column($rows_go,'gia_co_ban')))?'<span style="color:#16a34a;font-size:0.9rem;">(Rẻ nhất)</span>':''?></h3>
          <p><strong>Đi:</strong> <?=$r['ten_di']?> (<?=fmtTime($r['gio_di'])?>)</p>
          <p><strong>Đến:</strong> <?=$r['ten_den']?> (<?=fmtTime($r['gio_den'])?>)</p>
          <p><strong>⏱ Thời gian bay:</strong> <?=$time?></p>
          <p><strong>Số ghế trống:</strong> <?=$r['so_ghe_con']?></p>
        </div>
        <div class="card-right">
          <p class="price"><?=number_format($totalPrice)?> VND</p>
          <a class="btn" href="index.php?p=select_seat&flight_id=<?=$r['chuyen_id']?>&cabin=<?=$cab?>">Chọn</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($return)): ?>
    <h3 class="section-title">↩ Chuyến về (<?=$to?> → <?=$from?>)</h3>
    <?php if (!$rows_back): ?>
      <p>Không tìm thấy chuyến bay phù hợp cho chiều về.</p>
    <?php else: ?>
      <?php foreach ($rows_back as $r): 
        $time = diffMinutes($r['gio_di'], $r['gio_den']);
        $totalPrice = calcTotalPrice($r['gia_co_ban'], $adults, $children, $infants);
      ?>
        <div class="card">
          <div class="card-left">
            <h3><?=$r['so_hieu']?></h3>
            <p><strong>Đi:</strong> <?=$r['ten_di']?> (<?=fmtTime($r['gio_di'])?>)</p>
            <p><strong>Đến:</strong> <?=$r['ten_den']?> (<?=fmtTime($r['gio_den'])?>)</p>
            <p><strong>⏱ Thời gian bay:</strong> <?=$time?></p>
            <p><strong>Số ghế trống:</strong> <?=$r['so_ghe_con']?></p>
          </div>
          <div class="card-right">
            <p class="price"><?=number_format($totalPrice)?> VND</p>
            <a class="btn" href="index.php?p=select_seat&flight_id=<?=$r['chuyen_id']?>&cabin=<?=$cab?>">Chọn</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>

  <div style="text-align:center;margin-top:2rem;">
    <a href="index.php" class="btn outline">← Quay lại</a>
  </div>
</main>

<footer>© <?=date('Y')?> VNAir Ticket</footer>
</body>
</html>
