<?php
// pages/customer_dashboard.php — Trang riêng cho Khách hàng 
// Giữ require_login để bảo đảm chỉ khách hàng đã đăng nhập mới vào được.
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

// Lấy user hiện tại an toàn
$user = null;
$displayName = 'Khách';
if (function_exists('me')) {
    try {
        $u = me();
        if (!empty($u) && is_array($u)) {
            $user = $u;
            $displayName = htmlspecialchars($user['ho_ten'] ?? $user['email'] ?? 'Khách', ENT_QUOTES, 'UTF-8');
        }
    } catch (Throwable $e) {
        // nếu me() lỗi thì giữ $displayName mặc định
        $user = null;
    }
}

// Lấy danh sách chuyến bay trong 2 ngày gần nhất — phiên bản "tự động dò cột tuyen_bay"
$flights = [];
try {
    $pdo = db();

    // 1) lấy danh sách column của bảng tuyen_bay
    $colStmt = $pdo->prepare("
        SELECT COLUMN_NAME
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'tuyen_bay'
    ");
    $colStmt->execute();
    $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $cols = array_map('strtolower', $cols); // chuẩn hóa

    // 2) danh sách tên cột ứng viên cho "diem_di" và "diem_den"
    $candidates_from = ['diem_di','san_bay_di','from_airport','origin','departure_code','departure_airport','ma_di','code_from'];
    $candidates_to   = ['diem_den','san_bay_den','to_airport','destination','arrival_code','arrival_airport','ma_den','code_to'];

    $fromCol = null; $toCol = null;
    foreach ($candidates_from as $c) {
        if (in_array(strtolower($c), $cols)) { $fromCol = $c; break; }
    }
    foreach ($candidates_to as $c) {
        if (in_array(strtolower($c), $cols)) { $toCol = $c; break; }
    }

    // Nếu không tìm được, fallback sang tên mặc định (sẽ trả NULL nếu không tồn tại)
    if ($fromCol === null) $fromCol = $cols[0] ?? null;
    if ($toCol === null)   $toCol   = $cols[1] ?? null;

    // Build SELECT — map về alias diem_di, diem_den để phần hiển thị không đổi
    $selectParts = [
        "cb.id", "cb.so_hieu", "cb.gio_di", "cb.gio_den", "cb.trang_thai"
    ];
    if ($fromCol) $selectParts[] = "tb.`{$fromCol}` AS diem_di";
    else $selectParts[] = "NULL AS diem_di";
    if ($toCol) $selectParts[] = "tb.`{$toCol}` AS diem_den";
    else $selectParts[] = "NULL AS diem_den";

    $sql = "
        SELECT " . implode(", ", $selectParts) . "
        FROM chuyen_bay cb
        LEFT JOIN tuyen_bay tb ON cb.tuyen_bay_id = tb.id
        WHERE cb.trang_thai IN ('LEN_KE_HOACH', 'TRE')
          AND cb.gio_di BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 DAY)
        ORDER BY cb.gio_di ASC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    // trong dev bạn có thể log $e->getMessage()
    $flights = [];
}


  include dirname(__DIR__).'/../templates/customer_dashboard_view.php';
?>
