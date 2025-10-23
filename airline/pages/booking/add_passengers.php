<?php
// session_start();
// if (!isset($_SESSION['user_id'])) {
//   header('Location: index.php?p=login');
//   exit;
// }
if (!function_exists('db')) { 
    require_once dirname(__DIR__,2).'/config.php'; 
}
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(400);
  echo "Bad request: Vui lòng chọn ghế ở bước trước.";
  exit;
}

// --- Lấy dữ liệu chuyến ---
$flight_id  = (int)($_POST['flight_id'] ?? 0);
$return_id  = (int)($_POST['return_id'] ?? 0);
$cabin      = trim($_POST['cabin'] ?? 'ECON');

// 🧩 Nhận danh sách ghế đi & về (nếu có)
$seats_go_raw     = $_POST['seats'] ?? [];
$seats_return_raw = $_POST['return_seats'] ?? [];

// Chuẩn hóa dữ liệu
$seats_go = array_values(array_unique(is_array($seats_go_raw) ? $seats_go_raw : explode(',', $seats_go_raw)));
$seats_return = array_values(array_unique(is_array($seats_return_raw) ? $seats_return_raw : explode(',', $seats_return_raw)));

$qty = max(count($seats_go), count($seats_return));

// Gộp hiển thị ghế đi/về
$seat_pairs = [];
for ($i = 0; $i < $qty; $i++) {
  $go = $seats_go[$i] ?? '';
  $back = $seats_return[$i] ?? '';
  $seat_pairs[] = trim($go . ($back ? " / $back" : ""));
}

if ($flight_id <= 0 || $qty === 0) {
  die("Thiếu thông tin chuyến bay hoặc chưa chọn ghế.");
}

