<?php
// session_start();
// if (!isset($_SESSION['user_id'])) {
//   header('Location: index.php?p=login');
//   exit;
// }
if (!function_exists('db')) require_once dirname(__DIR__,2).'/config.php';
$code = $_GET['code'] ?? '';
$st = db()->prepare("
  SELECT v.so_ve, v.trang_thai, v.so_ghe, hk.ho_ten, cb.so_hieu, s1.ten AS san_bay_di, s2.ten AS san_bay_den
  FROM ve v
  JOIN hanh_khach hk ON v.hanh_khach_id = hk.id
  JOIN chuyen_bay cb ON v.chuyen_bay_id = cb.id
  JOIN tuyen_bay tb ON cb.tuyen_bay_id = tb.id
  JOIN san_bay s1 ON tb.di = s1.ma
  JOIN san_bay s2 ON tb.den = s2.ma
  WHERE v.so_ve = ?
");
$st->execute([$code]);
$ve = $st->fetch();
if (!$ve) die("❌ Không tìm thấy vé.");
?>
<h2>Vé điện tử <?=htmlspecialchars($ve['so_ve'])?></h2>
<p>Hành khách: <?=htmlspecialchars($ve['ho_ten'])?></p>
<p>Chuyến: <?=htmlspecialchars($ve['so_hieu'])?></p>
<p><?=htmlspecialchars($ve['san_bay_di'])?> → <?=htmlspecialchars($ve['san_bay_den'])?></p>
<p>Ghế: <?=htmlspecialchars($ve['so_ghe'])?></p>
<p>Trạng thái: <?=htmlspecialchars($ve['trang_thai'])?></p>
