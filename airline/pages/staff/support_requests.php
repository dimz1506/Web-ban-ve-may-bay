<?php
// pages/staff/support_requests.php — Trang nhân viên xem yêu cầu hỗ trợ
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['STAFF', 'ADMIN']);


$user_role = $_SESSION['user']['role'] ?? '';
$is_admin = ($user_role === 'ADMIN');

$requests = [];
try {
    $stmt = db()->query("
        SELECT h.*, n.ho_ten AS ten_khach
        FROM ho_tro_khach_hang h
        JOIN nguoi_dung n ON h.khach_hang_id = n.id
        ORDER BY 
            CASE 
                WHEN h.trang_thai = 'MOI' THEN 1
                WHEN h.trang_thai = 'DANG_XU_LY' THEN 2
                WHEN h.trang_thai = 'DA_TRA_LOI' THEN 3
                ELSE 4
            END,
            h.created_at DESC
    ");
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Không thể tải danh sách yêu cầu: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <title>Yêu cầu hỗ trợ | Nhân viên</title>
    <link rel="stylesheet" href="assets/home.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f9fafb; padding: 20px; }
        h1 { text-align: center; color: #0b4f7d; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #0b4f7d; color: white; }
        tr:hover { background: #f1f5f9; }
        .status { padding: 5px 10px; border-radius: 8px; font-weight: bold; font-size: 12px; }
        .status-MOI { background: #dbeafe; color: #1e40af; }
        .status-DANG_XU_LY { background: #fef3c7; color: #d97706; }
        .status-DA_TRA_LOI { background: #d1fae5; color: #065f46; }
        .status-DONG { background: #f3f4f6; color: #6b7280; }
        .btn { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-view { background: #3b82f6; color: white; }
        .btn-edit { background: #10b981; color: white; }
    </style>
</head>
<body>
    <h1>📩 Danh sách yêu cầu hỗ trợ khách hàng</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Khách hàng</th>
                <th>Loại</th>
                <th>Tiêu đề</th>
                <th>Mức ưu tiên</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="8" style="text-align:center; color: gray;">Không có yêu cầu hỗ trợ nào.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['ten_khach']) ?></td>
                        <td><?= htmlspecialchars($r['loai_ho_tro']) ?></td>
                        <td><?= htmlspecialchars($r['tieu_de']) ?></td>
                        <td><?= htmlspecialchars($r['muc_do_uu_tien']) ?></td>
                        <td><span class="status status-<?= $r['trang_thai'] ?>"><?= $r['trang_thai'] ?></span></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                        <td>
                          <a href="index.php?p=support_detail&id=<?= $r['id'] ?>" class="btn btn-view">Xem</a>
                          <a href="index.php?p=support_update&id=<?= $r['id'] ?>" class="btn btn-view" >Cập nhật</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <td>
                <?php if ($is_admin): ?>
                <a class="btn btn-view" href="index.php?p=admin">Quay lại</a>
            <?php else: ?>
                <a class="btn btn-view" href="index.php?p=staff">Quay lại</a>
            <?php endif; ?>
            </td>
        </tbody>
    </table>
</body>
</html>
