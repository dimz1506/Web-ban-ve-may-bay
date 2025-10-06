<?php
// pages/admin_dashboard.php — Trang riêng cho ADMIN
if (!function_exists('db')) {
  require_once dirname(__DIR__) . '/config.php';
}
require_login(['ADMIN']);
//goi view
include dirname(__DIR__).'/../templates/admin_dashboard_view.php';

?>
