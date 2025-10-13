<?php
// pages/bookings.php — Quản lý đơn đặt (Bookings) — ADMIN
if (!function_exists('db')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_login(['ADMIN']);
$pdo = db();

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
?>


<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý đơn đặt | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <style>
    /* ---------- Refreshed admin bookings UI ---------- */
    :root{
      --bg:#f6f8fb;
      --card:#fff;
      --muted:#6b7280;
      --primary:#0b63d6;
      --accent:#0b63d6;
      --border:#e6e9ef;
      --radius:12px;
      --success:#16a34a;
      --danger:#ef4444;
    }
    body { background: var(--bg); color:#0f172a; font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial; margin:0; }
    .container { max-width:1200px; margin:20px auto; padding:0 16px; box-sizing:border-box; }

    /* Top row */
    .page-head { display:flex; gap:12px; align-items:center; justify-content:space-between; margin-bottom:14px; }
    .page-head h2 { margin:0; font-size:20px; }
    .page-actions { display:flex; gap:8px; align-items:center; }

    /* Card */
    .card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:14px; box-shadow:0 8px 24px rgba(15,23,42,0.04); margin-bottom:14px; }

    /* Filters */
    .filters { display:flex; gap:12px; flex-wrap:wrap; align-items:end; }
    .filters .field { min-width:160px; flex:1; }
    .filters label { display:block; font-weight:700; margin-bottom:6px; font-size:13px; color:var(--muted); }
    .filters input[type="text"], .filters select { width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--border); font-size:14px; background:#fff; box-sizing:border-box; }

    .btn { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; background:var(--primary); color:#fff; border:0; cursor:pointer; font-weight:700; text-decoration:none; }
    .btn.ghost { background:transparent; color:var(--primary); border:1px solid rgba(11,99,214,0.12); }
    .btn.small { padding:6px 8px; font-size:13px; border-radius:8px; }

    /* Table */
    .tbl-wrap { overflow:auto; border-radius:10px; border:1px solid var(--border); }
    table.tbl { width:100%; border-collapse:collapse; min-width:860px; }
    table.tbl thead th { position:sticky; top:0; background:linear-gradient(180deg,#fff,#fbfdff); padding:12px 14px; text-align:left; font-weight:700; border-bottom:1px solid var(--border); z-index:2; font-size:13px; color:var(--muted); }
    table.tbl th, table.tbl td { padding:12px 14px; border-bottom:1px solid rgba(15,23,42,0.04); vertical-align:middle; font-size:14px; }
    table.tbl tbody tr:hover { background: #fcfdff; }
    table.tbl tbody tr:nth-child(even) td { background: rgba(11,99,214,0.02); }

    .muted { color:var(--muted); font-size:13px; }

    /* badges */
    .badge { display:inline-block; padding:6px 10px; border-radius:999px; font-weight:700; font-size:13px; }
    .badge.chua { background:rgba(99,102,241,0.12); color:#6366f1; }
    .badge.xuat { background:rgba(16,185,129,0.12); color:#10b981; }
    .badge.huy { background:rgba(239,68,68,0.12); color:#ef4444; }
    .badge.hoan { background:rgba(102,126,234,0.08); color:#6b7280; }

    /* actions */
    .actions { display:flex; gap:8px; align-items:center; justify-content:flex-end; }
    .icon-btn { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:8px; border:1px solid transparent; background:transparent; cursor:pointer; }
    .icon-btn:hover { background:#f4f8ff; }

    .small-muted { font-size:12px; color:var(--muted); }

    /* pagination */
    .pagination { display:flex; gap:8px; align-items:center; justify-content:flex-end; padding-top:10px; }
    .page-btn { padding:6px 10px; border-radius:8px; border:1px solid var(--border); background:#fff; cursor:pointer; }
    .page-btn.active { background:var(--primary); color:#fff; border-color:var(--primary); }

    /* responsive: collapse columns on small screens */
    @media (max-width:920px) {
      .filters .field { min-width:140px; flex: 1 1 45%; }
      table.tbl { min-width:700px; }
    }
    @media (max-width:700px) {
      .page-head { flex-direction: column; align-items:flex-start; gap:10px; }
      .filters { flex-direction:column; align-items:stretch; }
      table.tbl { min-width:640px; }
      /* hide some columns */
      table.tbl th:nth-child(3), table.tbl td:nth-child(3) { display:none; } /* SĐT/Email */
      table.tbl th:nth-child(5), table.tbl td:nth-child(5) { display:none; } /* Thời gian */
    }

    /* modal */
    .modal-backdrop { display:none; position:fixed; inset:0; background:rgba(2,6,23,0.5); align-items:center; justify-content:center; z-index:400; }
    .modal { width:520px; max-width:92%; background:#fff; border-radius:12px; padding:18px; box-shadow:0 30px 60px rgba(2,6,23,0.3); }
    .modal h3 { margin:0 0 8px; }
    .modal .modal-body { color:var(--muted); margin-bottom:12px; }
    .modal .modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:8px; }
  </style>
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <div class="brand">
      <div class="logo">✈</div>
      <div>VNAir Ticket</div>
    </div>
  </div>
</header>
    
  <main class="container">
    <div class="page-head">
      <div>
        <h2>Quản lý đơn đặt</h2>
        <div class="muted" style="margin-top:6px">Xem, lọc, và quản lý các vé / đơn đặt.</div>
      </div>
      <div class="page-actions">
        <a class="btn ghost" href="index.php?p=admin">Quay lại</a>

      </div>
    </div>

    <!-- Filters -->
    <div class="card">
      <form class="filters" method="get" action="index.php" aria-label="Bộ lọc vé">
        <input type="hidden" name="p" value="bookings">
        <div class="field" style="flex:2;">
          <label for="q">Tìm kiếm</label>
          <input id="q" name="q" placeholder="mã vé, tên khách, SĐT" value="<?= htmlspecialchars($q ?? '') ?>">
        </div>

        <div class="field" style="flex:1;">
          <label for="flight">Số hiệu chuyến</label>
          <input id="flight" name="flight" placeholder="VD: VN123" value="<?= htmlspecialchars($flight ?? '') ?>">
        </div>

        <div class="field" style="flex:1;min-width:160px;">
          <label for="status">Trạng thái</label>
          <select id="status" name="status">
            <option value="">-- Tất cả --</option>
            <?php foreach ($status_map as $k => $v): ?>
              <option value="<?= $k ?>" <?= (isset($status) && $status === $k) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:flex;align-items:flex-end;gap:8px">
          <button class="btn small" type="submit">Lọc</button>
          <!-- <a class="btn ghost small" href="index.php?p=bookings">Xóa</a> -->
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0">Danh sách đơn đặt <span class="muted" style="font-weight:600">(<?= $total ?>)</span></h3>
        <div class="muted">Trang <?= $page ?> / <?= max(1, $pages) ?></div>
      </div>

      <div class="tbl-wrap" style="margin-top:12px">
        <table class="tbl" role="table" aria-label="Danh sách đơn đặt">
          <thead>
            <tr>
              <th style="width:120px">Mã vé</th>
              <th>Hành khách</th>
              <th>SĐT / Email</th>
              <th style="width:140px">Chuyến</th>
              <th style="width:140px">Thời gian</th>
              <th style="width:130px">Trạng thái</th>
              <th style="width:140px"></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookings)): ?>
              <tr><td colspan="7" style="text-align:center;padding:28px">Không có đơn đặt theo lọc.</td></tr>
            <?php else: foreach ($bookings as $b): ?>
              <tr>
                <td>
                  <div style="font-weight:700"><?= htmlspecialchars($b['so_ve'] ?? '') ?></div>
                  <div class="small-muted">#<?= (int)($b['id'] ?? 0) ?></div>
                </td>

                <td><?= htmlspecialchars($b['passenger_name'] ?? '—') ?></td>

                <td class="muted">
                  <?= ($hk_has('sdt') ? htmlspecialchars($b['passenger_phone'] ?? '—') : '—') ?>
                  <?php if ($hk_has('email')): ?><br><a href="mailto:<?= htmlspecialchars($b['passenger_email'] ?? '') ?>"><?= htmlspecialchars($b['passenger_email'] ?? '') ?></a><?php endif; ?>
                </td>

                <td>
                  <div style="font-weight:700"><?= htmlspecialchars($b['flight_no'] ?? '—') ?></div>
                  <?php if (isset($b['ma_tuyen'])): ?><div class="small-muted"><?= htmlspecialchars($b['ma_tuyen']) ?></div><?php endif; ?>
                </td>

                <td class="muted"><?= htmlspecialchars(isset($b['phat_hanh_luc']) && $b['phat_hanh_luc'] ? date('Y-m-d H:i', strtotime($b['phat_hanh_luc'])) : '-') ?></td>

                <td>
                  <?php
                    $s = $b['trang_thai'] ?? '';
                    $cls = 'badge';
                    if ($s === 'CHUA_XUAT') $cls .= ' chua';
                    elseif ($s === 'DA_XUAT') $cls .= ' xuat';
                    elseif ($s === 'HUY') $cls .= ' huy';
                    elseif ($s === 'HOAN') $cls .= ' hoan';
                  ?>
                  <span class="<?= $cls ?>"><?= htmlspecialchars($status_map[$s] ?? $s) ?></span>
                </td>

                <td>
                  <div class="actions">
                    <!-- Quick form: mark DA_XUAT -->
                    <form method="post" style="display:inline">
                      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="id" value="<?= (int)($b['id'] ?? 0) ?>">
                      <input type="hidden" name="action" value="change_status">
                      <input type="hidden" name="new_status" value="DA_XUAT">
                      <button class="btn ghost small" type="submit" title="Đánh dấu đã xuất">
                        <!-- check icon -->
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M20 6L9 17l-5-5" stroke="#0b63d6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Đã xuất
                      </button>
                    </form>

                    <!-- Open modal for more actions -->
                    <button class="btn ghost small" onclick="openChangeModal(<?= (int)($b['id'] ?? 0) ?>, '<?= htmlspecialchars($b['so_ve'] ?? '', ENT_QUOTES) ?>')">
                      <!-- ellipsis icon -->
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden><circle cx="5" cy="12" r="1.6" fill="#0f172a"/><circle cx="12" cy="12" r="1.6" fill="#0f172a"/><circle cx="19" cy="12" r="1.6" fill="#0f172a"/></svg>
                      Thao tác
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <div class="pagination" aria-label="Phân trang">
        <?php if ($pages > 1): ?>
          <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a class="page-btn <?= $p === $page ? 'active' : '' ?>" href="index.php?p=bookings<?= qs(['page' => $p]) ?>"><?= $p ?></a>
          <?php endfor; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- Modal -->
  <div id="modalBackdrop" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal" role="document" aria-labelledby="modalTitle">
      <h3 id="modalTitle">Thao tác vé</h3>
      <div id="modalBody" class="modal-body">Chọn hành động cho vé.</div>

      <form id="modalForm" method="post">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" id="modalId" value="">
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn" type="submit" onclick="return setAction('change_status','DA_XUAT')">Đánh dấu Đã xuất</button>
          <button class="btn ghost" type="button" onclick="setAction('change_status','HUY')">Huỷ vé</button>
          <button class="btn ghost" type="button" onclick="setAction('change_status','HOAN')">Hoàn vé</button>
          <button class="btn ghost" type="button" style="color:var(--danger);border-color:rgba(239,68,68,0.12)" onclick="setAction('delete','')">Xoá</button>
        </div>

        <input type="hidden" name="action" id="modalAction" value="">
        <input type="hidden" name="new_status" id="modalNewStatus" value="">
      </form>

      <div style="text-align:right;margin-top:12px">
        <button class="btn ghost" onclick="closeModal()">Đóng</button>
      </div>
    </div>
  </div>

  <script>
    // Modal logic
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalIdEl = document.getElementById('modalId');
    const modalActionEl = document.getElementById('modalAction');
    const modalNewStatusEl = document.getElementById('modalNewStatus');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');

    function openChangeModal(id, ticketNo) {
      modalBackdrop.style.display = 'flex';
      modalIdEl.value = id;
      modalTitle.textContent = 'Thao tác vé ' + ticketNo;
      modalBody.textContent = 'Chọn hành động thực hiện với vé ' + ticketNo + '.';
    }
    function closeModal() {
      modalBackdrop.style.display = 'none';
      modalActionEl.value = '';
      modalNewStatusEl.value = '';
    }
    function setAction(action, newStatus) {
      modalActionEl.value = action;
      modalNewStatusEl.value = newStatus;
      if (action === 'change_status') {
        document.getElementById('modalForm').submit();
      } else if (action === 'delete') {
        if (confirm('Bạn có chắc muốn xoá vé này? Hành động không thể hoàn tác.')) {
          document.getElementById('modalForm').submit();
        }
      }
      return false;
    }

    // keyboard accessibility
    window.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });
    modalBackdrop.addEventListener('click', (e) => {
      if (e.target === modalBackdrop) closeModal();
    });
  </script>
</body>
</html>
