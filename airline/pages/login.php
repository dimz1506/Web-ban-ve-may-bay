<?php
// pages/login.php — Đăng nhập / Đăng xuất (điều hướng theo vai trò)
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }

// Đăng xuất
if ((($_GET['p'] ?? '') === 'logout') || isset($_GET['logout'])) {
  auth_logout();
  flash_set('ok','Đã đăng xuất.');
  redirect('index.php?p=login');
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_post_csrf();
  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  if ($email === '' || $password === '') {
    flash_set('err','Vui lòng nhập email và mật khẩu.');
  } else if (auth_login($email, $password)) {
    flash_set('ok','Đăng nhập thành công.');
    $role = $_SESSION['user']['role'] ?? 'CUSTOMER';
    if ($role === 'ADMIN') {
      redirect('index.php?p=admin');
    } elseif ($role === 'STAFF') {
      redirect('index.php?p=staff');
    } else {
      redirect('index.php?p=customer');
    }
  } else {
    flash_set('err','Sai email hoặc mật khẩu, hoặc tài khoản bị khóa.');
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
  <style>.card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px;max-width:420px;margin:24px auto}</style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    <nav>
      <a href="./index.php#uu-dai">Ưu đãi</a>
      <a href="./index.php#quy-trinh">Quy trình</a>
      <a href="./index.php#lien-he">Liên hệ</a>
    </nav>
    <div class="nav-cta">
      <a class="btn outline" href="index.php?p=register">Đăng ký</a>
    </div>
  </div>
</header>

<main class="container">
  <?php if ($msg = flash_get('ok')): ?><div class="ok"><?=htmlspecialchars($msg)?></div><?php endif; ?>
  <?php if ($msg = flash_get('err')): ?><div class="err" style="display:block"><?=htmlspecialchars($msg)?></div><?php endif; ?>

  <div class="card">
    <h2>Đăng nhập</h2>
    <form method="post" autocomplete="off">
      <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
      <label>Email
        <input type="email" name="email" placeholder="you@example.com" required>
      </label>
      <label>Mật khẩu
        <input type="password" name="password" required>
      </label>
      <button class="btn" type="submit">Đăng nhập</button>
    </form>
    <p class="muted">Chưa có tài khoản? <a href="index.php?p=register">Đăng ký</a></p>
  </div>
</main>

<footer>
  <div class="container">© <span id="y"></span> VNAir Ticket</div>
</footer>
<script>document.getElementById('y').textContent = new Date().getFullYear();</script>
</body>
</html>
