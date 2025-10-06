<?php
require_once dirname(__DIR__,2).'/includes/db.php';
require_once dirname(__DIR__,2).'/includes/auth.php';
require_login(['CUSTOMER']);
$user = me();
$pdo = db();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    // Giả lập thanh toán
    $bookingId = $_POST['booking_id'];
    $method = $_POST['method'];
    // Lấy tổng tiền
    $stmt = $pdo->prepare("SELECT tong_tien FROM dat_cho WHERE id=? AND khach_hang_id=? AND trang_thai='CHO_THANH_TOAN'");
    $stmt->execute([$bookingId, $user['id']]);
    $row = $stmt->fetch();
    if ($row) {
        $amount = $row['tong_tien'];
        // Tạo bản ghi thanh toán
        $payStmt = $pdo->prepare("INSERT INTO thanh_toan (dat_cho_id, nha_cung_cap, phuong_thuc, so_tien, tien_te, trang_thai, ma_giao_dich, thanh_toan_luc) VALUES (?, ?, ?, ?, 'VND', 'THANH_CONG', ?, NOW())");
        $payStmt->execute([
            $bookingId,
            $method,
            $method,
            $amount,
            'GD'.time().rand(1000,9999)
        ]);
        // Cập nhật trạng thái đặt chỗ
        $pdo->prepare("UPDATE dat_cho SET trang_thai='XAC_NHAN', xac_nhan_luc=NOW() WHERE id=?")->execute([$bookingId]);
    } else {
        $err = 'Không tìm thấy đặt chỗ hoặc đã thanh toán.';
    }
}
// Lấy các đặt chỗ chưa thanh toán
$stmt = $pdo->prepare("SELECT * FROM dat_cho WHERE khach_hang_id=? AND trang_thai='CHO_THANH_TOAN' ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();
?>
<h1>Thanh toán đặt vé</h1>
<?php if ($err): ?><div style="color:red;"><?=htmlspecialchars($err)?></div><?php endif; ?>
<?php if (!$bookings): ?>
    <p>Bạn không có đặt chỗ nào cần thanh toán.</p>
<?php else: ?>
    <table border="1" cellpadding="6" style="border-collapse:collapse;">
        <tr>
            <th>Mã đặt chỗ (PNR)</th><th>Tổng tiền</th><th>Ngày tạo</th><th>Thanh toán</th>
        </tr>
        <?php foreach ($bookings as $b): ?>
        <tr>
            <td><?=htmlspecialchars($b['pnr'])?></td>
            <td><?=number_format($b['tong_tien'])?> VND</td>
            <td><?=htmlspecialchars($b['created_at'])?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="booking_id" value="<?=$b['id']?>">
                    <select name="method">
                        <option value="BANK">Ngân hàng</option>
                        <option value="EWALLET">Ví điện tử</option>
                    </select>
                    <button name="pay" type="submit">Thanh toán</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

