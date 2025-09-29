<?php
// pages/users.php — Quản lý tài khoản người dùng (ADMIN)
// Tính năng: liệt kê, tìm kiếm, lọc theo vai trò & trạng thái, tạo/sửa, khoá/mở, reset mật khẩu, xoá (bảo vệ admin cuối cùng)
if (!function_exists('db')) {
  require_once dirname(__DIR__) . '/config.php';
}
require_login(['ADMIN']);
$pdo = db();

function flash_ok($m)
{
  flash_set('ok', $m);
}
function flash_err($m)
{
  flash_set('err', $m);
}
function make_pwd(int $len = 10): string
{
  return substr(bin2hex(random_bytes(16)), 0, $len);
}

// Lấy danh sách vai trò
$roles = $pdo->query("SELECT id, ma, ten FROM vai_tro ORDER BY id")->fetchAll();
$roleMap = [];
foreach ($roles as $r) {
  $roleMap[(int)$r['id']] = $r;
}

// Helper: đếm số ADMIN đang hoạt động (có thể trừ đi 1 id nếu cung cấp)
function count_active_admins(?int $excludeId = null): int
{
  $sql = "SELECT COUNT(*) FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id WHERE r.ma='ADMIN' AND u.trang_thai='HOAT_DONG'";
  $args = [];
  if ($excludeId) {
    $sql .= " AND u.id<>?";
    $args[] = $excludeId;
  }
  $st = db()->prepare($sql);
  $st->execute($args);
  return (int)$st->fetchColumn();
}

