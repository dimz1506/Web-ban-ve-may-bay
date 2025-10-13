<?php
// pages/customer_dashboard.php — Trang riêng cho Khách hàng (nâng cấp)
// Yêu cầu: giữ config & hàm auth (me(), require_login, csrf_token, v.v.)
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

// Lấy user hiện tại nếu có
$user = null;
if (function_exists('me')) {
    $user = me();
} elseif (function_exists('current_user')) {
    $user = current_user();
}
$user_name = htmlspecialchars($user['ho_ten'] ?? 'Khách');
$user_email = htmlspecialchars($user['email'] ?? '');
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Trang khách hàng | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <link rel="stylesheet" href="assets/customer.css">
</head>
<body>
  <header class="topbar" role="banner">
    <div class="container nav" role="navigation" aria-label="Main navigation">
      <div class="brand" style="display:flex;align-items:center;gap:10px">
        <div class="logo" aria-hidden="true">✈</div>
        <div>
          <div style="font-weight:800">VNAir Ticket</div>
          <div class="muted" style="font-size:13px">Khu khách hàng</div>
        </div>
      </div>

      <nav aria-label="khách hàng links" style="display:flex;gap:14px;align-items:center">
        <a href="index.php?p=customer">Trang của tôi</a>
        <a href="index.php?p=my_tickets">Vé của tôi</a>
      </nav>

      <div class="nav-cta" style="margin-left:auto">
        <div class="muted" style="margin-right:8px">Xin chào, <strong><?= $user_name ?></strong></div>
        <a class="btn outline" href="index.php?p=logout">Đăng xuất</a>
      </div>
    </div>
  </header>

  <main>
    <div class="hero" role="region" aria-label="Tìm chuyến bay">
      <div class="container hero-inner">
        <div>
          <h1 class="headline">Đặt vé nhanh, rõ ràng — VNAir Ticket</h1>
          <p class="subline">So sánh giá, chọn hạng ghế và nhận vé điện tử ngay sau khi thanh toán.</p>
          <p class="muted">An toàn • Bảo mật • Hỗ trợ 24/7</p>
        </div>

        <div class="search-card" aria-labelledby="searchTitle">
          <h3 id="searchTitle" style="margin:0 0 8px">Tìm chuyến bay</h3>
          <div class="tabs" role="tablist" aria-label="Kiểu chuyến">
            <button class="tab active" id="onewayTab" role="tab" aria-selected="true" aria-controls="searchForm">Một chiều</button>
            <button class="tab" id="roundTab" role="tab" aria-selected="false" aria-controls="searchForm">Khứ hồi</button>
          </div>

          <!-- gửi GET sang trang xử lý tìm kiếm (ví dụ index.php?p=search) -->
          <form id="searchForm" method="get" action="index.php" role="search" aria-label="Form tìm chuyến">
            <input type="hidden" name="p" value="search">
            <input type="hidden" name="trip_type" id="tripType" value="oneway">
            <div id="errBox" class="err" role="alert" style="display:none"></div>

            <div class="field">
              <label for="from">Đi từ</label>
              <div style="display:flex;gap:8px">
                <input id="from" name="from" placeholder="VD: HAN - Nội Bài" list="airports" required>
                <button type="button" id="swapBtn" class="swap" aria-label="Đổi điểm đi/đến">↕</button>
              </div>
            </div>

            <div class="field">
              <label for="to">Đến</label>
              <input id="to" name="to" placeholder="VD: SGN - Tân Sơn Nhất" list="airports" required>
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">
              <div class="field">
                <label for="depart">Ngày đi</label>
                <input id="depart" name="depart" type="date" required>
              </div>
              <div class="field" id="returnWrap" hidden>
                <label for="return">Ngày về</label>
                <input id="return" name="return" type="date">
              </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:8px">
              <div class="field">
                <label for="pax">Số khách</label>
                <input id="pax" name="pax" type="number" min="1" value="1" required>
              </div>
              <div class="field">
                <label for="cabin">Hạng ghế</label>
                <select id="cabin" name="cabin">
                  <option value="ECON">Phổ thông (ECON)</option>
                  <option value="PREM">Phổ thông đặc biệt (PREM)</option>
                  <option value="BUSI">Thương gia (BUSI)</option>
                </select>
              </div>
            </div>

            <div style="margin-top:12px;display:flex;justify-content:flex-end">
              <button class="btn" type="submit">Tìm chuyến</button>
            </div>
          </form>

          <datalist id="airports" aria-hidden="true">
            <!-- một số mẫu; bạn có thể nạp thực từ DB bằng AJAX nếu cần -->
            <option value="HAN - Noi Bai (Hanoi)"></option>
            <option value="SGN - Tan Son Nhat (Ho Chi Minh)"></option>
            <option value="DAD - Da Nang (Da Nang)"></option>
            <option value="CXR - Cam Ranh (Nha Trang)"></option>
            <option value="VCA - Can Tho (Can Tho)"></option>
          </datalist>
        </div>
      </div>
    </div>

    <section id="uu-dai" class="container" style="padding:28px 16px">
      <h2>Ưu đãi nổi bật</h2>
      <div class="cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:12px">
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
    </section>

    <section id="quy-trinh" style="background:#f1f5f9;padding:28px 0">
      <div class="container">
        <h2>Quy trình 3 bước</h2>
        <div class="cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:12px">
          <div class="card"><h3>1) Chọn chuyến</h3><p class="muted">So sánh giờ bay và hạng ghế phù hợp.</p></div>
          <div class="card"><h3>2) Thanh toán</h3><p class="muted">Ví điện tử, thẻ nội địa/quốc tế hoặc chuyển khoản.</p></div>
          <div class="card"><h3>3) Nhận vé điện tử</h3><p class="muted">Vé gửi về email/SMS, có thể tra cứu bất kỳ lúc nào.</p></div>
        </div>
      </div>
    </section>
  </main>

  <footer id="lien-he" style="padding:18px 0;background:#0c2236;color:#c7d3e3;margin-top:18px">
    <div class="container">© <span id="y"></span> VNAir Ticket — Khu khách hàng.</div>
  </footer>

  <script>
    // UI: toggle one-way / round-trip
    const onewayTab = document.getElementById('onewayTab');
    const roundTab = document.getElementById('roundTab');
    const returnWrap = document.getElementById('returnWrap');
    const tripTypeInput = document.getElementById('tripType');

    function setTrip(type){
      if(type==='round'){
        roundTab.classList.add('active'); onewayTab.classList.remove('active');
        returnWrap.hidden = false; tripTypeInput.value = 'round';
      } else {
        onewayTab.classList.add('active'); roundTab.classList.remove('active');
        returnWrap.hidden = true; tripTypeInput.value = 'oneway';
      }
    }
    onewayTab.addEventListener('click', ()=> setTrip('oneway'));
    roundTab.addEventListener('click', ()=> setTrip('round'));

    // swap from/to
    document.getElementById('swapBtn').addEventListener('click', ()=>{
      const a = document.getElementById('from');
      const b = document.getElementById('to');
      const t = a.value; a.value = b.value; b.value = t;
      a.focus();
    });

    // basic client validation (optional)
    document.getElementById('searchForm').addEventListener('submit', function(e){
      const from = document.getElementById('from').value.trim();
      const to = document.getElementById('to').value.trim();
      const depart = document.getElementById('depart').value;
      const trip = tripTypeInput.value;
      const ret = document.getElementById('return').value;

      if(!from || !to){ e.preventDefault(); alert('Vui lòng nhập cả điểm đi và điểm đến.'); return; }
      if(!depart){ e.preventDefault(); alert('Vui lòng chọn ngày đi.'); return; }
      if(trip==='round' && !ret){ e.preventDefault(); alert('Vui lòng chọn ngày về.'); return; }
    });

    // footer year
    document.getElementById('y').textContent = new Date().getFullYear();
  </script>
</body>
</html>
