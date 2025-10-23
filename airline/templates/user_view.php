<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý tài khoản | VNAir Ticket</title>
  <link rel="stylesheet" href="assets/user.css">
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <div class="brand">
        <div class="logo">✈</div>
        <div>
          <div style="font-weight:800">VNAir Ticket</div>
        </div>
      </div>
      
    </header>

    <div class="layout">
      <aside class="sidebar" aria-label="Thanh điều hướng">
        <h4>Quản trị</h4>
         <a class="side-link active" href="index.php?p=admin">Trang chủ</a>
        <div style="height:12px"></div>
      </aside>

      <main class="content">
        <?php if ($m = flash_get('ok')): ?>
          <div class="card" style="border-left:4px solid var(--success)"><div class="muted"><?= htmlspecialchars($m) ?></div></div>
        <?php endif; ?>
        <?php if ($m = flash_get('err')): ?>
          <div class="card" style="border-left:4px solid var(--danger)"><div class="muted"><?= htmlspecialchars($m) ?></div></div>
        <?php endif; ?>

        <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
          <div>
            <h2 style="margin:0">Quản lý tài khoản</h2>
            <div class="muted" style="margin-top:6px">Xem, tạo, sửa, khoá/mở & xoá tài khoản.</div>
          </div>
          
        </div>

        <!-- FILTER -->
        <form class="card" method="get" action="index.php" aria-label="Bộ lọc người dùng">
          <input type="hidden" name="p" value="users">
          <div class="filters">
            <div style="flex:1;min-width:220px">
              <label>Tìm kiếm</label>
              <input type="text" name="q" placeholder="Email, họ tên, SĐT" value="<?= htmlspecialchars($q) ?>">
            </div>
            <div style="width:220px">
              <label>Vai trò</label>
              <select name="role">
                <option value="0">-- Tất cả --</option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= $role_id === $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['ma'].' - '.$r['ten']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="width:160px">
              <label>Trạng thái</label>
              <select name="status">
                <option value="">-- Tất cả --</option>
                <option value="HOAT_DONG" <?= $status === 'HOAT_DONG' ? 'selected' : '' ?>>HOẠT ĐỘNG</option>
                <option value="KHOA" <?= $status === 'KHOA' ? 'selected' : '' ?>>ĐÃ KHÓA</option>
              </select>
            </div>
            <div style="min-width:140px;display:flex;align-items:flex-end">
              <button class="btn" type="submit">Lọc</button>
            </div>
          </div>
        </form>

        <!-- CREATE / EDIT FORM -->
        <div id="form-create" class="card" aria-labelledby="form-title">
          <h3 id="form-title"><?= $edit_row ? 'Sửa tài khoản #' . (int)$edit_row['id'] : 'Thêm tài khoản mới' ?></h3>
          <form method="post" style="margin-top:12px;display:grid;grid-template-columns:repeat(12,1fr);gap:12px">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= (int)$edit_row['id'] ?>"><?php endif; ?>

            <div style="grid-column:span 4">
              <label>Email</label>
              <input name="email" type="email" required value="<?= htmlspecialchars($edit_row['email'] ?? '') ?>">
            </div>
            <div style="grid-column:span 4">
              <label>Họ tên</label>
              <input name="ho_ten" type="text" required value="<?= htmlspecialchars($edit_row['ho_ten'] ?? '') ?>">
            </div>
            <div style="grid-column:span 4">
              <label>Số điện thoại</label>
              <input name="sdt" type="text" value="<?= htmlspecialchars($edit_row['sdt'] ?? '') ?>">
            </div>

            <div style="grid-column:span 4">
              <label>Vai trò</label>
              <select name="vai_tro_id" required>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= isset($edit_row['vai_tro_id']) && (int)$edit_row['vai_tro_id'] === $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['ma'].' - '.$r['ten']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="grid-column:span 4">
              <label>Trạng thái</label>
              <select name="trang_thai">
                <?php foreach (['HOAT_DONG','KHOA'] as $s): ?>
                  <option value="<?= $s ?>" <?= $edit_row && $edit_row['trang_thai'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if ($edit_row): ?>
              <div style="grid-column:span 4">
                <label>Mật khẩu mới (tuỳ chọn)</label>
                <input name="new_password" type="password" placeholder="Để trống nếu không đổi">
              </div>
            <?php else: ?>
              <div style="grid-column:span 4">
                <label>Mật khẩu</label>
                <input name="password" type="password" required>
              </div>
            <?php endif; ?>

            <div style="grid-column:span 12;display:flex;gap:8px;margin-top:6px">
              <button class="btn" type="submit" name="action" value="<?= $edit_row ? 'update' : 'create' ?>"><?= $edit_row ? 'Lưu thay đổi' : 'Tạo tài khoản' ?></button>
              <?php if ($edit_row): ?><a class="btn ghost" href="index.php?p=users">Hủy</a><?php endif; ?>
            </div>
          </form>
        </div>

        <!-- USERS TABLE -->
        <div class="card" aria-live="polite">
          <h3 style="margin-top:0">Danh sách tài khoản <span class="muted">(<?= count($users) ?>)</span></h3>
          <div style="overflow:auto">
            <table class="tbl" role="table" aria-label="Danh sách người dùng">
              <thead>
                <tr>
                  <th style="width:64px">#</th>
                  <th>Họ tên</th>
                  <th>Email</th>
                  <th style="width:130px">SĐT</th>
                  <th style="width:130px">Vai trò</th>
                  <th style="width:120px">Trạng thái</th>
                  <th style="width:80px"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr data-id="<?= (int)$u['id'] ?>">
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars($u['ho_ten']) ?></td>
                    <td><a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a></td>
                    <td class="muted"><?= htmlspecialchars($u['sdt']) ?></td>
                    <td><?= htmlspecialchars($u['role_ma']) ?></td>
                    <td>
                      <?php
                        $s = $u['trang_thai'];
                        if ($s === 'HOAT_DONG') echo '<span class="badge ok">HOẠT ĐỘNG</span>';
                        elseif ($s === 'KHOA') echo '<span class="badge err">ĐÃ KHÓA</span>';
                        else echo '<span class="badge">'.$s.'</span>';
                      ?>
                    </td>
                    <td style="text-align:right">
                      <div class="action-menu">
                        <button class="action-btn" aria-haspopup="true" aria-expanded="false" title="Thao tác">
                          <!-- 3 dots icon -->
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden><circle cx="5" cy="12" r="2" fill="#0f172a"/><circle cx="12" cy="12" r="2" fill="#0f172a"/><circle cx="19" cy="12" r="2" fill="#0f172a"/></svg>
                        </button>
                        <div class="menu" role="menu" aria-hidden="true">
                          <a href="index.php?p=users&edit=<?= (int)$u['id'] ?>">Sửa</a>

                          <form method="post" class="menu-form" data-action="resetpw" onsubmit="return false;">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="button" class="menu-btn" data-id="<?= (int)$u['id'] ?>">Reset mật khẩu</button>
                          </form>

                          <form method="post" class="menu-form" data-action="toggle" onsubmit="return false;">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="button" class="menu-btn" data-id="<?= (int)$u['id'] ?>"><?= $u['trang_thai'] === 'HOAT_DONG' ? 'Khoá tài khoản' : 'Mở khoá' ?></button>
                          </form>

                          <form method="post" class="menu-form" data-action="delete" onsubmit="return false;">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="button" class="menu-btn" data-id="<?= (int)$u['id'] ?>" style="color:var(--danger)">Xoá</button>
                          </form>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>
    </div>

    <footer style="margin-top:18px">
      <div class="muted">© <span id="y"></span> VNAir Ticket</div>
    </footer>
  </div>

  <!-- Modal xác nhận (dùng chung cho toggle/reset/delete) -->
  <div id="confirmModal" style="display:none;position:fixed;inset:0;background:rgba(2,6,23,0.5);align-items:center;justify-content:center;z-index:120">
    <div style="width:460px;background:#fff;border-radius:12px;padding:18px;box-shadow:0 20px 50px rgba(2,6,23,0.4);">
      <h3 id="modalTitle" style="margin:0 0 8px 0">Xác nhận</h3>
      <div id="modalBody" class="muted" style="margin-bottom:12px">Bạn có chắc muốn thực hiện hành động này?</div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button id="modalCancel" class="btn ghost">Hủy</button>
        <form id="modalForm" method="post" style="display:inline">
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="id" id="modalId" value="">
          <input type="hidden" name="action" id="modalAction" value="">
          <button class="btn" type="submit">Xác nhận</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Footer year
    document.getElementById('y').textContent = new Date().getFullYear();

    // Keyboard shortcut: N => focus email field
    document.addEventListener('keydown', (e)=> {
      if (e.key.toLowerCase() === 'n' && !e.ctrlKey && !e.metaKey) {
        const el = document.querySelector('#form-create input[name="email"]');
        if (el) { el.focus(); e.preventDefault(); }
      }
    });

    // Action menu toggles
    document.querySelectorAll('.action-menu').forEach(menuWrap => {
      const btn = menuWrap.querySelector('.action-btn');
      const menu = menuWrap.querySelector('.menu');
      btn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        // close other menus
        document.querySelectorAll('.menu').forEach(m => { if (m !== menu) m.style.display = 'none'; });
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
      });
    });

    // Close menus when clicking outside
    document.addEventListener('click', ()=> {
      document.querySelectorAll('.menu').forEach(m => m.style.display = 'none');
    });

    // Modal logic
    const modal = document.getElementById('confirmModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalForm = document.getElementById('modalForm');
    const modalId = document.getElementById('modalId');
    const modalAction = document.getElementById('modalAction');
    const modalCancel = document.getElementById('modalCancel');

    // Attach handlers for menu buttons
    document.querySelectorAll('.menu .menu-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const formWrap = btn.closest('.menu-form');
        const action = formWrap.getAttribute('data-action'); // resetpw/toggle/delete
        const id = btn.getAttribute('data-id');
        let title = 'Xác nhận';
        let body = 'Bạn có chắc muốn thực hiện hành động này?';
        if (action === 'resetpw') { title = 'Reset mật khẩu'; body = 'Reset mật khẩu người dùng và tạo mật khẩu tạm thời. Bạn sẽ nhận được mật khẩu mới hiển thị sau khi xác nhận.'; }
        if (action === 'toggle') { title = 'Khoá / Mở tài khoản'; body = 'Hành động này sẽ thay đổi trạng thái tài khoản. Bạn có muốn tiếp tục?'; }
        if (action === 'delete') { title = 'Xoá tài khoản'; body = 'Xoá tài khoản là hành động không thể hoàn tác. Bạn có chắc chắn?'; }

        modalTitle.textContent = title;
        modalBody.textContent = body;
        modalId.value = id;
        modalAction.value = action;
        modal.style.display = 'flex';

      });
    });

    modalCancel.addEventListener('click', ()=> { modal.style.display = 'none'; });

    // close modal on outside click
    modal.addEventListener('click', (e)=> { if (e.target === modal) modal.style.display = 'none'; });

    // Accessibility: close modal with ESC
    document.addEventListener('keydown', (e)=> { if (e.key === 'Escape') modal.style.display = 'none'; });
  </script>
</body>
</html>
