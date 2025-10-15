<?php
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();

$tuyen_bay_id = (int)($_GET['tuyen_bay_id'] ?? 0);
$hang_ghe_id = (int)($_GET['hang_ghe_id'] ?? 0);

if ($tuyen_bay_id <= 0 || $hang_ghe_id <= 0) {
    echo json_encode(['error' => 'Thiếu tham số']);
    exit;
}

$stmt = $pdo->prepare("SELECT gia_co_ban, hanh_ly_kg, duoc_hoan, phi_doi 
                       FROM gia_ve_mac_dinh 
                       WHERE tuyen_bay_id = ? AND hang_ghe_id = ?");
$stmt->execute([$tuyen_bay_id, $hang_ghe_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không có giá mặc định']);
}
