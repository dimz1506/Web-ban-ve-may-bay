<?php
// pages/account_profile.php
// Yêu cầu: user đã login (CUSTOMER)
require_once dirname(__DIR__,2).'/includes/db.php';
require_once dirname(__DIR__,2).'/includes/auth.php';
require_login(['CUSTOMER']);

$pdo = db();
$user = me(); // should return current user array with id, ho_ten, sdt, email, mat_khau_ma_hoa (or adjust)
$err = '';
$ok = '';

// Name of password column in your DB; adjust if different
$pwd_col = 'mat_khau_ma_hoa';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('require_post_csrf')) require_post_csrf();

    $action = $_POST['action'] ?? 'profile';

    if ($action === 'profile') {
        // Update profile (ho_ten, sdt)
        $sdt = trim((string)($_POST['sdt'] ?? ''));
        $ho_ten = trim((string)($_POST['ho_ten'] ?? ''));

        if ($ho_ten === '') {
            $err = 'Họ tên không được để trống.';
        } elseif ($sdt !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/u', $sdt)) {
            $err = 'Số điện thoại không hợp lệ.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE nguoi_dung SET sdt = ?, ho_ten = ? WHERE id = ?");
                $stmt->execute([$sdt ?: null, $ho_ten, (int)$user['id']]);

                // update session/user
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['ho_ten'] = $ho_ten;
                    $_SESSION['user']['sdt'] = $sdt;
                }
                // refresh $user for display
                $user['ho_ten'] = $ho_ten;
                $user['sdt'] = $sdt;
                $ok = 'Cập nhật thông tin thành công.';
            } catch (Throwable $e) {
                error_log("Profile update error for user {$user['id']}: " . $e->getMessage());
                $err = 'Có lỗi xảy ra khi cập nhật. Vui lòng thử lại.';
            }
        }
    } elseif ($action === 'password') {
        // Change password
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if ($current === '' || $new === '' || $confirm === '') {
            $err = 'Vui lòng điền đầy đủ các trường mật khẩu.';
        } elseif (strlen($new) < 6) {
            $err = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($new !== $confirm) {
            $err = 'Mật khẩu mới và xác nhận không khớp.';
        } else {
            // fetch existing hash from DB
            try {
                $st = $pdo->prepare("SELECT {$pwd_col} FROM nguoi_dung WHERE id = ? LIMIT 1");
                $st->execute([(int)$user['id']]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                $hash = $row[$pwd_col] ?? null;

                if (!$hash || !password_verify($current, $hash)) {
                    $err = 'Mật khẩu hiện tại không đúng.';
                } else {
                    $newhash = password_hash($new, PASSWORD_BCRYPT);
                    $up = $pdo->prepare("UPDATE nguoi_dung SET {$pwd_col} = ? WHERE id = ?");
                    $up->execute([$newhash, (int)$user['id']]);

                    $ok = 'Đổi mật khẩu thành công.';
                }
            } catch (Throwable $e) {
                error_log("Password change error for user {$user['id']}: " . $e->getMessage());
                $err = 'Có lỗi khi thay đổi mật khẩu. Vui lòng thử lại.';
            }
        }
    } else {
        $err = 'Hành động không hợp lệ.';
    }
}

// Refresh info for display (email, sdt, ho_ten)
$stmt = $pdo->prepare("SELECT email, sdt, ho_ten FROM nguoi_dung WHERE id = ?");
$stmt->execute([(int)$user['id']]);
$info = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['email'=>'','sdt'=>'','ho_ten'=>''];

?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Hồ sơ của tôi | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    .profile-wrap { max-width:920px; margin:28px auto; }
    .profile-card { background:#fff; border:1px solid #e6e9ef; border-radius:12px; padding:18px; box-shadow:0 8px 22px rgba(2,6,23,0.04); }
    .grid-2 { display:grid; grid-template-columns: 1fr 360px; gap:18px; align-items:start; }
    .field { margin-bottom:12px; }
    label { display:block; font-weight:700; margin-bottom:6px; font-size:13px; color:#0f172a; }
    input[type="text"], input[type="email"], input[type="tel"], input[type="password"] {
      width:100%; padding:10px 12px; border-radius:8px; border:1px solid #e6e9ef; font-size:14px;
    }
    .muted { color:#6b7280; font-size:13px; }
    .actions { display:flex; gap:8px; justify-content:flex-end; margin-top:6px; }
    .btn { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; background:#0b63d6; color:#fff; border:0; cursor:pointer; font-weight:700; }
    .btn.ghost { background:transparent; color:#0b63d6; border:1px solid rgba(11,99,214,0.12); }
    .message.ok { background:rgba(22,163,74,0.08); color:#065f46; padding:10px; border-radius:8px; margin-bottom:12px; }
    .message.err { background:rgba(239,68,68,0.06); color:#7f1d1d; padding:10px; border-radius:8px; margin-bottom:12px; }
    .box { border:1px solid #eef2ff; background:#fbfdff; padding:12px; border-radius:8px; }
    @media (max-width:900px){ .grid-2{ grid-template-columns: 1fr; } .actions{justify-content:stretch} }
  </style>
</head>
<body>
 <header class="topbar">
  <div class="container nav">
    <div class="brand">
      <div class="logo">✈</div>
      <div>VNAir Ticket</div>
    </div>
  </div>
</header>


  <main class="container profile-wrap">
    <div class="profile-card">
      <?php if ($ok): ?><div class="message ok"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <?php if ($err): ?><div class="message err"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

      <div class="grid-2">
        <div>
          <h2>Hồ sơ cá nhân</h2>
          <p class="muted">Quản lý thông tin liên hệ và mật khẩu của bạn.</p>

          <!-- PROFILE FORM -->
          <form method="post" novalidate>
            <?php if (function_exists('csrf_token')): ?>
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <input type="hidden" name="action" value="profile">

            <div class="field">
              <label>Email</label>
              <input type="email" value="<?= htmlspecialchars($info['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly>
            </div>

            <div class="field">
              <label for="sdt">Số điện thoại</label>
              <input id="sdt" name="sdt" type="tel" pattern="[0-9+\-\s]{6,20}" value="<?= htmlspecialchars($info['sdt'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ví dụ: 0901234567">
            </div>

            <div class="field">
              <label for="ho_ten">Họ và tên</label>
              <input id="ho_ten" name="ho_ten" type="text" required value="<?= htmlspecialchars($info['ho_ten'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="actions" style="margin-bottom:18px">
              <!-- <a class="btn ghost" href="index.php?p=my_tickets">Vé của tôi</a> -->
              <button class="btn" type="submit">Lưu thay đổi</button>
            </div>
          </form>

          <!-- PASSWORD FORM -->
          <div style="margin-top:12px">
            <h3>Đổi mật khẩu</h3>
            <p class="muted">Nhập mật khẩu hiện tại và mật khẩu mới.</p>
            <form method="post" novalidate>
              <?php if (function_exists('csrf_token')): ?>
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
              <?php endif; ?>
              <input type="hidden" name="action" value="password">

              <div class="field">
                <label for="current_password">Mật khẩu hiện tại</label>
                <input id="current_password" name="current_password" type="password" required>
              </div>

              <div class="field">
                <label for="new_password">Mật khẩu mới</label>
                <input id="new_password" name="new_password" type="password" minlength="6" required>
                <div class="muted">Mật khẩu mới ít nhất 6 ký tự.</div>
              </div>

              <div class="field">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <input id="confirm_password" name="confirm_password" type="password" required>
              </div>

              <div class="actions">
                <a class="btn ghost" href="index.php?p=customer">Hủy</a>
                <button class="btn" type="submit">Đổi mật khẩu</button>
              </div>
            </form>
          </div>
        </div>

        <aside>
          <h4>Tài khoản</h4>
          <p class="muted"><strong>Vai trò:</strong> Khách hàng</p>

          <div style="height:12px"></div>
          <h4>Hỗ trợ</h4>
          <div class="box">
            <p class="muted" style="margin:0">Cần trợ giúp? Liên hệ <a href="index.php?p=contact">support@vnair.vn</a>.</p>
          </div>
        </aside>
      </div>
    </div>
  </main>

  <footer style="margin-top:24px">
    <div class="container muted">© <span id="y"></span> VNAir Ticket</div>
  </footer>

  <script>
    document.getElementById('y').textContent = new Date().getFullYear();

    // client-side small helper for password confirm
    (function(){
      const pwdForm = document.querySelector('form[input][name="action"][value="password"]');
      // the above selector won't work across browsers; so just add listener to password form by attribute
      const forms = document.querySelectorAll('form');
      forms.forEach(f => {
        const a = f.querySelector('input[name="action"]');
        if (a && a.value === 'password') {
          f.addEventListener('submit', function(e){
            const newp = f.querySelector('input[name="new_password"]').value;
            const conf = f.querySelector('input[name="confirm_password"]').value;
            if (newp.length < 6) {
              alert('Mật khẩu mới phải có ít nhất 6 ký tự.');
              e.preventDefault();
              return false;
            }
            if (newp !== conf) {
              alert('Mật khẩu mới và xác nhận không khớp.');
              e.preventDefault();
              return false;
            }
          });
        }
      });
    })();
  </script>
</body>
</html>
