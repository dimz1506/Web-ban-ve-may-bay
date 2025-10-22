<?php
// session_start();
// if (!isset($_SESSION['user_id'])) {
//   header('Location: index.php?p=login');
//   exit;
// }
// ✅ pages/booking/my_bookings.php
if (!function_exists('db')) require_once dirname(__DIR__,2).'/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$flash_ok  = flash_get('ok') ?? null;
$flash_err = flash_get('err') ?? null;

/* -------------------- pick booking -------------------- */
$pnr = trim($_GET['pnr'] ?? '');

if ($pnr === '') {
  $row = db()->query("SELECT pnr FROM dat_cho ORDER BY created_at DESC LIMIT 1")->fetch();
  if ($row) $pnr = $row['pnr'];
}

$notfound = false;
$booking  = null;
$tickets  = [];

if ($pnr === '') {
  $notfound = true;
} else {
  // Lấy thông tin đặt chỗ
  $st = db()->prepare("SELECT id, pnr, created_at FROM dat_cho WHERE pnr=? LIMIT 1");
  $st->execute([$pnr]);
  $booking = $st->fetch();
  $notfound = !$booking;

  // Lấy danh sách vé
  if (!$notfound) {
    // ✅ kiểm tra có cột gioi_tinh trong DB không
    $colCheck = db()->query("SHOW COLUMNS FROM hanh_khach LIKE 'gioi_tinh'")->fetch();
    $hasGenderCol = !!$colCheck;

    $sql = "
      SELECT 
        v.id AS ve_id, v.so_ve, v.trang_thai, v.so_ghe,
        cb.id AS chuyen_id, cb.so_hieu, cb.gio_di, cb.gio_den,
        s1.ten AS san_bay_di, s2.ten AS san_bay_den,
        hg.ten AS ten_hang,
        hk.ho_ten, hk.ngay_sinh, hk.so_giay_to
        ".($hasGenderCol ? ", hk.gioi_tinh" : "")."
      FROM ve v
      JOIN chuyen_bay cb         ON cb.id = v.chuyen_bay_id
      JOIN tuyen_bay tb          ON tb.id = cb.tuyen_bay_id
      JOIN san_bay s1            ON s1.ma = tb.di
      JOIN san_bay s2            ON s2.ma = tb.den
      JOIN hang_ghe hg           ON hg.id = v.hang_ghe_id
      JOIN hanh_khach hk         ON hk.id = v.hanh_khach_id
      WHERE v.dat_cho_id = ?
      ORDER BY hk.ho_ten, cb.gio_di ASC
    ";
    $st2 = db()->prepare($sql);
    $st2->execute([$booking['id']]);
    $tickets = $st2->fetchAll();
  }
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function fmtDT($d){ return $d ? date('d/m/Y H:i', strtotime($d)) : ''; }
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>Đặt chỗ của tôi <?= $pnr ? ('- PNR ' . h($pnr)) : '' ?></title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    body{background:#f8fafc;font-family:'Segoe UI',sans-serif;margin:0;}
    main.container{max-width:1000px;margin:24px auto;padding:22px;background:#fff;border-radius:18px;box-shadow:0 6px 16px rgba(0,0,0,.08);}
    .header-line{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .pnr-form{display:flex;gap:8px;align-items:center}
    .pnr-form input{height:36px;padding:0 10px;border:1px solid #cbd5e1;border-radius:8px;min-width:220px}
    .pnr-form button{height:36px}
    .btn{background:#1e40af;color:#fff;padding:.45rem .9rem;border-radius:8px;border:none;text-decoration:none;cursor:pointer;}
    .btn:hover{background:#1e3a8a;}
    .btn.outline{background:transparent;border:1px solid #1e40af;color:#1e40af;}
    .btn.outline:hover{background:#e0e7ff;}
    .pill{padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600}
    .alert{padding:10px 12px;border-radius:10px;margin:10px 0}
    .alert.ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
    .alert.err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
    .card{border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;margin:12px 0;background:#fff}
    .status{display:inline-block;font-weight:700}
    .status.CONFIRMED,.status.DA_XUAT{color:#166534;background:#dcfce7}
    .status.PENDING{color:#92400e;background:#fef3c7}
    .status.HUY{color:#991b1b;background:#fee2e2}
    .muted{color:#64748b}
  </style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
  </div>
</header>

<main class="container">
  <div class="header-line">
    <h2>Đặt chỗ của tôi</h2>
    <form class="pnr-form" method="get" action="index.php">
      <input type="hidden" name="p" value="my_bookings">
      <input name="pnr" placeholder="Nhập PNR (ví dụ: ABC123)" value="<?=h($pnr)?>">
      <button class="btn" type="submit">Tra cứu</button>
      <a class="btn outline" href="index.php?p=my_tickets">Danh sách vé</a>
    </form>
  </div>

  <?php if ($flash_ok): ?><div class="alert ok"><?=h($flash_ok)?></div><?php endif; ?>
  <?php if ($flash_err): ?><div class="alert err"><?=h($flash_err)?></div><?php endif; ?>

  <?php if ($notfound): ?>
    <div class="card">
      <p>Không tìm thấy đặt chỗ phù hợp. Hãy nhập đúng <strong>PNR</strong> hoặc xem <a href="index.php?p=my_tickets">danh sách vé</a>.</p>
    </div>
  <?php else: ?>
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
        <div>
          <div><strong>PNR:</strong> <span class="pill" style="background:#eff6ff;color:#1e40af"><?=h($booking['pnr'])?></span></div>
          <div class="muted">Ngày đặt: <?=fmtDT($booking['created_at'])?></div>
  </div>
      </div>
    </div>

    <?php if (!$tickets): ?>
      <div class="card"><em>Chưa có vé nào trong đặt chỗ này.</em></div>
    <?php else: ?>

    <?php
      // ✅ Gom vé theo hành khách
      $grouped = [];
      foreach ($tickets as $t) {
          $key = $t['ho_ten'] . '_' . $t['so_giay_to'];
          $grouped[$key][] = $t;
      }
    ?>

    <?php foreach ($grouped as $passenger => $list): $p = $list[0]; ?>
      <div class="card" style="display:flex;justify-content:space-between;align-items:center;gap:20px;padding:18px 22px;margin-bottom:16px;box-shadow:0 2px 5px rgba(0,0,0,0.05);">
        
        <!-- Cột trái -->
        <div style="flex:1;">
          <div style="font-weight:600;font-size:16px;color:#1e3a8a;">
            👤 <?=h($p['ho_ten'])?> 
            (<?= isset($p['gioi_tinh']) ? ($p['gioi_tinh']==='M'?'Nam':($p['gioi_tinh']==='F'?'Nữ':'Khác')) : 'Không rõ' ?>, <?=h($p['ngay_sinh'])?>)
          </div>

          <!-- Các chiều bay -->
          <div style="font-size:14px;color:#334155;line-height:1.6;margin-top:6px;">
            <?php foreach ($list as $idx => $t): ?>
              <div style="margin-top:4px;">
                <?= $idx == 0 ? '✈' : '↩' ?> 
                <strong><?=$t['san_bay_di']?> → <?=$t['san_bay_den']?></strong> 
                (<?=$t['so_hieu']?>) • Ghế <strong><?=$t['so_ghe']?></strong>
                <br>Giờ đi: <?=fmtDT($t['gio_di'])?> — Giờ đến: <?=fmtDT($t['gio_den'])?>
                <br>Hạng: <?=h($t['ten_hang'])?> • 
                Trạng thái: <span class="status pill <?=$t['trang_thai']?>"><?=h($t['trang_thai'])?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- QR -->
        <div style="text-align:center;flex:0 0 140px;">
          <img 
            src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=<?=urlencode($p['ho_ten'].'-'.$p['so_giay_to'].'-'.$booking['pnr'])?>" 
            alt="QR Vé <?=h($p['ho_ten'])?>"
            style="width:120px;height:120px;border:1px solid #e2e8f0;border-radius:8px;padding:4px;background:#fff;"
          >
          <div style="font-size:12px;color:#475569;margin-top:4px;">
            <?=h($booking['pnr'])?>
          </div>
        </div>

        <!-- Nút -->
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:10px;min-width:130px;">
          <a href="index.php?p=print_ticket&ve_id=<?=$list[0]['ve_id']?>" 
             target="_blank"
             class="btn outline"
             style="width:110px;text-align:center;">🖨 In vé</a>
          <a href="index.php?p=edit_ticket&id=<?=$list[0]['ve_id']?>"
             class="btn"
             style="width:110px;text-align:center;">✏ Sửa</a>
          <?php if ($list[0]['trang_thai']!=='HUY'): ?>
            <a href="index.php?p=cancel_ticket&ve_id=<?=$list[0]['ve_id']?>"
               onclick="return confirm('Bạn có chắc muốn hủy vé này?');"
               class="btn outline"
               style="color:#dc2626;border-color:#dc2626;width:110px;text-align:center;">❌ Hủy vé</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <?php endif; ?>
  <?php endif; ?>

  <div style="margin-top:16px;display:flex;gap:10px;">
    <a class="btn outline" href="javascript:history.back()">← Quay lại</a>
    <a class="btn" href="index.php?p=customer">🏠 Trang chủ</a>
  </div>
</main>

<footer class="footer">
  <div class="container">&copy; <?=date('Y')?> VNAir Ticket</div>
</footer>
</body>
</html>
