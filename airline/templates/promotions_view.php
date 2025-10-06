<!doctype html>
<html lang="vi">

<head>
         <meta charset="utf-8">
         <title>Quản lý khuyến mãi</title>
         <link rel="stylesheet" href="assets/home.css">
         <link rel="stylesheet" href="assets/promotions.css">
</head>

<body>
         <?php include __DIR__ . '/../includes/header.php'; ?>
         <main class="container">
                  <h2>Khuyến mãi</h2>

                  <?php if ($m = flash_get('ok')): ?><div class="ok"><?= $m ?></div><?php endif; ?>
                  <?php if ($m = flash_get('err')): ?><div class="err"><?= $m ?></div><?php endif; ?>

                  <div class="card">
                           <h3><?= $edit_row ? 'Sửa #' . $edit_row['id'] : 'Thêm mới' ?></h3>
                           <form method="post">
                                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                    <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= $edit_row['id'] ?>"><?php endif; ?>

                                    <div class="form-grid">
                                             <div class="field">
                                                      <label>Mã</label>
                                                      <input name="ma" value="<?= htmlspecialchars($edit_row['ma'] ?? '') ?>" required>
                                             </div>
                                             <div class="field">
                                                      <label>Tên khuyến mãi</label>
                                                      <input name="ten" value="<?=htmlspecialchars($edit_row['ten'] ?? '') ?>" required >
                                             </div>
                                             <div class="field">
                                                      <label>Kiểu</label>
                                                      <select name="kieu" id="kieuSelect" onchange="updateGiaTriInput()">
                                                               <option value="PHAN_TRAM" <?= isset($edit_row['kieu']) && $edit_row['kieu'] === 'PHAN_TRAM' ? 'selected' : '' ?>>Phần trăm (%)</option>
                                                               <option value="SO_TIEN" <?= isset($edit_row['kieu']) && $edit_row['kieu'] === 'SO_TIEN' ? 'selected' : '' ?>>Số tiền (VNĐ)</option>
                                                      </select>
                                             </div>
                                             <div class="field">
                                                      <label>Giá trị</label>
                                                      <input name="gia_tri" id="giaTriInput" type="number" step="0.01"
                                                               value="<?= htmlspecialchars($edit_row['gia_tri'] ?? '') ?>">
                                             </div>
                                             <div class="field">
                                                      <label>Bắt đầu</label>
                                                      <input type="datetime-local" name="bat_dau"
                                                               value="<?= isset($edit_row['bat_dau']) ? date('Y-m-d\TH:i', strtotime($edit_row['bat_dau'])) : '' ?>">
                                             </div>
                                             <div class="field">
                                                      <label>Kết thúc</label>
                                                      <input type="datetime-local" name="ket_thuc"
                                                               value="<?= isset($edit_row['ket_thuc']) ? date('Y-m-d\TH:i', strtotime($edit_row['ket_thuc'])) : '' ?>">
                                             </div>
                                             <div class="field">
                                                      <label>Áp dụng</label>
                                                      <select name="scope">
                                                               <option value="ALL">Tất cả</option>
                                                               <option value="TUYEN">Tuyến bay</option>
                                                               <option value="CHUYEN">Chuyến bay</option>
                                                               <option value="HANG">Hạng ghế</option>
                                                      </select>
                                             </div>
                                             <div class="field" style="grid-column: span 2;">
                                                      <label>Đối tượng</label>
                                                      <select name="scope_id">
                                                               <option value="">-- Chọn --</option>
                                                               <optgroup label="Tuyến bay">
                                                                        <?php foreach ($routes as $r): ?>
                                                                                 <option value="route:<?= $r['id'] ?>"><?= $r['ma_tuyen'] ?> (<?= $r['di'] ?>→<?= $r['den'] ?>)</option>
                                                                        <?php endforeach; ?>
                                                               </optgroup>
                                                               <optgroup label="Chuyến bay">
                                                                        <?php foreach ($flights as $f): ?>
                                                                                 <option value="flight:<?= $f['id'] ?>"><?= $f['so_hieu'] ?></option>
                                                                        <?php endforeach; ?>
                                                               </optgroup>
                                                               <optgroup label="Hạng ghế">
                                                                        <?php foreach ($classes as $c): ?>
                                                                                 <option value="class:<?= $c['id'] ?>"><?= $c['ma'] . ' - ' . $c['ten'] ?></option>
                                                                        <?php endforeach; ?>
                                                               </optgroup>
                                                      </select>
                                             </div>
                                             <div class="field">
                                                      <label>Kích hoạt</label>
                                                      <input type="checkbox" name="kich_hoat" <?= !empty($edit_row['kich_hoat']) ? 'checked' : '' ?>>
                                             </div>
                                    </div>

                                    <div class="submit-row" style="margin-top:12px">
                                             <button class="btn" name="action" value="<?= $edit_row ? 'update' : 'create' ?>">Lưu</button>
                                             <?php if ($edit_row): ?><a class="btn outline" href="index.php?p=promotions">Hủy</a><?php endif; ?>
                                    </div>
                           </form>
                  </div>

                  <div class="card">
                           <h3>Danh sách (<?= count($rows) ?>)</h3>
                           <table class="tbl">
                                    <tr>
                                             <th>#</th>
                                             <th>Mã</th>
                                             <th>Tên</th>
                                             <th>Kiểu</th>
                                             <th>Giá trị</th>
                                             <th>Bắt đầu</th>
                                             <th>Kết thúc</th>
                                             <th>Áp dụng</th>
                                             <th>Kích hoạt</th>
                                             <th></th>
                                    </tr>
                                    <?php foreach ($rows as $r): ?>
                                             <tr>
                                                      <td><?= $r['id'] ?></td>
                                                      <td><?= htmlspecialchars($r['ma']) ?></td>
                                                      <td><?=htmlspecialchars($r['ten']) ?></td>
                                                      <td><?= $r['kieu'] ?></td>
                                                      <td><?= $r['gia_tri'] ?></td>
                                                      <td><?= $r['bat_dau'] ?></td>
                                                      <td><?= $r['ket_thuc'] ?></td>
                                                      <td><?= $r['ap_dung'] . ' ' . $r['ap_dung_id'] ?></td>
                                                      <td><?= $r['kich_hoat'] ? '✔' : '✖' ?></td>
                                                      <td>
                                                               <a href="index.php?p=promotions&edit=<?= $r['id'] ?>" class="btn outline">Sửa</a>
                                                               <form method="post" class="inline" onsubmit="return confirm('Xóa?')">
                                                                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                                        <button class="btn" name="action" value="delete">Xóa</button>
                                                               </form>
                                                      </td>
                                             </tr>
                                    <?php endforeach; ?>
                           </table>
                  </div>
         </main>

         <script>
                  function updateGiaTriInput() {
                           const kieu = document.getElementById('kieuSelect').value;
                           const input = document.getElementById('giaTriInput');
                           if (kieu === 'PHAN_TRAM') {
                                    input.min = 1;
                                    input.max = 100;
                                    input.placeholder = "Nhập % giảm (1-100)";
                           } else {
                                    input.min = 1;
                                    input.removeAttribute('max');
                                    input.placeholder = "Nhập số tiền giảm (VNĐ)";
                           }
                  }
                  window.onload = updateGiaTriInput;
         </script>
</body>

</html>