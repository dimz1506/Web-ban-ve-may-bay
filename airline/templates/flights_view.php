<!doctype html>
<html lang="vi">

<head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Quản lý chuyến bay | VNAir Ticket</title>
        <link rel="stylesheet" href="assets/home.css">
        <link rel="stylesheet" href="assets/admin.css">
</head>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const routeSelect = document.querySelector('select[name="tuyen_bay_id"]');
  const fareBlocks = document.querySelectorAll('[data-hang]');

  routeSelect.addEventListener('change', () => {
    const routeId = routeSelect.value;

    fareBlocks.forEach(block => {
      const hangId = block.dataset.hang;

      fetch(`api_get_fare.php?tuyen_bay_id=${routeId}&hang_ghe_id=${hangId}`)
        .then(res => res.json())
        .then(json => {
          if (json.success && json.data) {
            const parent = block.closest('.field') || block.parentElement;
            block.value = json.data.gia_co_ban ?? '';
            
            // Tìm các input khác cùng nhóm
            const hanhLy = parent.querySelector('.hanh-ly');
            const phiDoi = parent.querySelector('.phi-doi');
            const duocHoan = parent.querySelector('.duoc-hoan');
            
            if (hanhLy) hanhLy.value = json.data.hanh_ly_kg ?? 0;
            if (phiDoi) phiDoi.value = json.data.phi_doi ?? 0;
            if (duocHoan) duocHoan.checked = !!json.data.duoc_hoan;
          }
        })
        .catch(err => console.error('Lỗi tải giá:', err));
    });
  });
});
</script>

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
                <?php if ($m = flash_get('ok')): ?><div class="ok"><?= $m ?></div><?php endif; ?>
                <?php if ($m = flash_get('err')): ?><div class="err" style="display:block"><?= $m ?></div><?php endif; ?>

         <div class="p">
    <h2>Quản lý chuyến bay</h2>
</div>
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
                                        <button class="btn loc" type="submit">Lọc</button>
                                </div>
                        </div>
                </form>

               
                <?php if (!empty($isAdmin)): ?>
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
                                                                        <input
                                                                                class="gia-co-ban"
                                                                                data-hang="<?= $c['id'] ?>"
                                                                                type="number"
                                                                                name="fare[<?= $c['id'] ?>][gia]"
                                                                                step="0.01"
                                                                                min="0"
                                                                                value="<?= htmlspecialchars($ex['gia_co_ban'] ?? '') ?>">
                                                                </label>
                                                                <label>Số ghế còn
                                                                        <input name="fare[<?= $c['id'] ?>][so_ghe]" type="number" min="0"
                                                                                value="<?= htmlspecialchars($ex['so_ghe_con'] ?? '') ?>">
                                                                </label>
                                                                <label>Hành lý (kg)
                                                                        <input clas="hanh_ly" name="fare[<?= $c['id'] ?>][kg]" type="number" min="0"
                                                                                value="<?= htmlspecialchars($ex['hanh_ly_kg'] ?? '') ?>">
                                                                </label>
                                                                <label>Được hoàn?
                                                                        <input class="duoc_hoan" name="fare[<?= $c['id'] ?>][hoan]" type="checkbox" <?= !empty($ex) && (int)$ex['duoc_hoan'] === 1 ? 'checked' : '' ?>>
                                                                </label>
                                                                <label>Phí đổi
                                                                        <input class="phi_doi" name="fare[<?= $c['id'] ?>][phi_doi]" type="number" step="0.01" min="0"
                                                                                value="<?= htmlspecialchars($ex['phi_doi'] ?? '') ?>">
                                                                </label>
                                                        </div>
                                                <?php endforeach; ?>
                                        </div>
                                </div>

                                <div class="submit-row">
                                        <button class="btn luu" type="submit" name="action" value="<?= $edit_row ? 'update' : 'create' ?>">Lưu</button>
                                        <?php if ($edit_row): ?>
                                                <a class="btn outline" href="index.php?p=flights">Hủy</a>
                                        <?php endif; ?>
                                </div>
                        </form>
                 </div>
              <?php else: ?>
            <div class="card">
             <h3>Quản lý chuyến bay</h3>
              <div class="muted">Bạn không có quyền tạo hoặc sửa chuyến bay. Nếu cần, vui lòng liên hệ ADMIN.</div>
               </div>
                <?php endif; ?>

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
                                                        <!-- <a class="btn outline" href="index.php?p=flights&edit=<?= (int)$f['id'] ?>">Sửa</a>
                                                        <form method="post" class="inline" onsubmit="return confirm('Xóa chuyến #<?= (int)$f['id'] ?>?')">
                                                                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                                                <button class="btn" name="action" value="delete" type="submit">Xóa</button>
                                                        </form> -->
                                                        <?php if (!empty($isAdmin)): ?>
                                                    <a href="index.php?p=flights&edit=<?= (int)$f['id'] ?>" class="btn outline">Sửa</a>
                                                    <form method="post" style="display:inline" onsubmit="return confirm('Xóa chuyến bay?')">
                                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                                   <button class="btn danger" name="action" value="delete">Xóa</button>
                                                     </form>
                                             <?php else: ?>
                                          <!-- <span class="small-muted">Không có quyền</span> -->
                                            <?php endif; ?>

                                                </td>
                                        </tr>
                                <?php endforeach; ?>
                        </table>
                        <p class="muted">Hiển thị tối đa 300 bản ghi theo bộ lọc.</p>
                </div>
                <br>
                <div class="page-actions">
        <?php if (!empty($isAdmin)): ?>
            <a class="btn ghost" href="index.php?p=admin">Quay lại</a>
        <?php elseif (!empty($isStaff)): ?>
            <a class="btn ghost" href="index.php?p=staff">Quay lại</a>
        <?php endif; ?>
    </div>
        </main>
        <br>
        <footer>
                <div class="container">© <span id="y"></span> VNAir Ticket</div>
        </footer>
        <script>
                document.getElementById('y').textContent = new Date().getFullYear();
        </script>
</body>

</html>