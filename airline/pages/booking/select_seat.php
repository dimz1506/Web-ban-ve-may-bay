<?php
if (!function_exists('db')) { 
    require_once dirname(__DIR__,2).'/config.php'; 
}
if (session_status() === PHP_SESSION_NONE) session_start();

// 🟢 Nhận thông tin chuyến bay
$flight_id = intval($_GET['flight_id'] ?? 0);
$return_id = intval($_GET['return_id'] ?? 0);
$cabin = $_GET['cabin'] ?? 'ECON';

// --- Thông tin chuyến đi ---
$st = db()->prepare("
  SELECT cb.*, s1.ten as ten_di, s2.ten as ten_den
  FROM chuyen_bay cb
  JOIN tuyen_bay tb ON tb.id = cb.tuyen_bay_id
  JOIN san_bay s1 ON s1.ma=tb.di
  JOIN san_bay s2 ON s2.ma=tb.den
  WHERE cb.id=?
");
$st->execute([$flight_id]);
$flight = $st->fetch();

// --- Thông tin hạng ghế ---
$st2 = db()->prepare("
  SELECT hg.id, hg.ma, hg.ten, cgh.gia_co_ban, cgh.so_ghe_con
  FROM chuyen_bay_gia_hang cgh
  JOIN hang_ghe hg ON hg.id=cgh.hang_ghe_id
  WHERE cgh.chuyen_bay_id=? AND hg.ma=?
");
$st2->execute([$flight_id, $cabin]);
$cabinInfo = $st2->fetch();

if (!$flight || !$cabinInfo) die("Không tìm thấy chuyến bay hoặc hạng ghế.");

// --- Ghế đã đặt chiều đi ---
$st3 = db()->prepare("
  SELECT so_ghe FROM ve 
  WHERE chuyen_bay_id=? AND hang_ghe_id=? 
  AND trang_thai='DA_XUAT' AND so_ghe IS NOT NULL
");
$st3->execute([$flight_id, $cabinInfo['id']]);
$takenSeats = $st3->fetchAll(PDO::FETCH_COLUMN);

// --- Nếu có chuyến về ---
$flight_return = null;
$cabinReturn = null;
$takenSeatsReturn = [];

if ($return_id > 0) {
  $st->execute([$return_id]);
  $flight_return = $st->fetch();

  $st2->execute([$return_id, $cabin]);
  $cabinReturn = $st2->fetch();

  if ($cabinReturn) {
    $st3->execute([$return_id, $cabinReturn['id']]);
    $takenSeatsReturn = $st3->fetchAll(PDO::FETCH_COLUMN);
  }
}

// --- Tính toán thống kê ---
$totalSeats = (int)$cabinInfo['so_ghe_con'];
$bookedSeats = count($takenSeats);
$emptySeats = $totalSeats - $bookedSeats;
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Chọn ghế (<?=$cabin?>)</title>
<link rel="stylesheet" href="assets/home.css">
<style>
  .plane { display:flex; flex-direction:column; align-items:center; margin-top:2rem; }
  .row { display:flex; gap:.5rem; margin-bottom:.5rem; }
  .seat {
    width:42px; height:42px; line-height:42px; text-align:center;
    border-radius:6px; background:#e2e8f0; cursor:pointer; font-size:.9rem;
    user-select:none; font-weight:500;
  }
  .seat.taken { background:#f87171; cursor:not-allowed; color:white; }
  .seat.selected { background:#34d399; color:white; }
  #summary { margin-top:1.5rem; font-weight:bold; text-align:center; }
  .legend { display:flex; gap:1.5rem; margin-top:1rem; justify-content:center; }
  .legend-item { display:flex; align-items:center; gap:.4rem; font-size:.9rem; }
  .legend-box { width:20px; height:20px; border-radius:4px; }
  .stats { margin-top:1rem; padding:.8rem; background:#f1f5f9; border-radius:8px; text-align:center; }
  .btn-row { margin-top:2rem; display:flex; gap:1rem; justify-content:center; }
  .btn { background:#1e40af; color:#fff; padding:.5rem 1.2rem;
         border-radius:8px; text-decoration:none; text-align:center; }
  .btn:hover { background:#1e3a8a; }
  .btn.outline { background:transparent; border:1px solid #1e40af; color:#1e40af; }
  .btn.outline:hover { background:#e0e7ff; }
</style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand">
      <div class="logo">✈</div>
      <div>VNAir Ticket</div>
    </div>
  </div>
</header>

<main class="container">
  <h2>Chọn ghế (<?=$cabinInfo['ten']?> - <?=$cabin?>)</h2>
  <p>
    Chuyến <strong><?=htmlspecialchars($flight['so_hieu'])?></strong>  
    (<?=htmlspecialchars($flight['ten_di'])?> → <?=htmlspecialchars($flight['ten_den'])?>)<br>
    Ngày đi: <?=$flight['gio_di']?> - Ngày đến: <?=$flight['gio_den']?>
  </p>

  <div class="stats">
    Tổng ghế: <strong><?=$totalSeats?></strong> | 
    Đã đặt: <strong id="bookedCount"><?=$bookedSeats?></strong> | 
    Đang chọn: <strong id="selectedCount">0</strong> | 
    Còn trống: <strong id="emptyCount"><?=$emptySeats?></strong>
  </div>

  <form method="POST" action="index.php?p=add_passengers">
    <input type="hidden" name="flight_id" value="<?=$flight_id?>">
    <?php if ($return_id > 0): ?><input type="hidden" name="return_id" value="<?=$return_id?>"><?php endif; ?>
    <input type="hidden" name="cabin" value="<?=$cabin?>">
    <input type="hidden" name="price" value="<?=$cabinInfo['gia_co_ban']?>" id="priceInput">
    <input type="hidden" name="seats" id="seatsGoInput">
    <input type="hidden" name="return_seats" id="seatsReturnInput">

    <h3 style="margin-top:1.5rem;">✈ Chuyến đi</h3>
    <div class="plane">
      <?php
      $cols = ['A','B','C','D','E','F'];
      $rows = ceil($totalSeats / count($cols));
      $seatCount = 0;
      for ($r=1; $r <= $rows; $r++) {
        echo "<div class='row'>";
        foreach ($cols as $c) {
          $seatCount++;
          if ($seatCount > $totalSeats) break;
          $seat = $r.$c;
          $cls = "seat";
          if (in_array($seat, $takenSeats)) $cls .= " taken";
          echo "<div class='$cls' data-seat='$seat' data-flight='go'>$seat</div>";
        }
        echo "</div>";
      }
      ?>
    </div>

    <?php if ($return_id > 0 && $flight_return): ?>
      <hr style="margin:2rem 0;">
      <h3>↩ Chuyến về</h3>
      <div class="plane">
        <?php
        $cols = ['A','B','C','D','E','F'];
        $rows = ceil($cabinReturn['so_ghe_con'] / count($cols));
        $seatCount = 0;
        for ($r=1; $r <= $rows; $r++) {
          echo "<div class='row'>";
          foreach ($cols as $c) {
            $seatCount++;
            if ($seatCount > $cabinReturn['so_ghe_con']) break;
            $seat = $r.$c;
            $cls = "seat";
            if (in_array($seat, $takenSeatsReturn)) $cls .= " taken";
            echo "<div class='$cls' data-seat='$seat' data-flight='return'>$seat</div>";
          }
          echo "</div>";
        }
        ?>
      </div>
    <?php endif; ?>

    <div class="legend">
      <div class="legend-item"><div class="legend-box" style="background:#e2e8f0;"></div> Còn trống</div>
      <div class="legend-item"><div class="legend-box" style="background:#34d399;"></div> Đang chọn</div>
      <div class="legend-item"><div class="legend-box" style="background:#f87171;"></div> Đã đặt</div>
    </div>

    <div id="summary">Chưa chọn ghế nào.</div>
    <div class="btn-row">
      <button type="submit" class="btn">Tiếp tục</button>
      <a href="javascript:history.back()" class="btn outline">← Quay lại tìm chuyến</a>
    </div>
  </form>
</main>

<script>
let selected = { go: [], return: [] };

document.querySelectorAll('.seat').forEach(s => {
  s.addEventListener('click', () => {
    if (s.classList.contains('taken')) return;
    const f = s.dataset.flight || 'go';
    const seat = s.dataset.seat;
    s.classList.toggle('selected');
    if (s.classList.contains('selected')) selected[f].push(seat);
    else selected[f] = selected[f].filter(x => x !== seat);
    updateSummary();
  });
});

function updateSummary() {
  const total = selected.go.length + selected.return.length;
  let text = total === 0 ? "Chưa chọn ghế nào." : 
    `Đã chọn: ${selected.go.length>0?'Chiều đi ('+selected.go.join(', ')+')':''}${selected.return.length>0?' | Chiều về ('+selected.return.join(', ')+')':''}`;
  document.getElementById('summary').textContent = text;
  document.getElementById('selectedCount').textContent = total;
  document.getElementById('seatsGoInput').value = selected.go.join(',');
  document.getElementById('seatsReturnInput').value = selected.return.join(',');
}

document.querySelector("form").addEventListener("submit", e => {
  if (selected.go.length === 0 && selected.return.length === 0) {
    alert("⚠️ Vui lòng chọn ít nhất một ghế trước khi tiếp tục!");
    e.preventDefault();
  }
});
</script>
</body>
</html>
