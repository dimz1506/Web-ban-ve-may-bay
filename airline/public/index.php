<?php
// public/index.php
// Router + Trang chủ (landing). Khi có tham số ?p=... sẽ nạp trang trong /pages.
require_once dirname(__DIR__) . '/config.php';

$flights_week = [];
try {
  $stmt = db()->prepare("
    SELECT 
      so_hieu,
      gio_di,
      gio_den,
      trang_thai
    FROM chuyen_bay
    WHERE YEARWEEK(gio_di, 1) = YEARWEEK(CURDATE(), 1)
    ORDER BY gio_di ASC
  ");
  $stmt->execute();
  $flights_week = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $flights_week = [];
}

$p = $_GET['p'] ?? null;

// Nếu có tham số p (và không phải 'home'), dùng router trang cũ
if ($p !== null && $p !== 'home') {
  $map = [
    'home'         => 'dashboard.php', // vẫn map để tương thích nếu gọi p=home
    'login'        => 'login.php',
    'logout'       => 'login.php',
    'register'     => 'register.php',
    'admin'        => 'admin/admin_dashboard.php',
    'users'        => 'admin/user.php',
    'staff'        => 'staff/staff_dashboard.php',
    'customer'     => 'customer/customer_dashboard.php',
    'contact'      => 'customer/contact.php',
    'profile'      => 'customer/profile.php',
    'flights'      => 'admin/flights.php',
    'promotions'   => 'admin/promotions.php',
    'classes'      => 'admin/classes.php',
    'reports'      => 'admin/reports.php',
    'bookings'     => 'admin/bookings.php',
    'router'      => 'admin/router.php',
    'sanbay'    => 'admin/sanbay.php',
    'fare'        => 'admin/fare.php',
    
    'book_search'  => 'customer/book_search.php',
    'notifications'=> 'customer/notifications.php',
    'invoice'      => 'customer/invoice.php',
    
    'search_results' => 'booking/search_results.php',
    'select_seat' => 'booking/select_seat.php',
    'add_passengers' => 'booking/add_passengers.php',
    'review_checkout' => 'booking/review_checkout.php',
    'my_bookings' => 'booking/my_bookings.php',
    'my_tickets' => 'booking/my_tickets.php',
    'edit_ticket' => 'booking/edit_ticket.php',
    'cancel_ticket' => 'booking/cancel_ticket.php',
    'confirm_booking'=> 'booking/confirm_booking.php',
    'payment'=> 'booking/payment.php',
    'search_roundtrip'=> 'booking/search_roundtrip.php',
    'verify_ticket' => 'booking/verify_ticket.php',
    'print_ticket' => 'booking/print_ticket.php',

    //hỡ trợ khách hàng
    "support_requests" => 'staff/support_requests.php',
    "support_detail"   => 'staff/support_detail.php',
    "support_update"   => 'staff/support_update.php',

  ];
  if (!isset($map[$p])) {
    http_response_code(404);
    exit('Not found');
  }
  require_once dirname(__DIR__) . '/pages/' . $map[$p];
  exit;
}
?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Trang chủ - Đặt vé máy bay</title>
  <link rel="stylesheet" href="assets/home.css">
   <link rel="stylesheet" href="assets/flightsweek.css">
</head>

<body>
  <header class="topbar">
    <div class="container nav">
      <div class="brand">
        <div class="logo">✈</div>
        <div>VNAir Ticket</div>
      </div>
      <!-- <nav>
        <a href="#uu-dai">Ưu đãi</a>
        <a href="#quy-trinh">Quy trình</a>
        <a href="#lien-he">Liên hệ</a>
      </nav> -->
      <div class="nav-cta">
        <a class="btn outline" href="index.php?p=login" aria-label="Đăng nhập">Đăng nhập</a>
        <a class="btn" href="index.php?p=register" aria-label="Đăng ký">Đăng ký</a>
      </div>
    </div>
  </header>

  <div class="hero">
    <div class="container hero-inner">
      <div>
        <h1 class="headline">Đặt vé Vietnam Airlines nhanh chóng, giá minh bạch</h1>
        <p class="subline">So sánh hạng ghế, chọn giờ bay phù hợp và nhận vé điện tử ngay sau thanh toán.</p>
        <div class="muted">An toàn • Bảo mật • Hỗ trợ 24/7</div>
      </div>

      <div class="search-card" role="search" aria-label="Tìm chuyến bay">
        <div class="tabs" role="tablist">
          <button class="tab active" type="button" id="tab-oneway" aria-selected="true">Một chiều</button>
          <button class="tab" type="button" id="tab-round">Khứ hồi</button>
        </div>

        <form id="searchForm" action="index.php" method="get" autocomplete="off">
          <input type="hidden" name="p" value="search_results">
          <div class="err" id="errBox" role="alert"></div>

          <div class="grid">
            <div class="field row" style="grid-column: span 6;">
              <label for="from">Đi từ</label>
              <input id="from" name="from" placeholder="Vd: HAN - Ha Noi (Noi Bai)" list="airports" required>
              <button class="swap" type="button" id="swapBtn" title="Đổi chiều" aria-label="Đổi điểm đi/đến">↕</button>
            </div>
            <div class="field" style="grid-column: span 6;">
              <label for="to">Đến</label>
              <input id="to" name="to" placeholder="Vd: SGN - Ho Chi Minh (Tan Son Nhat)" list="airports" required>
            </div>

            <div class="field" style="grid-column: span 6;">
              <label for="depart">Ngày đi</label>
              <input id="depart" name="depart" type="date" required>
            </div>
            <div class="field" style="grid-column: span 6;" id="returnWrap" hidden>
              <label for="return">Ngày về</label>
              <input id="return" name="return" type="date">
            </div>

            <div class="field" style="grid-column: span 6;">
              <label for="pax">Số khách</label>
              <input id="pax" name="pax" type="number" min="1" value="1">
            </div>
            <div class="field" style="grid-column: span 6;">
              <label for="cabin">Hạng ghế</label>
              <select id="cabin" name="cabin">
                <option value="ECON">Phổ thông (ECON)</option>
                <option value="PREM">Phổ thông đặc biệt (PREM)</option>
                <option value="BUSI">Thương gia (BUSI)</option>
              </select>
            </div>
          </div>
          <div class="submit-row"><button class="btn" type="submit">Tìm chuyến</button></div>
        </form>
        
        <datalist id="airports"><!-- filled by JS --></datalist>
      </div>
    </div>
  </div>

<section id="flights-week">
  <div class="container">
    <h2>✈️ Các chuyến bay trong tuần</h2>
    <?php if (empty($flights_week)): ?>
      <p>Không có chuyến bay nào trong tuần này.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Số hiệu</th>
            <th>Giờ đi</th>
            <th>Giờ đến</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($flights_week as $f): ?>
             <tr>
              <td data-label="Số hiệu"><?= htmlspecialchars($f['so_hieu']) ?></td>
              <td data-label="Giờ đi"><?= date('d/m/Y H:i', strtotime($f['gio_di'])) ?></td>
              <td data-label="Giờ đến"><?= date('d/m/Y H:i', strtotime($f['gio_den'])) ?></td>
              <td data-label="Trạng thái"><?= htmlspecialchars($f['trang_thai']) ?></td>
         </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>



  <section id="uu-dai">
    <div class="container">
      <h2>Ưu đãi nổi bật</h2>
      <div class="cards">
        <article class="card">
          <h3>Cuối tuần vàng</h3>
          <p class="muted">Giảm 15% cho tuyến HAN ⇄ SGN, xuất vé trong 72 giờ.</p>
        </article>
        <article class="card">
          <h3>Mua sớm giá tốt</h3>
          <p class="muted">Đặt trước ≥ 21 ngày: ưu đãi bổ sung đến 300.000đ.</p>
        </article>
        <article class="card">
          <h3>Doanh nghiệp thân thiết</h3>
          <p class="muted">Tài khoản công ty hưởng hạng đặt chỗ linh hoạt.</p>
        </article>
      </div>
    </div>
  </section>

  <section id="quy-trinh" style="background:#f1f5f9">
    <div class="container">
      <h2>Quy trình 3 bước</h2>
      <div class="cards">
        <div class="card">
          <h3>1) Chọn chuyến</h3>
          <p class="muted">So sánh giờ bay và hạng ghế phù hợp.</p>
        </div>
        <div class="card">
          <h3>2) Thanh toán</h3>
          <p class="muted">Ví điện tử, thẻ nội địa/quốc tế hoặc chuyển khoản.</p>
        </div>
        <div class="card">
          <h3>3) Nhận vé điện tử</h3>
          <p class="muted">Vé gửi về email/SMS, có thể tra cứu bất kỳ lúc nào.</p>
        </div>
      </div>
    </div>
  </section>


  <footer id="lien-he">

    <div class="container">
      <div>© 2025<span id="y"></span> VNAir Ticket.
      </div>
    </div>
  </footer>

  <script src="assets/home.js" defer></script>
</body>

</html>