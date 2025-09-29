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
?>
<!doctype html>
<html lang="vi">

<head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Quản lý chuyến bay | VNAir Ticket</title>
        <link rel="stylesheet" href="assets/home.css">
        <style>
                .tbl {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 12px
                }

                .tbl th,
                .tbl td {
                        border: 1px solid var(--border);
                        padding: 10px;
                        text-align: left;
                        vertical-align: top
                }

                .card {
                        background: #fff;
                        border: 1px solid var(--border);
                        border-radius: 10px;
                        padding: 14px;
                        margin: 12px 0
                }

                .grid {
                        display: grid;
                        grid-template-columns: repeat(12, 1fr);
                        gap: 12px
                }

                form.inline {
                        display: inline
                }

                .muted {
                        color: #666
                }
        </style>
</head>

<body>
        <!-- <header class="topbar">
                <div class="container nav">
                        <div class="brand">
                                <div class="logo">✈</div>
                                <div>VNAir Ticket</div>
                        </div>
                        <nav>
                                <a href="<?= APP_BASE ?>/index.php?p=admin">Admin</a>
                                <a href="<?= APP_BASE ?>/index.php?p=flights">Quản lý chuyến bay</a>
                                <a href="<?= APP_BASE ?>/index.php?p=users">Quản lý tài khoản</a>
                                <a href="<?= APP_BASE ?>/index.php?p=promotions">KKhuyến mãi</a>
                        </nav>
                        <div class="nav-cta">
                                <a class="btn outline" href="index.php?p=logout">Đăng xuất</a>
                        </div>
                </div>
        </header>  -->
        <?php include dirname(__DIR__) . '/../includes/header.php'; ?>

        <main class="container">
                <?php if ($m = flash_get('ok')): ?><div class="ok"><?= $m ?></div><?php endif; ?>
                <?php if ($m = flash_get('err')): ?><div class="err" style="display:block"><?= $m ?></div><?php endif; ?>

                <h2>Quản lý chuyến bay</h2>

                <!-- Bộ lọc -->
                <form class="card" method="get" action="index.php">
                        <input type="hidden" name="p" value="flights">
                        <div class="grid">
                                <div class="field" style="grid-column: span 3;">
                                        <label for="q">Số hiệu</label>
                                        <input id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="VD: VN123">
                                </div>
                                <div class="field" style="grid-column: span 3;">
                                        <label for="route">Tuyến</label>
                                        <select id="route" name="route">
                                                <option value="0">-- Tất cả --</option>
                                                <?php foreach ($routes as $r): ?>
                                                        <option value="<?= $r['id'] ?>" <?= $routeId === $r['id'] ? 'selected' : '' ?>><?= $r['ma_tuyen'] ?> (<?= $r['di'] ?>→<?= $r['den'] ?>)</option>
                                                <?php endforeach; ?>
                                        </select>
                                </div>
                                <div class="field" style="grid-column: span 3;">
                                        <label for="status">Trạng thái</label>
                                        <select id="status" name="status">
                                                <option value="">-- Tất cả --</option>
                                                <?php foreach (['LEN_KE_HOACH', 'HUY', 'TRE', 'DA_CAT_CANH', 'DA_HA_CANH'] as $s): ?>
                                                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                        </select>
                                </div>
                                <div class="field" style="grid-column: span 1;">
                                        <label for="from">Từ ngày</label>
                                        <input id="from" name="from" type="datetime-local" value="<?= htmlspecialchars($from) ?>">
                                </div>
                                <div class="field" style="grid-column: span 1;">
                                        <label for="to">Đến ngày</label>
                                        <input id="to" name="to" type="datetime-local" value="<?= htmlspecialchars($to) ?>">
                                </div>
                                <div class="submit-row" style="grid-column: span 1;display:flex;align-items:end;justify-content:flex-end">
                                        <button class="btn" type="submit">Lọc</button>
                                </div>
                        </div>
                </form>

                <!-- Form tạo/sửa -->
                <div class="card">
                        <h3><?= $edit_row ? 'Sửa chuyến #' . (int)$edit_row['id'] : 'Thêm chuyến bay' ?></h3>
                        <form method="post" autocomplete="off">
                                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= (int)$edit_row['id'] ?>"><?php endif; ?>
                                <div class="grid">
                                        <div class="field" style="grid-column: span 3;">
                                                <label>Số hiệu</label>
                                                <input name="so_hieu" required value="<?= htmlspecialchars($edit_row['so_hieu'] ?? '') ?>" placeholder="VN123">
                                        </div>
                                        <div class="field" style="grid-column: span 3;">
                                                <label>Tuyến</label>
                                                <select name="tuyen_bay_id" required>
                                                        <?php foreach ($routes as $r): ?>
                                                                <option value="<?= $r['id'] ?>" <?= isset($edit_row['tuyen_bay_id']) && (int)$edit_row['tuyen_bay_id'] === $r['id'] ? 'selected' : '' ?>>
                                                                        <?= $r['ma_tuyen'] ?> (<?= $r['di'] ?>→<?= $r['den'] ?>)
                                                                </option>
                                                        <?php endforeach; ?>
                                                </select>
                                                <div class="muted">Nếu thiếu tuyến, hãy tạo trong phần quản trị tuyến_bay.</div>
                                        </div>
                                        <div class="field" style="grid-column: span 3;">
                                                <label>Tàu bay (tuỳ chọn)</label>
                                                <select name="tau_bay_id">
                                                        <option value="">-- Chưa gán --</option>
                                                        <?php foreach ($planes as $a): ?>
                                                                <option value="<?= $a['id'] ?>" <?= isset($edit_row['tau_bay_id']) && (string)$edit_row['tau_bay_id'] === (string)$a['id'] ? 'selected' : '' ?>>
                                                                        <?= $a['so_dang_ba'] ?: ('ID ' . $a['id']) ?> — <?= $a['dong_may_bay'] ?>
                                                                </option>
                                                        <?php endforeach; ?>
                                                </select>
                                        </div>
                                        <div class="field" style="grid-column: span 3;">
                                                <label>Trạng thái</label>
                                                <select name="trang_thai">
                                                        <?php foreach (['LEN_KE_HOACH', 'HUY', 'TRE', 'DA_CAT_CANH', 'DA_HA_CANH'] as $s): ?>
                                                                <option <?= $edit_row && $edit_row['trang_thai'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                                        <?php endforeach; ?>
                                                </select>
                                        </div>
                                        <div class="field" style="grid-column: span 3;">
                                                <label>Giờ đi</label>
                                                <input name="gio_di" type="datetime-local" required
                                                        value="<?= isset($edit_row['gio_di']) ? date('Y-m-d\TH:i', strtotime($edit_row['gio_di'])) : '' ?>">
                                        </div>
                                        <div class="field" style="grid-column: span 3;">
                                                <label>Giờ đến</label>
                                                <input name="gio_den" type="datetime-local" required
                                                        value="<?= isset($edit_row['gio_den']) ? date('Y-m-d\TH:i', strtotime($edit_row['gio_den'])) : '' ?>">
                                        </div>
                                        <div class="field" style="grid-column: span 2;">
                                                <label>Tiền tệ</label>
                                                <select name="tien_te">
                                                        <?php foreach (['VND', 'USD', 'EUR'] as $ccy): ?>
                                                                <option <?= $edit_row && $edit_row['tien_te'] === $ccy ? 'selected' : '' ?>><?= $ccy ?></option>
                                                        <?php endforeach; ?>
                                                </select>
                                        </div>
                                </div>

                                <!-- Giá theo hạng ghế -->
                                <div class="card" style="margin-top:12px">
                                        <h4>Giá & chỗ theo hạng ghế</h4>
                                        <div class="grid">
                                                <?php foreach ($classes as $c):
                                                        $ex = $edit_row ? ($fares[$c['id']] ?? null) : null; ?>
                                                        <div class="field" style="grid-column: span 4;">
                                                                <div><b><?= $c['ma'] ?> - <?= $c['ten'] ?></b></div>
                                                                <label>Giá cơ bản
                                                                        <input name="fare[<?= $c['id'] ?>][gia]" type="number" step="0.01" min="0"
                                                                                value="<?= htmlspecialchars($ex['gia_co_ban'] ?? '') ?>">
                                                                </label>
                                                                <label>Số ghế còn
                                                                        <input name="fare[<?= $c['id'] ?>][so_ghe]" type="number" min="0"
                                                                                value="<?= htmlspecialchars($ex['so_ghe_con'] ?? '') ?>">
                                                                </label>
                                                                <label>Hành lý (kg)
                                                                        <input name="fare[<?= $c['id'] ?>][kg]" type="number" min="0"
                                                                                value="<?= htmlspecialchars($ex['hanh_ly_kg'] ?? '') ?>">
                                                                </label>
                                                                <label>Được hoàn?
                                                                        <input name="fare[<?= $c['id'] ?>][hoan]" type="checkbox" <?= !empty($ex) && (int)$ex['duoc_hoan'] === 1 ? 'checked' : '' ?>>
                                                                </label>
                                                                <label>Phí đổi
                                                                        <input name="fare[<?= $c['id'] ?>][phi_doi]" type="number" step="0.01" min="0"
                                                                                value="<?= htmlspecialchars($ex['phi_doi'] ?? '') ?>">
                                                                </label>
                                                        </div>
                                                <?php endforeach; ?>
                                        </div>
                                </div>

                                <div class="submit-row">
                                        <button class="btn" type="submit" name="action" value="<?= $edit_row ? 'update' : 'create' ?>">Lưu</button>
                                        <?php if ($edit_row): ?>
                                                <a class="btn outline" href="index.php?p=flights">Hủy</a>
                                        <?php endif; ?>
                                </div>
                        </form>
                </div>

                <!-- Bảng danh sách -->
                <div class="card">
                        <h3>Danh sách chuyến (<?= count($flights) ?>)</h3>
                        <table class="tbl">
                                <tr>
                                        <th>#</th>
                                        <th>Số hiệu</th>
                                        <th>Tuyến</th>
                                        <th>Giờ đi</th>
                                        <th>Giờ đến</th>
                                        <th>Trạng thái</th>
                                        <th>Tàu bay</th>
                                        <th></th>
                                </tr>
                                <?php foreach ($flights as $f): ?>
                                        <tr>
                                                <td><?= (int)$f['id'] ?></td>
                                                <td><?= htmlspecialchars($f['so_hieu']) ?></td>
                                                <td><?= htmlspecialchars($f['ma_tuyen']) ?> (<?= htmlspecialchars($f['di']) ?>→<?= htmlspecialchars($f['den']) ?>)</td>
                                                <td><?= date('Y-m-d H:i', strtotime($f['gio_di'])) ?></td>
                                                <td><?= date('Y-m-d H:i', strtotime($f['gio_den'])) ?></td>
                                                <td><?= htmlspecialchars($f['trang_thai']) ?></td>
                                                <td><?= $f['so_dang_ba'] ? htmlspecialchars($f['so_dang_ba'] . ' — ' . $f['dong_may_bay']) : '<span class="muted">Chưa gán</span>' ?></td>
                                                <td>
                                                        <a class="btn outline" href="index.php?p=flights&edit=<?= (int)$f['id'] ?>">Sửa</a>
                                                        <form method="post" class="inline" onsubmit="return confirm('Xóa chuyến #<?= (int)$f['id'] ?>?')">
                                                                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                                                <button class="btn" name="action" value="delete" type="submit">Xóa</button>
                                                        </form>
                                                </td>
                                        </tr>
                                <?php endforeach; ?>
                        </table>
                        <p class="muted">Hiển thị tối đa 300 bản ghi theo bộ lọc.</p>
                </div>
        </main>

        <footer>
                <div class="container">© <span id="y"></span> VNAir Ticket</div>
        </footer>
        <script>
                document.getElementById('y').textContent = new Date().getFullYear();
        </script>
</body>

</html>