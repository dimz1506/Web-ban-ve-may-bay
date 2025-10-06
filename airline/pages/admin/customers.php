<?php
require_once dirname(__DIR__,2).'/includes/db.php';

$pdo = db();
// Get CUSTOMER role id
$roleStmt = $pdo->prepare("SELECT id FROM vai_tro WHERE ma = 'CUSTOMER'");
$roleStmt->execute();
$customerRole = $roleStmt->fetchColumn();

// Handle add/edit/delete actions
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        // Add customer
        $stmt = $pdo->prepare("INSERT INTO nguoi_dung (email, sdt, mat_khau_ma_hoa, ho_ten, vai_tro_id) VALUES (?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['email'],
                $_POST['sdt'],
                password_hash($_POST['mat_khau'], PASSWORD_DEFAULT),
                $_POST['ho_ten'],
                $customerRole
            ]);
        } catch (Exception $e) {
            $err = 'Lỗi: '.$e->getMessage();
        }
    } elseif (isset($_POST['edit'])) {
        // Edit customer
        $fields = [$_POST['email'], $_POST['sdt'], $_POST['ho_ten'], $_POST['id']];
        $sql = "UPDATE nguoi_dung SET email=?, sdt=?, ho_ten=? WHERE id=? AND vai_tro_id=?";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([$_POST['email'], $_POST['sdt'], $_POST['ho_ten'], $_POST['id'], $customerRole]);
        } catch (Exception $e) {
            $err = 'Lỗi: '.$e->getMessage();
        }
    } elseif (isset($_POST['delete'])) {
        // Delete customer
        $stmt = $pdo->prepare("DELETE FROM nguoi_dung WHERE id=? AND vai_tro_id=?");
        try {
            $stmt->execute([$_POST['id'], $customerRole]);
        } catch (Exception $e) {
            $err = 'Lỗi: '.$e->getMessage();
        }
    }
}
// Get all customers
$stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE vai_tro_id=? ORDER BY id DESC");
$stmt->execute([$customerRole]);
$customers = $stmt->fetchAll();
?>
<h1>Quản lý khách hàng</h1>
<?php if ($err): ?><div style="color:red;"><?=htmlspecialchars($err)?></div><?php endif; ?>
<table border="1" cellpadding="6" style="border-collapse:collapse;">
    <tr>
        <th>ID</th><th>Email</th><th>SĐT</th><th>Họ tên</th><th>Trạng thái</th><th>Hành động</th>
    </tr>
    <?php foreach ($customers as $c): ?>
    <tr>
        <td><?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td><?= htmlspecialchars($c['sdt']) ?></td>
        <td><?= htmlspecialchars($c['ho_ten']) ?></td>
        <td><?= htmlspecialchars($c['trang_thai']) ?></td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($c['email']) ?>">
                <input type="hidden" name="sdt" value="<?= htmlspecialchars($c['sdt']) ?>">
                <input type="hidden" name="ho_ten" value="<?= htmlspecialchars($c['ho_ten']) ?>">
                <button name="edit" type="button" onclick="showEdit(this.form)">Sửa</button>
                <button name="delete" type="submit" onclick="return confirm('Xóa khách hàng này?')">Xóa</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<h2>Thêm khách hàng</h2>
<form method="post">
    <input name="email" placeholder="Email" required>
    <input name="sdt" placeholder="SĐT">
    <input name="ho_ten" placeholder="Họ tên" required>
    <input name="mat_khau" type="password" placeholder="Mật khẩu" required>
    <button name="add" type="submit">Thêm</button>
</form>
<div id="editBox" style="display:none;">
    <h2>Sửa khách hàng</h2>
    <form method="post" id="editForm">
        <input name="id" type="hidden">
        <input name="email" placeholder="Email" required>
        <input name="sdt" placeholder="SĐT">
        <input name="ho_ten" placeholder="Họ tên" required>
        <button name="edit" type="submit">Lưu</button>
        <button type="button" onclick="document.getElementById('editBox').style.display='none'">Hủy</button>
    </form>
</div>
<script>
function showEdit(form) {
    var box = document.getElementById('editBox');
    var f = document.getElementById('editForm');
    f.id.value = form.id.value;
    f.email.value = form.email.value;
    f.sdt.value = form.sdt.value;
    f.ho_ten.value = form.ho_ten.value;
    box.style.display = 'block';
}
</script>
