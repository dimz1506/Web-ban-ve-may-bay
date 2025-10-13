<?php
// pages/users.php — Quản lý tài khoản người dùng (ADMIN)
// (PHP logic giữ nguyên — chỉ đổi giao diện)
if (!function_exists('db')) {
  require_once dirname(__DIR__) . '/config.php';
}
require_login(['ADMIN']);
$pdo = db();

function flash_ok($m){ flash_set('ok',$m); }
function flash_err($m){ flash_set('err',$m); }
function make_pwd(int $len=10): string { return substr(bin2hex(random_bytes(16)), 0, $len); }

// Lấy danh sách vai trò
$roles = $pdo->query("SELECT id, ma, ten FROM vai_tro ORDER BY id")->fetchAll();
$roleMap = []; foreach ($roles as $r){ $roleMap[(int)$r['id']] = $r; }

// Helper: đếm số ADMIN đang hoạt động (có thể trừ đi 1 id nếu cung cấp)
function count_active_admins(?int $excludeId=null): int {
  $sql = "SELECT COUNT(*) FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id WHERE r.ma='ADMIN' AND u.trang_thai='HOAT_DONG'";
  $args = [];
  if ($excludeId) { $sql .= " AND u.id<>?"; $args[] = $excludeId; }
  $st = db()->prepare($sql); $st->execute($args); return (int)$st->fetchColumn();
}

