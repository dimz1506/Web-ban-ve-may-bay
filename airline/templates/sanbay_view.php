<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý sân bay</title>
    <link rel="stylesheet" href="assets/sanbay.css">
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
    <h2>✈️ Quản lý sân bay</h2>

    <?php if ($m = flash_get('ok')): ?><div class="ok"><?= $m ?></div><?php endif; ?>
    <?php if ($m = flash_get('err')): ?><div class="err"><?= $m ?></div><?php endif; ?>

    <?php if (current_user_role() === 'ADMIN'): ?>
        <div class="card">
            <h3>➕ Thêm sân bay</h3>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <div class="form-grid">
                    <div class="field">
                        <label>Mã sân bay</label>
                        <input name="ma" maxlength="3" required placeholder="VD: HAN">
                    </div>
                    <div class="field">
                        <label>Tên sân bay</label>
                        <input name="ten" required placeholder="VD: Nội Bài">
                    </div>
                    <div class="field">
                        <label>Thành phố</label>
                        <input name="thanh_pho" placeholder="VD: Hà Nội">
                    </div>
                    <div class="field">
                        <label>Quốc gia</label>
                        <input name="quoc_gia" value="Việt Nam">
                    </div>
                    <div class="field">
                        <label>Múi giờ</label>
                        <input name="mui_gio" value="Asia/Ho_Chi_Minh">
                    </div>
                </div>
                <div class="submit-row">
                    <button class="btn" name="action" value="create">💾 Lưu</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>📋 Danh sách sân bay</h3>
        <table class="tbl">
            <tr>
                <th>Mã</th>
                <th>Tên</th>
                <th>Thành phố</th>
                <th>Quốc gia</th>
                <?php if (current_user_role() === 'ADMIN'): ?>
                    <th style="text-align:center;">Thao tác</th>
                <?php endif; ?>
            </tr>

            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['ma']) ?></td>
                    <td><?= htmlspecialchars($r['ten']) ?></td>
                    <td><?= htmlspecialchars($r['thanh_pho']) ?></td>
                    <td><?= htmlspecialchars($r['quoc_gia']) ?></td>

                    <?php if (current_user_role() === 'ADMIN'): ?>
                        <td style="text-align:center;">
                            <form method="post" onsubmit="return confirm('Xóa sân bay này?')" style="display:inline;">
                                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id" value="<?= $r['ma'] ?>">
                                <button class="btn danger" name="action" value="delete">🗑 Xóa</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="page-actions">
        <?php if (current_user_role() === 'ADMIN'): ?>
            <a class="btn" href="index.php?p=admin">Quay lại</a>
        <?php else: ?>
            <a class="btn" href="index.php?p=staff">Quay lại</a>
        <?php endif; ?>
    </div>
</main>


</body>
</html>
