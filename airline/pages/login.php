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
<!-- <!doctype html>
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
</html> -->
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Đăng nhập | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    /* Reset nhẹ */
    :root{
      --bg: #f6f8fb;
      --card-bg: #ffffff;
      --muted: #6b7280;
      --primary: #0b63d6;
      --accent: #0b63d6;
      --border: #e6e9ef;
      --danger: #ef4444;
      --radius: 12px;
      --max-width: 980px;
      --container-padding: 16px;
      --glass: rgba(255,255,255,0.6);
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }

    html,body{
      height:100%;
      margin:0;
      background: linear-gradient(180deg,var(--bg),#ffffff 60%);
      color:#0f172a;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    /* Container */
    .container{
      width:100%;
      max-width:var(--max-width);
      margin:0 auto;
      padding:0 var(--container-padding);
      box-sizing:border-box;
    }

    /* Topbar */
    .topbar{
      background:transparent;
      position:sticky;
      top:0;
      z-index:40;
      backdrop-filter: blur(6px);
      border-bottom:1px solid transparent;
    }
    .nav{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      padding:14px 0;
    }
    .brand{
      display:flex;
      align-items:center;
      gap:10px;
      font-weight:600;
      font-size:18px;
    }
    .logo{
      width:44px;
      height:44px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border-radius:10px;
      background: linear-gradient(135deg,#0b63d6,#2ea1ff 120%);
      color:white;
      box-shadow: 0 4px 10px rgba(11,99,214,0.12);
      font-size:20px;
    }

    nav{
      display:flex;
      gap:12px;
      align-items:center;
    }
    nav a{
      text-decoration:none;
      color:var(--muted);
      font-weight:500;
      padding:8px 10px;
      border-radius:8px;
    }
    nav a:hover{ color:var(--primary); background: rgba(11,99,214,0.06); }

    .nav-cta { display:flex; align-items:center; gap:8px; }
    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      border:0;
      background:var(--primary);
      color:#fff;
      padding:10px 14px;
      border-radius:10px;
      cursor:pointer;
      font-weight:600;
      text-decoration:none;
      font-size:14px;
    }
    .btn.outline{
      background:transparent;
      color:var(--primary);
      border:1px solid rgba(11,99,214,0.12);
      padding:8px 12px;
    }

    /* Main layout: center form vertically with some breathing room on larger screens */
    main.container{
      min-height: calc(100vh - 140px);
      display:flex;
      align-items:center;
      justify-content:center;
      padding-top:28px;
      padding-bottom:28px;
    }

    .auth-wrap{
      width:100%;
      max-width:920px;
      display:grid;
      grid-template-columns: 1fr 420px;
      gap:28px;
      align-items:center;
      padding:24px;
      box-sizing:border-box;
    }

    /* Left promo panel (ảnh/điểm nhấn) */
    .promo{
      padding:28px;
      border-radius:var(--radius);
      background: linear-gradient(180deg, rgba(11,99,214,0.06), rgba(11,99,214,0.02));
      min-height:320px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      gap:12px;
    }
    .promo h3{ margin:0; font-size:22px; }
    .promo p{ margin:0; color:var(--muted); line-height:1.5; }

    /* Card (form) */
    .card{
      background:var(--card-bg);
      border:1px solid var(--border);
      border-radius:var(--radius);
      padding:20px;
      max-width:420px;
      margin:0 auto;
      box-shadow: 0 8px 30px rgba(17,24,39,0.04);
    }

    .card h2{ margin:0 0 8px 0; font-size:20px; }
    form{ display:flex; flex-direction:column; gap:12px; margin-top:8px; }

    label{
      display:flex;
      flex-direction:column;
      gap:8px;
      font-size:13px;
      color:#0f172a;
      font-weight:600;
    }
    input[type="email"],
    input[type="password"],
    input[type="text"]{
      padding:12px 14px;
      border-radius:10px;
      border:1px solid var(--border);
      background:transparent;
      outline:none;
      font-size:15px;
      transition:box-shadow .12s, border-color .12s;
    }
    input::placeholder{ color: #94a3b8; font-weight:400; }
    input:focus{
      border-color:var(--accent);
      box-shadow: 0 6px 18px rgba(11,99,214,0.08);
    }
    .muted{ color:var(--muted); font-size:13px; margin-top:12px; }

    /* Flash messages */
    .ok, .err{
      max-width:var(--max-width);
      margin:12px auto;
      padding:10px 14px;
      border-radius:10px;
      box-sizing:border-box;
      font-weight:600;
      color:#064e3b;
      background: #ecfdf5;
      border:1px solid rgba(16,185,129,0.12);
    }
    .err{
      color:#7f1d1d;
      background:#fff1f2;
      border:1px solid rgba(239,68,68,0.12);
    }

    /* Footer */
    footer{
      padding:18px 0;
      color:var(--muted);
      font-size:13px;
      text-align:center;
      border-top:1px solid rgba(15,23,42,0.03);
      margin-top:18px;
      background:transparent;
    }

    /* Small screens: stack */
    @media (max-width:880px){
      .auth-wrap{ grid-template-columns: 1fr; padding:12px; gap:18px; }
      .promo{ order:2; }
      .card{ order:1; }
      nav{ display:none; }
      .topbar .nav{ padding:12px 0; }
    }
  </style>
</head>
<body>
<header class="topbar">
  <div class="container nav" role="navigation" aria-label="Main navigation">
    <div class="brand"><div class="logo" aria-hidden="true">✈</div><div>VNAir Ticket</div></div>
    <nav>
      <a href="./index.php#uu-dai">Ưu đãi</a>
      <a href="./index.php#quy-trinh">Quy trình</a>
      <a href="./index.php#lien-he">Liên hệ</a>
    </nav>
    <div class="nav-cta">
      <a class="btn outline" href="index.php?p=register" aria-label="Đăng ký">Đăng ký</a>
    </div>
  </div>
</header>

<main class="container">
  <?php if ($msg = flash_get('ok')): ?><div class="ok"><?=htmlspecialchars($msg)?></div><?php endif; ?>
  <?php if ($msg = flash_get('err')): ?><div class="err" style="display:block"><?=htmlspecialchars($msg)?></div><?php endif; ?>

  <div class="auth-wrap" aria-live="polite">
    <div class="promo" aria-hidden="false">
      <h3>Chào mừng đến với VNAir Ticket</h3>
      <p>Đặt vé nhanh — giá tốt — quản lý dễ dàng. Đăng nhập để xem lịch sử đặt vé, đổi thông tin hoặc hưởng ưu đãi dành cho thành viên.</p>
      <p class="muted">Bạn cần hỗ trợ? <a href="index.php#lien-he">Liên hệ</a></p>
    </div>

    <div class="card" role="form" aria-labelledby="login-title">
      <h2 id="login-title">Đăng nhập</h2>
      <form method="post" autocomplete="off" novalidate>
        <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
        <label for="email">Email
          <input id="email" type="email" name="email" placeholder="" required>
        </label>
        <label for="password">Mật khẩu
          <input id="password" type="password" name="password" placeholder="" required>
        </label>
        <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;margin-top:6px">
          <!-- <a href="index.php?p=forgot" class="muted" style="text-decoration:none">Quên mật khẩu?</a> -->
          <button class="btn" type="submit">Đăng nhập</button>
        </div>
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
