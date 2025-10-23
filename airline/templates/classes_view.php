<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Quản lý hạng ghế</title>
    <link rel="stylesheet" href="assets/home.css">
    <link rel="stylesheet" href="assets/classes_view.css">
    <style>
        /* Một vài style phụ nếu file css không có */
        .readonly { padding: 8px; background:#fafafa; border:1px solid #eee; border-radius:4px; }
        .muted { color: #666; }
        .badge-color { display:inline-block; padding:4px 8px; border-radius:4px; color:#fff; font-size:0.9em; }
        .form-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="container">
        <div class="p">
            <h2>Quản lý hạng ghế</h2>
        </div>
        <br>

        <?php if ($m = flash_get('ok')) : ?><div class="ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
        <?php if ($m = flash_get('err')) : ?><div class="err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

        <div class="card">
            <?php if (!empty($is_admin)): ?>
                <h3><?= $edit_row ? 'Sửa hạng #' . intval($edit_row['id']) : 'Thêm hạng ghế' ?></h3>
                <form method="post" novalidate>
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= intval($edit_row['id']) ?>"><?php endif; ?>
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
                        <!-- Nếu muốn bật thứ tự: -->
                        <!-- <div class="field">
                            <label>Thứ tự</label>
                            <input type="number" name="thu_tu" value="<?= htmlspecialchars($edit_row['thu_tu'] ?? 0) ?>">
                        </div> -->
                    </div>

                    <div class="submit-row" style="margin-top:12px;">
                        <button class="btn primary" name="action" value="<?= $edit_row ? 'update' : 'create' ?>">Lưu</button>
                        <?php if ($edit_row): ?>
                            <a class="btn outline" href="index.php?p=classes">Hủy</a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php else: ?>
                <h3>📌 Chế độ xem </h3>
                <p class="muted">Bạn đang ở chế độ <strong>chỉ xem</strong>. Liên hệ quản trị viên để được cấp quyền chỉnh sửa.</p>

                <?php if ($edit_row): ?>
                    <div style="margin-top:12px;">
                        <h4>Thông tin hạng (chỉ đọc)</h4>
                        <div class="form-grid" style="margin-top:8px;">
                            <div>
                                <label>Mã</label>
                                <div class="readonly"><?= htmlspecialchars($edit_row['ma'] ?? '') ?></div>
                            </div>
                            <div>
                                <label>Tên</label>
                                <div class="readonly"><?= htmlspecialchars($edit_row['ten'] ?? '') ?></div>
                            </div>
                            <div>
                                <label>Mô tả</label>
                                <div class="readonly"><?= nl2br(htmlspecialchars($edit_row['mo_ta'] ?? '')) ?></div>
                            </div>
                            <div>
                                <label>Tiện ích</label>
                                <div class="readonly"><?= htmlspecialchars($edit_row['tien_ich'] ?? '') ?></div>
                            </div>
                            <div>
                                <label>Màu sắc</label>
                                <div class="readonly">
                                    <?php if (!empty($edit_row['mau_sac'])): ?>
                                        <span class="badge-color" style="background:<?= htmlspecialchars($edit_row['mau_sac']) ?>">
                                            <?= htmlspecialchars($edit_row['mau_sac']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Danh sách hạng ghế</h3>
            <table class="tbl" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Mã</th>
                        <th>Tên</th>
                        <th>Mô tả</th>
                        <th>Tiện ích</th>
                        <th>Màu sắc</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" class="muted">Chưa có hạng ghế nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= intval($r['id']) ?></td>
                                <td><strong><?= htmlspecialchars($r['ma']) ?></strong></td>
                                <td><?= htmlspecialchars($r['ten']) ?></td>
                                <td><?= htmlspecialchars($r['mo_ta'] ?? '') ?></td>
                                <td><?= htmlspecialchars($r['tien_ich'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($r['mau_sac'])): ?>
                                        <span class="badge-color" style="background:<?= htmlspecialchars($r['mau_sac']) ?>">
                                            <?= htmlspecialchars($r['mau_sac']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($is_admin)): ?>
                                        <a href="index.php?p=classes&edit=<?= intval($r['id']) ?>" class="btn outline">Sửa</a>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Xóa hạng ghế?')">
                                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="id" value="<?= intval($r['id']) ?>">
                                            <button class="btn danger" name="action" value="delete">Xóa</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted">Chỉ xem</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="page-actions">
            <a class="btn" href="index.php?p=admin">Quay lại</a>
        </div>
    </main>

    <footer>
        <div class="container">© 2025 VNAir Ticket</div>
    </footer>
</body>

</html>