// ====== Xử lý POST (tạo/sửa/khoá/reset/xoá) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_post_csrf();
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create') {
      $email = trim($_POST['email'] ?? '');
      $name  = trim($_POST['ho_ten'] ?? '');
      $sdt   = trim($_POST['sdt'] ?? '');
      $pwd   = (string)($_POST['password'] ?? '');
      $rid   = (int)($_POST['vai_tro_id'] ?? 0);
      $status = ($_POST['trang_thai'] ?? 'HOAT_DONG');

      if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email không hợp lệ');
      if (strlen($pwd) < 6) throw new RuntimeException('Mật khẩu tối thiểu 6 ký tự');
      if (!$rid || !isset($roleMap[$rid])) throw new RuntimeException('Vai trò không hợp lệ');
      if (!in_array($status, ['HOAT_DONG', 'KHOA'], true)) $status = 'HOAT_DONG';

      $chk = $pdo->prepare('SELECT 1 FROM nguoi_dung WHERE email=? LIMIT 1');
      $chk->execute([$email]);
      if ($chk->fetchColumn()) throw new RuntimeException('Email đã tồn tại');

      $hash = password_hash($pwd, PASSWORD_BCRYPT);
      $ins = $pdo->prepare("INSERT INTO nguoi_dung(email,sdt,mat_khau_ma_hoa,ho_ten,trang_thai,vai_tro_id) VALUES (?,?,?,?,?,?)");
      $ins->execute([$email, ($sdt ?: null), $hash, $name, $status, $rid]);
      flash_ok('Đã tạo tài khoản mới.');
    } elseif ($action === 'update') {
      $id    = (int)($_POST['id'] ?? 0);
      $email = trim($_POST['email'] ?? '');
      $name  = trim($_POST['ho_ten'] ?? '');
      $sdt   = trim($_POST['sdt'] ?? '');
      $rid   = (int)($_POST['vai_tro_id'] ?? 0);
      $status = ($_POST['trang_thai'] ?? 'HOAT_DONG');
      $newpw = (string)($_POST['new_password'] ?? '');

      if ($id <= 0) throw new RuntimeException('Thiếu ID tài khoản');
      if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email không hợp lệ');
      if (!$rid || !isset($roleMap[$rid])) throw new RuntimeException('Vai trò không hợp lệ');
      if (!in_array($status, ['HOAT_DONG', 'KHOA'], true)) $status = 'HOAT_DONG';

      // lấy tài khoản hiện tại
      $cur = $pdo->prepare('SELECT * FROM nguoi_dung WHERE id=?');
      $cur->execute([$id]);
      $cur = $cur->fetch();
      if (!$cur) throw new RuntimeException('Không tìm thấy tài khoản');

      // kiểm tra email trùng
      $chke = $pdo->prepare('SELECT 1 FROM nguoi_dung WHERE email=? AND id<>? LIMIT 1');
      $chke->execute([$email, $id]);
      if ($chke->fetchColumn()) throw new RuntimeException('Email đã tồn tại');

      // bảo vệ admin cuối cùng
      $isCurAdmin = false;
      $newIsAdmin = false;
      if ($cur['vai_tro_id'] && isset($roleMap[(int)$cur['vai_tro_id']]) && $roleMap[(int)$cur['vai_tro_id']]['ma'] === 'ADMIN') $isCurAdmin = true;
      if ($rid && isset($roleMap[$rid]) && $roleMap[$rid]['ma'] === 'ADMIN') $newIsAdmin = true;

      if ($isCurAdmin) {
        // nếu đổi vai trò khỏi ADMIN hoặc khoá tài khoản admin
        if (!$newIsAdmin || $status === 'KHOA') {
          $remain = count_active_admins($id);
          if ($remain <= 0) throw new RuntimeException('Không thể thay đổi. Cần ít nhất 1 ADMIN hoạt động.');
        }
      }

      if ($newpw !== '' && strlen($newpw) < 6) throw new RuntimeException('Mật khẩu mới tối thiểu 6 ký tự');

      if ($newpw !== '') {
        $hash = password_hash($newpw, PASSWORD_BCRYPT);
        $sql = 'UPDATE nguoi_dung SET email=?, sdt=?, mat_khau_ma_hoa=?, ho_ten=?, trang_thai=?, vai_tro_id=? WHERE id=?';
        $pdo->prepare($sql)->execute([$email, ($sdt ?: null), $hash, $name, $status, $rid, $id]);
      } else {
        $sql = 'UPDATE nguoi_dung SET email=?, sdt=?, ho_ten=?, trang_thai=?, vai_tro_id=? WHERE id=?';
        $pdo->prepare($sql)->execute([$email, ($sdt ?: null), $name, $status, $rid, $id]);
      }
      flash_ok('Đã cập nhật tài khoản.');
    } elseif ($action === 'toggle') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Thiếu ID tài khoản');
      if ($id === (int)me()['id']) throw new RuntimeException('Không thể tự khoá tài khoản của chính bạn.');
      $u = $pdo->prepare('SELECT u.*, r.ma AS role_ma FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id WHERE u.id=?');
      $u->execute([$id]);
      $u = $u->fetch();
      if (!$u) throw new RuntimeException('Không tìm thấy tài khoản');
      $new = ($u['trang_thai'] === 'HOAT_DONG') ? 'KHOA' : 'HOAT_DONG';
      if ($u['role_ma'] === 'ADMIN' && $new === 'KHOA') {
        $remain = count_active_admins($id);
        if ($remain <= 0) throw new RuntimeException('Không thể khoá ADMIN cuối cùng.');
      }
      $pdo->prepare('UPDATE nguoi_dung SET trang_thai=? WHERE id=?')->execute([$new, $id]);
      flash_ok(($new === 'KHOA' ? 'Đã khoá' : 'Đã mở khoá') . ' tài khoản #' . $id);
    } elseif ($action === 'resetpw') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Thiếu ID tài khoản');
      $temp = make_pwd(12);
      $hash = password_hash($temp, PASSWORD_BCRYPT);
      $pdo->prepare('UPDATE nguoi_dung SET mat_khau_ma_hoa=? WHERE id=?')->execute([$hash, $id]);
      flash_ok('Mật khẩu tạm thời: <code>' . $temp . '</code> (hãy gửi cho người dùng & yêu cầu đổi ngay)');
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Thiếu ID tài khoản');
      if ($id === (int)me()['id']) throw new RuntimeException('Không thể xoá tài khoản của chính bạn.');
      // bảo vệ admin cuối cùng
      $u = $pdo->prepare('SELECT u.*, r.ma AS role_ma FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id WHERE u.id=?');
      $u->execute([$id]);
      $u = $u->fetch();
      if (!$u) throw new RuntimeException('Không tìm thấy tài khoản');
      if ($u['role_ma'] === 'ADMIN') {
        $remain = count_active_admins($id);
        if ($remain <= 0) throw new RuntimeException('Không thể xoá ADMIN cuối cùng.');
      }
      $pdo->prepare('DELETE FROM nguoi_dung WHERE id=?')->execute([$id]);
      flash_ok('Đã xoá tài khoản #' . $id);
    } else {
      throw new RuntimeException('Hành động không hợp lệ');
    }
  } catch (Throwable $e) {
    flash_err($e->getMessage());
  }
  redirect('index.php?p=users' . (isset($_GET['q']) ? '&q=' . urlencode((string)$_GET['q']) : ''));
}

