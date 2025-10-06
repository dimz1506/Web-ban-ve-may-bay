<?php
// includes/helpers.php
function base_url(string $path=''): string {
  $p = rtrim(APP_BASE, '/');
  return $p . '/' . ltrim($path,'/');
}

function redirect(string $path): never {
  header('Location: '.base_url($path)); exit;
}

function flash_set(string $key, string $msg): void { $_SESSION['_flash'][$key] = $msg; }
function flash_get(string $key): ?string {
  if (!empty($_SESSION['_flash'][$key])) { $m = $_SESSION['_flash'][$key]; unset($_SESSION['_flash'][$key]); return $m; }
  return null;
}

function csrf_token(): string {
  if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['_csrf'];
}
function csrf_ok(): bool {
  return isset($_POST['_csrf']) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $_POST['_csrf']);
}

function require_post_csrf(): void {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(400); exit('Bad request'); }
}

function p(string $key, $default='') { return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES, 'UTF-8'); }
function g(string $key, $default='') { return htmlspecialchars($_GET[$key] ?? $default, ENT_QUOTES, 'UTF-8'); }

function send_confirmation_email(string $to, string $subject, string $body): bool {
  // Giả lập gửi email: hiển thị ra màn hình hoặc ghi log
  // Nếu cấu hình SMTP, có thể dùng mail($to, $subject, $body)
  if (function_exists('mail')) {
    // Uncomment dòng sau nếu muốn gửi thật
    // return mail($to, $subject, $body);
  }
  // Demo: ghi ra file hoặc hiển thị
  file_put_contents(__DIR__.'/../email_log.txt', date('Y-m-d H:i:s')." | $to | $subject\n$body\n\n", FILE_APPEND);
  return true;
}
