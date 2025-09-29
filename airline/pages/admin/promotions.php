<?php
// pages/promotions.php
if (!function_exists('db')) {
         require_once dirname(__DIR__) . '/../config.php';
}
require_login(['ADMIN']);
$pdo = db();

// Flash helpers
function flash_ok($m)
{
         flash_set('ok', $m);
}
function flash_err($m)
{
         flash_set('err', $m);
}

// Load danh mục
$routes  = $pdo->query("SELECT id,ma_tuyen,di,den FROM tuyen_bay ORDER BY ma_tuyen")->fetchAll();
$flights = $pdo->query("SELECT id,so_hieu FROM chuyen_bay ORDER BY gio_di DESC LIMIT 100")->fetchAll();
$classes = $pdo->query("SELECT id,ma FROM hang_ghe ORDER BY id")->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         require_post_csrf();
         $action = $_POST['action'] ?? '';
         try {
                  if ($action === 'create' || $action === 'update') {
                           $id   = (int)($_POST['id'] ?? 0);
                           $ma   = trim($_POST['ma'] ?? '');
                           $kieu = $_POST['kieu'] ?? 'PHAN_TRAM';
                           $gia  = (float)($_POST['gia_tri'] ?? 0);
                           $batdau = $_POST['bat_dau'] ?? '';
                           $ketthuc = $_POST['ket_thuc'] ?? '';
                           $scope  = $_POST['scope'] ?? 'ALL';
                           $scope_id = $_POST['scope_id'] ?? null;
                           $kichhoat = isset($_POST['kich_hoat']) ? 1 : 0;

                           if ($ma === '') throw new RuntimeException("Thiếu mã.");
                           if (!in_array($kieu, ['PHAN_TRAM', 'SO_TIEN'], true)) $kieu = 'PHAN_TRAM';
                           if ($kieu === 'PHAN_TRAM' && ($gia <= 0 || $gia > 100)) throw new RuntimeException('Giảm % phải từ 1–100.');
                           if ($kieu === 'SO_TIEN' && $gia <= 0) throw new RuntimeException('Giảm số tiền phải > 0.');
                           if (!$batdau || !$ketthuc) throw new RuntimeException("Chọn thời gian.");

                           if ($action === 'create') {
                                    $sql = "INSERT INTO khuyen_mai(ma,kieu,gia_tri,bat_dau,ket_thuc,don_toi_thieu,giam_toi_da,gioi_han_luot,da_su_dung,kich_hoat)
        VALUES(?,?,?,?,?,?,?,?,?,?)";
                                    $pdo->prepare($sql)->execute([
                                             $ma,
                                             $kieu,
                                             $gia,
                                             $batdau,
                                             $ketthuc,
                                             0.00,
                                             null,
                                             null,
                                             0,
                                             $kichhoat
                                    ]);
                                    flash_ok("Đã thêm khuyến mãi.");
                           } else {
                                    if ($id <= 0) throw new RuntimeException("Thiếu ID.");
                                    $sql = "UPDATE khuyen_mai 
        SET ma=?, kieu=?, gia_tri=?, bat_dau=?, ket_thuc=?, kich_hoat=? 
        WHERE id=?";
                                    $pdo->prepare($sql)->execute([$ma, $kieu, $gia, $batdau, $ketthuc, $kichhoat, $id]);
                                    flash_ok("Đã cập nhật.");
                           }
                  } elseif ($action === 'delete') {
                           $id = (int)($_POST['id'] ?? 0);
                           if ($id <= 0) throw new RuntimeException("Thiếu ID.");
                           $pdo->prepare("DELETE FROM khuyen_mai WHERE id=?")->execute([$id]);
                           flash_ok("Đã xóa khuyến mãi #$id");
                  }
         } catch (Throwable $e) {
                  flash_err($e->getMessage());
         }
         redirect("index.php?p=promotions");
         exit;
}

// Danh sách
$rows = $pdo->query("SELECT * FROM khuyen_mai ORDER BY bat_dau DESC")->fetchAll();

// Edit
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id > 0) {
         foreach ($rows as $r) {
                  if ((int)$r['id'] === $edit_id) {
                           $edit_row = $r;
                           break;
                  }
         }
}
?>
<!doctype html>
<html lang="vi">

<head>
         <meta charset="utf-8">
         <title>Quản lý khuyến mãi</title>
         <link rel="stylesheet" href="assets/home.css">
         <style>
                  .form-grid {
                           display: grid;
                           grid-template-columns: repeat(2, 1fr);
                           gap: 14px 20px;
                           margin-top: 10px
                  }

                  .field {
                           display: flex;
                           flex-direction: column
                  }

                  .field label {
                           font-weight: 600;
                           margin-bottom: 4px
                  }

                  .tbl {
                           width: 100%;
                           border-collapse: collapse;
                           margin-top: 12px
                  }

                  .tbl th,
                  .tbl td {
                           border: 1px solid var(--border);
                           padding: 8px;
                           text-align: left
                  }

                  .tbl th {
                           background: #f0f4f8
                  }

                  .tbl tr:nth-child(even) {
                           background: #f9fbfd
                  }

                  .card {
                           background: #fff;
                           border: 1px solid var(--border);
                           border-radius: 10px;
                           padding: 16px;
                           margin: 16px 0
                  }
         </style>
</head>

<body>
         <?php include dirname(__DIR__) . '/../includes/header.php'; ?>
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