// ====== Lọc & tìm kiếm ======
$q = trim($_GET['q'] ?? '');
$role_id = (int)($_GET['role'] ?? 0);
$status = $_GET['status'] ?? '';

$where = [];
$args = [];
if ($q !== '') {
  $where[] = '(u.email LIKE ? OR u.ho_ten LIKE ? OR u.sdt LIKE ?)';
  $kw = '%' . $q . '%';
  array_push($args, $kw, $kw, $kw);
}
if ($role_id > 0) {
  $where[] = 'u.vai_tro_id=?';
  $args[] = $role_id;
}
if (in_array($status, ['HOAT_DONG', 'KHOA'], true)) {
  $where[] = 'u.trang_thai=?';
  $args[] = $status;
}
$sql = "SELECT u.*, r.ma AS role_ma, r.ten AS role_ten FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id" .
  ($where ? (' WHERE ' . implode(' AND ', $where)) : '') .
  ' ORDER BY u.id DESC';
$st = $pdo->prepare($sql);
$st->execute($args);
$users = $st->fetchAll();

// Nếu có ?edit=id, lấy thông tin để bind vào form sửa
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id > 0) {
  foreach ($users as $u) {
    if ((int)$u['id'] === $edit_id) {
      $edit_row = $u;
      break;
    }
  }
  if (!$edit_row) {
    $st2 = $pdo->prepare('SELECT u.*, r.ma AS role_ma, r.ten AS role_ten FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id WHERE u.id=?');
    $st2->execute([$edit_id]);
    $edit_row = $st2->fetch();
  }
}
?>

<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý tài khoản | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    .tbl {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px
    }

    .tbl th,
    .tbl td {
      border: 1px solid var(--border);
      padding: 10px;
      text-align: left;
      vertical-align: top
    }

    .actions button,
    .actions a {
      margin-right: 6px
    }

    .card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 14px;
      margin: 12px 0
    }

    form.inline {
      display: inline
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 12px
    }
  </style>
</head>

