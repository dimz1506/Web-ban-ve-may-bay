<?php
// session_start();
// if (!isset($_SESSION['user_id'])) {
//   header('Location: index.php?p=login');
//   exit;
// }
if (!function_exists('db')) { 
    require_once dirname(__DIR__,2).'/config.php'; 
}
if (session_status() === PHP_SESSION_NONE) session_start();

// 🧭 Nhận dữ liệu form
$from    = strtoupper(trim($_GET['from'] ?? ''));
$to      = strtoupper(trim($_GET['to'] ?? ''));
$depart  = trim($_GET['depart'] ?? '');
$return  = trim($_GET['ret'] ?? '');
$cab     = trim($_GET['cabin'] ?? 'ECON');
$adults  = (int)($_GET['adults'] ?? 1);

if (empty($from) || empty($to) || empty($depart) || empty($return)) {
    die("<p>Thiếu thông tin hành trình. Vui lòng quay lại và nhập đầy đủ.</p>");
}

// 🪑 Lấy ID hạng ghế
$hgId = db()->prepare("SELECT id FROM hang_ghe WHERE ma=? LIMIT 1");
$hgId->execute([$cab]);
$hang_ghe_id = $hgId->fetchColumn();

if (!$hang_ghe_id) {
    die("<p>Không tìm thấy hạng ghế hợp lệ.</p>");
}

