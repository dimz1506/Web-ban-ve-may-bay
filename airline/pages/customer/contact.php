<?php
require_once dirname(__DIR__,2).'/includes/auth.php';
require_once dirname(__DIR__,2).'/includes/helpers.php';
require_login(['CUSTOMER']);
$user = me();
$err = '';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['message'] ?? '');
    if ($subject && $body) {
        $to = 'admin@localhost'; // Địa chỉ admin demo
        $fullBody = "Khách hàng: {$user['ho_ten']} ({$user['email']})\nChủ đề: $subject\nNội dung: $body";
        if (send_confirmation_email($to, "Yêu cầu hỗ trợ: $subject", $fullBody)) {
            $msg = 'Yêu cầu của bạn đã được gửi. Chúng tôi sẽ phản hồi sớm nhất.';
        } else {
            $err = 'Gửi yêu cầu thất bại.';
        }
    } else {
        $err = 'Vui lòng nhập đầy đủ chủ đề và nội dung.';
    }
}
?>
<h1>Liên hệ hỗ trợ</h1>
<?php if ($msg): ?><div style="color:green;"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<?php if ($err): ?><div style="color:red;"><?=htmlspecialchars($err)?></div><?php endif; ?>
<form method="post">
    <label>Chủ đề:</label><br>
    <input name="subject" required style="width:300px"><br>
    <label>Nội dung:</label><br>
    <textarea name="message" required style="width:300px;height:100px"></textarea><br>
    <button type="submit">Gửi yêu cầu</button>
</form>