<body>
  <header class="topbar">
    <div class="container nav">
      <div class="brand">
        <div class="logo">✈</div>
        <div>VNAir Ticket</div>
      </div>
      <nav>
        <a href="index.php?p=admin">Admin</a>
        <a href="index.php?p=users">Quản lý tài khoản</a>
        <a href="index.php?p=flights">Quản lý chuyến bay</a>
      </nav>
      <div class="nav-cta">
        <a class="btn outline" href="index.php?p=logout">Đăng xuất</a>
      </div>
    </div>
  </header>

  <main class="container">
    <?php if ($m = flash_get('ok')): ?><div class="ok"><?= $m ?></div><?php endif; ?>
    <?php if ($m = flash_get('err')): ?><div class="err" style="display:block"><?= $m ?></div><?php endif; ?>

    <h2>Quản lý tài khoản</h2>

    <!-- Bộ lọc tìm kiếm -->
    <form class="card" method="get" action="index.php">
      <input type="hidden" name="p" value="users">
      <div class="grid">
        <div class="field" style="grid-column: span 5;">
          <label for="q">Tìm kiếm</label>
          <input id="q" name="q" placeholder="email, họ tên, SĐT" value="<?= htmlspecialchars($q) ?>">
        </div>
        <div class="field" style="grid-column: span 3;">
          <label for="role">Vai trò</label>
          <select id="role" name="role">
            <option value="0">-- Tất cả --</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>" <?= $role_id === $r['id'] ? 'selected' : '' ?>><?= $r['ma'] ?> - <?= $r['ten'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field" style="grid-column: span 2;">
          <label for="status">Trạng thái</label>
          <select id="status" name="status">
            <option value="">-- Tất cả --</option>
            <option value="HOAT_DONG" <?= $status === 'HOAT_DONG' ? 'selected' : '' ?>>HOAT_DONG</option>
            <option value="KHOA" <?= $status === 'KHOA' ? 'selected' : '' ?>>KHOA</option>
          </select>
        </div>
        <div class="submit-row" style="grid-column: span 2;align-items:end;display:flex;justify-content:flex-end">
          <button class="btn" type="submit">Lọc</button>
        </div>
      </div>
    </form>

    <!-- Form tạo/sửa -->
    <div class="card">
      <h3><?= $edit_row ? 'Sửa tài khoản #' . (int)$edit_row['id'] : 'Thêm tài khoản' ?></h3>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= (int)$edit_row['id'] ?>"><?php endif; ?>
        <div class="grid">
          <div class="field" style="grid-column: span 4;">
            <label>Email</label>
            <input name="email" type="email" required value="<?= htmlspecialchars($edit_row['email'] ?? '') ?>">
          </div>
          <div class="field" style="grid-column: span 4;">
            <label>Họ tên</label>
            <input name="ho_ten" required value="<?= htmlspecialchars($edit_row['ho_ten'] ?? '') ?>">
          </div>
          <div class="field" style="grid-column: span 4;">
            <label>Số điện thoại</label>
            <input name="sdt" value="<?= htmlspecialchars($edit_row['sdt'] ?? '') ?>">
          </div>
          <div class="field" style="grid-column: span 4;">
            <label>Vai trò</label>
            <select name="vai_tro_id" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= isset($edit_row['vai_tro_id']) && (int)$edit_row['vai_tro_id'] === $r['id'] ? 'selected' : '' ?>><?= $r['ma'] ?> - <?= $r['ten'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field" style="grid-column: span 4;">
            <label>Trạng thái</label>
            <select name="trang_thai">
              <?php foreach (['HOAT_DONG', 'KHOA'] as $s): ?>
                <option <?= $edit_row && $edit_row['trang_thai'] === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($edit_row): ?>
            <div class="field" style="grid-column: span 4;">
              <label>Mật khẩu mới (tuỳ chọn)</label>
              <input name="new_password" type="password" placeholder="Để trống nếu không đổi">
            </div>
          <?php else: ?>
            <div class="field" style="grid-column: span 4;">
              <label>Mật khẩu</label>
              <input name="password" type="password" required>
            </div>
          <?php endif; ?>
        </div>
        <div class="submit-row">
          <button class="btn" type="submit" name="action" value="<?= $edit_row ? 'update' : 'create' ?>">Lưu</button>
          <?php if ($edit_row): ?>
            <a class="btn outline" href="index.php?p=users">Hủy</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Bảng tài khoản -->
    <div class="card">
      <h3>Danh sách tài khoản (<?= count($users) ?>)</h3>
      <table class="tbl">
        <tr>
          <th>#</th>
          <th>Họ tên</th>
          <th>Email</th>
          <th>SĐT</th>
          <th>Vai trò</th>
          <th>Trạng thái</th>
          <th></th>
        </tr>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['ho_ten']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['sdt']) ?></td>
            <td><?= htmlspecialchars($u['role_ma']) ?></td>
            <td><?= htmlspecialchars($u['trang_thai']) ?></td>
            <td class="actions">
              <a class="btn outline" href="index.php?p=users&edit=<?= (int)$u['id'] ?>">Sửa</a>
              <form method="post" class="inline" onsubmit="return confirm('Reset mật khẩu cho #<?= (int)$u['id'] ?>?')">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button class="btn" name="action" value="resetpw" type="submit">Reset mật khẩu</button>
              </form>
              <form method="post" class="inline" onsubmit="return confirm('<?= $u['trang_thai'] === 'HOAT_DONG' ? 'Khoá' : 'Mở khoá' ?> tài khoản #<?= (int)$u['id'] ?>?')">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button class="btn" name="action" value="toggle" type="submit"><?= $u['trang_thai'] === 'HOAT_DONG' ? 'Khoá' : 'Mở khoá' ?></button>
              </form>
              <form method="post" class="inline" onsubmit="return confirm('Xoá tài khoản #<?= (int)$u['id'] ?>? Hành động không thể hoàn tác.')">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button class="btn" name="action" value="delete" type="submit">Xoá</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </main>

  <footer>
    <div class="container">© <span id="y"></span> VNAir Ticket</div>
  </footer>
  <script>
    document.getElementById('y').textContent = new Date().getFullYear();
  </script>
</body>

</html>