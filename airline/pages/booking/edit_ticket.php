<?php
// pages/booking/edit_ticket.php
if (!function_exists('db')) { 
    require_once dirname(__DIR__,2).'/config.php'; 
}
if (session_status() === PHP_SESSION_NONE) session_start();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "Không tìm thấy vé cần sửa.";
    exit;
}

// Lấy thông tin vé + hành khách + booking
$st = db()->prepare("
    SELECT v.id as ve_id, v.so_ghe, v.so_ve, v.dat_cho_id,
           h.id as hk_id, h.ho_ten, h.gioi_tinh, h.ngay_sinh, h.so_giay_to,
           d.pnr, d.created_at
    FROM ve v
    JOIN hanh_khach h ON v.hanh_khach_id = h.id
    JOIN dat_cho d ON v.dat_cho_id = d.id
    WHERE v.id=?
");
$st->execute([$id]);
$ticket = $st->fetch();

if (!$ticket) {
    echo "Không tìm thấy vé này.";
    exit;
}

// Kiểm tra thời gian đặt vé
$dat_cho_time = strtotime($ticket['created_at'] ?? '');
$now = time();
$hours_passed = ($now - $dat_cho_time)/3600;
$allow_edit = ($hours_passed <= 48);

// Khi submit form
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!$allow_edit) {
        $_SESSION['flash'] = "Vé đã quá hạn 48h, không thể sửa thông tin. Vui lòng liên hệ tổng đài.";
        header("Location: index.php?p=my_bookings&pnr=" . urlencode($ticket['pnr']));
        exit;
    }

    $ho_ten   = trim($_POST['ho_ten']);
    $gioi_tinh= $_POST['gioi_tinh'];
    $ngay_sinh= $_POST['ngay_sinh'];
    $so_giay_to= trim($_POST['so_giay_to']);

    $st2 = db()->prepare("
        UPDATE hanh_khach 
        SET ho_ten=?, gioi_tinh=?, ngay_sinh=?, so_giay_to=? 
        WHERE id=?
    ");
    $st2->execute([$ho_ten, $gioi_tinh, $ngay_sinh, $so_giay_to, $ticket['hk_id']]);

    $_SESSION['flash'] = "Cập nhật thông tin hành khách thành công!";
    header("Location: index.php?p=my_bookings&pnr=" . urlencode($ticket['pnr']));
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>Sửa thông tin vé</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    body {background:#f9fafb;font-family:sans-serif;}
    .edit-box {
      max-width: 400px;
      margin: 40px auto;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 24px;
      box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    }
    .edit-box h2 {
      text-align: center;
      margin-bottom: 16px;
      color: #1e40af;
      font-size: 18px;
    }
    .form-group {margin-bottom:12px;}
    .form-group label {
      display:block;
      font-weight:600;
      margin-bottom:4px;
      font-size:13px;
      color:#374151;
    }
    .form-group input,
    .form-group select {
      width:100%;
      padding:7px 10px;
      border:1px solid #cbd5e1;
      border-radius:6px;
      font-size:13px;
    }
    .form-group input:focus,
    .form-group select:focus {
      border-color:#1e40af;
      outline:none;
      box-shadow:0 0 0 1px rgba(30,64,175,0.2);
    }
    .btn-row {
      margin-top:15px;
      display:flex;
      justify-content:center;
      gap:10px;
    }
    .btn {
      background:#1e40af;
      color:#fff;
      padding:.4rem 1rem;
      border-radius:6px;
      border:none;
      text-decoration:none;
      cursor:pointer;
      font-size:13px;
      font-weight:500;
    }
    .btn:hover {background:#1e3a8a;}
    .btn.outline {
      background:transparent;
      border:1px solid #1e40af;
      color:#1e40af;
    }
    .btn.outline:hover {background:#e0e7ff;}
    .alert {
      padding:10px;
      margin-bottom:12px;
      border-radius:6px;
      font-size:13px;
    }
    .alert.error {background:#fee2e2;color:#b91c1c;}
  </style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    <nav>
      <a href="index.php">Trang chủ</a>
      <a href="index.php?p=my_tickets">Vé của tôi</a>
    </nav>
  </div>
</header>

<main class="container">
  <div class="edit-box">
    <h2>✏ Chỉnh sửa thông tin hành khách</h2>

    <?php if(!$allow_edit): ?>
      <div class="alert error">
        Vé này đã quá hạn <strong>48h</strong> kể từ khi đặt (<?=date('d/m/Y H:i', $dat_cho_time)?>).<br>
        Bạn không thể sửa thông tin, vui lòng liên hệ tổng đài hỗ trợ.
      </div>
      <div class="btn-row">
        <a href="index.php?p=my_bookings&pnr=<?=$ticket['pnr']?>" class="btn outline">← Quay lại</a>
      </div>
    <?php else: ?>
      <form method="post">
        <div class="form-group">
          <label>Họ tên</label>
          <input type="text" name="ho_ten" value="<?=htmlspecialchars($ticket['ho_ten'])?>" required>
        </div>

        <div class="form-group">
          <label>Ngày sinh</label>
          <input type="date" name="ngay_sinh" value="<?=htmlspecialchars($ticket['ngay_sinh'])?>" required>
        </div>

        <div class="form-group">
          <label>Giới tính</label>
          <select name="gioi_tinh">
            <option value="M" <?=$ticket['gioi_tinh']==='M'?'selected':''?>>Nam</option>
            <option value="F" <?=$ticket['gioi_tinh']==='F'?'selected':''?>>Nữ</option>
            <option value="X" <?=$ticket['gioi_tinh']==='X'?'selected':''?>>Khác</option>
          </select>
        </div>

        <div class="form-group">
          <label>CMND/Hộ chiếu</label>
          <input type="text" name="so_giay_to" value="<?=htmlspecialchars($ticket['so_giay_to'])?>" required>
        </div>

        <div class="btn-row">
          <button type="submit" class="btn">💾 Lưu</button>
          <a href="index.php?p=my_bookings&pnr=<?=$ticket['pnr']?>" class="btn outline">← Quay lại</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</main>

<footer class="footer">
  <div class="container">&copy; <?=date('Y')?> VNAir Ticket</div>
</footer>
</body>
</html>
