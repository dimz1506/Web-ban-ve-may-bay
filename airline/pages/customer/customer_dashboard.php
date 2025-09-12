<?php
// pages/customer_dashboard.php — Trang riêng cho Khách hàng
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Trang khách hàng | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>.card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px;margin:16px 0}</style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    <nav>
      <strong>Khách hàng</strong>
    </nav>
    <div class="nav-cta">
      <a class="btn outline" href="index.php?p=logout">Đăng xuất</a>
    </div>
  </div>
</header>
<main class="container">
  <h2>Xin chào!</h2>
  <div class="card">
    <p>Đây là khu vực dành cho <b>Khách hàng</b>. Bạn có thể:</p>
  </div>
</main>
<footer><div class="container">© <span id="y"></span> VNAir Ticket</div></footer>
<script>document.getElementById('y').textContent = new Date().getFullYear();</script>
</body>
</html>
