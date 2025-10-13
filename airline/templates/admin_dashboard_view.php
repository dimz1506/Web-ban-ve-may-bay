<?php
// pages/admin_dashboard.php — Trang riêng cho ADMIN

$pdo = db();
// TODO: thay các biến demo bằng truy vấn thật từ DB (COUNT, latest rows, v.v.)
  // Thống kê số lượng bản ghi trong bảng nguoi_dung
try {
  $pdo = db(); // hoặc global $pdo; nếu bạn đã có $pdo sẵn trong config.php
  $sql = "SELECT COUNT(*) AS total FROM nguoi_dung";
  $demo_counts['users'] = $pdo->query($sql)->fetchColumn();
  $demo_counts['flights'] = $pdo->query("SELECT COUNT(*) AS total FROM chuyen_bay")->fetchColumn();
  $demo_counts['bookings'] = $pdo->query("SELECT COUNT(*) AS total FROM ve")->fetchColumn();
  $demo_counts['promos'] = $pdo->query("SELECT COUNT(*) AS total FROM khuyen_mai")->fetchColumn();
} catch (Exception $e) {
  error_log("Lỗi khi đếm người dùng: " . $e->getMessage());
  $demo_counts['users'] = 0;
}



// Lấy dữ liệu người dùng mới nhất từ bảng nguoi_dung
$recent_users = [];
try {
  $sql = "SELECT id, ho_ten AS name, email, DATE(created_at) AS created
          FROM nguoi_dung
          ORDER BY created_at DESC
          LIMIT 5";
  $st = $pdo->prepare($sql);
  $st->execute();
  $recent_users = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Lỗi khi tải danh sách người dùng mới: " . $e->getMessage());
  $recent_users = [];
}




// Lấy dữ liệu đơn đặt mới nhất từ bảng ve
$recent_tickets = [];
try {
  $pdo = db();

  $sql = "
     SELECT
      v.so_ve AS ticket_no,
      COALESCE(hk.ho_ten, '—') AS passenger_name,
      cb.so_hieu AS flight_no,
      v.trang_thai AS status
    FROM ve v
    LEFT JOIN hanh_khach hk ON hk.id = v.hanh_khach_id
    LEFT JOIN chuyen_bay cb ON cb.id = v.chuyen_bay_id
    ORDER BY v.phat_hanh_luc DESC, v.id DESC
    LIMIT 5
  ";

  $st = $pdo->prepare($sql);
  $st->execute();
  $recent_tickets = $st->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  error_log("Lỗi khi lấy vé mới: " . $e->getMessage());
  $recent_tickets = [];
}

if (!function_exists('current_user')) {
  function current_user() {
    // Example: return user info from session, adjust as needed
    return isset($_SESSION['user']) ? $_SESSION['user'] : ['email' => 'admin@domain.com'];
  }
}
$user = current_user();

?>



<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bảng điều khiển Admin | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <link rel="stylesheet" href="assets/admin.css">
  
</head>

