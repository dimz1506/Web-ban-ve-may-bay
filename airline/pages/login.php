<?php
// pages/login.php — Đăng nhập / Đăng xuất (điều hướng theo vai trò)
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }

// --- ĐĂNG XUẤT ---
if ((($_GET['p'] ?? '') === 'logout') || isset($_GET['logout'])) {
  auth_logout();
  flash_set('ok','Đã đăng xuất.');
  redirect('index.php?p=login');
  exit;
}

// --- XỬ LÝ POST (ĐĂNG NHẬP) ---
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_post_csrf();

  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('err', 'Email không hợp lệ.');
}


  if ($email === '' || $password === '') {
    flash_set('err','Vui lòng nhập email và mật khẩu.');
  } else {
    if (auth_login($email, $password)) {
      flash_set('ok','Đăng nhập thành công.');
      $role = $_SESSION['user']['role'] ?? 'CUSTOMER';
      if ($role === 'ADMIN') redirect('index.php?p=admin');
      elseif ($role === 'STAFF') redirect('index.php?p=staff');
      else redirect('index.php?p=customer');
      exit;
    } else {
      flash_set('err','Sai email hoặc mật khẩu.');
    }
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Đăng nhập | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <link rel="stylesheet" href="assets/login.css">
 
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
  </div>
</header>

<main class="container">
  <div class="auth-wrap">
    <div class="promo">
      <h3>Chào mừng đến với VNAir Ticket</h3>
      <p>Đặt vé nhanh — giá tốt — quản lý dễ dàng. Đăng nhập để xem lịch sử đặt vé, đổi thông tin hoặc hưởng ưu đãi dành cho thành viên.</p>
    </div>

    <div class="card">
      <h2>Đăng nhập</h2>
      <form method="post" autocomplete="off" novalidate>
        <input type="hidden" name="_csrf" value="<?=csrf_token()?>">

        <label for="email">Email
          <input id="email" type="email" name="email" placeholder="you@gmail.com"
                 value="<?=htmlspecialchars($email)?>" required>
        </label>

        <label for="password">Mật khẩu
          <input id="password" type="password" name="password" required>
        </label>

        <button class="btn" type="submit">Đăng nhập</button>

        <?php if ($msg = flash_get('err')): ?>
          <div class="form-error"><?=htmlspecialchars($msg)?></div>
        <?php elseif ($msg = flash_get('ok')): ?>
          <div class="form-ok"><?=htmlspecialchars($msg)?></div>
        <?php endif; ?>

      </form>
      <p class="muted" style="margin-top:14px">Chưa có tài khoản? <a href="index.php?p=register">Đăng ký</a></p>
    </div>
  </div>
</main>

<footer>
  <div class="container">© <span id="y"></span> VNAir Ticket</div>
</footer>
<script>document.getElementById('y').textContent = new Date().getFullYear();</script>
</body>
</html>
