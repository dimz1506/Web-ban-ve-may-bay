<?php
// includes/auth.php
function auth_login(string $email, string $password): bool {
  $stmt = db()->prepare("SELECT u.*, r.ma AS role_code FROM nguoi_dung u JOIN vai_tro r ON r.id=u.vai_tro_id WHERE u.email=? AND u.trang_thai='HOAT_DONG' LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if (!$u) return false;
  if (!password_verify($password, $u['mat_khau_ma_hoa'])) return false;
  $_SESSION['user'] = [
    'id'=>$u['id'], 'email'=>$u['email'], 'ho_ten'=>$u['ho_ten'], 'role'=>$u['role_code']
  ];
  db()->prepare("UPDATE nguoi_dung SET dang_nhap_gan_nhat=NOW() WHERE id=?")->execute([$u['id']]);
  return true;
}

function auth_logout(): void { unset($_SESSION['user']); }

function me(): ?array { return $_SESSION['user'] ?? null; }
function is_role(string $r): bool { return isset($_SESSION['user']) && $_SESSION['user']['role']===$r; }
function require_login(array $roles=[]): void {
  if (!me()) redirect('index.php?p=login');
  if ($roles && !in_array($_SESSION['user']['role'], $roles, true)) { http_response_code(403); exit('Forbidden'); }
}
