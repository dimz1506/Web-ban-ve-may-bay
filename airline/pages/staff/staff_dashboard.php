<?php
// pages/staff_dashboard.php — Trang riêng cho STAFF
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['STAFF']);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bảng điều khiển Nhân viên | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>.card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px;margin:16px 0}</style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand"><div class="logo">✈</div><div>VNAir Ticket</div></div>
    <nav>
      <strong>Nhân viên</strong>
      <!-- Có thể giới hạn quyền khác với Admin tuỳ yêu cầu -->
    </nav>
    <div class="nav-cta">
      <a class="btn outline" href="index.php?p=logout">Đăng xuất</a>
    </div>
  </div>
</header>
<main class="container">
  <h2>Xin chào, Nhân viên</h2>
  <div class="card">
    <p>Đây là khu vực dành riêng cho <b>NHAN VIEN</b>. Bạn có thể:</p>
    <ul>
    </ul>
  </div>
</main>
<footer><div class="container">© <span id="y"></span> VNAir Ticket</div></footer>
<script>document.getElementById('y').textContent = new Date().getFullYear();</script>
</body>
</html>