// ====== Xử lý POST (tạo/sửa/khoá/reset/xoá) ======
if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_post_csrf();
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create') {
      $email = trim($_POST['email'] ?? '');
      $name  = trim($_POST['ho_ten'] ?? '');
      $sdt   = trim($_POST['sdt'] ?? '');
      $pwd   = (string)($_POST['password'] ?? '');
      $rid   = (int)($_POST['vai_tro_id'] ?? 0);
      $status= ($_POST['trang_thai'] ?? 'HOAT_DONG');

      if (!$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email không hợp lệ');
      if (strlen($pwd) < 6) throw new RuntimeException('Mật khẩu tối thiểu 6 ký tự');
      if (!$rid || !isset($roleMap[$rid])) throw new RuntimeException('Vai trò không hợp lệ');
      if (!in_array($status,['HOAT_DONG','KHOA'], true)) $status='HOAT_DONG';

      $chk = $pdo->prepare('SELECT 1 FROM nguoi_dung WHERE email=? LIMIT 1');
      $chk->execute([$email]); if ($chk->fetchColumn()) throw new RuntimeException('Email đã tồn tại');

      $hash = password_hash($pwd, PASSWORD_BCRYPT);
      $ins = $pdo->prepare("INSERT INTO nguoi_dung(email,sdt,mat_khau_ma_hoa,ho_ten,trang_thai,vai_tro_id) VALUES (?,?,?,?,?,?)");
      $ins->execute([$email, ($sdt?:null), $hash, $name, $status, $rid]);
      flash_ok('Đã tạo tài khoản mới.');

    } elseif ($action === 'update') {
      $id    = (int)($_POST['id'] ?? 0);
      $email = trim($_POST['email'] ?? '');
      $name  = trim($_POST['ho_ten'] ?? '');
      $sdt   = trim($_POST['sdt'] ?? '');
      $rid   = (int)($_POST['vai_tro_id'] ?? 0);
      $status= ($_POST['trang_thai'] ?? 'HOAT_DONG');
      $newpw = (string)($_POST['new_password'] ?? '');

      if ($id<=0) throw new RuntimeException('Thiếu ID tài khoản');
      if (!$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email không hợp lệ');
      if (!$rid || !isset($roleMap[$rid])) throw new RuntimeException('Vai trò không hợp lệ');
      if (!in_array($status,['HOAT_DONG','KHOA'], true)) $status='HOAT_DONG';

      // lấy tài khoản hiện tại
      $cur = $pdo->prepare('SELECT * FROM nguoi_dung WHERE id=?'); $cur->execute([$id]); $cur = $cur->fetch();
      if (!$cur) throw new RuntimeException('Không tìm thấy tài khoản');

      // kiểm tra email trùng
      $chke = $pdo->prepare('SELECT 1 FROM nguoi_dung WHERE email=? AND id<>? LIMIT 1');
      $chke->execute([$email,$id]); if ($chke->fetchColumn()) throw new RuntimeException('Email đã tồn tại');

      // bảo vệ admin cuối cùng
      $isCurAdmin = false; $newIsAdmin = false;
      if ($cur['vai_tro_id'] && isset($roleMap[(int)$cur['vai_tro_id']]) && $roleMap[(int)$cur['vai_tro_id']]['ma']==='ADMIN') $isCurAdmin=true;
      if ($rid && isset($roleMap[$rid]) && $roleMap[$rid]['ma']==='ADMIN') $newIsAdmin=true;

      if ($isCurAdmin) {
        if (!$newIsAdmin || $status==='KHOA') {
          $remain = count_active_admins($id);
          if ($remain <= 0) throw new RuntimeException('Không thể thay đổi. Cần ít nhất 1 ADMIN hoạt động.');
        }
      }

      if ($newpw!=='' && strlen($newpw) < 6) throw new RuntimeException('Mật khẩu mới tối thiểu 6 ký tự');

      if ($newpw!=='') {
        $hash = password_hash($newpw, PASSWORD_BCRYPT);
        $sql = 'UPDATE nguoi_dung SET email=?, sdt=?, mat_khau_ma_hoa=?, ho_ten=?, trang_thai=?, vai_tro_id=? WHERE id=?';
        $pdo->prepare($sql)->execute([$email, ($sdt?:null), $hash, $name, $status, $rid, $id]);
      } else {
        $sql = 'UPDATE nguoi_dung SET email=?, sdt=?, ho_ten=?, trang_thai=?, vai_tro_id=? WHERE id=?';
        $pdo->prepare($sql)->execute([$email, ($sdt?:null), $name, $status, $rid, $id]);
      }
      flash_ok('Đã cập nhật tài khoản.');

    } elseif ($action === 'toggle') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) throw new RuntimeException('Thiếu ID tài khoản');
      if ($id === (int)me()['id']) throw new RuntimeException('Không thể tự khoá tài khoản của chính bạn.');
      $u = $pdo->prepare('SELECT u.*, r.ma AS role_ma FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id WHERE u.id=?');
      $u->execute([$id]); $u=$u->fetch(); if(!$u) throw new RuntimeException('Không tìm thấy tài khoản');
      $new = ($u['trang_thai']==='HOAT_DONG') ? 'KHOA' : 'HOAT_DONG';
      if ($u['role_ma']==='ADMIN' && $new==='KHOA') {
        $remain = count_active_admins($id);
        if ($remain <= 0) throw new RuntimeException('Không thể khoá ADMIN cuối cùng.');
      }
      $pdo->prepare('UPDATE nguoi_dung SET trang_thai=? WHERE id=?')->execute([$new,$id]);
      flash_ok(($new==='KHOA'?'Đã khoá':'Đã mở khoá').' tài khoản #'.$id);

    } elseif ($action === 'resetpw') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) throw new RuntimeException('Thiếu ID tài khoản');
      $temp = make_pwd(12);
      $hash = password_hash($temp, PASSWORD_BCRYPT);
      $pdo->prepare('UPDATE nguoi_dung SET mat_khau_ma_hoa=? WHERE id=?')->execute([$hash,$id]);
      flash_ok('Mật khẩu tạm thời: <code>'.$temp.'</code> (hãy gửi cho người dùng & yêu cầu đổi ngay)');

    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) throw new RuntimeException('Thiếu ID tài khoản');
      if ($id === (int)me()['id']) throw new RuntimeException('Không thể xoá tài khoản của chính bạn.');
      $u = $pdo->prepare('SELECT u.*, r.ma AS role_ma FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id WHERE u.id=?');
      $u->execute([$id]); $u=$u->fetch(); if(!$u) throw new RuntimeException('Không tìm thấy tài khoản');
      if ($u['role_ma']==='ADMIN') {
        $remain = count_active_admins($id);
        if ($remain <= 0) throw new RuntimeException('Không thể xoá ADMIN cuối cùng.');
      }
      $pdo->prepare('DELETE FROM nguoi_dung WHERE id=?')->execute([$id]);
      flash_ok('Đã xoá tài khoản #'.$id);

    } else {
      throw new RuntimeException('Hành động không hợp lệ');
    }
  } catch (Throwable $e) {
    flash_err($e->getMessage());
  }
  redirect('index.php?p=users'.(isset($_GET['q'])?'&q='.urlencode((string)$_GET['q']):''));
}

