<?php
// tools/create_admin.php
// Tạo nhanh tài khoản ADMIN qua CLI.
// Chạy: php tools/create_admin.php [--email=you@example.com] [--name="Your Name"] [--password=secret] [--force-update]

declare(strict_types=1);

require_once dirname(__DIR__).'/config.php';

function println(string $s=''): void { fwrite(STDOUT, $s."\n"); }
function prompt(string $label, ?string $default=null): string {
  $d = $default !== null ? " [{$default}]" : '';
  fwrite(STDOUT, $label.$d.': ');
  $line = fgets(STDIN);
  $val = $line === false ? '' : trim($line);
  if ($val === '' && $default !== null) return $default;
  return $val;
}
function promptConfirm(string $label, bool $defaultYes=true): bool {
  $def = $defaultYes ? 'Y/n' : 'y/N';
  fwrite(STDOUT, $label." ($def): ");
  $line = fgets(STDIN);
  $v = $line === false ? '' : strtolower(trim($line));
  if ($v === '') return $defaultYes;
  return in_array($v, ['y','yes'], true);
}

// Parse options (optional)
$opts = getopt('', ['email::','name::','password::','force-update']);
$email = isset($opts['email']) ? (string)$opts['email'] : '';
$name  = isset($opts['name'])  ? (string)$opts['name']  : '';
$pass  = isset($opts['password']) ? (string)$opts['password'] : '';
$force = array_key_exists('force-update', $opts);

println("== Tạo tài khoản ADMIN ==");
if ($email === '') $email = prompt('Email');
if ($name  === '')  $name  = prompt('Họ tên');
while ($pass === '') {
  $pass = prompt('Mật khẩu (>= 6 ký tự)');
  if (strlen($pass) < 6) { println('  * Mật khẩu quá ngắn, vui lòng nhập lại.'); $pass = ''; }
}

try {
  $pdo = db();
  // Đảm bảo có vai_tro ADMIN
  $st = $pdo->prepare("SELECT id FROM vai_tro WHERE ma='ADMIN' LIMIT 1");
  $st->execute();
  $roleId = $st->fetchColumn();
  if (!$roleId) {
    $pdo->prepare("INSERT INTO vai_tro(ma,ten) VALUES ('ADMIN','Quan tri')")->execute();
    $roleId = (int)$pdo->lastInsertId();
    println("Đã tạo vai trò ADMIN (id=$roleId).");
  }

  // Kiểm tra email tồn tại
  $q = $pdo->prepare('SELECT id, ho_ten FROM nguoi_dung WHERE email=? LIMIT 1');
  $q->execute([$email]);
  $exist = $q->fetch();

  if ($exist) {
    println("Tài khoản đã tồn tại: #{$exist['id']} ({$exist['ho_ten']}).");
    if (!$force && !promptConfirm('Bạn có muốn CẬP NHẬT mật khẩu & vai trò ADMIN cho tài khoản này?', true)) {
      println('Huỷ thao tác.');
      exit(0);
    }
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $upd = $pdo->prepare('UPDATE nguoi_dung SET ho_ten=?, mat_khau_ma_hoa=?, vai_tro_id=?, trang_thai=\'HOAT_DONG\' WHERE id=?');
    $upd->execute([$name, $hash, $roleId, (int)$exist['id']]);
    println('Đã cập nhật tài khoản ADMIN thành công.');
    exit(0);
  }

  // Tạo tài khoản mới
  $hash = password_hash($pass, PASSWORD_BCRYPT);
  $ins = $pdo->prepare("INSERT INTO nguoi_dung(email, sdt, mat_khau_ma_hoa, ho_ten, trang_thai, vai_tro_id) VALUES (?,?,?,?, 'HOAT_DONG', ?)");
  $ins->execute([$email, null, $hash, $name, $roleId]);
  $uid = (int)$pdo->lastInsertId();
  println("Tạo ADMIN thành công: id=$uid, email=$email");
  println('Bạn có thể đăng nhập tại: index.php?p=login');
} catch (Throwable $e) {
  fwrite(STDERR, 'Lỗi: '.$e->getMessage()."\n");
  exit(1);
}