// ✈️ Lấy danh sách chuyến đi
$st = db()->prepare("
   SELECT cb.id AS chuyen_id, cb.so_hieu, cb.gio_di, cb.gio_den,
          s1.ten AS ten_di, s2.ten AS ten_den,
          cgh.gia_co_ban, cgh.so_ghe_con
   FROM tuyen_bay tb
   JOIN san_bay s1 ON s1.ma=tb.di
   JOIN san_bay s2 ON s2.ma=tb.den
   JOIN chuyen_bay cb ON cb.tuyen_bay_id=tb.id
   JOIN chuyen_bay_gia_hang cgh 
       ON cgh.chuyen_bay_id=cb.id AND cgh.hang_ghe_id=?
   WHERE tb.di=? AND tb.den=? 
     AND DATE(cb.gio_di)=? 
     AND cb.trang_thai='LEN_KE_HOACH'
   ORDER BY cb.gio_di ASC
");
$st->execute([$hang_ghe_id, $from, $to, $depart]);
$rows_go = $st->fetchAll();

// ✈️ Lấy danh sách chuyến về
$st2 = db()->prepare("
   SELECT cb.id AS chuyen_id, cb.so_hieu, cb.gio_di, cb.gio_den,
          s1.ten AS ten_di, s2.ten AS ten_den,
          cgh.gia_co_ban, cgh.so_ghe_con
   FROM tuyen_bay tb
   JOIN san_bay s1 ON s1.ma=tb.di
   JOIN san_bay s2 ON s2.ma=tb.den
   JOIN chuyen_bay cb ON cb.tuyen_bay_id=tb.id
   JOIN chuyen_bay_gia_hang cgh 
       ON cgh.chuyen_bay_id=cb.id AND cgh.hang_ghe_id=?
   WHERE tb.di=? AND tb.den=? 
     AND DATE(cb.gio_di)=? 
     AND cb.trang_thai='LEN_KE_HOACH'
   ORDER BY cb.gio_di ASC
");
$st2->execute([$hang_ghe_id, $to, $from, $return]);
$rows_back = $st2->fetchAll();

function fmtDate($d){ return date('d/m/Y', strtotime($d)); }
function fmtTime($d){ return date('H:i', strtotime($d)); }

// 💎 Tính chuyến giá tốt nhất
$best_go = null;
$best_back = null;
$min_total = PHP_INT_MAX;
if ($rows_go && $rows_back) {
    foreach ($rows_go as $g) {
        foreach ($rows_back as $b) {
            $total = $g['gia_co_ban'] + $b['gia_co_ban'];
            if ($total < $min_total) {
                $min_total = $total;
                $best_go = $g;
                $best_back = $b;
            }
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Kết quả tìm chuyến khứ hồi</title>
<link rel="stylesheet" href="assets/home.css">
<style>
body {background:#f1f5f9;font-family:'Segoe UI',sans-serif;margin:0;}
main.container {background:#fff;border-radius:16px;padding:2rem;margin:2rem auto;max-width:1000px;box-shadow:0 4px 10px rgba(0,0,0,.08);}
.section-title{text-align:center;margin-top:2rem;border-bottom:2px solid #1e40af;padding-bottom:4px;color:#1e40af;}
.card{border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin:12px 0;display:flex;justify-content:space-between;align-items:center;transition:.2s;}
.card:hover{background:#f8fafc;transform:translateY(-2px);}
.price{font-weight:bold;color:#1e40af;}
.seat-info{font-size:13px;color:#475569;margin-top:4px;}
.btn{background:#1e40af;color:#fff;padding:.4rem 1rem;border-radius:8px;text-decoration:none;}
.btn:hover{background:#1e3a8a;}
.selected-card{border:2px solid #2563eb;background:#eff6ff;}
.sort-box {text-align:center;margin-bottom:1rem;}
.sort-box select {
  border-radius:8px;
  border:1px solid #ccc;
  padding:6px 10px;
  font-size:14px;
}
.best-deal {
  border:2px solid #2563eb;
  background:#eff6ff;
  border-radius:12px;
  padding:1rem 1.2rem;
  margin:1.5rem 0;
  box-shadow:0 2px 8px rgba(0,0,0,0.05);
}
.best-deal h3 {color:#1e40af;margin-bottom:.8rem;}
.best-deal strong {color:#111;}
.note {font-size:13px;color:#475569;margin-top:6px;}
</style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    <nav><a href="index.php#uu-dai">Ưu đãi</a><a href="index.php#quy-trinh">Quy trình</a><a href="index.php#lien-he">Liên hệ</a></nav>
  </div>
</header>

<main class="container">
  <h2 style="text-align:center;">Tìm chuyến khứ hồi</h2>
  <p style="text-align:center;">Hành trình: <strong><?=$from?> → <?=$to?></strong> và <strong><?=$to?> → <?=$from?></strong><br>
  Ngày đi: <?=fmtDate($depart)?> | Ngày về: <?=fmtDate($return)?> | Hạng: <?=$cab?></p>

  <div class="sort-box">
    <label>Sắp xếp theo: </label>
    <select id="sortSelect">
      <option value="asc">Giá tăng dần</option>
      <option value="desc">Giá giảm dần</option>
    </select>
  </div>

  <!-- 💎 Gợi ý giá tốt nhất -->
  <?php if ($best_go && $best_back): 
    $discount = 0.1;
    $discounted = $min_total * (1 - $discount);
  ?>
  <div class="best-deal">
    <h3>💎 Gợi ý khứ hồi giá tốt nhất</h3>
    <div><strong>Chiều đi:</strong> <?=$best_go['so_hieu']?> (<?=fmtTime($best_go['gio_di'])?> → <?=fmtTime($best_go['gio_den'])?>)
      <span style="float:right;color:#15803d;font-weight:600;"><?=number_format($best_go['gia_co_ban'])?> VND</span>
    </div>
    <div><strong>Chiều về:</strong> <?=$best_back['so_hieu']?> (<?=fmtTime($best_back['gio_di'])?> → <?=fmtTime($best_back['gio_den'])?>)
      <span style="float:right;color:#15803d;font-weight:600;"><?=number_format($best_back['gia_co_ban'])?> VND</span>
    </div>
    <div style="margin-top:.6rem;font-weight:600;">
      Giá gốc: <?=number_format($min_total)?> VND | 
      Ưu đãi khứ hồi: <span style="color:#dc2626;">-10%</span> →
      <span style="color:#1e40af;"><?=number_format($discounted)?> VND</span>
    </div>
    <div class="note">💡 Giá khứ hồi đã bao gồm ưu đãi 10% so với tổng giá 1 chiều.</div>
    <form method="get" action="index.php">
      <input type="hidden" name="p" value="select_seat">
      <input type="hidden" name="flight_id" value="<?=$best_go['chuyen_id']?>">
      <input type="hidden" name="return_id" value="<?=$best_back['chuyen_id']?>">
      <input type="hidden" name="cabin" value="<?=htmlspecialchars($cab)?>">
      <button class="btn" style="margin-top:1rem;">Chọn chuyến này</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- CHIỀU ĐI -->
  <h3 class="section-title">✈ Chiều đi (<?=$from?> → <?=$to?>)</h3>
  <div id="go-list">
  <?php foreach($rows_go as $r): ?>
    <div class="card go" data-id="<?=$r['chuyen_id']?>" data-price="<?=$r['gia_co_ban']?>">
      <div>
        <strong><?=$r['so_hieu']?></strong><br>
        Đi: <?=$r['ten_di']?> (<?=fmtTime($r['gio_di'])?>)<br>
        Đến: <?=$r['ten_den']?> (<?=fmtTime($r['gio_den'])?>)
        <div class="seat-info">Còn lại: <?=$r['so_ghe_con']?> ghế</div>
      </div>
      <div class="price"><?=number_format($r['gia_co_ban'])?> VND</div>
    </div>
  <?php endforeach; ?>
  </div>

  <!-- CHIỀU VỀ -->
  <h3 class="section-title">↩ Chiều về (<?=$to?> → <?=$from?>)</h3>
  <div id="back-list">
  <?php foreach($rows_back as $r): ?>
    <div class="card back" data-id="<?=$r['chuyen_id']?>" data-price="<?=$r['gia_co_ban']?>">
      <div>
        <strong><?=$r['so_hieu']?></strong><br>
        Đi: <?=$r['ten_di']?> (<?=fmtTime($r['gio_di'])?>)<br>
        Đến: <?=$r['ten_den']?> (<?=fmtTime($r['gio_den'])?>)
        <div class="seat-info">Còn lại: <?=$r['so_ghe_con']?> ghế</div>
      </div>
      <div class="price"><?=number_format($r['gia_co_ban'])?> VND</div>
    </div>
  <?php endforeach; ?>
  </div>

  <div style="text-align:center;margin-top:2rem;">
    <button id="continueBtn" class="btn" disabled>Tiếp tục chọn ghế</button><br>
    <a href="index.php" class="btn outline" style="margin-top:1rem;">← Quay lại</a>
  </div>
</main>

<script>
let selectedGo = null, selectedBack = null;

document.querySelectorAll('.card.go').forEach(c => {
  c.onclick = () => {
    document.querySelectorAll('.card.go').forEach(x => x.classList.remove('selected-card'));
    c.classList.add('selected-card');
    selectedGo = c.dataset.id;
    checkContinue();
  };
});
document.querySelectorAll('.card.back').forEach(c => {
  c.onclick = () => {
    document.querySelectorAll('.card.back').forEach(x => x.classList.remove('selected-card'));
    c.classList.add('selected-card');
    selectedBack = c.dataset.id;
    checkContinue();
  };
});

function checkContinue(){
  const btn = document.getElementById('continueBtn');
  btn.disabled = !(selectedGo && selectedBack);
  if(selectedGo && selectedBack){
    btn.onclick = () => {
      const url = `index.php?p=select_seat&flight_id=${selectedGo}&return_id=${selectedBack}&cabin=<?=$cab?>`;
      window.location.href = url;
    }
  }
}

// --- sắp xếp theo giá ---
document.getElementById('sortSelect').addEventListener('change', function() {
  const order = this.value;
  sortList('go-list', order);
  sortList('back-list', order);
});

function sortList(id, order){
  const container = document.getElementById(id);
  const items = Array.from(container.children);
  items.sort((a,b) => {
    const pa = parseFloat(a.dataset.price);
    const pb = parseFloat(b.dataset.price);
    return order === 'asc' ? pa - pb : pb - pa;
  });
  container.innerHTML = '';
  items.forEach(i => container.appendChild(i));
}
</script>
<footer class="footer">
  <div class="container">
    © <?=date('Y')?> VNAir Ticket
  </div>
</footer>
</body>
</html>
