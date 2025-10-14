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
  <link rel="stylesheet" href="assets/register.css">
<body>
<header class="topbar">
  <div class="container nav" role="navigation" aria-label="Main navigation">
    <div class="brand"><div class="logo" aria-hidden="true">✈</div><div>VNAir Ticket</div></div>
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
