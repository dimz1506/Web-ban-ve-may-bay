<?php
// pages/contact_support.php
// Form "Liên hệ hỗ trợ" cho khách hàng (CUSTOMER)

require_once dirname(__DIR__,2).'/includes/auth.php';
require_once dirname(__DIR__,2).'/includes/helpers.php';
require_once dirname(__DIR__,2).'/includes/db.php'; // nếu bạn muốn lưu request vào DB (optional)

// Định nghĩa hàm send_mail nếu chưa có (simple version)
if (!function_exists('send_mail')) {
    /**
     * Gửi email đơn giản (plain text hoặc HTML)
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string|null $htmlBody
     * @return bool
     */
    function send_mail($to, $subject, $body, $htmlBody = null) {
        $headers = "From: no-reply@yourdomain.tld\r\n";
        $headers .= "Reply-To: no-reply@yourdomain.tld\r\n";
        if ($htmlBody) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            return mail($to, $subject, $htmlBody, $headers);
        } else {
            return mail($to, $subject, $body, $headers);
        }
    }
}

require_login(['CUSTOMER']);
$user = me(); // current user
$pdo = db();

$err = '';
$msg = '';
$subject = '';
$message = '';

// Simple rate-limit: allow one request per 60 seconds per session
$RATE_LIMIT_SECONDS = 60;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection if your app provides it
    if (function_exists('require_post_csrf')) {
        require_post_csrf();
    }

    // Rate-limit
    if (!isset($_SESSION)) session_start();
    $last_contact = $_SESSION['last_contact_time'] ?? 0;
    if (time() - $last_contact < $RATE_LIMIT_SECONDS) {
        $err = 'Bạn gửi yêu cầu quá nhanh. Vui lòng chờ ít phút rồi thử lại.';
    } else {
        // collect & trim
        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        // basic validation
        if ($subject === '' || $message === '') {
            $err = 'Vui lòng nhập cả chủ đề và nội dung.';
        } elseif (mb_strlen($subject) > 200) {
            $err = 'Chủ đề quá dài (tối đa 200 ký tự).';
        } elseif (mb_strlen($message) > 5000) {
            $err = 'Nội dung quá dài (tối đa 5000 ký tự).';
        } else {
            // prepare email
            $adminEmail = 'nhanvien@gmail.com'; // <-- thay bằng email nhân viên/hỗ trợ thực tế
            $userEmail = $user['email'] ?? null;
            $userName  = trim($user['ho_ten'] ?? ($userEmail ?: 'Khách'));

            // build email bodies (plain text + optional HTML)
            $plainBody = "Yêu cầu hỗ trợ từ khách hàng\n"
                       . "Tên: {$userName}\n"
                       . "Email: " . ($userEmail ?? '—') . "\n"
                       . "ID người dùng: " . ((int)($user['id'] ?? 0)) . "\n"
                       . "Thời gian: " . date('Y-m-d H:i:s') . "\n\n"
                       . "Chủ đề: {$subject}\n\n"
                       . "Nội dung:\n{$message}\n";

            $htmlBody = "<p><strong>Yêu cầu hỗ trợ từ khách hàng</strong></p>"
                      . "<p><strong>Tên:</strong> " . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . "<br>"
                      . "<strong>Email:</strong> " . htmlspecialchars($userEmail ?? '—', ENT_QUOTES, 'UTF-8') . "<br>"
                      . "<strong>ID:</strong> " . ((int)($user['id'] ?? 0)) . "<br>"
                      . "<strong>Thời gian:</strong> " . date('Y-m-d H:i:s') . "</p>"
                      . "<p><strong>Chủ đề:</strong> " . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "</p>"
                      . "<p><strong>Nội dung:</strong><br>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</p>";

            // Save to DB optional (uncomment if you have support_requests table)
            try {
                // Example schema: support_requests(id, user_id, subject, message, created_at, status)
                /*
                $ins = $pdo->prepare("INSERT INTO support_requests (user_id, subject, message, created_at, status) VALUES (?, ?, ?, NOW(), ?)");
                $ins->execute([(int)$user['id'], $subject, $message, 'NEW']);
                $requestId = (int)$pdo->lastInsertId();
                */
            } catch (Throwable $e) {
                // don't block sending email if DB save fails; log instead
                error_log("Lỗi lưu support request: " . $e->getMessage());
            }

            // send email to admin/support
            $sentToAdmin = false;
            try {
                // Prefer generic send_mail(to, subject, body, html) helper if available
                if (function_exists('send_mail')) {
                    // try HTML + plain text
                    $sentToAdmin = send_mail($adminEmail, "Yêu cầu hỗ trợ: " . $subject, $plainBody, $htmlBody);
                } elseif (function_exists('send_confirmation_email')) {
                    // fallback to your existing helper name used previously
                    $sentToAdmin = send_confirmation_email($adminEmail, "Yêu cầu hỗ trợ: " . $subject, $plainBody);
                } else {
                    // last resort: use PHP mail() (may not be configured)
                    $headers = "From: no-reply@yourdomain.tld\r\nReply-To: " . ($userEmail ?? 'no-reply@yourdomain.tld') . "\r\n";
                    $sentToAdmin = mail($adminEmail, "[Support] " . $subject, $plainBody, $headers);
                }
            } catch (Throwable $e) {
                error_log("Lỗi khi gửi email tới admin: " . $e->getMessage());
                $sentToAdmin = false;
            }

            // send confirmation to user (optional, non-blocking)
            $sentToUser = false;
            if ($userEmail && $sentToAdmin) {
                try {
                    $userConfirmSubject = "Xác nhận: Yêu cầu hỗ trợ của bạn — " . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
                    $userPlain = "Xin chào {$userName},\n\nChúng tôi đã nhận được yêu cầu hỗ trợ của bạn với chủ đề: {$subject}.\nChúng tôi sẽ liên hệ trong thời gian sớm nhất.\n\nNội dung bạn gửi:\n{$message}\n\nTrân trọng,\nĐội hỗ trợ.";
                    if (function_exists('send_mail')) {
                        $sentToUser = send_mail($userEmail, $userConfirmSubject, $userPlain);
                    } elseif (function_exists('send_confirmation_email')) {
                        $sentToUser = send_confirmation_email($userEmail, $userConfirmSubject, $userPlain);
                    } else {
                        $hdr = "From: support@yourdomain.tld\r\n";
                        $sentToUser = mail($userEmail, $userConfirmSubject, $userPlain, $hdr);
                    }
                } catch (Throwable $e) {
                    error_log("Lỗi gửi email xác nhận tới user: " . $e->getMessage());
                }
            }

            if ($sentToAdmin) {
                // mark last contact time to limit repeat submits
                $_SESSION['last_contact_time'] = time();

                // use flash + redirect to avoid form re-submit
                if (function_exists('flash_set') && function_exists('redirect')) {
                    flash_set('ok', 'Yêu cầu của bạn đã được gửi. Chúng tôi sẽ phản hồi sớm nhất.');
                    redirect('index.php?p=contact_support');
                    exit;
                } else {
                    $msg = 'Yêu cầu của bạn đã được gửi. Chúng tôi sẽ phản hồi sớm nhất.';
                    // clear form
                    $subject = $message = '';
                }
            } else {
                $err = 'Gửi yêu cầu thất bại. Vui lòng thử lại sau.';
            }
        }
    }
}

