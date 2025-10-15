<?php
// public/index.php
// Router + Trang chủ (landing). Khi có tham số ?p=... sẽ nạp trang trong /pages.
require_once dirname(__DIR__) . '/config.php';

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
    'flights'     => 'admin/flights.php',
    'promotions'   => 'admin/promotions.php',
    // Customer pages
    'profile'      => 'customer/profile.php',
    'payment'      => 'customer/payment.php',
    'invoice'      => 'customer/invoice.php',
    'contact'      => 'customer/contact.php',
    'notifications'=> 'customer/notifications.php',
    'book_search'  => 'customer/book_search.php',
    'my_tickets'   => 'customer/my_tickets.php',
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
</head>

<body>
  <header class="topbar">
    <div class="container nav">
      <div class="brand">
        <div class="logo">✈</div>
        <div>VNAir Ticket</div>
      </div>
      <nav>
        <a href="#uu-dai">Ưu đãi</a>
        <a href="#quy-trinh">Quy trình</a>
        <a href="#lien-he">Liên hệ</a>
      </nav>
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

        <form id="searchForm" autocomplete="off">
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
      <div>© <span id="y"></span> VNAir Ticket — Trang demo học tập.
      </div>
    </div>
  </footer>

  <script src="assets/home.js" defer></script>
</body>

</html>