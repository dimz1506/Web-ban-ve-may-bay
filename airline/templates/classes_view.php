<!DOCTYPE html>
<html lang="vi">

<head>
         <meta charset="utf-8">
         <title>Quản lý hạng ghế</title>
         <link rel="stylesheet" href="assets/admin.css">
</head>

<body>
         <?php include __DIR__ . '/../includes/header.php'; ?>
         <main class="container">
                  <h2>Hạng ghế</h2>
                  <?php if ($m = flash_get('ok')) : ?><div class="ok"><?= $m ?></div><?php endif; ?>
                  <?php if ($m = flash_get('err')) : ?><div class="err"><?= $m ?></div><?php endif; ?>

                  <div class="card">
                           <h3><?= $edit_row ? 'Sửa hạng #' . $edit_row['id'] : 'Thêm hạng ghế' ?></h3>
                           <form method="post">
                                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                    <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= $edit_row['id'] ?>"><?php endif; ?>
                                    <div class="form-grid">
                                             <div class="field">
                                                      <label>Mã</label>
                                                      <input name="ma" value="<?= htmlspecialchars($edit_row['ma'] ?? '') ?>" required>
                                             </div>
                                             <div class="field">
                                                      <label>Tên</label>
                                                      <input name="ten" value="<?= htmlspecialchars($edit_row['ten'] ?? '') ?>" required>
                                             </div>
                                             <div class="field">
                                                      <label>Mô tả</label>
                                                      <textarea name="mo_ta" rows="2"><?= htmlspecialchars($edit_row['mo_ta'] ?? '') ?></textarea>
                                             </div>
                                             <div class="field">
                                                      <label>Tiện ích (ngăn cách bởi ,)</label>
                                                      <input name="tien_ich" value="<?= htmlspecialchars($edit_row['tien_ich'] ?? '') ?>">
                                             </div>
                                             <div class="field">
                                                      <label>Màu sắc</label>
                                                      <input type="color" name="mau_sac" value="<?= htmlspecialchars($edit_row['mau_sac'] ?? '#cccccc') ?>">
                                             </div>
                                             <div class="field">
                                                      <label>Thứ tự</label>
                                                      <input type="number" name="thu_tu" value="<?= htmlspecialchars($edit_row['thu_tu'] ?? 0) ?>">
                                             </div>
                                    </div>
                                    <div class="submit-row">
                                             <button class="btn primary" name="action" value="<?= $edit_row ? 'update' : 'create' ?>">Lưu</button>
                                             <?php if ($edit_row): ?>
                                                      <a class="btn outline" href="index.php?p=classes">Hủy</a>
                                             <?php endif; ?>
                                    </div>
                           </form>
                  </div>

                  <div class="card">
                           <h3>Danh sách hạng ghế</h3>
                           <table class="tbl">
                                    <tr>
                                             <th>ID</th>
                                             <th>Mã</th>
                                             <th>Tên</th>
                                             <th>Mô tả</th>
                                             <th>Tiện ích</th>
                                             <th>Màu sắc</th>
                                             <th>Thứ tự</th>
                                             <th>Hành động</th>
                                    </tr>
                                    <?php foreach ($rows as $r): ?>
                                             <tr>
                                                      <td><?= $r['id'] ?></td>
                                                      <td><strong><?= htmlspecialchars($r['ma']) ?></strong></td>
                                                      <td><?= htmlspecialchars($r['ten']) ?></td>
                                                      <td><?= htmlspecialchars($r['mo_ta'] ?? '') ?></td>
                                                      <td><?= htmlspecialchars($r['tien_ich'] ?? '') ?></td>
                                                      <td>
                                                               <?php if (!empty($r['mau_sac'])): ?>
                                                                        <span class="badge-color" style="background:<?= htmlspecialchars($r['mau_sac']) ?>">
                                                                                 <?= htmlspecialchars($r['mau_sac']) ?>
                                                                        </span>
                                                               <?php endif; ?>
                                                      </td>
                                                      <td><?= (int)$r['thu_tu'] ?></td>
                                                      <td>
                                                               <a href="index.php?p=classes&edit=<?= $r['id'] ?>" class="btn outline">Sửa</a>
                                                               <form method="post" style="display:inline" onsubmit="return confirm('Xóa hạng ghế?')">
                                                                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                                        <button class="btn danger" name="action" value="delete">Xóa</button>
                                                               </form>
                                                      </td>
                                             </tr>
                                    <?php endforeach; ?>
                           </table>
                  </div>
         </main>
</body>

</html>