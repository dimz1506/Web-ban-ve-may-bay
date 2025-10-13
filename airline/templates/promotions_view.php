<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Quản lý khuyến mãi</title>
    <link rel="stylesheet" href="assets/home.css">
    <link rel="stylesheet" href="assets/promotions.css">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="container">
        <div class="page-head">
      <div>
        <h2>Quản lý khuyến mãi</h2>
      </div>
      <div class="page-actions">
        <a class="btn " href="index.php?p=admin">Quay lại</a>

      </div>
    </div>
    <br>
        <?php if ($m = flash_get('ok')): ?><div class="card" style="border-left:4px solid var(--success)"><div class="small-muted"><?= htmlspecialchars($m) ?></div></div><?php endif; ?>
        <?php if ($m = flash_get('err')): ?><div class="card" style="border-left:4px solid var(--danger)"><div class="small-muted"><?= htmlspecialchars($m) ?></div></div><?php endif; ?>

        <div class="card" aria-labelledby="promo-form">
            <h3 id="promo-form" style="margin-top:0"><?= $edit_row ? 'Sửa #' . (int)$edit_row['id'] : 'Thêm khuyến mãi mới' ?></h3>
            <form method="post" novalidate>
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= (int)$edit_row['id'] ?>"><?php endif; ?>

                <div class="form-grid" role="group" aria-label="Thông tin khuyến mãi">
                    <div class="field">
                        <label for="ma">Mã</label>
                        <input id="ma" name="ma" required value="<?= htmlspecialchars($edit_row['ma'] ?? '') ?>" placeholder="VD: KHUYENMAI2025">
                        <div class="helper">Mã duy nhất (không dấu cách).</div>
                    </div>

                    <div class="field">
                        <label for="ten">Tên khuyến mãi</label>
                        <input id="ten" name="ten" required value="<?= htmlspecialchars($edit_row['ten'] ?? '') ?>" placeholder="Ví dụ: Giảm giá hè 20%">
                    </div>

                    <div class="field">
                        <label for="kieuSelect">Kiểu</label>
                        <select id="kieuSelect" name="kieu" onchange="updateGiaTriInput()">
                            <option value="PHAN_TRAM" <?= (isset($edit_row['kieu']) && $edit_row['kieu'] === 'PHAN_TRAM') ? 'selected' : '' ?>>Phần trăm (%)</option>
                            <option value="SO_TIEN" <?= (isset($edit_row['kieu']) && $edit_row['kieu'] === 'SO_TIEN') ? 'selected' : '' ?>>Số tiền (VNĐ)</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="giaTriInput">Giá trị</label>
                        <input id="giaTriInput" name="gia_tri" type="number" step="0.01" value="<?= htmlspecialchars($edit_row['gia_tri'] ?? '') ?>" placeholder="Nhập giá trị giảm">
                    </div>

                    <div class="field">
                        <label for="bat_dau">Bắt đầu</label>
                        <input id="bat_dau" type="datetime-local" name="bat_dau" value="<?= isset($edit_row['bat_dau']) ? date('Y-m-d\TH:i', strtotime($edit_row['bat_dau'])) : '' ?>">
                    </div>

                    <div class="field">
                        <label for="ket_thuc">Kết thúc</label>
                        <input id="ket_thuc" type="datetime-local" name="ket_thuc" value="<?= isset($edit_row['ket_thuc']) ? date('Y-m-d\TH:i', strtotime($edit_row['ket_thuc'])) : '' ?>">
                    </div>

                    <div class="field">
                        <label for="scopeSelect">Áp dụng</label>
                        <?php $edit_scope = $edit_row['scope'] ?? 'ALL'; ?>
                        <select id="scopeSelect" name="scope" onchange="onScopeChange()">
                            <option value="ALL" <?= $edit_scope === 'ALL' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="TUYEN" <?= $edit_scope === 'TUYEN' ? 'selected' : '' ?>>Tuyến bay</option>
                            <option value="CHUYEN" <?= $edit_scope === 'CHUYEN' ? 'selected' : '' ?>>Chuyến bay</option>
                            <option value="HANG" <?= $edit_scope === 'HANG' ? 'selected' : '' ?>>Hạng ghế</option>
                        </select>
                    </div>

                    <div class="field col-span-2">
                        <label for="scopeIdSelect">Đối tượng (tùy chọn)</label>
                        <?php $edit_scope_id = $edit_row['scope_id'] ?? ''; ?>
                        <select id="scopeIdSelect" name="scope_id">
                            <option value="">-- Chọn (hoặc để trống) --</option>
                            <optgroup label="Tuyến bay">
                                <?php foreach ($routes as $r):
                                    $val = 'route:' . $r['id'];
                                    $sel = ($val === $edit_scope_id) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>><?= htmlspecialchars($r['ma_tuyen'] . ' — ' . $r['di'] . ' → ' . $r['den']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Chuyến bay">
                                <?php foreach ($flights as $f):
                                    $val = 'flight:' . $f['id'];
                                    $sel = ($val === $edit_scope_id) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>><?= htmlspecialchars($f['so_hieu']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Hạng ghế">
                                <?php foreach ($classes as $c):
                                    $val = 'class:' . $c['id'];
                                    $label = isset($c['ten']) ? ($c['ma'] . ' — ' . $c['ten']) : $c['ma'];
                                    $sel = ($val === $edit_scope_id) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <div class="helper">Nếu để trống, áp dụng theo mục "Áp dụng". Ví dụ: chọn một chuyến cụ thể.</div>
                    </div>

                    <div class="field">
                        <label>Kích hoạt</label>
                        <div class="switch" title="Kích hoạt khuyến mãi">
                            <input id="kich_hoat" type="checkbox" name="kich_hoat" <?= !empty($edit_row['kich_hoat']) ? 'checked' : '' ?>>
                            <label for="kich_hoat" class="small-muted"><?= !empty($edit_row['kich_hoat']) ? 'Đang bật' : 'Tắt' ?></label>
                        </div>
                    </div>
                </div>

                <div class="submit-row">
                    <button class="btn" name="action" value="<?= $edit_row ? 'update' : 'create' ?>"><?= $edit_row ? 'Cập nhật' : 'Thêm' ?></button>
                    <?php if ($edit_row): ?><a class="btn outline" href="index.php?p=promotions">Hủy</a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Danh sách khuyến mãi <span class="small-muted">(<?= count($rows) ?>)</span></h3>

            <div style="overflow:auto;margin-top:8px">
                <table class="tbl" role="table" aria-label="Danh sách khuyến mãi">
                    <thead>
                        <tr>
                            <th style="width:56px">#</th>
                            <th>Mã</th>
                            <th>Tên</th>
                            <th>Kiểu</th>
                            <th>Giá trị</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                            <th>Áp dụng</th>
                            <th>Kích hoạt</th>
                            <th style="width:160px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r):
                            $rid = (int)($r['id'] ?? 0);
                            $r_ma = htmlspecialchars($r['ma'] ?? '');
                            $r_ten = htmlspecialchars($r['ten'] ?? '');
                            $r_kieu = htmlspecialchars($r['kieu'] ?? '');
                            $r_gia = htmlspecialchars($r['gia_tri'] ?? '');
                            $r_bat = htmlspecialchars($r['bat_dau'] ?? '');
                            $r_ket = htmlspecialchars($r['ket_thuc'] ?? '');
                            $r_scope = $r['scope'] ?? ($r['ap_dung'] ?? 'ALL');
                            $r_scope_id = $r['scope_id'] ?? ($r['ap_dung_id'] ?? '');
                            $scope_disp = htmlspecialchars($r_scope);
                            if ($r_scope_id) $scope_disp .= ' ' . htmlspecialchars($r_scope_id);
                        ?>
                            <tr>
                                <td><?= $rid ?></td>
                                <td><strong><?= $r_ma ?></strong></td>
                                <td><?= $r_ten ?></td>
                                <td><?= $r_kieu ?></td>
                                <td>
                                    <?php if ($r_kieu === 'PHAN_TRAM'): ?>
                                        <?= $r_gia ?>%
                                    <?php else: ?>
                                        <?= number_format((float)$r_gia) ?> VNĐ
                                    <?php endif; ?>
                                </td>
                                <td class="small-muted"><?= $r_bat ?: '-' ?></td>
                                <td class="small-muted"><?= $r_ket ?: '-' ?></td>
                                <td><span class="scope-pill"><?= $scope_disp ?></span></td>
                                <td><?= (!empty($r['kich_hoat']) && $r['kich_hoat']) ? '✔' : '✖' ?></td>
                                <td style="text-align:right">
                                    <a class="btn ghost" href="index.php?p=promotions&edit=<?= $rid ?>">Sửa</a>
                                    <form method="post" class="inline" style="display:inline-block" onsubmit="return confirm('Xóa khuyến mãi này?')">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= $rid ?>">
                                        <button class="btn" name="action" value="delete">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="10" style="text-align:center;padding:22px" class="small-muted">Chưa có khuyến mãi</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        // cập nhật input giá trị theo kiểu
        function updateGiaTriInput(){
            const k = document.getElementById('kieuSelect').value;
            const inp = document.getElementById('giaTriInput');
            if(!inp) return;
            if(k === 'PHAN_TRAM'){
                inp.min = 1;
                inp.max = 100;
                inp.step = 0.01;
                inp.placeholder = 'Nhập % (1 - 100)';
            } else {
                inp.min = 1;
                inp.removeAttribute('max');
                inp.step = 0.01;
                inp.placeholder = 'Nhập số tiền (VNĐ)';
            }
        }
        // bật/tắt hiển thị target khi scope thay đổi (optional)
        function onScopeChange(){
            const scope = document.getElementById('scopeSelect').value;
            const target = document.getElementById('scopeIdSelect');
            // bạn có thể thay đổi options dynamic nếu cần
            if(scope === 'ALL'){
                target.disabled = true;
                target.value = '';
            } else {
                target.disabled = false;
            }
        }
        // initialize
        window.addEventListener('DOMContentLoaded', function(){
            updateGiaTriInput();
            onScopeChange();
            // dynamic label for switch
            const switchInput = document.getElementById('kich_hoat');
            if(switchInput){
                const lbl = switchInput.nextElementSibling;
                switchInput.addEventListener('change', function(){
                    if(lbl) lbl.textContent = this.checked ? 'Đang bật' : 'Tắt';
                });
            }
        });
    </script>
</body>

</html>
