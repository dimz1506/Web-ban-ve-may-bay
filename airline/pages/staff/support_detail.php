<?php
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['STAFF', 'ADMIN']);

$id = $_GET['id'] ?? 0;

try {
    $stmt = db()->prepare("
        SELECT h.*, n.ho_ten AS ten_khach
        FROM ho_tro_khach_hang h
        JOIN nguoi_dung n ON h.khach_hang_id = n.id
        WHERE h.id = ?
    ");
    $stmt->execute([$id]);
    $support = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

if (!$support) {
    echo "<p>Không tìm thấy yêu cầu hỗ trợ.</p>";
    exit;
}

// Tạo màu cho trạng thái
$trangThaiColors = [
    'MOI' => '#2563eb',
    'DANG_XU_LY' => '#f59e0b',
    'DA_TRA_LOI' => '#10b981',
    'DONG' => '#6b7280'
];
$color = $trangThaiColors[$support['trang_thai']] ?? '#9ca3af';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Chi tiết yêu cầu hỗ trợ</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f3f4f6;
      margin: 0;
      padding: 40px;
      color: #1f2937;
    }
    .container {
      max-width: 800px;
      margin: auto;
    }
    .card {
      background: #fff;
      padding: 28px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    h2 {
      margin-top: 0;
      color: #0b4f7d;
      font-size: 24px;
      border-bottom: 2px solid #e5e7eb;
      padding-bottom: 10px;
    }
    .info {
      margin: 16px 0;
      line-height: 1.6;
    }
    .info b {
      display: inline-block;
      width: 160px;
      color: #374151;
    }
    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      color: white;
      font-weight: 500;
      font-size: 14px;
    }
    .btn-group {
      margin-top: 30px;
      display: flex;
      gap: 12px;
    }
    .btn {
      padding: 10px 18px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: 0.25s;
    }
    .btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .btn-back { background: #6b7280; color: #fff; }
    .btn-update { background: #0b4f7d; color: #fff; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2>📋 Chi tiết yêu cầu #<?= $support['id'] ?></h2>
      <div class="info"><b>Khách hàng:</b> <?= htmlspecialchars($support['ten_khach']) ?></div>
      <div class="info"><b>Loại:</b> <?= htmlspecialchars($support['loai_ho_tro']) ?></div>
      <div class="info"><b>Tiêu đề:</b> <?= htmlspecialchars($support['tieu_de']) ?></div>
      <div class="info"><b>Nội dung:</b><br><?= nl2br(htmlspecialchars($support['noi_dung'])) ?></div>
      <div class="info"><b>Mức ưu tiên:</b> <?= htmlspecialchars($support['muc_do_uu_tien']) ?></div>
      <div class="info">
        <b>Trạng thái:</b>
        <span class="status-badge" style="background: <?= $color ?>;">
          <?= htmlspecialchars($support['trang_thai']) ?>
        </span>
      </div>
      <div class="info"><b>Ngày tạo:</b> <?= date('d/m/Y H:i', strtotime($support['created_at'])) ?></div>

      <div class="btn-group">
        <a href="index.php?p=support_requests" class="btn btn-back">⬅ Quay lại</a>
        <a href="index.php?p=support_update&id=<?= $support['id'] ?>" class="btn btn-update">⚙️ Cập nhật trạng thái</a>
      </div>
    </div>
  </div>
</body>
</html>
