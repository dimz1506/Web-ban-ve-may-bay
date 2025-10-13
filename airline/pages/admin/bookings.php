<?php
// pages/bookings.php — Quản lý đơn đặt (Bookings) — ADMIN
if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_login(['ADMIN', 'STAFF']);
$pdo = db();


$user = null;
if (function_exists('me')) {
    $user = me();
} elseif (function_exists('current_user')) {
    $user = current_user();
}

$isAdmin = false;
$isStaff = false;

$role = null;
if ($user) {
    // điều chỉnh key nếu project bạn dùng tên khác cho cột role
    $role = strtoupper((string)($user['role'] ?? $user['role_ma'] ?? $user['vai_tro_ma'] ?? ''));
    $isAdmin = ($role === 'ADMIN');
    $isStaff = ($role === 'STAFF');
}

function flash_ok($m){ flash_set('ok',$m); }
function flash_err($m){ flash_set('err',$m); }

// Helper: map trạng thái -> label
$status_map = [
    'CHUA_XUAT' => 'Chưa xuất',
    'DA_XUAT'   => 'Đã xuất',
    'HUY'       => 'Đã huỷ',
    'HOAN'      => 'Hoàn vé',
];

// ---------- DYNAMIC SCHEMA DETECTION FOR hanh_khach AND chuyen_bay ----------
function get_table_columns(PDO $pdo, string $table) : array {
    try {
        $st = $pdo->prepare("
            SELECT LOWER(COLUMN_NAME) AS col
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
        ");
        $st->execute([$table]);
        $cols = $st->fetchAll(PDO::FETCH_COLUMN, 0);
        return $cols ?: [];
    } catch (Throwable $e) {
        error_log("Không thể đọc schema $table: " . $e->getMessage());
        return []; // fallback to empty (we'll handle later)
    }
}

$hk_cols = get_table_columns($pdo, 'hanh_khach');
$cb_cols = get_table_columns($pdo, 'chuyen_bay');

$hk_has = function($n) use ($hk_cols) { return in_array(strtolower($n), $hk_cols, true); };
$cb_has = function($n) use ($cb_cols) { return in_array(strtolower($n), $cb_cols, true); };

// Build hk select parts depending on existing columns
$hk_select_parts = [];
if ($hk_has('ho_ten')) $hk_select_parts[] = "hk.ho_ten AS passenger_name";
if ($hk_has('sdt')) $hk_select_parts[] = "hk.sdt AS passenger_phone";
if ($hk_has('email')) $hk_select_parts[] = "hk.email AS passenger_email";
// if none found, still select NULL to keep consistent indices
if (empty($hk_select_parts)) $hk_select_parts[] = "NULL AS passenger_name";

// Build chuyen_bay select parts depending on existing columns
$cb_select_parts = [];
if ($cb_has('so_hieu')) $cb_select_parts[] = "cb.so_hieu AS flight_no";
if ($cb_has('ma_tuyen')) $cb_select_parts[] = "cb.ma_tuyen";
if ($cb_has('gio_di')) $cb_select_parts[] = "cb.gio_di";
if ($cb_has('gio_den')) $cb_select_parts[] = "cb.gio_den";
// if none found, put placeholder
if (empty($cb_select_parts)) $cb_select_parts[] = "NULL AS flight_no";

// ---------- Handle POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'change_status') {
            $id = (int)($_POST['id'] ?? 0);
            $new = $_POST['new_status'] ?? '';
            if ($id <= 0) throw new RuntimeException('Thiếu ID vé.');
            if (!in_array($new, array_keys($status_map), true)) throw new RuntimeException('Trạng thái không hợp lệ.');

            // Cập nhật trạng thái
            $pdo->prepare('UPDATE ve SET trang_thai=?, cap_nhat_luc = NOW() WHERE id=?')->execute([$new, $id]);
            flash_ok('Đã cập nhật trạng thái vé #' . $id . ' → ' . $status_map[$new]);

        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Thiếu ID vé.');
            $pdo->prepare('DELETE FROM ve WHERE id=?')->execute([$id]);
            flash_ok('Đã xóa vé #' . $id);

        } else {
            throw new RuntimeException('Hành động không hợp lệ.');
        }
    } catch (Throwable $e) {
        flash_err($e->getMessage());
    }
    // giữ lại filter/page khi redirect
    $qs = [];
    if (!empty($_GET['q'])) $qs[] = 'q=' . urlencode((string)$_GET['q']);
    if (!empty($_GET['status'])) $qs[] = 'status=' . urlencode((string)$_GET['status']);
    if (!empty($_GET['flight'])) $qs[] = 'flight=' . urlencode((string)$_GET['flight']);
    if (!empty($_GET['page'])) $qs[] = 'page=' . urlencode((string)$_GET['page']);
    redirect('index.php?p=bookings' . ($qs ? '&' . implode('&', $qs) : ''));
}

