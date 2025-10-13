<?php
// pages/customer_dashboard.php — Trang riêng cho Khách hàng (giao diện giống trang chủ)
// Mục tiêu: giữ nguyên chức năng, làm sạch output (htmlspecialchars) và loại bớt phần comment thừa.
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

// lấy user hiện tại an toàn
$user = null;
if (function_exists('me')) {
    $user = me();
} elseif (function_exists('current_user')) {
    $user = current_user();
}
$userName = htmlspecialchars($user['ho_ten'] ?? 'Khách', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Trang khách hàng | VNAir Ticket</title>
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
        <a href="index.php?p=customer">Trang của tôi</a>
        <a href="index.php?p=my_tickets">Vé của tôi</a>
      </nav>

      <div class="nav-cta">
        <div class="muted" style="margin-right:10px">Xin chào, <strong><?= $userName ?></strong></div>
        <a class="btn outline" href="index.php?p=logout">Đăng xuất</a>
      </div>
    </div>
  </header>

  <!-- Hero + Search (giống trang chủ) -->
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
        <datalist id="airports"></datalist>
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
        <div class="card"><h3>1) Chọn chuyến</h3><p class="muted">So sánh giờ bay và hạng ghế phù hợp.</p></div>
        <div class="card"><h3>2) Thanh toán</h3><p class="muted">Ví điện tử, thẻ nội địa/quốc tế hoặc chuyển khoản.</p></div>
        <div class="card"><h3>3) Nhận vé điện tử</h3><p class="muted">Vé gửi về email/SMS, có thể tra cứu bất kỳ lúc nào.</p></div>
      </div>
    </div>
  </section>

  <footer id="lien-he">
    <div class="container">
      <div>© <span id="y"></span> VNAir Ticket — Khu khách hàng.</div>
    </div>
  </footer>

  <script src="assets/home.js" defer></script>
  <script>
    // footer year
    document.getElementById('y').textContent = new Date().getFullYear();

    // UI: toggle one-way / round-trip
    const onewayBtn = document.getElementById('tab-oneway');
    const roundBtn = document.getElementById('tab-round');
    const returnWrap = document.getElementById('returnWrap');

    function setRound(on) {
      if (on) {
        roundBtn.classList.add('active');
        onewayBtn.classList.remove('active');
        returnWrap.hidden = false;
      } else {
        onewayBtn.classList.add('active');
        roundBtn.classList.remove('active');
        returnWrap.hidden = true;
      }
    }
    if (onewayBtn && roundBtn) {
      onewayBtn.addEventListener('click', ()=> setRound(false));
      roundBtn.addEventListener('click', ()=> setRound(true));
    }

    // swap from/to
    const swapBtn = document.getElementById('swapBtn');
    if (swapBtn) {
      swapBtn.addEventListener('click', function(){
        const a = document.getElementById('from');
        const b = document.getElementById('to');
        const tmp = a.value;
        a.value = b.value;
        b.value = tmp;
        a.focus();
      });
    }

    // basic client validation on submit (keeps behavior)
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
      searchForm.addEventListener('submit', function(e){
        const from = document.getElementById('from').value.trim();
        const to = document.getElementById('to').value.trim();
        const depart = document.getElementById('depart').value;
        if (!from || !to) {
          e.preventDefault();
          alert('Vui lòng nhập cả điểm đi và điểm đến.');
        } else if (!depart) {
          e.preventDefault();
          alert('Vui lòng chọn ngày đi.');
        }
      });
    }
  </script>
</body>
</html>
