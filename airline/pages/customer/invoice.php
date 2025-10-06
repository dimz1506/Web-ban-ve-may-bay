<?php
require_once dirname(__DIR__,2).'/includes/db.php';
require_once dirname(__DIR__,2).'/includes/auth.php';
require_login(['CUSTOMER']);
$user = me();
$pdo = db();
// Lấy các đặt chỗ đã thanh toán
$stmt = $pdo->prepare("SELECT d.*, t.so_tien, t.phuong_thuc, t.thanh_toan_luc FROM dat_cho d JOIN thanh_toan t ON t.dat_cho_id=d.id WHERE d.khach_hang_id=? AND d.trang_thai='XAC_NHAN' ORDER BY d.xac_nhan_luc DESC");
$stmt->execute([$user['id']]);
$invoices = $stmt->fetchAll();
?>
<h1>Hóa đơn thanh toán</h1>
<?php if (!$invoices): ?>
    <p>Bạn chưa có hóa đơn nào.</p>
<?php else: ?>
    <table border="1" cellpadding="6" style="border-collapse:collapse;">
        <tr>
            <th>Mã đặt chỗ (PNR)</th><th>Số tiền</th><th>Phương thức</th><th>Ngày thanh toán</th><th>Xem hóa đơn</th>
        </tr>
        <?php foreach ($invoices as $inv): ?>
        <tr>
            <td><?=htmlspecialchars($inv['pnr'])?></td>
            <td><?=number_format($inv['so_tien'])?> VND</td>
            <td><?=htmlspecialchars($inv['phuong_thuc'])?></td>
            <td><?=htmlspecialchars($inv['thanh_toan_luc'])?></td>
            <td>
                <button onclick="showInvoice('<?=htmlspecialchars(json_encode($inv))?>')">Xem</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div id="invoiceBox" style="display:none; border:1px solid #ccc; padding:16px; margin-top:20px; background:#f9f9f9;"></div>
    <script>
    function showInvoice(data) {
        var inv = JSON.parse(data);
        var html = `<h2>Hóa đơn</h2>
            <b>Mã đặt chỗ (PNR):</b> ${inv.pnr}<br>
            <b>Số tiền:</b> ${Number(inv.so_tien).toLocaleString()} VND<br>
            <b>Phương thức:</b> ${inv.phuong_thuc}<br>
            <b>Ngày thanh toán:</b> ${inv.thanh_toan_luc}<br>
            <b>Ngày đặt:</b> ${inv.created_at}<br>
            <b>Trạng thái:</b> ${inv.trang_thai}<br>`;
        document.getElementById('invoiceBox').innerHTML = html;
        document.getElementById('invoiceBox').style.display = 'block';
    }
    </script>
<?php endif; ?>

