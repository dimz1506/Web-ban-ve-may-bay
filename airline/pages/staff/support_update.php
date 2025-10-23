<?php
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['STAFF', 'ADMIN']);

$id = $_GET['id'] ?? 0;

// --- Lấy thông tin yêu cầu hỗ trợ ---
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

// --- Nếu người dùng bấm nút Lưu ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trang_thai = $_POST['trang_thai'] ?? '';

    if ($trang_thai) {
        try {
            $update = db()->prepare("
                UPDATE ho_tro_khach_hang 
                SET trang_thai = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $update->execute([$trang_thai, $id]);

            // Thông báo & quay lại danh sách
            flash_set('ok', '✅ Cập nhật trạng thái thành công!');
            redirect('index.php?p=support_requests');
            exit;
        } catch (Exception $e) {
            $error = "Lỗi khi cập nhật: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng chọn trạng thái.";
    }
}
?>

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Cập nhật yêu cầu hỗ trợ</title>
    <link rel="stylesheet" href="assets/home.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f9fafb; padding: 30px; }
        .container { max-width: 600px; margin: auto; background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        h1 { color: #0b4f7d; text-align: center; }
        label { font-weight: bold; margin-top: 10px; display: block; }
        select, textarea { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-save { background: #10b981; color: white; }
        .btn-back { background: #3b82f6; color: white; text-decoration: none; padding: 10px 20px; display: inline-block; border-radius: 6px; }
        p { margin: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ Cập nhật trạng thái yêu cầu #<?= htmlspecialchars($support['id']) ?></h1>

        <?php if (!empty($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <p><strong>Khách hàng:</strong> <?= htmlspecialchars($support['ten_khach']) ?></p>
        <p><strong>Tiêu đề:</strong> <?= htmlspecialchars($support['tieu_de']) ?></p>
        <p><strong>Nội dung:</strong><br><?= nl2br(htmlspecialchars($support['noi_dung'])) ?></p>

        <form method="post">
            <label for="trang_thai">Trạng thái:</label>
            <select name="trang_thai" id="trang_thai" required>
                <option value="">-- Chọn trạng thái --</option>
                <option value="MOI" <?= $support['trang_thai'] == 'MOI' ? 'selected' : '' ?>>Mới</option>
                <option value="DANG_XU_LY" <?= $support['trang_thai'] == 'DANG_XU_LY' ? 'selected' : '' ?>>Đang xử lý</option>
                <option value="DA_TRA_LOI" <?= $support['trang_thai'] == 'DA_TRA_LOI' ? 'selected' : '' ?>>Đã trả lời</option>
                <option value="DONG" <?= $support['trang_thai'] == 'DONG' ? 'selected' : '' ?>>Đóng</option>
            </select>

            <br><br>
            <button class="btn btn-save" type="submit">💾 Lưu</button>
            <a href="index.php?p=support_requests" class="btn-back">⬅️ Quay lại</a>
        </form>
    </div>
</body>
</html>
