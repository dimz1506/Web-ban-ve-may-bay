<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bảng điều khiển Admin | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <link rel="stylesheet" href="assets/admin.css">
  
</head>

<body>
  <header class="topbar">
    <div class="container nav">
      <div class="brand">
        <div class="logo">✈</div>
        <div>VNAir Ticket</div>
      </div>
      <nav>
        <strong>Admin</strong>
        <a href="index.php?p=users">Quản lý tài khoản</a>
        <a href="index.php?p=flights">Quản lý chuyến bay</a>
        <a href="index.php?p=promotions">Quản lý khuyến mãi</a>
        <a href="index.php?p=classes">Quản lý hạng ghế</a>
        <a href="index.php?p=reports">Báo cáo/thống kê</a>
        <!-- Có thể bổ sung: quản lý người dùng, tuyến, khuyến mại, báo cáo -->
      </nav>
      <div class="nav-cta">
        <a class="btn outline" href="index.php?p=logout">Đăng xuất</a>
      </div>
    </div>
  </header>
  <main class="container">
    <h2>Xin chào, Admin</h2>
    <div class="card">
      <ul>
        <a href="index.php?p=users">Quản lý tài khoản</a>
        <a href="index.php?p=flights">Quản lý chuyến bay</a>
        <a href="index.php?p=promotions">Quản lý khuyến mãi</a>
        <a href="index.php?p=classes">Quản lý hạng ghế</a>
        <a href="index.php?p=reports">Báo cáo/thống kê</a>

      </ul>
    </div>
  </main>
  <footer>
    <div class="container">© <span id="y"></span> VNAir Ticket</div>
  </footer>
  <script>
    document.getElementById('y').textContent = new Date().getFullYear();
  </script>
</body>

</html>