<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý giá vé mặc định | VNAir Ticket</title>
    <link rel="stylesheet" href="assets/fare.css">
    <link rel="stylesheet" href="assets/home.css">
</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <div class="layout">

            <!-- Main Content -->
            <section class="main">
                <div class="page-title">
                    <h1>💰 Quản lý giá vé mặc định</h1>
                </div>

                <?php if ($m = flash_get('ok')): ?><div class="ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
                <?php if ($m = flash_get('err')): ?><div class="err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

                <!-- Form thêm/sửa (chỉ cho ADMIN) -->
                <div class="card">
                    <?php if ($is_admin): ?>
                        <h3><?= $edit_row ? "✏️ Sửa giá vé #" . intval($edit_row['id']) : "➕ Thêm giá vé mặc định" ?></h3>
                        <form method="post" novalidate>
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= intval($edit_row['id']) ?>"><?php endif; ?>

                            <div class="grid">
                                <div class="field">
                                    <label>Tuyến bay</label>
                                    <select name="tuyen_bay_id" required>
                                        <option value="">-- Chọn tuyến --</option>
                                        <?php foreach ($routes as $r): ?>
                                            <option value="<?= intval($r['id']) ?>" <?= isset($edit_row['tuyen_bay_id']) && $edit_row['tuyen_bay_id'] == $r['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($r['ma_tuyen']) ?> (<?= htmlspecialchars($r['di']) ?>→<?= htmlspecialchars($r['den']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field">
                                    <label>Hạng ghế</label>
                                    <select name="hang_ghe_id" required>
                                        <option value="">-- Chọn hạng --</option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?= intval($c['id']) ?>" <?= isset($edit_row['hang_ghe_id']) && $edit_row['hang_ghe_id'] == $c['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['ten']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field">
                                    <label>Giá cơ bản (VND)</label>
                                    <input type="number" name="gia_co_ban" min="0" required value="<?= htmlspecialchars($edit_row['gia_co_ban'] ?? '') ?>" placeholder="Nhập giá cơ bản...">
                                </div>

                                <div class="field">
                                    <label>Hành lý (kg)</label>
                                    <input type="number" name="hanh_ly_kg" min="0" value="<?= htmlspecialchars($edit_row['hanh_ly_kg'] ?? '') ?>" placeholder="VD: 20">
                                </div>

                                <div class="field">
                                    <label>Phí đổi (VND)</label>
                                    <input type="number" name="phi_doi" min="0" value="<?= htmlspecialchars($edit_row['phi_doi'] ?? '') ?>" placeholder="VD: 50000">
                                </div>

                                <div class="field" style="display:flex;align-items:center;gap:8px;margin-top:28px;">
                                    <label>Được hoàn?</label>
                                    <input type="checkbox" name="duoc_hoan" <?= !empty($edit_row['duoc_hoan']) ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="submit-row">
                                <button class="btn" name="action" value="<?= $edit_row ? 'update' : 'create' ?>">💾 Lưu</button>
                                <?php if ($edit_row): ?><a class="btn outline" href="index.php?p=fare">Hủy</a><?php endif; ?>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Nếu là STAFF thì chỉ hiển thị thông báo, không hiện form -->
                        <h3>📌 Chế độ xem</h3>
                        <p class="muted">Bạn đang ở chế độ <strong>chỉ xem</strong>. Liên hệ quản trị viên để được cấp quyền chỉnh sửa.</p>
                        <?php if ($edit_row): ?>
                            <!-- Nếu có edit_row (khả năng nhỏ vì server đã chặn), hiển thị thông tin read-only -->
                            <div class="grid">
                                <div class="field">
                                    <label>Tuyến bay</label>
                                    <?php
                                        $r_label = '';
                                        foreach ($routes as $r) {
                                            if ($r['id'] == $edit_row['tuyen_bay_id']) {
                                                $r_label = htmlspecialchars($r['ma_tuyen']) . ' (' . htmlspecialchars($r['di']) . '→' . htmlspecialchars($r['den']) . ')';
                                                break;
                                            }
                                        }
                                    ?>
                                    <div class="readonly"><?= $r_label ?></div>
                                </div>
                                <div class="field">
                                    <label>Hạng ghế</label>
                                    <?php
                                        $c_label = '';
                                        foreach ($classes as $c) {
                                            if ($c['id'] == $edit_row['hang_ghe_id']) {
                                                $c_label = htmlspecialchars($c['ten']);
                                                break;
                                            }
                                        }
                                    ?>
                                    <div class="readonly"><?= $c_label ?></div>
                                </div>
                                <div class="field">
                                    <label>Giá cơ bản (VND)</label>
                                    <div class="readonly"><?= isset($edit_row['gia_co_ban']) ? number_format($edit_row['gia_co_ban']) . "₫" : '' ?></div>
                                </div>
                                <div class="field">
                                    <label>Hành lý (kg)</label>
                                    <div class="readonly"><?= isset($edit_row['hanh_ly_kg']) ? intval($edit_row['hanh_ly_kg']) . " kg" : '' ?></div>
                                </div>
                                <div class="field">
                                    <label>Phí đổi (VND)</label>
                                    <div class="readonly"><?= isset($edit_row['phi_doi']) ? number_format($edit_row['phi_doi']) . "₫" : '' ?></div>
                                </div>
                                <div class="field">
                                    <label>Hoàn vé</label>
                                    <div class="readonly"><?= !empty($edit_row['duoc_hoan']) ? '✅ Có' : '❌ Không' ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Danh sách giá vé -->
                <div class="card">
                    <h3>📋 Danh sách giá vé mặc định</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tuyến bay</th>
                                <th>Hạng ghế</th>
                                <th>Giá cơ bản</th>
                                <th>Hành lý</th>
                                <th>Phí đổi</th>
                                <th>Hoàn vé</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fares as $r): ?>
                                <tr>
                                    <td><?= intval($r['id']) ?></td>
                                    <td><?= htmlspecialchars($r['ma_tuyen']) ?> (<?= htmlspecialchars($r['di']) ?>→<?= htmlspecialchars($r['den']) ?>)</td>
                                    <td><?= htmlspecialchars($r['hang_ten']) ?></td>
                                    <td><?= number_format($r['gia_co_ban']) ?>₫</td>
                                    <td><?= intval($r['hanh_ly_kg']) ?> kg</td>
                                    <td><?= number_format($r['phi_doi']) ?>₫</td>
                                    <td><?= $r['duoc_hoan'] ? '✅ Có' : '❌ Không' ?></td>
                                    <td style="display:flex;gap:6px;align-items:center;">
                                        <?php if ($is_admin): ?>
                                            <a href="index.php?p=fare&edit=<?= intval($r['id']) ?>" class="btn outline">✏️ Sửa</a>
                                            <form method="post" style="display:inline" onsubmit="return confirm('Xóa giá vé này?')">
                                                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="id" value="<?= intval($r['id']) ?>">
                                                <button class="btn" name="action" value="delete">🗑 Xóa</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="muted">Chỉ xem</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($fares)): ?>
                                <tr><td colspan="8" class="muted">Chưa có giá vé mặc định nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="page-actions">
            <a class="btn" href="index.php?p=admin">Quay lại trang admin</a>
        </div>
        <br>
    </main>

    <footer>
        <div class="container">© 2025 VNAir Ticket</div>
    </footer>
</body>

</html>
