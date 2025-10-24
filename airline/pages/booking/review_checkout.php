<?php
if (!function_exists('db')) require_once dirname(__DIR__,2).'/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ✅ Nếu nhấn “Xác nhận & Thanh toán” — tiến hành lưu vé thật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
  $user = me();
  if (!$user) die("Vui lòng đăng nhập để đặt vé.");

  $flight_id  = (int)($_POST['flight_id'] ?? 0);
  $return_id  = (int)($_POST['return_id'] ?? 0);
  $cabin      = trim($_POST['cabin'] ?? 'ECON');
  $price_per  = (float)($_POST['price_per'] ?? 0);
  $discount   = (float)($_POST['discount'] ?? 0);
  $passengers = $_POST['passengers'] ?? [];
  $seats_go   = $_POST['seats'] ?? [];
  $seats_back = $_POST['return_seats'] ?? [];

  // 🔧 FIX: Giải mã nếu dữ liệu là JSON string
if (is_string($passengers)) {
    $decoded = json_decode($passengers, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $passengers = $decoded;
    } else {
        $passengers = [];
    }
}
if (is_string($seats_go)) $seats_go = explode(',', $seats_go);
if (is_string($seats_back)) $seats_back = explode(',', $seats_back);
  if ($flight_id <= 0 || empty($passengers)) die("Thiếu thông tin chuyến bay hoặc hành khách.");

  // 🧾 Tạo mã PNR
  $pnr = strtoupper(substr(bin2hex(random_bytes(6)), 0, 6));

  // 🧮 Tính tổng tiền
  $qty = count($passengers);
  $subtotal = $qty * $price_per;
  $discount_amount = $subtotal * $discount;
  $total = $subtotal - $discount_amount;

  try {
    db()->beginTransaction();

    // 🧩 Tạo bản ghi đặt chỗ
    $stmt = db()->prepare("
      INSERT INTO dat_cho (khach_hang_id, pnr, trang_thai, kenh, tong_tien, tien_te)
      VALUES (?, ?, 'XAC_NHAN', 'WEB', ?, 'VND')
    ");
    $stmt->execute([$user['id'], $pnr, $total]);
    $dat_cho_id = db()->lastInsertId();

    // 🧩 Lấy id hạng ghế
    $stmt_hg = db()->prepare("SELECT id FROM hang_ghe WHERE ma = ? LIMIT 1");
    $stmt_hg->execute([$cabin]);
    $hang_ghe_id = $stmt_hg->fetchColumn();

    // 🧾 Tạo hành khách + vé
    foreach ($passengers as $i => $p) {
      // Thêm hành khách
      $stmt_hk = db()->prepare("
        INSERT INTO hanh_khach (dat_cho_id, loai, ho_ten, gioi_tinh, ngay_sinh, loai_giay_to, so_giay_to, quoc_tich)
        VALUES (?, 'ADT', ?, ?, ?, 'CCCD', ?, 'Việt Nam')
      ");
      $stmt_hk->execute([$dat_cho_id, $p['ho_ten'], $p['gioi_tinh'], $p['ngay_sinh'], $p['giay_to']]);
      $hanh_khach_id = db()->lastInsertId();

      // Vé chiều đi
      $so_ve_go = 'VN' . time() . rand(100, 999);
      $seat_go = $seats_go[$i] ?? ($p['seat_go'] ?? 'A' . ($i + 1));
      $stmt_ve = db()->prepare("
        INSERT INTO ve (so_ve, dat_cho_id, hanh_khach_id, chuyen_bay_id, hang_ghe_id, so_ghe, trang_thai)
        VALUES (?, ?, ?, ?, ?, ?, 'DA_XUAT')
      ");
      $stmt_ve->execute([$so_ve_go, $dat_cho_id, $hanh_khach_id, $flight_id, $hang_ghe_id, $seat_go]);

      // Vé chiều về (nếu có)
      if ($return_id > 0) {
        $so_ve_ve = 'VN' . time() . rand(100, 999);
        $seat_back = $seats_back[$i] ?? ($p['seat_back'] ?? null);
        $stmt_ve->execute([$so_ve_ve, $dat_cho_id, $hanh_khach_id, $return_id, $hang_ghe_id, $seat_back]);
      }
    }

    db()->commit();
    header("Location: index.php?p=my_bookings&pnr=" . $pnr);
    exit;
  } catch (Exception $e) {
    db()->rollBack();
    die("Lỗi khi tạo booking: " . $e->getMessage());
  }
}

// ✅ Nếu không phải POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(400);
  die("Bad request: Vui lòng quay lại chọn vé.");
}

// ✅ Dữ liệu hiển thị xác nhận
$flight_id  = (int)($_POST['flight_id'] ?? 0);
$return_id  = (int)($_POST['return_id'] ?? 0);
$cabin      = trim($_POST['cabin'] ?? 'ECON');
$price_per  = (float)($_POST['price_per'] ?? 0);
$discount   = (float)($_POST['discount'] ?? 0);
$passengers = $_POST['passengers'] ?? [];
$seats      = $_POST['seats'] ?? [];

if ($flight_id <= 0 || empty($passengers)) {
  die("Thiếu thông tin chuyến bay hoặc hành khách.");
}

// 🧾 Lấy thông tin chuyến bay
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

// --- Tính toán hiển thị ---
$qty = count($passengers);
$subtotal = $qty * $price_per;
$discount_amount = $subtotal * $discount;
$total = $subtotal - $discount_amount;

function vnd($n){ return number_format((float)$n, 0, ',', '.') . ' VND'; }
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Xác nhận đặt vé</title>
<link rel="stylesheet" href="assets/home.css">
<style>
body{background:#f8fafc;font-family:'Segoe UI',sans-serif;margin:0;}
main.container{max-width:950px;margin:2rem auto;padding:2rem;background:#fff;border-radius:20px;box-shadow:0 6px 16px rgba(0,0,0,0.08);}
.card{border:1px solid #e2e8f0;border-radius:12px;padding:14px 18px;margin:16px 0;background:#f9fafb;}
.summary{background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px 14px;margin:16px 0;font-size:15px;}
.total{background:#f1f5f9;border:2px solid #1e3a8a;border-radius:14px;padding:1rem 1.2rem;text-align:center;font-weight:600;color:#1e3a8a;font-size:16px;}
.btn{background:#1e40af;color:#fff;padding:.6rem 1.3rem;border-radius:8px;border:none;cursor:pointer;}
.btn:hover{background:#1e3a8a;}
.btn.outline{background:transparent;border:1px solid #1e40af;color:#1e40af;}
.btn.outline:hover{background:#e0e7ff;}
#toast{display:none;position:fixed;top:20px;right:20px;background:#16a34a;color:white;padding:12px 18px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.2);}
.loading{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);align-items:center;justify-content:center;font-size:20px;color:#1e40af;}
</style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    <nav>
      <a href="index.php#uu-dai">Ưu đãi</a>
      <a href="index.php#quy-trinh">Quy trình</a>
      <a href="index.php#lien-he">Liên hệ</a>
    </nav>
  </div>
</header>

<main class="container">
  <h2>Xác nhận đặt vé</h2>

  <div class="summary">
    <strong>✈ Chiều đi:</strong> <?=$flight['so_hieu']?> (<?=$flight['ten_di']?> → <?=$flight['ten_den']?>)<br>
    Ngày đi: <?=$flight['gio_di']?> — Ngày đến: <?=$flight['gio_den']?><br>
    Hạng ghế: <strong><?=$cabin?></strong> | Giá vé / người: <strong><?=vnd($price_per)?></strong>
  </div>

  <?php if ($returnFlight): ?>
  <div class="summary">
    <strong>↩ Chiều về:</strong> <?=$returnFlight['so_hieu']?> (<?=$returnFlight['ten_di']?> → <?=$returnFlight['ten_den']?>)<br>
    Ngày đi: <?=$returnFlight['gio_di']?> — Ngày đến: <?=$returnFlight['gio_den']?><br>
    Hạng ghế: <strong><?=$cabin?></strong> | Giá vé / người: <strong><?=vnd($price_per)?></strong>
  </div>
  <?php endif; ?>

  <h3>👥 Thông tin hành khách</h3>
  <?php foreach ($passengers as $i => $p): ?>
    <div class="card">
      <strong>Hành khách #<?=$i+1?></strong><br>
      <ul style="margin:6px 0 0 18px; line-height:1.5;">
        <li>Họ tên: <strong><?=htmlspecialchars($p['ho_ten'])?></strong></li>
        <li>Ngày sinh: <?=htmlspecialchars($p['ngay_sinh'])?></li>
        <li>Giới tính: <?=($p['gioi_tinh']=='M'?'Nam':'Nữ')?></li>
        <li>CMND/Hộ chiếu: <?=htmlspecialchars($p['giay_to'])?></li>
        <li>Ghế chiều đi: <strong><?=htmlspecialchars($p['seat_go'] ?? '')?></strong></li>
        <?php if (!empty($p['seat_back'])): ?>
          <li>Ghế chiều về: <strong><?=htmlspecialchars($p['seat_back'])?></strong></li>
        <?php endif; ?>
      </ul>
    </div>
  <?php endforeach; ?>

  <div class="total">
    Tổng cộng <?=$qty?> vé x <?=vnd($price_per)?> = <?=vnd($subtotal)?><br>
    <?php if ($discount > 0): ?>
      Giảm khứ hồi: <strong style="color:#dc2626;">-<?=vnd($discount_amount)?></strong><br>
    <?php endif; ?>
    <span style="font-size:18px;">Tổng thanh toán: <?=vnd($total)?></span>
  </div>

  <div style="text-align:center;margin-top:2rem;">
    <button class="btn" type="button" onclick="confirmBooking()">Xác nhận & Thanh toán</button>
    <a href="javascript:history.back()" class="btn outline">← Quay lại</a>
  </div>
</main>

<div id="toast">🎉 Đặt vé thành công!</div>
<div class="loading" id="loading">⏳ Đang xử lý thanh toán...</div>

<script>
function confirmBooking() {
  const btn = document.querySelector('.btn');
  const loading = document.getElementById('loading');
  btn.disabled = true;
  btn.textContent = 'Đang xử lý...';
  loading.style.display = 'flex';

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '';

  const confirmInput = document.createElement('input');
  confirmInput.type = 'hidden';
  confirmInput.name = 'confirm_booking';
  confirmInput.value = '1';
  form.appendChild(confirmInput);

  const data = {
    'flight_id': '<?= $flight_id ?>',
    'return_id': '<?= $return_id ?>',
    'cabin': '<?= $cabin ?>',
    'price_per': '<?= $price_per ?>',
    'discount': '<?= $discount ?>',
    'passengers': <?= json_encode($passengers) ?>,
    'seats': <?= json_encode($seats) ?>
  };

  Object.keys(data).forEach(key => {
    if (key === 'passengers' || key === 'seats') {
      data[key].forEach((item, index) => {
        if (typeof item === 'object') {
          Object.keys(item).forEach(subKey => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `${key}[${index}][${subKey}]`;
            input.value = item[subKey];
            form.appendChild(input);
          });
        } else {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = `${key}[${index}]`;
          input.value = item;
          form.appendChild(input);
        }
      });
    } else {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = data[key];
      form.appendChild(input);
    }
  });

  document.body.appendChild(form);
  form.submit();
}
</script>

<footer style="text-align:center;color:#64748b;font-size:14px;margin-top:2rem;">
  © <?=date('Y')?> VNAir Ticket — Trang demo học tập
</footer>
</body>
</html>
