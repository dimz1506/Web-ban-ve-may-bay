<?php
// pages/register.php — Đăng ký tài khoản khách hàng
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
if (me()) { redirect('index.php'); }

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
  <style>.card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px;max-width:520px;margin:24px auto}</style>
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
      <a class="btn outline" href="index.php?p=login">Đăng nhập</a>
    </div>
  </div>
</header>

<main class="container">
  <?php if ($errors): ?>
    <div class="err" style="display:block">
      <?php foreach ($errors as $e): ?><div><?=htmlspecialchars($e)?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <h2>Tạo tài khoản</h2>
    <form method="post" autocomplete="off">
      <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
      <label>Họ tên
        <input name="ho_ten" required value="<?=htmlspecialchars($_POST['ho_ten'] ?? '')?>">
      </label>
      <label>Email
        <input type="email" name="email" required placeholder="you@example.com" value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
      </label>
      <label>Số điện thoại (tuỳ chọn)
        <input name="sdt" value="<?=htmlspecialchars($_POST['sdt'] ?? '')?>">
      </label>
      <label>Mật khẩu
        <input type="password" name="password" required>
      </label>
      <label>Nhập lại mật khẩu
        <input type="password" name="confirm" required>
      </label>
      <button class="btn" type="submit">Đăng ký</button>
    </form>
    <p class="muted">Đã có tài khoản? <a href="index.php?p=login">Đăng nhập</a></p>
  </div>
</main>

<footer>
  <div class="container">© <span id="y"></span> VNAir Ticket</div>
</footer>
<script>document.getElementById('y').textContent = new Date().getFullYear();</script>
</body>
</html>