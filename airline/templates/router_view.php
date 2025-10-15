<!DOCTYPE html>
<html lang="vi">

<head>
         <meta charset="UTF-8">
         <title>Quản lý tuyến bay</title>
         <link rel="stylesheet" href="assets/admin.css">
</head>

<body>
         <?php include __DIR__ . '/../includes/header.php'; ?>

         <main class="container">
                  <h2>Quản lý tuyến bay</h2>

                  <?php if ($m = flash_get('ok')): ?><div class="ok"><?= $m ?></div><?php endif; ?>
                  <?php if ($m = flash_get('err')): ?><div class="err"><?= $m ?></div><?php endif; ?>

                  <div class="card">
                           <h3><?= $edit_row ? 'Sửa tuyến #' . $edit_row['id'] : 'Thêm tuyến mới' ?></h3>
                           <form method="post" action="index.php?p=router">
                                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                    <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= $edit_row['id'] ?>"><?php endif; ?>

                                    <div class="grid">
                                             <div class="field">
                                                      <label>Mã tuyến</label>
                                                      <input name="ma_tuyen" value="<?= htmlspecialchars($edit_row['ma_tuyen'] ?? '') ?>" required>
                                             </div>

                                             <div class="field">
                                                      <label>Sân bay đi</label>
                                                      <select name="di" required>
                                                               <option value="">-- Chọn sân bay đi --</option>
                                                               <?php foreach ($airports as $a): ?>
                                                                        <option value="<?= $a['ma'] ?>" <?= ($edit_row['di'] ?? '') === $a['ma'] ? 'selected' : '' ?>>
                                                                                 <?= $a['ma'] ?> — <?= htmlspecialchars($a['ten']) ?> (<?= htmlspecialchars($a['thanh_pho']) ?>)
                                                                        </option>
                                                               <?php endforeach; ?>
                                                      </select>
                                             </div>

                                             <div class="field">
                                                      <label>Sân bay đến</label>
                                                      <select name="den" required>
                                                               <option value="">-- Chọn sân bay đến --</option>
                                                               <?php foreach ($airports as $a): ?>
                                                                        <option value="<?= $a['ma'] ?>" <?= ($edit_row['den'] ?? '') === $a['ma'] ? 'selected' : '' ?>>
                                                                                 <?= $a['ma'] ?> — <?= htmlspecialchars($a['ten']) ?> (<?= htmlspecialchars($a['thanh_pho']) ?>)
                                                                        </option>
                                                               <?php endforeach; ?>
                                                      </select>
                                             </div>


                                             <div class="field">
                                                      <label>Khoảng cách (km)</label>
                                                      <input type="number" name="khoang_cach_km" min="0" value="<?= htmlspecialchars($edit_row['khoang_cach_km'] ?? '') ?>">
                                             </div>
                                    </div>

                                    <div class="submit-row">
                                             <button class="btn" name="action" value="<?= $edit_row ? 'update' : 'create' ?>">Lưu</button>
                                             <?php if ($edit_row): ?><a class="btn outline" href="index.php?p=router">Hủy</a><?php endif; ?>
                                    </div>
                           </form>
                  </div>

                  <div class="card">
                           <h3>Danh sách tuyến bay</h3>
                           <table class="tbl">
                                    <tr>
                                             <th>ID</th>
                                             <th>Mã tuyến</th>
                                             <th>Đi</th>
                                             <th>Đến</th>
                                             <th>Khoảng cách (km)</th>
                                             <th></th>
                                    </tr>
                                    <?php foreach ($rows as $r): ?>
                                             <tr>
                                                      <td><?= $r['id'] ?></td>
                                                      <td><?= htmlspecialchars($r['ma_tuyen']) ?></td>
                                                      <td><?= htmlspecialchars($r['di']) ?></td>
                                                      <td><?= htmlspecialchars($r['den']) ?></td>
                                                      <td><?= (int)$r['khoang_cach_km'] ?></td>
                                                      <td>
                                                               <a class="btn outline" href="index.php?p=routes&edit=<?= $r['id'] ?>">Sửa</a>
                                                               <form method="post" class="inline" onsubmit="return confirm('Xóa tuyến này?')">
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
</body>

</html>