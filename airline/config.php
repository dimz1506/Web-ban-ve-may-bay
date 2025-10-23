<?php
// config.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!function_exists('current_user_role')) {
    function current_user_role() {
        return $_SESSION['user']['role'] ?? null;
    }
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'dat_ve_may_bay');
define('DB_USER', 'root');
define('DB_PASS', ''); // đổi cho phù hợp

define('APP_BASE', '/Web-ban-ve-may-bay/airline/public'); // đổi cho phù hợp

define('APP_NAME', 'Airline Manager');
// session_start();
// Autoload các helper
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';