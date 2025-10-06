<?php
// pages/flights.php — Quản lý chuyến bay & lịch bay
if (!function_exists('db')) {
        require_once dirname(__DIR__) . '/config.php';
}
require_login(['ADMIN']);

$pdo = db();

function flash_ok($m)
{
        flash_set('ok', $m);
}
function flash_err($m)
{
        flash_set('err', $m);
}

// ====== Danh mục ======
$routes = $pdo->query("SELECT id, ma_tuyen, di, den FROM tuyen_bay ORDER BY ma_tuyen")->fetchAll();
$routeMap = [];
foreach ($routes as $r) {
        $routeMap[(int)$r['id']] = $r;
}

$planes = $pdo->query("SELECT id, so_dang_ba, dong_may_bay FROM tau_bay ORDER BY id DESC")->fetchAll();
$planeMap = [];
foreach ($planes as $a) {
        $planeMap[(int)$a['id']] = $a;
}

$classes = $pdo->query("SELECT id, ma, ten FROM hang_ghe ORDER BY id")->fetchAll();
$classMap = [];
foreach ($classes as $c) {
        $classMap[(int)$c['id']] = $c;
}

// Helper: validate datetime
function dt_valid($s)
{
        return $s && (DateTime::createFromFormat('Y-m-d\TH:i', $s) || DateTime::createFromFormat('Y-m-d H:i:s', $s));
}
function to_mysql_dt($s)
{
        // chấp nhận 'YYYY-mm-ddTHH:ii' (input datetime-local) hoặc 'YYYY-mm-dd HH:ii:ss'
        if (DateTime::createFromFormat('Y-m-d\TH:i', $s)) {
                return (new DateTime($s))->format('Y-m-d H:i:s');
        }
        return (new DateTime($s))->format('Y-m-d H:i:s');
}

