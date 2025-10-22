<?php
// session_start();
// if (!isset($_SESSION['user_id'])) {
//   header('Location: index.php?p=login');
//   exit;
// }

if (!function_exists('db')) { require_once dirname(__DIR__,2).'/config.php'; }
require_login(['CUSTOMER']);
$ve_id=(int)($_GET['ve_id'] ?? 0);


$st = db()->prepare("SELECT v.id, v.trang_thai, v.chuyen_bay_id, v.hang_ghe_id FROM ve v WHERE v.id=?");
$st->execute([$ve_id]);
$ve=$st->fetch();
if (!$ve) { flash_set('err','Không tìm thấy vé'); redirect('index.php?p=my_bookings'); }


try {
db()->beginTransaction();
if ($ve['trang_thai']!=='HUY') {
db()->prepare("UPDATE ve SET trang_thai='HUY' WHERE id=?")->execute([$ve_id]);
db()->prepare("UPDATE chuyen_bay_gia_hang SET so_ghe_con = so_ghe_con + 1 WHERE chuyen_bay_id=? AND hang_ghe_id=?")
->execute([$ve['chuyen_bay_id'],$ve['hang_ghe_id']]);
}
db()->commit();
flash_set('ok','Đã hủy vé');
} catch (Throwable $e) {
if (db()->inTransaction()) db()->rollBack();
flash_set('err',$e->getMessage());
}
redirect('index.php?p=my_bookings');