// ===== Filters & pagination
$q = trim($_GET['q'] ?? '');             // tìm kiếm mã vé / tên hành khách / email/phone (tuỳ có)
$status = $_GET['status'] ?? '';         // trạng thái
$flight = trim($_GET['flight'] ?? '');   // số hiệu chuyến (so_hieu)
$page = max(1, (int)($_GET['page'] ?? 1));
$perpage = 15;
$offset = ($page - 1) * $perpage;

$where = [];
$args = [];

// build searchable fields dynamically
$search_clauses = ["v.so_ve LIKE ?"]; // always can search so_ve
$search_args = ['%' . $q . '%'];
if ($hk_has('ho_ten')) { $search_clauses[] = "hk.ho_ten LIKE ?"; $search_args[] = '%' . $q . '%'; }
if ($hk_has('sdt'))     { $search_clauses[] = "hk.sdt LIKE ?";     $search_args[] = '%' . $q . '%'; }
if ($hk_has('email'))   { $search_clauses[] = "hk.email LIKE ?";   $search_args[] = '%' . $q . '%'; }

if ($q !== '') {
    $where[] = '(' . implode(' OR ', $search_clauses) . ')';
    foreach ($search_args as $sa) $args[] = $sa;
}

if ($status !== '' && in_array($status, array_keys($status_map), true)) {
    $where[] = 'v.trang_thai = ?';
    $args[] = $status;
}
if ($flight !== '') {
    // only add flight filter if cb has so_hieu
    if ($cb_has('so_hieu')) {
        $where[] = 'cb.so_hieu LIKE ?';
        $args[] = '%' . $flight . '%';
    }
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$count_sql = "
    SELECT COUNT(*) FROM ve v
    LEFT JOIN hanh_khach hk ON hk.id = v.hanh_khach_id
    LEFT JOIN chuyen_bay cb ON cb.id = v.chuyen_bay_id
    $where_sql
";
$st = $pdo->prepare($count_sql);
$st->execute($args);
$total = (int)$st->fetchColumn();
$pages = (int)ceil($total / $perpage);

// Build main SELECT dynamically
$select_parts = [
    "v.id", "v.so_ve", "v.trang_thai", "v.phat_hanh_luc"
];
$select_parts = array_merge($select_parts, $hk_select_parts, $cb_select_parts);
$select_sql = implode(",\n    ", $select_parts);

// Main query: fetch bookings
$sql = "
  SELECT
    {$select_sql}
  FROM ve v
  LEFT JOIN hanh_khach hk ON hk.id = v.hanh_khach_id
  LEFT JOIN chuyen_bay cb ON cb.id = v.chuyen_bay_id
  $where_sql
  ORDER BY v.phat_hanh_luc DESC, v.id DESC
  LIMIT $perpage OFFSET $offset
";
$st = $pdo->prepare($sql);
$st->execute($args);
$bookings = $st->fetchAll(PDO::FETCH_ASSOC);

// Helper to build querystring for pagination links
function qs(array $keep = []) {
    $params = [];
    foreach (['q','status','flight','page'] as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') $params[$k] = $_GET[$k];
    }
    foreach ($keep as $k => $v) $params[$k] = $v;
    return $params ? '&' . http_build_query($params) : '';
}

include __DIR__ . '/../../templates/booking_view.php';

?>
