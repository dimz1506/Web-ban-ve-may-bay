<?php
// pages/booking/add_passengers.php
if (!function_exists('db')) {
    require_once dirname(__DIR__,2).'/config.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(400);
  echo "Bad request: Vui lòng chọn ghế ở bước trước.";
  exit;
}

// Dữ liệu chuyến bay
$flight_id = (int)($_POST['flight_id'] ?? 0);
$cabin     = trim($_POST['cabin'] ?? 'ECON');

// Luôn ép seats thành mảng
$seats_raw = $_POST['seats'] ?? [];
if (!is_array($seats_raw)) {
    $seats = array_map('trim', explode(',', $seats_raw));
} else {
    $seats = $seats_raw;
}
$qty = count($seats);

// Nếu quay lại từ review_checkout có passengers
$passengers = $_POST['passengers'] ?? [];

if ($flight_id <= 0 || $qty === 0) {
  http_response_code(400);
  echo "Bad request: Thiếu thông tin chuyến bay hoặc chưa chọn ghế.";
  exit;
}

// Lấy thông tin chuyến bay
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
if (!$flight) { http_response_code(404); echo "Không tìm thấy chuyến bay."; exit; }

// Lấy giá theo hạng ghế
$st2 = db()->prepare(
  "SELECT hg.ten, hg.ma, cgh.gia_co_ban, hg.id as hang_ghe_id
   FROM chuyen_bay_gia_hang cgh
   JOIN hang_ghe hg ON hg.id=cgh.hang_ghe_id
   WHERE cgh.chuyen_bay_id=? AND hg.ma=?"
);
$st2->execute([$flight_id, $cabin]);
$cabinInfo = $st2->fetch();
$price_per = $cabinInfo ? (float)$cabinInfo['gia_co_ban'] : 0.0;
$total     = $qty * $price_per;

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
    :root{--bg:#f7fafc;--card:#ffffff;--muted:#6b7280;--accent:#0f172a;--primary:#0ea5e9;--success:#10b981}
    *{box-sizing:border-box}
    body{font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:var(--bg); color:var(--accent); margin:0}
    .container{max-width:980px;margin:24px auto;padding:0 16px}
    .topbar{background:#fff;border-bottom:1px solid #e6edf3}
    .topbar .nav{display:flex;align-items:center;gap:12px;padding:12px 16px}
    .brand{display:flex;align-items:center;gap:10px;font-weight:600}
    .logo{width:40px;height:40px;border-radius:8px;background:linear-gradient(135deg,#06b6d4,#3b82f6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}

    h2{margin:18px 0 6px}
    p.lead{color:var(--muted);margin:0 0 12px}

    .summary{display:flex;flex-wrap:wrap;gap:12px;background:#ffffff;border:1px solid #e6eef6;border-radius:12px;padding:12px;margin:16px 0;align-items:center}
    .summary .item{min-width:180px}
    .summary strong{display:block}

    form{display:grid;grid-template-columns:1fr 320px;gap:20px}
    @media (max-width:880px){form{grid-template-columns:1fr}}

    .passenger-list{display:flex;flex-direction:column;gap:14px}
    .card{background:var(--card);border:1px solid #e6edf3;border-radius:12px;padding:14px}
    legend{font-weight:600;margin-bottom:8px}

    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
    label{display:block;font-size:13px;color:var(--muted);margin-bottom:6px}
    input[type="text"], input[type="date"], select{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:14px}
    input:required{outline-offset:2px}

    .side{position:relative}
    .price-card{position:sticky;top:16px;background:var(--card);border:1px solid #e6edf3;border-radius:12px;padding:16px}
    .price-row{display:flex;justify-content:space-between;margin:8px 0}
    .muted{color:var(--muted);font-size:14px}

    .submit-row{margin-top:1rem;display:flex;gap:10px}
    .btn{background:#0ea5e9;color:#fff;padding:.6rem 1rem;border-radius:10px;border:0;cursor:pointer;font-weight:600}
    .btn.ghost{background:transparent;border:1px solid #0ea5e9;color:#0b5d78}

    footer.footer{margin-top:28px;padding:18px 0;text-align:center;color:var(--muted);font-size:14px}

    .help{font-size:13px;color:var(--muted);margin-top:8px}
  </style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
  </div>
</header>

<main class="container">
  <h2>Nhập thông tin hành khách</h2>
  <p class="lead">
    Chuyến <strong><?=htmlspecialchars($flight['so_hieu'])?></strong>
    (<?=htmlspecialchars($flight['ten_di'])?> → <?=htmlspecialchars($flight['ten_den'])?>) —
    <span class="muted">Ngày đi: <?=$flight['gio_di']?> · Ngày đến: <?=$flight['gio_den']?></span>
  </p>

  <div class="summary">
    <div class="item">Hạng ghế: <strong><?=htmlspecialchars($cabinInfo['ten'] ?? $cabin)?></strong></div>
    <div class="item">Ghế đã chọn: <strong><?=htmlspecialchars(implode(', ', $seats))?></strong></div>
    <div class="item">Giá / ghế: <strong><?=vnd($price_per)?></strong></div>
    <div class="item">Số lượng: <strong><?=$qty?></strong></div>
    <div class="item">Tổng tạm tính: <strong><?=vnd($total)?></strong></div>
  </div>

  <form method="post" action="index.php?p=review_checkout" novalidate>
    <input type="hidden" name="flight_id" value="<?=$flight_id?>">
    <input type="hidden" name="cabin" value="<?=htmlspecialchars($cabin)?>">
    <input type="hidden" name="price_per" value="<?=$price_per?>">
    <?php foreach ($seats as $s): ?>
      <input type="hidden" name="seats[]" value="<?=htmlspecialchars($s)?>">
    <?php endforeach; ?>

    <div class="passenger-list">
      <?php foreach ($seats as $i => $s): 
        $p = $passengers[$i] ?? [];
      ?>
        <fieldset class="card" aria-labelledby="p<?=$i?>-legend">
          <legend id="p<?=$i?>-legend">Hành khách #<?=$i+1?> — Ghế <?=htmlspecialchars($s)?></legend>
          <div class="grid">
            <div>
              <label for="ho_ten_<?=$i?>">Họ tên</label>
              <input id="ho_ten_<?=$i?>" name="passengers[<?=$i?>][ho_ten]" type="text" value="<?=htmlspecialchars($p['ho_ten']??'')?>" required placeholder="Nguyễn Văn A">
            </div>
            <div>
              <label for="ngay_sinh_<?=$i?>">Ngày sinh</label>
              <input id="ngay_sinh_<?=$i?>" type="date" name="passengers[<?=$i?>][ngay_sinh]" value="<?=htmlspecialchars($p['ngay_sinh']??'')?>" required>
            </div>
            <div>
              <label for="gioi_tinh_<?=$i?>">Giới tính</label>
              <select id="gioi_tinh_<?=$i?>" name="passengers[<?=$i?>][gioi_tinh]" aria-label="Giới tính hành khách <?=$i+1?>">
                <option value="M" <?=($p['gioi_tinh']??'')==='M'?'selected':''?>>Nam</option>
                <option value="F" <?=($p['gioi_tinh']??'')==='F'?'selected':''?>>Nữ</option>
                <option value="X" <?=($p['gioi_tinh']??'')==='X'?'selected':''?>>Khác</option>
              </select>
            </div>
            <div>
              <label for="giay_to_<?=$i?>">CMND/Hộ chiếu</label>
              <input id="giay_to_<?=$i?>" name="passengers[<?=$i?>][giay_to]" type="text" value="<?=htmlspecialchars($p['giay_to']??'')?>" required placeholder="Số chứng minh / hộ chiếu">
            </div>
          </div>
          <p class="help">Vui lòng kiểm tra chính xác thông tin hành khách — thông tin sai có thể dẫn tới hủy hành trình.</p>
        </fieldset>
      <?php endforeach; ?>

      <div class="card" style="display:flex;gap:10px;align-items:center;justify-content:space-between">
        <div>
          <button class="btn" type="submit">Tiếp tục</button>
          <a class="btn ghost" href="javascript:history.back()">← Quay lại chọn ghế</a>
        </div>
      </div>
    </div>

    <aside class="side">
      <div class="price-card">
        <h4 style="margin:0 0 8px">Tóm tắt đơn</h4>
        <div class="price-row"><div class="muted">Hạng ghế</div><div><?=htmlspecialchars($cabinInfo['ten'] ?? $cabin)?></div></div>
        <div class="price-row"><div class="muted">Số ghế</div><div><?=implode(', ', $seats)?></div></div>
        <div class="price-row"><div class="muted">Giá / ghế</div><div><?=vnd($price_per)?></div></div>
        <div class="price-row"><div class="muted">Số lượng</div><div><?=$qty?></div></div>
        <hr style="border:none;border-top:1px solid #eef6fb;margin:12px 0">
        <div class="price-row" style="font-weight:700;font-size:1.05rem"><div>Tổng tạm tính</div><div><?=vnd($total)?></div></div>
        <p class="help">Giá chưa bao gồm thuế/phí hành lý (nếu có). Thanh toán và chọn dịch vụ thêm ở bước tiếp theo.</p>
      </div>
    </aside>
  </form>
</main>

<footer id="lien-he">
  <div class="container">
    <div>© <span id="y"></span>2025 VNAir Ticket.</div>
  </div>
</footer>

</body>
</html>