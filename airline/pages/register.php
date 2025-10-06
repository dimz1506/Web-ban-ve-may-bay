<?php
// pages/register.php — Đăng ký tài khoản khách hàng
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
if (me()) { redirect('index.php?p=register'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_post_csrf();
  $ho_ten = trim($_POST['ho_ten'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $sdt = trim($_POST['sdt'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $confirm  = (string)($_POST['confirm']  ?? '');

  if ($ho_ten==='') $errors[] = 'Vui lòng nhập họ tên.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
  if (strlen($password) < 6) $errors[] = 'Mật khẩu tối thiểu 6 ký tự.';
  if ($password !== $confirm) $errors[] = 'Mật khẩu nhập lại không khớp.';

  // Kiểm tra trùng email
  if (!$errors) {
    $st = db()->prepare("SELECT 1 FROM nguoi_dung WHERE email=? LIMIT 1");
    $st->execute([$email]);
    if ($st->fetchColumn()) $errors[] = 'Email đã tồn tại.';
  }

  if (!$errors) {
    // Lấy role CUSTOMER, nếu chưa có thì tạo
    $roleId = db()->prepare("SELECT id FROM vai_tro WHERE ma='CUSTOMER' LIMIT 1");
    $roleId->execute();
    $role_id = $roleId->fetchColumn();
    if (!$role_id) {
      db()->prepare("INSERT INTO vai_tro(ma,ten) VALUES ('CUSTOMER','Khach hang')")->execute();
      $role_id = (int)db()->lastInsertId();
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = db()->prepare("INSERT INTO nguoi_dung(email,sdt,mat_khau_ma_hoa,ho_ten,trang_thai,vai_tro_id) VALUES (?,?,?,?, 'HOAT_DONG', ?)");
    $ins->execute([$email, ($sdt?:null), $hash, $ho_ten, $role_id]);

    // Tự động đăng nhập sau khi đăng ký
    auth_login($email, $password);
    flash_set('ok','Tạo tài khoản thành công.');
    redirect('index.php?p=customer');
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Đăng ký | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
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
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }
    html,body{height:100%;margin:0;background: linear-gradient(180deg,var(--bg),#ffffff 60%);color:#0f172a;}
    .container{width:100%;max-width:var(--max-width);margin:0 auto;padding:0 var(--container-padding);box-sizing:border-box;}

    /* Topbar */
    .topbar{background:transparent;position:sticky;top:0;z-index:40;backdrop-filter: blur(6px);}
    .nav{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 0;}
    .brand{display:flex;align-items:center;gap:10px;font-weight:600;font-size:18px;}
    .logo{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;background: linear-gradient(135deg,#0b63d6,#2ea1ff);color:white;box-shadow: 0 4px 10px rgba(11,99,214,0.12);font-size:20px;}
    nav{display:flex;gap:12px;align-items:center;}
    nav a{ text-decoration:none;color:var(--muted);font-weight:500;padding:8px 10px;border-radius:8px;}
    nav a:hover{ color:var(--primary); background: rgba(11,99,214,0.06); }
    .nav-cta{display:flex;align-items:center;gap:8px;}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;background:var(--primary);color:#fff;padding:10px 14px;border-radius:10px;cursor:pointer;font-weight:600;text-decoration:none;font-size:14px;}
    .btn.outline{background:transparent;color:var(--primary);border:1px solid rgba(11,99,214,0.12);padding:8px 12px;}

    /* Main */
    main.container{min-height: calc(100vh - 140px);display:flex;align-items:center;justify-content:center;padding-top:28px;padding-bottom:28px;}
    .auth-wrap{width:100%;max-width:920px;display:grid;grid-template-columns: 1fr 520px;gap:28px;align-items:center;padding:24px;box-sizing:border-box;}
    .promo{padding:28px;border-radius:var(--radius);background: linear-gradient(180deg, rgba(11,99,214,0.06), rgba(11,99,214,0.02));min-height:360px;display:flex;flex-direction:column;justify-content:center;gap:12px;}
    .promo h3{margin:0;font-size:22px;}
    .promo p{margin:0;color:var(--muted);line-height:1.5;}

    /* Card */
    .card{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:20px;max-width:520px;margin:0 auto;box-shadow: 0 8px 30px rgba(17,24,39,0.04);}
    .card h2{margin:0 0 8px 0;font-size:20px;}
    form{display:flex;flex-direction:column;gap:12px;margin-top:8px;}
    label{display:flex;flex-direction:column;gap:8px;font-size:13px;color:#0f172a;font-weight:600;}
    input[type="email"], input[type="password"], input[type="text"], input:not([type]){padding:12px 14px;border-radius:10px;border:1px solid var(--border);background:transparent;outline:none;font-size:15px;transition:box-shadow .12s, border-color .12s;}
    input::placeholder{ color: #94a3b8; font-weight:400; }
    input:focus{ border-color:var(--accent); box-shadow: 0 6px 18px rgba(11,99,214,0.08); }

    .muted{ color:var(--muted); font-size:13px; margin-top:12px; }

    /* Flash/errors */
    .ok, .err{max-width:var(--max-width);margin:12px auto;padding:10px 14px;border-radius:10px;box-sizing:border-box;font-weight:600;}
    .ok{color:#064e3b;background:#ecfdf5;border:1px solid rgba(16,185,129,0.12);}
    .err{color:#7f1d1d;background:#fff1f2;border:1px solid rgba(239,68,68,0.12);}

    /* small helper row */
    .row{display:flex;gap:10px;align-items:center;}
    .flex-1{flex:1;}

    footer{padding:18px 0;color:var(--muted);font-size:13px;text-align:center;border-top:1px solid rgba(15,23,42,0.03);margin-top:18px;background:transparent;}

    @media (max-width:880px){
      .auth-wrap{grid-template-columns: 1fr; padding:12px; gap:18px;}
      .promo{order:2;}
      .card{order:1;}
      nav{display:none;}
      .topbar .nav{padding:12px 0;}
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
      <a class="btn outline" href="index.php?p=login" aria-label="Đăng nhập">Đăng nhập</a>
    </div>
  </div>
</header>

<main class="container">
  <?php if ($errors): ?>
    <div class="err" style="display:block">
      <?php foreach ($errors as $e): ?><div><?=htmlspecialchars($e)?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="auth-wrap" aria-live="polite">
    <div class="promo" aria-hidden="false">
      <h3>Tạo tài khoản mới</h3>
      <p>Đăng ký nhanh để đặt vé, quản lý lịch sử và nhận ưu đãi thành viên. Chỉ mất vài bước — thông tin của bạn được bảo mật.</p>
      <p class="muted">Bạn đã có tài khoản? <a href="index.php?p=login">Đăng nhập</a></p>
    </div>

    <div class="card" role="form" aria-labelledby="reg-title">
      <h2 id="reg-title">Tạo tài khoản</h2>
      <form method="post" autocomplete="off" novalidate>
        <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
        <label for="ho_ten">Họ tên
          <input id="ho_ten" name="ho_ten" required value="<?=htmlspecialchars($_POST['ho_ten'] ?? '')?>" placeholder="">
        </label>

        <label for="email">Email
          <input id="email" type="email" name="email" required placeholder="" value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
        </label>

        <label for="sdt">Số điện thoại (tuỳ chọn)
          <input id="sdt" name="sdt" value="<?=htmlspecialchars($_POST['sdt'] ?? '')?>" placeholder="">
        </label>

        <div class="row">
          <div class="flex-1">
            <label for="password">Mật khẩu
              <input id="password" type="password" name="password" required placeholder="">
            </label>
          </div>
          <div class="flex-1">
            <label for="confirm">Nhập lại mật khẩu
              <input id="confirm" type="password" name="confirm" required placeholder="Nhập lại mật khẩu">
            </label>
          </div>
        </div>

        <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;margin-top:6px">
          <!--<a href="index.php?p=privacy" class="muted" style="text-decoration:none">Chính sách bảo mật</a>-->
          <button class="btn" type="submit">Đăng ký</button>
        </div>
      </form>

      <p class="muted" style="margin-top:14px">Hoặc <a href="index.php?p=login">Đăng nhập</a> nếu bạn đã có tài khoản.</p>
    </div>
  </div>
</main>

<footer>
  <div class="container">© <span id="y"></span> VNAir Ticket</div>
</footer>
<script>document.getElementById('y').textContent = new Date().getFullYear();</script>
</body>
</html>