// ====== Lọc & tìm kiếm ======
$q = trim($_GET['q'] ?? '');
$role_id = (int)($_GET['role'] ?? 0);
$status = $_GET['status'] ?? '';

$where = []; $args=[];
if ($q !== '') { $where[] = '(u.email LIKE ? OR u.ho_ten LIKE ? OR u.sdt LIKE ?)'; $kw='%'.$q.'%'; array_push($args,$kw,$kw,$kw); }
if ($role_id>0) { $where[] = 'u.vai_tro_id=?'; $args[]=$role_id; }
if (in_array($status,['HOAT_DONG','KHOA'], true)) { $where[]='u.trang_thai=?'; $args[]=$status; }
$sql = "SELECT u.*, r.ma AS role_ma, r.ten AS role_ten FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id".
       ($where? (' WHERE '.implode(' AND ',$where)) : '') .
       ' ORDER BY u.id DESC';
$st = $pdo->prepare($sql); $st->execute($args); $users = $st->fetchAll();

// Nếu có ?edit=id, lấy thông tin để bind vào form sửa
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id>0) {
  foreach($users as $u){ if ((int)$u['id']===$edit_id) { $edit_row=$u; break; } }
  if (!$edit_row) {
    $st2 = $pdo->prepare('SELECT u.*, r.ma AS role_ma, r.ten AS role_ten FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id WHERE u.id=?');
    $st2->execute([$edit_id]); $edit_row = $st2->fetch();
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý tài khoản | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/user.css">
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <div class="brand">
        <div class="logo">✈</div>
        <div>
          <div style="font-weight:800">VNAir Ticket</div>
        </div>
      </div>
      
    </header>

    <div class="layout">
      <aside class="sidebar" aria-label="Thanh điều hướng">
        <h4>Quản trị</h4>
         <a class="side-link active" href="index.php?p=admin">Trang chủ</a>
        <a class="side-link active" href="index.php?p=users">Tài khoản</a>
      
    
        <div style="height:12px"></div>
      </aside>

      <main class="content">
        <?php if ($m = flash_get('ok')): ?>
          <div class="card" style="border-left:4px solid var(--success)"><div class="muted"><?= htmlspecialchars($m) ?></div></div>
        <?php endif; ?>
        <?php if ($m = flash_get('err')): ?>
          <div class="card" style="border-left:4px solid var(--danger)"><div class="muted"><?= htmlspecialchars($m) ?></div></div>
        <?php endif; ?>

        <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
          <div>
            <h2 style="margin:0">Quản lý tài khoản</h2>
            <div class="muted" style="margin-top:6px">Xem, tạo, sửa, khoá/mở & xoá tài khoản.</div>
          </div>
          <div style="display:flex;gap:8px;align-items:center">
            <a class="btn ghost" href="#form-create">Thêm tài khoản</a>
        
          </div>
        </div>

        <!-- FILTER -->
        <form class="card" method="get" action="index.php" aria-label="Bộ lọc người dùng">
          <input type="hidden" name="p" value="users">
          <div class="filters">
            <div style="flex:1;min-width:220px">
              <label>Tìm kiếm</label>
              <input type="text" name="q" placeholder="Email, họ tên, SĐT" value="<?= htmlspecialchars($q) ?>">
            </div>
            <div style="width:220px">
              <label>Vai trò</label>
              <select name="role">
                <option value="0">-- Tất cả --</option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= $role_id === $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['ma'].' - '.$r['ten']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="width:160px">
              <label>Trạng thái</label>
              <select name="status">
                <option value="">-- Tất cả --</option>
                <option value="HOAT_DONG" <?= $status === 'HOAT_DONG' ? 'selected' : '' ?>>HOẠT ĐỘNG</option>
                <option value="KHOA" <?= $status === 'KHOA' ? 'selected' : '' ?>>ĐÃ KHÓA</option>
              </select>
            </div>
            <div style="min-width:140px;display:flex;align-items:flex-end">
              <button class="btn" type="submit">Lọc</button>
            </div>
          </div>
        </form>

        <!-- CREATE / EDIT FORM -->
        <div id="form-create" class="card" aria-labelledby="form-title">
          <h3 id="form-title"><?= $edit_row ? 'Sửa tài khoản #' . (int)$edit_row['id'] : 'Thêm tài khoản mới' ?></h3>
          <form method="post" style="margin-top:12px;display:grid;grid-template-columns:repeat(12,1fr);gap:12px">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= (int)$edit_row['id'] ?>"><?php endif; ?>

            <div style="grid-column:span 4">
              <label>Email</label>
              <input name="email" type="email" required value="<?= htmlspecialchars($edit_row['email'] ?? '') ?>">
            </div>
            <div style="grid-column:span 4">
              <label>Họ tên</label>
              <input name="ho_ten" type="text" required value="<?= htmlspecialchars($edit_row['ho_ten'] ?? '') ?>">
            </div>
            <div style="grid-column:span 4">
              <label>Số điện thoại</label>
              <input name="sdt" type="text" value="<?= htmlspecialchars($edit_row['sdt'] ?? '') ?>">
            </div>

            <div style="grid-column:span 4">
              <label>Vai trò</label>
              <select name="vai_tro_id" required>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= isset($edit_row['vai_tro_id']) && (int)$edit_row['vai_tro_id'] === $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['ma'].' - '.$r['ten']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="grid-column:span 4">
              <label>Trạng thái</label>
              <select name="trang_thai">
                <?php foreach (['HOAT_DONG','KHOA'] as $s): ?>
                  <option value="<?= $s ?>" <?= $edit_row && $edit_row['trang_thai'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if ($edit_row): ?>
              <div style="grid-column:span 4">
                <label>Mật khẩu mới (tuỳ chọn)</label>
                <input name="new_password" type="password" placeholder="Để trống nếu không đổi">
              </div>
            <?php else: ?>
              <div style="grid-column:span 4">
                <label>Mật khẩu</label>
                <input name="password" type="password" required>
              </div>
            <?php endif; ?>

            <div style="grid-column:span 12;display:flex;gap:8px;margin-top:6px">
              <button class="btn" type="submit" name="action" value="<?= $edit_row ? 'update' : 'create' ?>"><?= $edit_row ? 'Lưu thay đổi' : 'Tạo tài khoản' ?></button>
              <?php if ($edit_row): ?><a class="btn ghost" href="index.php?p=users">Hủy</a><?php endif; ?>
            </div>
          </form>
        </div>

        <!-- USERS TABLE -->
        <div class="card" aria-live="polite">
          <h3 style="margin-top:0">Danh sách tài khoản <span class="muted">(<?= count($users) ?>)</span></h3>
          <div style="overflow:auto">
            <table class="tbl" role="table" aria-label="Danh sách người dùng">
              <thead>
                <tr>
                  <th style="width:64px">#</th>
                  <th>Họ tên</th>
                  <th>Email</th>
                  <th style="width:130px">SĐT</th>
                  <th style="width:130px">Vai trò</th>
                  <th style="width:120px">Trạng thái</th>
                  <th style="width:80px"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr data-id="<?= (int)$u['id'] ?>">
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars($u['ho_ten']) ?></td>
                    <td><a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a></td>
                    <td class="muted"><?= htmlspecialchars($u['sdt']) ?></td>
                    <td><?= htmlspecialchars($u['role_ma']) ?></td>
                    <td>
                      <?php
                        $s = $u['trang_thai'];
                        if ($s === 'HOAT_DONG') echo '<span class="badge ok">HOẠT ĐỘNG</span>';
                        elseif ($s === 'KHOA') echo '<span class="badge err">ĐÃ KHÓA</span>';
                        else echo '<span class="badge">'.$s.'</span>';
                      ?>
                    </td>
                    <td style="text-align:right">
                      <div class="action-menu">
                        <button class="action-btn" aria-haspopup="true" aria-expanded="false" title="Thao tác">
                          <!-- 3 dots icon -->
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden><circle cx="5" cy="12" r="2" fill="#0f172a"/><circle cx="12" cy="12" r="2" fill="#0f172a"/><circle cx="19" cy="12" r="2" fill="#0f172a"/></svg>
                        </button>
                        <div class="menu" role="menu" aria-hidden="true">
                          <a href="index.php?p=users&edit=<?= (int)$u['id'] ?>">Sửa</a>

                          <form method="post" class="menu-form" data-action="resetpw" onsubmit="return false;">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="button" class="menu-btn" data-id="<?= (int)$u['id'] ?>">Reset mật khẩu</button>
                          </form>

                          <form method="post" class="menu-form" data-action="toggle" onsubmit="return false;">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="button" class="menu-btn" data-id="<?= (int)$u['id'] ?>"><?= $u['trang_thai'] === 'HOAT_DONG' ? 'Khoá tài khoản' : 'Mở khoá' ?></button>
                          </form>

                          <form method="post" class="menu-form" data-action="delete" onsubmit="return false;">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="button" class="menu-btn" data-id="<?= (int)$u['id'] ?>" style="color:var(--danger)">Xoá</button>
                          </form>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>
    </div>

    <footer style="margin-top:18px">
      <div class="muted">© <span id="y"></span> VNAir Ticket</div>
    </footer>
  </div>

  <!-- Modal xác nhận (dùng chung cho toggle/reset/delete) -->
  <div id="confirmModal" style="display:none;position:fixed;inset:0;background:rgba(2,6,23,0.5);align-items:center;justify-content:center;z-index:120">
    <div style="width:460px;background:#fff;border-radius:12px;padding:18px;box-shadow:0 20px 50px rgba(2,6,23,0.4);">
      <h3 id="modalTitle" style="margin:0 0 8px 0">Xác nhận</h3>
      <div id="modalBody" class="muted" style="margin-bottom:12px">Bạn có chắc muốn thực hiện hành động này?</div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button id="modalCancel" class="btn ghost">Hủy</button>
        <form id="modalForm" method="post" style="display:inline">
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="id" id="modalId" value="">
          <input type="hidden" name="action" id="modalAction" value="">
          <button class="btn" type="submit">Xác nhận</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Footer year
    document.getElementById('y').textContent = new Date().getFullYear();

    // Keyboard shortcut: N => focus email field
    document.addEventListener('keydown', (e)=> {
      if (e.key.toLowerCase() === 'n' && !e.ctrlKey && !e.metaKey) {
        const el = document.querySelector('#form-create input[name="email"]');
        if (el) { el.focus(); e.preventDefault(); }
      }
    });

    // Action menu toggles
    document.querySelectorAll('.action-menu').forEach(menuWrap => {
      const btn = menuWrap.querySelector('.action-btn');
      const menu = menuWrap.querySelector('.menu');
      btn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        // close other menus
        document.querySelectorAll('.menu').forEach(m => { if (m !== menu) m.style.display = 'none'; });
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
      });
    });

    // Close menus when clicking outside
    document.addEventListener('click', ()=> {
      document.querySelectorAll('.menu').forEach(m => m.style.display = 'none');
    });

    // Modal logic
    const modal = document.getElementById('confirmModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalForm = document.getElementById('modalForm');
    const modalId = document.getElementById('modalId');
    const modalAction = document.getElementById('modalAction');
    const modalCancel = document.getElementById('modalCancel');

    // Attach handlers for menu buttons
    document.querySelectorAll('.menu .menu-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const formWrap = btn.closest('.menu-form');
        const action = formWrap.getAttribute('data-action'); // resetpw/toggle/delete
        const id = btn.getAttribute('data-id');
        let title = 'Xác nhận';
        let body = 'Bạn có chắc muốn thực hiện hành động này?';
        if (action === 'resetpw') { title = 'Reset mật khẩu'; body = 'Reset mật khẩu người dùng và tạo mật khẩu tạm thời. Bạn sẽ nhận được mật khẩu mới hiển thị sau khi xác nhận.'; }
        if (action === 'toggle') { title = 'Khoá / Mở tài khoản'; body = 'Hành động này sẽ thay đổi trạng thái tài khoản. Bạn có muốn tiếp tục?'; }
        if (action === 'delete') { title = 'Xoá tài khoản'; body = 'Xoá tài khoản là hành động không thể hoàn tác. Bạn có chắc chắn?'; }

        modalTitle.textContent = title;
        modalBody.textContent = body;
        modalId.value = id;
        modalAction.value = action;
        modal.style.display = 'flex';

      });
    });

    modalCancel.addEventListener('click', ()=> { modal.style.display = 'none'; });

    // close modal on outside click
    modal.addEventListener('click', (e)=> { if (e.target === modal) modal.style.display = 'none'; });

    // Accessibility: close modal with ESC
    document.addEventListener('keydown', (e)=> { if (e.key === 'Escape') modal.style.display = 'none'; });
  </script>
</body>
</html>
