<?php

if (!function_exists('db')) { 
    require_once dirname(__DIR__,2).'/config.php'; 
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$flight_id = intval($_GET['flight_id'] ?? 0);
$cabin = $_GET['cabin'] ?? 'ECON';

// Thông tin chuyến bay
$st = db()->prepare(
  "SELECT cb.*, s1.ten as ten_di, s2.ten as ten_den
   FROM chuyen_bay cb
   JOIN tuyen_bay tb ON tb.id = cb.tuyen_bay_id
   JOIN san_bay s1 ON s1.ma=tb.di
   JOIN san_bay s2 ON s2.ma=tb.den
   WHERE cb.id=?"
);
$st->execute([$flight_id]);
$flight = $st->fetch();

// Thông tin hạng ghế + tổng ghế
$st2 = db()->prepare(
  "SELECT hg.id, hg.ma, hg.ten, cgh.gia_co_ban, cgh.so_ghe_con
   FROM chuyen_bay_gia_hang cgh
   JOIN hang_ghe hg ON hg.id=cgh.hang_ghe_id
   WHERE cgh.chuyen_bay_id=? AND hg.ma=?"
);
$st2->execute([$flight_id, $cabin]);
$cabinInfo = $st2->fetch();

if (!$flight || !$cabinInfo) {
    die("Không tìm thấy chuyến bay hoặc hạng ghế.");
}

// Ghế đã đặt
$st3 = db()->prepare(
  "SELECT so_ghe FROM ve 
   WHERE chuyen_bay_id=? AND hang_ghe_id=? AND trang_thai='DA_XUAT'"
);
$st3->execute([$flight_id, $cabinInfo['id']]);
$takenSeats = $st3->fetchAll(PDO::FETCH_COLUMN);

// Tổng ghế theo khoang
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
    user-select:none;
  }
  .seat.taken { background:#f87171; cursor:not-allowed; color:white; }
  .seat.selected { background:#34d399; color:white; }
  #summary { margin-top:1.5rem; font-weight:bold; }
  .legend { display:flex; gap:1.5rem; margin-top:1rem; justify-content:center; }
  .legend-item { display:flex; align-items:center; gap:.4rem; font-size:.9rem; }
  .legend-box { width:20px; height:20px; border-radius:4px; }
  .stats { margin-top:1rem; padding:.8rem; background:#f1f5f9; border-radius:8px; }
  .btn-row { margin-top:2rem; display:flex; gap:1rem; }
  .btn { background:#1e40af; color:#fff; padding:.5rem 1.2rem;
         border-radius:8px; text-decoration:none; text-align:center; border:0; cursor:pointer; }
  .btn[disabled], .btn[aria-disabled="true"] { opacity:.6; pointer-events:none; }
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
    <nav>
      <a href="index.php#uu-dai">Ưu đãi</a>
      <a href="index.php#quy-trinh">Quy trình</a>
      <a href="index.php#lien-he">Liên hệ</a>
    </nav>
  </div>
</header>

<main class="container">
  <h2>Chọn ghế (<?=$cabinInfo['ten']?> - <?=$cabin?>)</h2>
  <p>
    Chuyến <strong><?=htmlspecialchars($flight['so_hieu'])?></strong>  
    (<?=htmlspecialchars($flight['ten_di'])?> → <?=htmlspecialchars($flight['ten_den'])?>)  
    <br>Ngày đi: <?=$flight['gio_di']?> - Ngày đến: <?=$flight['gio_den']?>
  </p>

  <!-- Thống kê -->
  <div class="stats">
    Tổng ghế: <strong><?=$totalSeats?></strong> | 
    Đã đặt: <strong id="bookedCount"><?=$bookedSeats?></strong> | 
    Đang chọn: <strong id="selectedCount">0</strong> | 
    Còn trống: <strong id="emptyCount"><?=$emptySeats?></strong>
  </div>

  <form method="POST" action="index.php?p=add_passengers" id="seatForm" novalidate>
    <input type="hidden" name="flight_id" value="<?=$flight_id?>">
    <input type="hidden" name="cabin" value="<?=$cabin?>">
    <input type="hidden" name="price" value="<?=$cabinInfo['gia_co_ban']?>" id="priceInput">
    <input type="hidden" name="seats" id="seatsInput">

    <div class="plane" id="plane">
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

          echo "<div class='$cls' data-seat='$seat' role='button' aria-pressed='false' tabindex='0'>$seat</div>";
        }
        echo "</div>";
      }
      ?>
    </div>

    <!-- Legend -->
    <div class="legend">
      <div class="legend-item"><div class="legend-box" style="background:#e2e8f0;"></div> Còn trống</div>
      <div class="legend-item"><div class="legend-box" style="background:#34d399;"></div> Đang chọn</div>
      <div class="legend-item"><div class="legend-box" style="background:#f87171;"></div> Đã đặt</div>
    </div>

    <div id="summary">Chưa chọn ghế nào.</div>

    <div class="btn-row">
      <button type="submit" class="btn" id="continueBtn" aria-disabled="true" disabled>Tiếp tục</button>
      <a href="javascript:history.back()" class="btn outline">← Quay lại tìm chuyến</a>
      <a href="index.php" class="btn outline">← Trang chủ</a>
    </div>
  </form>
</main>

<footer id="lien-he">
  <div class="container">
    <div>© <span id="y"></span> VNAir Ticket.</div>
  </div>
</footer>
<script>
document.getElementById('y').textContent = new Date().getFullYear();

let selected = [];
const pricePerSeat = (function(){
  const v = document.getElementById('priceInput').value;
  const p = parseInt(v, 10);
  return isNaN(p) ? 0 : p;
})();

const bookedCountEl = document.getElementById('bookedCount');
const selectedCountEl = document.getElementById('selectedCount');
const emptyCountEl = document.getElementById('emptyCount');
const seatsInput = document.getElementById('seatsInput');
const continueBtn = document.getElementById('continueBtn');
const seatEls = Array.from(document.querySelectorAll('.seat'));

function setContinueEnabled(enabled){
  if (enabled){
    continueBtn.removeAttribute('disabled');
    continueBtn.setAttribute('aria-disabled','false');
  } else {
    continueBtn.setAttribute('disabled','disabled');
    continueBtn.setAttribute('aria-disabled','true');
  }
}

seatEls.forEach(s => {
  // click
  s.addEventListener('click', () => {
    if (s.classList.contains('taken')) return; // ghế đã đặt -> không chọn
    s.classList.toggle('selected');
    const seat = s.dataset.seat;
    if (s.classList.contains('selected')) {
      if (!selected.includes(seat)) selected.push(seat);
      s.setAttribute('aria-pressed','true');
    } else {
      selected = selected.filter(x => x !== seat);
      s.setAttribute('aria-pressed','false');
    }
    updateSummary();
  });

  // keyboard accessibility (space / enter)
  s.addEventListener('keydown', (ev) => {
    if (ev.key === ' ' || ev.key === 'Enter') {
      ev.preventDefault();
      s.click();
    }
  });
});

function updateSummary(){
  selectedCountEl.textContent = selected.length;
  emptyCountEl.textContent = <?=$totalSeats?> - parseInt(bookedCountEl.textContent) - selected.length;

  if (selected.length === 0) {
    document.getElementById('summary').textContent = "Chưa chọn ghế nào.";
    setContinueEnabled(false);
  } else {
    document.getElementById('summary').textContent =
      "Đã chọn: " + selected.join(', ') +
      " | Tổng: " + (selected.length * pricePerSeat).toLocaleString() + " VND";
    setContinueEnabled(true);
  }
  seatsInput.value = selected.join(',');
}

// Form submit validation (client-side)
document.getElementById('seatForm').addEventListener('submit', function(e){
  if (selected.length === 0){
    e.preventDefault();
    // Có thể thay bằng modal / toast trong project của bạn
    alert('Vui lòng chọn ít nhất 1 ghế trước khi tiếp tục.');
    // focus vào khu vực ghế
    const firstAvailable = document.querySelector('.seat:not(.taken)');
    if (firstAvailable) firstAvailable.focus();
    return false;
  }
  // Nếu có ghế -> form submit bình thường; server vẫn kiểm tra thêm
});

// initial state
updateSummary();
</script>
</body>
</html>
