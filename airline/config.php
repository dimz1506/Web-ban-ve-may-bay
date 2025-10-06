<?php
// config.php
declare(strict_types=1);
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'dat_ve_may_bay');
define('DB_USER', 'root');
define('DB_PASS', ''); // đổi cho phù hợp

define('APP_BASE', '/web-ban-ve-may-bay/airline/public'); // đổi cho phù hợp

define('APP_NAME', 'Airline Manager');
// session_start();
// Autoload các helper
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';