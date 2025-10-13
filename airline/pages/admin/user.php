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

include dirname(__DIR__).'/../templates/user_view.php';

?>