<body>
  <header class="topbar">
    <div class="container nav" role="navigation" aria-label="Main navigation">
      <div class="brand"><div class="logo" aria-hidden="true">✈</div><div>VNAir Ticket</div></div>
      <div class="nav-cta">
        <a class="btn outline" href="index.php?p=logout">Đăng xuất</a>
      </div>
    </div>
  </header>

  <main class="container">
    <div class="layout" role="main">
      <aside class="sidebar" aria-label="Thanh điều hướng phụ">
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,#0b63d6,#2ea1ff);display:flex;align-items:center;justify-content:center;color:white;font-weight:700">AD</div>
          <div>
            <div style="font-weight:700">Admin</div>
            <div class="muted" style="font-size:13px"><?=htmlspecialchars($user['email']) ?></div>
          </div>
        </div>

        <nav style="margin-top:16px;">
          <a href="index.php?p=admin" class="active">Tổng quan</a>
          <a href="index.php?p=users">Quản lý tài khoản</a>
          <a href="index.php?p=flights">Quản lý chuyến bay</a>
          <a href="index.php?p=promotions">Quản lý khuyến mãi</a>
           <a href="index.php?p=classes">Quản lý hạng ghế</a>
           <a href="index.php?p=bookings">Quản lý đơn đặt</a>
          <a href="index.php?p=reports">Báo cáo</a>
          <a href="index.php?p=settings">Cài đặt</a>
          
        </nav>

        <div style="margin-top:18px;">
          <div class="muted" style="font-weight:700;margin-bottom:8px">Nhanh</div>
          <div class="quick-links">
            <a href="index.php?p=users&pact=create">Thêm tài khoản</a>
            <a href="index.php?p=flights&pact=create">Tạo chuyến bay</a>
            <a href="index.php?p=promotions&pact=create">Tạo khuyến mãi</a>
             <a href="index.php?p=classes">Quản lý hạng ghế</a>
            <a href="index.php?p=reports">Xem báo cáo</a>
          </div>
        </div>
      </aside>

      <section class="main">
        <div class="page-title">
          <h1>Bảng điều khiển</h1>
          <div class="controls">
            <a class="btn outline" href="index.php?p=reports">Xuất báo cáo</a>
            <a class="btn" href="index.php?p=users">Quản lý người dùng</a>
          </div>
        </div>

        <!-- Stats -->
        <div class="grid-stats" role="status" aria-live="polite">
          <div class="stat">
            <div class="label">Tổng người dùng</div>
            <div class="num"><?=number_format($demo_counts['users'])?></div>
            <div class="muted">Thành viên đăng ký</div>
          </div>
          <div class="stat">
            <div class="label">Chuyến bay</div>
            <div class="num"><?=number_format($demo_counts['flights'])?></div>
            <div class="muted">Tuyến đang hoạt động</div>
          </div>
          <div class="stat">
            <div class="label">Đơn đặt</div>
            <div class="num"><?=number_format($demo_counts['bookings'])?></div>
            <div class="muted">Tổng đặt vé</div>
          </div>
          <div class="stat">
            <div class="label">Khuyến mãi</div>
            <div class="num"><?=number_format($demo_counts['promos'])?></div>
            <div class="muted">Ưu đãi đang chạy</div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:18px;">
          <div class="card">
            <h3>Đơn đặt mới nhất</h3>
            <div class="muted" style="margin-bottom:10px">Danh sách các đơn đặt gần đây</div>
            <!-- TODO: Lấy dữ liệu đơn đặt thực tế từ DB -->
            <table>
              <thead>
                <tr>
                  <th>Mã vé</th>
                  <th>Người đặt</th>
                  <th>Mã chuyến</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
             <tbody>
      <?php if (empty($recent_tickets)): ?>
        <tr><td colspan="4" style="text-align:center">Không có dữ liệu vé.</td></tr>
      <?php else: ?>
        <?php foreach ($recent_tickets as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t['ticket_no']) ?></td>
            <td><?= htmlspecialchars($t['passenger_name']) ?></td>
            <td><?= htmlspecialchars($t['flight_no']) ?></td>
            <td>
              <?php
                $map = [
                  'DA_XUAT' => 'Đã xuất',
                  'CHUA_XUAT' => 'Chưa xuất',
                  'HUY' => 'Đã hủy',
                  'HOAN' => 'Hoàn vé',
                ];
                echo htmlspecialchars($map[$t['status']] ?? $t['status']);
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
            </table>
          </div>

          <div class="card">
            <h3>Người dùng mới</h3>
            <div class="muted" style="margin-bottom:10px">Danh sách đăng ký gần đây</div>
            <table>
              <thead>
                <tr><th>Tên</th><th>Email</th><th>Ngày</th></tr>
              </thead>
              <tbody>
                <?php if (empty($recent_users)): ?>
        <tr><td colspan="3" style="text-align:center;">Không có dữ liệu</td></tr>
      <?php else: ?>
        <?php foreach ($recent_users as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['created']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
              </tbody>
            </table>

            <div style="margin-top:12px;text-align:right">
              <a class="btn outline" href="index.php?p=users">Xem tất cả</a>
            </div>
          </div>
        </div>


      </section>
    </div>
  </main>

  <footer>
    <div class="container">© <span id="y"></span> VNAir Ticket</div>
  </footer>

  <script>
    document.getElementById('y').textContent = new Date().getFullYear();
  </script>
</body>

</html>
