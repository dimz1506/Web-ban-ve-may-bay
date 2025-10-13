<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý đơn đặt | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/home.css">
  <link rel="stylesheet" href="assets/booking.css">
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
        <?php if (!empty($isAdmin)): ?>
            <a class="btn ghost" href="index.php?p=admin">Quay lại trang Admin</a>
        <?php elseif (!empty($isStaff)): ?>
            <a class="btn ghost" href="index.php?p=staff">Quay lại trang Nhân viên</a>
        <?php endif; ?>
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