// ====== Xử lý POST ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_post_csrf();
        $action = $_POST['action'] ?? '';

        try {
                if ($action === 'create' || $action === 'update') {
                        $id        = (int)($_POST['id'] ?? 0);
                        $so_hieu   = trim($_POST['so_hieu'] ?? '');
                        $route_id  = (int)($_POST['tuyen_bay_id'] ?? 0);
                        $plane_id  = isset($_POST['tau_bay_id']) && $_POST['tau_bay_id'] !== '' ? (int)$_POST['tau_bay_id'] : null;
                        $gio_di_in = trim($_POST['gio_di'] ?? '');
                        $gio_den_in = trim($_POST['gio_den'] ?? '');
                        $trang_thai = $_POST['trang_thai'] ?? 'LEN_KE_HOACH';
                        $tien_te   = $_POST['tien_te'] ?? 'VND';

                        if ($so_hieu === '') throw new RuntimeException('Vui lòng nhập số hiệu.');
                        if (!$route_id || !isset($routeMap[$route_id])) throw new RuntimeException('Tuyến bay không hợp lệ.');
                        if ($plane_id !== null && !isset($planeMap[$plane_id])) $plane_id = null;
                        if (!dt_valid($gio_di_in) || !dt_valid($gio_den_in)) throw new RuntimeException('Thời gian không hợp lệ.');
                        $gio_di  = to_mysql_dt($gio_di_in);
                        $gio_den = to_mysql_dt($gio_den_in);
                        if (strtotime($gio_den) <= strtotime($gio_di)) throw new RuntimeException('Giờ đến phải sau giờ đi.');

                        if (!in_array($trang_thai, ['LEN_KE_HOACH', 'HUY', 'TRE', 'DA_CAT_CANH', 'DA_HA_CANH'], true)) {
                                $trang_thai = 'LEN_KE_HOACH';
                        }

                        if (!in_array($tien_te, ['VND', 'USD', 'EUR'], true)) $tien_te = 'VND';

                        if ($action === 'create') {
                                $sql = "INSERT INTO chuyen_bay(so_hieu,tuyen_bay_id,tau_bay_id,gio_di,gio_den,trang_thai,tien_te)
                VALUES(?,?,?,?,?,?,?)";
                                $pdo->prepare($sql)->execute([$so_hieu, $route_id, $plane_id, $gio_di, $gio_den, $trang_thai, $tien_te]);
                                $flightId = (int)$pdo->lastInsertId();

                                // Lưu giá hạng ghế (nếu có)
                                if (!empty($_POST['fare'])) {
                                        $ins = $pdo->prepare("INSERT INTO chuyen_bay_gia_hang(chuyen_bay_id,hang_ghe_id,gia_co_ban,so_ghe_con,hanh_ly_kg,duoc_hoan,phi_doi)
                                VALUES(?,?,?,?,?,?,?)
                                ON DUPLICATE KEY UPDATE gia_co_ban=VALUES(gia_co_ban), so_ghe_con=VALUES(so_ghe_con),
                                hanh_ly_kg=VALUES(hanh_ly_kg), duoc_hoan=VALUES(duoc_hoan), phi_doi=VALUES(phi_doi)");
                                        foreach ($_POST['fare'] as $cid => $f) {
                                                $cid = (int)$cid;
                                                if (!isset($classMap[$cid])) continue;
                                                $gia = (float)($f['gia'] ?? 0);
                                                $ghe = max(0, (int)($f['so_ghe'] ?? 0));
                                                $kg  = max(0, (int)($f['kg'] ?? 0));
                                                $hoan = isset($f['hoan']) ? 1 : 0;
                                                $phi = max(0, (float)($f['phi_doi'] ?? 0));
                                                if ($gia > 0) $ins->execute([$flightId, $cid, $gia, $ghe, $kg, $hoan, $phi]);
                                        }
                                }

                                flash_ok('Đã tạo chuyến bay.');
                        } else {
                                if ($id <= 0) throw new RuntimeException('Thiếu ID chuyến bay.');
                                $sql = "UPDATE chuyen_bay SET so_hieu=?, tuyen_bay_id=?, tau_bay_id=?, gio_di=?, gio_den=?, trang_thai=?, tien_te=? WHERE id=?";
                                $pdo->prepare($sql)->execute([$so_hieu, $route_id, $plane_id, $gio_di, $gio_den, $trang_thai, $tien_te, $id]);

                                // Cập nhật giá hạng ghế
                                if (!empty($_POST['fare'])) {
                                        $ins = $pdo->prepare("INSERT INTO chuyen_bay_gia_hang(chuyen_bay_id,hang_ghe_id,gia_co_ban,so_ghe_con,hanh_ly_kg,duoc_hoan,phi_doi)
                                VALUES(?,?,?,?,?,?,?)
                                ON DUPLICATE KEY UPDATE gia_co_ban=VALUES(gia_co_ban), so_ghe_con=VALUES(so_ghe_con),
                                hanh_ly_kg=VALUES(hanh_ly_kg), duoc_hoan=VALUES(duoc_hoan), phi_doi=VALUES(phi_doi)");
                                        foreach ($_POST['fare'] as $cid => $f) {
                                                $cid = (int)$cid;
                                                if (!isset($classMap[$cid])) continue;
                                                $gia = (float)($f['gia'] ?? 0);
                                                $ghe = max(0, (int)($f['so_ghe'] ?? 0));
                                                $kg  = max(0, (int)($f['kg'] ?? 0));
                                                $hoan = isset($f['hoan']) ? 1 : 0;
                                                $phi = max(0, (float)($f['phi_doi'] ?? 0));
                                                // xóa when gia=0 && so_ghe=0? -> tùy chính sách; ở đây nếu giá=0 thì bỏ qua
                                                if ($gia > 0) $ins->execute([$id, $cid, $gia, $ghe, $kg, $hoan, $phi]);
                                        }
                                }

                                flash_ok('Đã cập nhật chuyến bay.');
                        }
                } elseif ($action === 'delete') {
                        $id = (int)($_POST['id'] ?? 0);
                        if ($id <= 0) throw new RuntimeException('Thiếu ID chuyến bay.');

                        // Kiểm tra ràng buộc vé đã xuất (tránh lỗi FK)
                        $cnt = $pdo->prepare("SELECT COUNT(*) FROM ve WHERE chuyen_bay_id=?");
                        $cnt->execute([$id]);
                        if ((int)$cnt->fetchColumn() > 0) {
                                throw new RuntimeException('Không thể xóa: đã có vé/liên quan.');
                        }

                        // Xóa giá hạng ghế của chuyến (ON DELETE CASCADE đã có, nhưng gọi rõ ràng cho chắc)
                        $pdo->prepare("DELETE FROM chuyen_bay_gia_hang WHERE chuyen_bay_id=?")->execute([$id]);
                        $pdo->prepare("DELETE FROM chuyen_bay WHERE id=?")->execute([$id]);
                        flash_ok('Đã xóa chuyến bay #' . $id);
                } else {
                        throw new RuntimeException('Hành động không hợp lệ.');
                }
        } catch (Throwable $e) {
                flash_err($e->getMessage());
        }

        // giữ lại filter hiện tại khi quay về
        $qs = [];
        foreach (['q', 'route', 'status', 'from', 'to'] as $k) {
                if (isset($_GET[$k]) && $_GET[$k] !== '') {
                        $qs[] = "$k=" . urlencode((string)$_GET[$k]);
                }
        }
        $suffix = $qs ? '&' . implode('&', $qs) : '';
        redirect('index.php?p=flights' . $suffix);
}

// ====== Lọc & tìm kiếm ======
$q       = trim($_GET['q'] ?? '');
$routeId = (int)($_GET['route'] ?? 0);
$status  = $_GET['status'] ?? '';
$from    = trim($_GET['from'] ?? '');
$to      = trim($_GET['to'] ?? '');

$where = [];
$args = [];
if ($q !== '') {
        $where[] = "(cb.so_hieu LIKE ?)";
        $args[] = '%' . $q . '%';
}
if ($routeId > 0) {
        $where[] = "cb.tuyen_bay_id=?";
        $args[] = $routeId;
}
if (in_array($status, ['LEN_KE_HOACH', 'HUY', 'TRE', 'DA_CAT_CANH', 'DA_HA_CANH'], true)) {
        $where[] = "cb.trang_thai=?";
        $args[] = $status;
}
if ($from !== '' && dt_valid($from)) {
        $where[] = "cb.gio_di >= ?";
        $args[] = to_mysql_dt($from);
}
if ($to !== '' && dt_valid($to)) {
        $where[] = "cb.gio_di <= ?";
        $args[] = to_mysql_dt($to);
}

$sql = "SELECT cb.*, tb.ma_tuyen, tb.di, tb.den,
               a.so_dang_ba, a.dong_may_bay
        FROM chuyen_bay cb
        JOIN tuyen_bay tb ON tb.id=cb.tuyen_bay_id
        LEFT JOIN tau_bay a ON a.id=cb.tau_bay_id
        " . ($where ? (' WHERE ' . implode(' AND ', $where)) : '') . "
        ORDER BY cb.gio_di DESC, cb.id DESC
        LIMIT 300";
$st = $pdo->prepare($sql);
$st->execute($args);
$flights = $st->fetchAll();

// Nếu edit
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
$fares = [];
if ($edit_id > 0) {
        foreach ($flights as $f) {
                if ((int)$f['id'] === $edit_id) {
                        $edit_row = $f;
                        break;
                }
        }
        if (!$edit_row) {
                $st2 = $pdo->prepare("SELECT cb.*, tb.ma_tuyen, tb.di, tb.den,
                               a.so_dang_ba, a.dong_may_bay
                        FROM chuyen_bay cb
                        JOIN tuyen_bay tb ON tb.id=cb.tuyen_bay_id
                        LEFT JOIN tau_bay a ON a.id=cb.tau_bay_id
                        WHERE cb.id=? LIMIT 1");
                $st2->execute([$edit_id]);
                $edit_row = $st2->fetch();
        }
        if ($edit_row) {
                $fares = $pdo->prepare("SELECT * FROM chuyen_bay_gia_hang WHERE chuyen_bay_id=?");
                $fares->execute([$edit_id]);
                $fares = $fares->fetchAll();
                $fareMap = [];
                foreach ($fares as $f) {
                        $fareMap[(int)$f['hang_ghe_id']] = $f;
                }
                $fares = $fareMap;
        }
}
//goi view
include dirname(__DIR__).'/../templates/flights_view.php';
?>