// Output HTML below (simple, clean)
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Liên hệ hỗ trợ | VNAir Ticket</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/home.css">
  <style>
    .container { max-width:900px; margin:24px auto; padding:0 16px; }
    .card { background:#fff; border:1px solid #e6e9ef; padding:18px; border-radius:12px; box-shadow:0 10px 30px rgba(2,6,23,0.03); }
    label { display:block; font-weight:700; margin-bottom:6px; }
    input[type="text"], textarea { width:100%; padding:10px; border-radius:8px; border:1px solid #e6e9ef; font-size:14px; }
    textarea { min-height:140px; resize:vertical; }
    .muted { color:#6b7280; font-size:13px; }
    .btn { background:#0b63d6; color:#fff; padding:10px 14px; border-radius:10px; border:0; cursor:pointer; font-weight:700; }
    .btn.ghost { background:transparent; color:#0b63d6; border:1px solid rgba(11,99,214,0.12); }
    .message.ok { background:rgba(22,163,74,0.08); color:#065f46; padding:10px; border-radius:8px; margin-bottom:12px; }
    .message.err { background:rgba(239,68,68,0.06); color:#7f1d1d; padding:10px; border-radius:8px; margin-bottom:12px; }
    .row { display:flex; gap:8px; justify-content:flex-end; margin-top:12px; }
    @media (max-width:640px){ .row { flex-direction:column-reverse; } }
  </style>
</head>
<body>
  <?php if (file_exists(dirname(__DIR__,2).'/includes/header.php')) include dirname(__DIR__,2).'/includes/header.php'; ?>

  <main class="container">
    <div class="card">
      <h2>Liên hệ hỗ trợ</h2>
      <p class="muted">Gửi chủ đề và nội dung. Chúng tôi sẽ phản hồi sớm nhất qua email.</p>

      <?php if ($msg): ?><div class="message ok"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <?php if ($err): ?><div class="message err"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

      <form method="post" autocomplete="off" novalidate>
        <?php if (function_exists('csrf_token')): ?>
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>

        <label for="subject">Chủ đề</label>
        <input id="subject" name="subject" type="text" maxlength="200" value="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="message" style="margin-top:8px">Nội dung</label>
        <textarea id="message" name="message" required><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></textarea>

        <div class="row">
          <a class="btn ghost" href="index.php?p=profile">Quay lại</a>
          <button class="btn" type="submit">Gửi yêu cầu</button>
        </div>
      </form>
    </div>
  </main>

  <?php if (file_exists(dirname(__DIR__,2).'/includes/footer.php')) include dirname(__DIR__,2).'/includes/footer.php'; ?>

</body>
</html>
