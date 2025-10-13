<?php
// pages/staff_dashboard.php — Trang riêng cho STAFF (giao diện cập nhật)
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['STAFF']);          // chỉ STAFF vào được
$pdo = db();

// cố gắng lấy thông tin user hiện tại (tùy hàm trong app của bạn)
$user = null;
if (function_exists('me')) {
    $user = me();
} elseif (function_exists('current_user')) {
    $user = current_user();
}

// lấy vài dữ liệu tóm tắt (an toàn với try/catch)
$recent_flights = [];
$recent_bookings = [];
try {
    $recent_flights = $pdo->query("SELECT id, so_hieu, gio_di, gio_den FROM chuyen_bay ORDER BY gio_di DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // ignore — không block giao diện
    error_log("Lỗi lấy chuyến gần nhất: " . $e->getMessage());
}
try {
    $recent_bookings = $pdo->query("SELECT id, so_ve, trang_thai FROM ve ORDER BY id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log("Lỗi lấy đơn đặt gần nhất: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bảng điều khiển Nhân viên | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <link rel="stylesheet" href="assets/staff.css">
</head>
<body>

  <main class="container">
    <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px">
      <div>
        <h2>Trang Nhân viên</h2>
      </div>
      <div class="actions">
        <a class="btn ghost" href="index.php?p=logout">Đăng xuất</a>
      </div>
    </header>

    <div class="grid">
      <!-- main column -->
      <div>
        <div class="card" aria-labelledby="profileTitle">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
            <div style="display:flex;gap:12px;align-items:center">
              <div class="avatar" aria-hidden="true"><?= isset($user['ho_ten']) ? strtoupper(substr($user['ho_ten'],0,1)) : 'NV' ?></div>
              <div>
                <div style="font-weight:800"><?= htmlspecialchars($user['ho_ten'] ?? 'Nhân viên') ?></div>
                <div class="muted small"><?= htmlspecialchars($user['email'] ?? '') ?></div>
              </div>
            </div>
            <div style="text-align:right">
              <a class="btn" href="index.php?p=bookings">Tra cứu vé</a>
            </div>
          </div>

          <div style="margin-top:12px" class="small muted">
            Gợi ý: nhân viên có thể tra cứu vé, theo dõi chuyến gần tới và báo cáo sự cố. Các thao tác tạo/chỉnh sửa chuyến bay chỉ dành cho ADMIN.
          </div>
        </div>

        <div class="card" aria-labelledby="flightsTitle" style="margin-top:12px">
          <h3 id="flightsTitle" style="margin:0 0 8px">Chuyến bay gần nhất</h3>
          <div class="small muted">Các chuyến được sắp xếp theo thời gian khởi hành</div>

          <table class="tbl" style="margin-top:10px">
            <thead>
              <tr><th>Mã chuyến</th><th>Khởi hành</th><th>Hạ cánh</th><th style="width:120px"> </th></tr>
            </thead>
            <tbody>
              <?php if (empty($recent_flights)): ?>
                <tr><td colspan="4" class="small muted" style="text-align:center;padding:12px">Không có dữ liệu chuyến.</td></tr>
              <?php else: foreach ($recent_flights as $f): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($f['so_hieu'] ?? '-') ?></strong></td>
                  <td class="small"><?= isset($f['gio_di']) && $f['gio_di'] ? date('Y-m-d H:i', strtotime($f['gio_di'])) : '-' ?></td>
                  <td class="small"><?= isset($f['gio_den']) && $f['gio_den'] ? date('Y-m-d H:i', strtotime($f['gio_den'])) : '-' ?></td>
                  <td style="text-align:right">
                    <a class="btn ghost" href="index.php?p=flights&view=<?= (int)$f['id'] ?>">Xem</a>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <div class="card" style="margin-top:12px">
          <h3 style="margin:0 0 8px">Đơn đặt mới nhất</h3>
          <div class="small muted">Các đơn đặt vừa phát sinh</div>

          <table class="tbl" style="margin-top:10px">
            <thead><tr><th>Mã vé</th><th>Trạng thái</th><th style="width:120px"></th></tr></thead>
            <tbody>
              <?php if (empty($recent_bookings)): ?>
                <tr><td colspan="3" class="small muted" style="text-align:center;padding:12px">Không có đơn đặt.</td></tr>
              <?php else: foreach ($recent_bookings as $b): ?>
                <tr>
                  <td><?= htmlspecialchars($b['so_ve'] ?? '-') ?></td>
                  <td class="small muted"><?= htmlspecialchars($b['trang_thai'] ?? '-') ?></td>
                  <td style="text-align:right"><a class="btn ghost" href="index.php?p=bookings&edit=<?= (int)$b['id'] ?>">Xem</a></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- right column -->
      <aside>
        <div class="card">
          <h4 style="margin:0 0 8px">Liên kết nhanh</h4>
          <div class="quick-links">
            <a href="index.php?p=bookings">Tra cứu / Lọc vé</a>
            <a href="index.php?p=flights">Danh sách chuyến bay</a>
            <a href="index.php?p=reports">Báo cáo chung</a>
            <a href="index.php?p=help">Quy trình & Hướng dẫn</a>
          </div>
        </div>

        <div class="card" style="margin-top:12px">
          <h4 style="margin:0 0 8px">Nhiệm vụ thường nhật</h4>
          <ul class="small muted" style="margin:0;padding-left:18px">
            <li>Tra cứu vé theo mã hoặc theo hành khách</li>
            <li>Theo dõi chuyến sắp/đang/đã cất/hạ cánh</li>
            <li>Báo cáo sự cố & chuyển thông tin cho đội vận hành</li>
          </ul>
        </div>
      </aside>
    </div>
  </main>

  <footer style="text-align: center;" class="small muted">© <span id="y"></span> VNAir Ticket</footer>

  <script>
    document.getElementById('y').textContent = new Date().getFullYear();
  </script>
</body>
</html>
