<?php


// pages/search_results.php — Kết quả tìm chuyến (phân chia rõ cho đã đăng nhập / chưa)
if (!function_exists('db')) {
    require_once dirname(__DIR__,2) . '/config.php';
}

$pdo = db();

// đảm bảo session (config.php thường đã start session; nhưng an toàn kiểm tra)
if (session_status() === PHP_SESSION_NONE) session_start();

// trạng thái user
function is_logged_in(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) return false;
    if (empty($_SESSION['user']['id']) || empty($_SESSION['user']['email'])) return false;
    return true;
}

$is_logged_in = is_logged_in();
$user = $is_logged_in ? $_SESSION['user'] : null;

// --- Input và chuẩn hóa ---
$from = strtoupper(trim((string)($_GET['from'] ?? '')));
$to   = strtoupper(trim((string)($_GET['to']   ?? '')));
$date = trim((string)($_GET['depart'] ?? ($_GET['date'] ?? '')));
$cab  = strtoupper(trim((string)($_GET['cabin'] ?? 'ECON')));

// Hàm kiểm tra ngày (YYYY-MM-DD)
function is_valid_date($d) {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

// Kiểm tra input cơ bản
$errors = [];
if ($from === '') $errors[] = 'Vui lòng nhập điểm đi.';
if ($to === '') $errors[] = 'Vui lòng nhập điểm đến.';
if ($date === '') $errors[] = 'Vui lòng chọn ngày đi.';
elseif (!is_valid_date($date)) $errors[] = 'Ngày không hợp lệ. Định dạng hợp lệ: YYYY-MM-DD.';

// Lấy id hạng ghế
$hang_ghe_id = null;
if (empty($errors)) {
    $st = $pdo->prepare("SELECT id FROM hang_ghe WHERE UPPER(ma)=? LIMIT 1");
    $st->execute([$cab]);
    $hang_ghe_id = $st->fetchColumn();
    if ($hang_ghe_id === false || $hang_ghe_id === null) {
        $errors[] = "Hạng ghế \"$cab\" không hợp lệ.";
    }
}

// Nếu có lỗi, không chạy query chuyến
$rows = [];
$min_price = null;
if (empty($errors)) {
    $sql = "
       SELECT cb.id AS chuyen_id,
              cb.so_hieu,
              cb.gio_di,
              cb.gio_den,
              s1.ten AS ten_di,
              s2.ten AS ten_den,
              cgh.gia_co_ban,
              cgh.so_ghe_con
       FROM tuyen_bay tb
       JOIN san_bay s1 ON s1.ma = tb.di
       JOIN san_bay s2 ON s2.ma = tb.den
       JOIN chuyen_bay cb ON cb.tuyen_bay_id = tb.id
       JOIN chuyen_bay_gia_hang cgh ON cgh.chuyen_bay_id = cb.id AND cgh.hang_ghe_id = ?
       WHERE tb.di = ? AND tb.den = ? AND DATE(cb.gio_di) = ? AND cb.trang_thai = 'LEN_KE_HOACH'
       ORDER BY cb.gio_di ASC
    ";

    $st = $pdo->prepare($sql);
    $st->execute([$hang_ghe_id, $from, $to, $date]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        $prices = array_column($rows, 'gia_co_ban');
        // filter out null/empty prices
        $prices = array_filter($prices, function($v){ return $v !== null && $v !== ''; });
        if (!empty($prices)) $min_price = min($prices);
    }
}

// Helper để format datetime an toàn
function fmt_dt($dtstr, $fmt = 'Y-m-d H:i') {
    if (!$dtstr) return '-';
    $ts = strtotime($dtstr);
    if ($ts === false) return htmlspecialchars($dtstr, ENT_QUOTES, 'UTF-8');
    return date($fmt, $ts);
}

// xây dựng url login có redirect về trang hiện tại
$current_url = $_SERVER['REQUEST_URI'] ?? 'index.php';
$login_with_next = 'index.php?p=login&next=' . rawurlencode($current_url);

?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kết quả tìm kiếm</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    .card {
      background:#fff;
      border:1px solid #e2e8f0;
      border-radius:12px;
      padding:20px;
      margin:16px 0;
      box-shadow:0 2px 4px rgba(0,0,0,.05);
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:20px;
    }
    .card.best-price {
      border:2px solid #1e40af;
      background:#f0f7ff;
    }
    .card-left { flex:3; }
    .card-right { flex:1; text-align:right; }
    .card h3 { font-size:1rem; margin-bottom:6px; }
    .card p { font-size:0.9rem; margin:6px 0; color:#0f172a; }
    .price { font-size:1.1rem; font-weight:bold; margin-bottom:8px; color:#1e40af; }
    .btn {
      background:#1e40af;
      color:#fff;
      padding:.45rem 1rem;
      border-radius:8px;
      text-decoration:none;
      font-size:0.95rem;
    }
    .btn:hover { background:#16356f; }
    .btn.outline {
      background:transparent;
      color:#1e40af;
      border:1px solid #c7d2fe;
    }
    .muted { color:#6b7280; font-size:0.95rem; }
    .alert { background:#fff3f2; border:1px solid #fecaca; color:#9a1f1f; padding:12px; border-radius:8px; margin:12px 0; }
    .user-greet { display:flex; gap:12px; align-items:center; justify-content:space-between; margin-bottom:12px; }
    .save-search { background:#06b6d4; color:#fff; padding:.35rem .6rem; border-radius:6px; font-size:0.85rem; text-decoration:none; }
    .login-prompt { background:#fffbee; border:1px solid #fde68a; color:#92400e; padding:10px 12px; border-radius:8px; margin:10px 0; }
  </style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    <div class="nav-cta">
      <?php if ($is_logged_in): ?>
        <span class="muted">Xin chào, <?=htmlspecialchars($user['name'] ?? $user['email'] ?? 'Thành viên', ENT_QUOTES, 'UTF-8')?></span>
      <?php else: ?>
        <a class="btn outline" href="<?= htmlspecialchars($login_with_next, ENT_QUOTES, 'UTF-8') ?>">Đăng nhập</a>
        <a class="btn" href="index.php?p=register">Đăng ký</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="container" style="margin:2rem 0;">
  <h2>Kết quả tìm chuyến</h2>
  <p class="muted">
    <strong><?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?></strong> →
    <strong><?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?></strong> |
    Ngày đi: <strong><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></strong> |
    Hạng: <strong><?= htmlspecialchars($cab, ENT_QUOTES, 'UTF-8') ?></strong>
  </p>

  <?php if ($errors): ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>
    <p><a class="btn outline" href="index.php">← Quay lại</a></p>
  <?php else: ?>

    <?php if (!$is_logged_in): ?>
      <div class="login-prompt">
        Bạn chưa đăng nhập. <strong>Đăng nhập</strong> để lưu tìm kiếm, xem giá thành viên và đặt/chọn ghế nhanh hơn.
        <a style="margin-left:8px;text-decoration:underline;" href="<?= htmlspecialchars($login_with_next, ENT_QUOTES, 'UTF-8') ?>">Đăng nhập ngay</a>
      </div>
    <?php else: ?>
      <div class="user-greet">
        <div>
          <strong>Xin chào, <?=htmlspecialchars($user['name'] ?? $user['email'], ENT_QUOTES, 'UTF-8')?></strong>
          <div class="muted">Bạn có thể lưu tìm kiếm hoặc tiếp tục chọn ghế.</div>
        </div>
        
      </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
      <div class="card">
        <div class="card-left">
          <h3>Không tìm thấy chuyến bay phù hợp</h3>
          <p class="muted">Không có chuyến bay trống theo bộ lọc. Bạn có thể thử thay đổi ngày hoặc hạng ghế.</p>
        </div>
        <div class="card-right"></div>
      </div>

    <?php else: ?>
      <?php foreach ($rows as $r):
        $isBest = ($min_price !== null && $r['gia_co_ban'] == $min_price);
        // bảo vệ các trường null
        $so_hieu = htmlspecialchars($r['so_hieu'] ?? '-', ENT_QUOTES, 'UTF-8');
        $ten_di  = htmlspecialchars($r['ten_di'] ?? '-', ENT_QUOTES, 'UTF-8');
        $ten_den = htmlspecialchars($r['ten_den'] ?? '-', ENT_QUOTES, 'UTF-8');
        $gio_di  = fmt_dt($r['gio_di'] ?? '');
        $gio_den = fmt_dt($r['gio_den'] ?? '');
        $so_ghe  = isset($r['so_ghe_con']) ? (int)$r['so_ghe_con'] : '-';
        $gia     = isset($r['gia_co_ban']) && $r['gia_co_ban'] !== null ? number_format((float)$r['gia_co_ban']) : '-';
        $flight_id = rawurlencode($r['chuyen_id']);
      ?>
        <div class="card <?= $isBest ? 'best-price' : '' ?>">
          <div class="card-left">
            <h3><?= $so_hieu ?> <?php if ($isBest): ?><small style="color:#16a34a;font-size:0.9rem;">(Rẻ nhất)</small><?php endif; ?></h3>
            <p><strong>Đi:</strong> <?= $ten_di ?> — <span class="muted"><?= $gio_di ?></span></p>
            <p><strong>Đến:</strong> <?= $ten_den ?> — <span class="muted"><?= $gio_den ?></span></p>
            <p><strong>Số ghế trống:</strong> <?= $so_ghe ?></p>
          </div>

          <div class="card-right">
            <p class="price"><?= $gia ?> VND</p>

            <?php if ($is_logged_in): ?>
              <!-- Người dùng đã đăng nhập: cho phép chọn/chuyển sang chọn ghế -->
              <a class="btn" href="index.php?p=select_seat&flight_id=<?= $flight_id ?>&cabin=<?= rawurlencode($cab) ?>">Chọn</a>
            <?php else: ?>
              <!-- Khách: dẫn tới login với next -> quay lại trang hiện tại -->
              <a class="btn outline" href="<?= htmlspecialchars($login_with_next, ENT_QUOTES, 'UTF-8') ?>">Đăng nhập để đặt</a>
            <?php endif; ?>

          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <div style="margin-top:12px">
      <a href="index.php?p=customer" class="btn outline">← Quay lại</a>
    </div>

  <?php endif; ?>

</main>

<footer id="lien-he">
  <div class="container">© <span id="y"></span> VNAir Ticket</div>
</footer>
<script>document.getElementById('y').textContent = new Date().getFullYear();</script>
</body>
</html>
