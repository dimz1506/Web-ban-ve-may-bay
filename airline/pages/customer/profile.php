<?php
require_once dirname(__DIR__,2).'/includes/db.php';
require_once dirname(__DIR__,2).'/includes/auth.php';
require_login(['CUSTOMER']);
$user = me();
$pdo = db();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update phone and name
    $stmt = $pdo->prepare("UPDATE nguoi_dung SET sdt=?, ho_ten=? WHERE id=?");
    try {
        $stmt->execute([
            $_POST['sdt'],
            $_POST['ho_ten'],
            $user['id']
        ]);
        $user['sdt'] = $_POST['sdt'];
        $user['ho_ten'] = $_POST['ho_ten'];
        $_SESSION['user']['ho_ten'] = $_POST['ho_ten'];
    } catch (Exception $e) {
        $err = 'Lỗi: '.$e->getMessage();
    }
}
// Get latest info
$stmt = $pdo->prepare("SELECT email, sdt, ho_ten FROM nguoi_dung WHERE id=?");
$stmt->execute([$user['id']]);
$info = $stmt->fetch();
?>
<h1>Hồ sơ cá nhân</h1>
<?php if ($err): ?><div style="color:red;"><?=htmlspecialchars($err)?></div><?php endif; ?>
<form method="post">
    <label>Email:</label>
    <input name="email" value="<?=htmlspecialchars($info['email'])?>" readonly><br>
    <label>SĐT:</label>
    <input name="sdt" value="<?=htmlspecialchars($info['sdt'])?>"><br>
    <label>Họ tên:</label>
    <input name="ho_ten" value="<?=htmlspecialchars($info['ho_ten'])?>"><br>
    <button type="submit">Cập nhật</button>
</form>