// --- Thông tin chuyến đi ---
$st = db()->prepare("
  SELECT cb.*, s1.ten AS ten_di, s2.ten AS ten_den
  FROM chuyen_bay cb
  JOIN tuyen_bay tb ON tb.id = cb.tuyen_bay_id
  JOIN san_bay s1 ON s1.ma = tb.di
  JOIN san_bay s2 ON s2.ma = tb.den
  WHERE cb.id = ?
");
$st->execute([$flight_id]);
$flight = $st->fetch();

// --- Thông tin chuyến về ---
$returnFlight = null;
if ($return_id) {
  $st2 = db()->prepare("
    SELECT cb.*, s1.ten AS ten_di, s2.ten AS ten_den
    FROM chuyen_bay cb
    JOIN tuyen_bay tb ON tb.id = cb.tuyen_bay_id
    JOIN san_bay s1 ON s1.ma = tb.di
    JOIN san_bay s2 ON s2.ma = tb.den
    WHERE cb.id = ?
  ");
  $st2->execute([$return_id]);
  $returnFlight = $st2->fetch();
}

// --- Giá vé theo hạng ---
$st3 = db()->prepare("
  SELECT hg.ten, hg.ma, cgh.gia_co_ban
  FROM chuyen_bay_gia_hang cgh
  JOIN hang_ghe hg ON hg.id = cgh.hang_ghe_id
  WHERE cgh.chuyen_bay_id = ? AND hg.ma = ?
");
$st3->execute([$flight_id, $cabin]);
$cabinInfo = $st3->fetch();

$price_per = $cabinInfo ? (float)$cabinInfo['gia_co_ban'] : 0;
$total = $qty * $price_per;

// ✅ Giảm giá khứ hồi 10%
$discount = $return_id ? 0.1 : 0;
$discount_amount = $total * $discount;
if ($discount > 0) $total *= (1 - $discount);

function vnd($n){ return number_format((float)$n, 0, ',', '.').' VND'; }
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Nhập thông tin hành khách</title>
<link rel="stylesheet" href="assets/home.css">
<style>
body {
  background:#f8fafc;
  font-family:'Segoe UI',sans-serif;
  margin:0;
}
main.container {
  max-width:950px;
  margin:2rem auto;
  padding:2rem;
  background:#fff;
  border-radius:20px;
  box-shadow:0 6px 16px rgba(0,0,0,0.08);
}
.card {
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius:16px;
  padding:14px 18px;
  margin:16px 0;
  box-shadow:0 3px 8px rgba(0,0,0,0.05);
}
input, select {
  width:100%;
  padding:.4rem .6rem;
  border:1px solid #cbd5e1;
  border-radius:8px;
  outline:none;
  font-size:14px;
  height:34px;
  box-shadow:inset 0 1px 2px rgba(0,0,0,.05);
}
input:focus, select:focus {
  border-color:#2563eb;
  box-shadow:0 0 0 2px #93c5fd;
}
.grid {
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:.8rem;
}
.summary {
  background:#eff6ff;
  border:1px solid #bfdbfe;
  border-radius:12px;
  padding:12px 14px;
  margin:16px 0;
  font-size:15px;
}
.total-summary {
  background:#f1f5f9;
  border:2px solid #1e3a8a;
  border-radius:14px;
  padding:1rem 1.2rem;
  margin-top:2rem;
  text-align:center;
  font-size:16px;
  font-weight:600;
  color:#1e3a8a;
  box-shadow:0 3px 8px rgba(0,0,0,0.05);
}
.total-summary small {
  display:block;
  color:#475569;
  font-weight:400;
  font-size:13px;
  margin-top:4px;
}
.btn {
  background:#1e40af;
  color:#fff;
  padding:.5rem 1.3rem;
  border-radius:8px;
  border:none;
  cursor:pointer;
}
.btn:hover {background:#1e3a8a;}
.btn.outline {
  background:transparent;
  border:1px solid #1e40af;
  color:#1e40af;
}
.btn.outline:hover {background:#e0e7ff;}
fieldset legend {
  font-weight:bold;
  color:#1e40af;
  font-size:15px;
}
footer {
  margin-top:3rem;
  text-align:center;
  color:#64748b;
  font-size:14px;
}
</style>
</head>
<body>

<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    <nav><a href="index.php#uu-dai">Ưu đãi</a><a href="index.php#quy-trinh">Quy trình</a><a href="index.php#lien-he">Liên hệ</a></nav>
  </div>
</header>

<main class="container">
  <h2>Nhập thông tin hành khách</h2>

  <div class="summary">
    <strong>✈ Chiều đi:</strong> <?=$flight['so_hieu']?> (<?=$flight['ten_di']?> → <?=$flight['ten_den']?>)<br>
    Ngày đi: <?=$flight['gio_di']?> — Ngày đến: <?=$flight['gio_den']?><br>
    <?php if ($returnFlight): ?>
      <br><strong>↩ Chiều về:</strong> <?=$returnFlight['so_hieu']?> (<?=$returnFlight['ten_di']?> → <?=$returnFlight['ten_den']?>)<br>
      Ngày đi: <?=$returnFlight['gio_di']?> — Ngày đến: <?=$returnFlight['gio_den']?>
    <?php endif; ?>
  </div>

  <div class="summary">
    Hạng ghế: <strong><?=$cabinInfo['ten']?></strong> |
    Ghế đã chọn: <strong><?=implode(', ', $seat_pairs)?></strong> |
    Giá / ghế: <strong><?=vnd($price_per)?></strong>
    <?php if ($discount > 0): ?> | Giảm khứ hồi: <strong>10%</strong><?php endif; ?>
    <br><strong>Tổng tiền tạm tính: <?=vnd($total)?></strong>
  </div>

  <form method="post" action="index.php?p=review_checkout" id="passengerForm">
    <input type="hidden" name="flight_id" value="<?=$flight_id?>">
    <input type="hidden" name="return_id" value="<?=$return_id?>">
    <input type="hidden" name="cabin" value="<?=$cabin?>">
    <input type="hidden" name="price_per" value="<?=$price_per?>">
    <input type="hidden" name="discount" value="<?=$discount?>">

    <?php for ($i = 0; $i < $qty; $i++): ?>
      <fieldset class="card passenger" data-index="<?=$i?>">
        <legend>👤 Hành khách #<?=$i+1?></legend>

        <div class="grid" style="margin-bottom:1rem;">
          <div>
            <label>✈ Ghế chiều đi</label>
            <input type="text" name="passengers[<?=$i?>][seat_go]" value="<?=$seats_go[$i] ?? ''?>" readonly>
          </div>
          <?php if ($return_id): ?>
          <div>
            <label>↩ Ghế chiều về</label>
            <input type="text" name="passengers[<?=$i?>][seat_back]" value="<?=$seats_return[$i] ?? ''?>" readonly>
          </div>
          <?php endif; ?>
        </div>

        <div class="grid">
          <div>
            <label>Họ tên</label>
            <input class="sync" name="passengers[<?=$i?>][ho_ten]" data-field="ho_ten" required>
          </div>
          <div>
            <label>Ngày sinh</label>
            <input class="sync" type="date" name="passengers[<?=$i?>][ngay_sinh]" data-field="ngay_sinh" required>
          </div>
          <div>
            <label>Giới tính</label>
            <select class="sync" name="passengers[<?=$i?>][gioi_tinh]" data-field="gioi_tinh">
              <option value="M">Nam</option>
              <option value="F">Nữ</option>
            </select>
          </div>
          <div>
            <label>CMND/Hộ chiếu</label>
            <input class="sync" name="passengers[<?=$i?>][giay_to]" data-field="giay_to" required>
          </div>
        </div>
      </fieldset>
    <?php endfor; ?>

    <div class="total-summary">
      Tổng cộng <?=$qty?> vé • 
      <?php if ($discount > 0): ?>
        <span style="color:#dc2626;">Giảm <?=vnd($discount_amount)?> (10%)</span><br>
      <?php endif; ?>
      <span style="font-size:18px;">Tổng thanh toán: <?=vnd($total)?></span>
      <small>Giá đã bao gồm thuế, phí và khuyến mãi khứ hồi (nếu có).</small>
    </div>

    <div style="text-align:center;margin-top:2rem;">
      <button class="btn" type="submit">Tiếp tục thanh toán</button>
      <a href="javascript:history.back()" class="btn outline">← Quay lại chọn ghế</a>
    </div>
  </form>
</main>

<footer>© <?=date('Y')?> VNAir Ticket — Trang demo học tập</footer>

<script>
// 🧩 Đồng bộ giữa ghế đi / về của cùng hành khách
document.querySelectorAll('.sync').forEach(el => {
  el.addEventListener('input', e => {
    const field = e.target.dataset.field;
    const val = e.target.value;
    const index = e.target.closest('.passenger').dataset.index;
    document.querySelectorAll(`.passenger[data-index="${index}"] .sync[data-field="${field}"]`)
      .forEach(x => { if (x !== e.target) x.value = val; });
  });
});
</script>
</body>
</html>
