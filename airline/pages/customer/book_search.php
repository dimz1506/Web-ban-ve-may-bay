<?php
// pages/customer/book_search.php — Tìm kiếm và đặt chuyến bay
if (!function_exists('db')) { require_once dirname(__DIR__).'/config.php'; }
require_login(['CUSTOMER']);

$user = me();
$flights = [];
$searchParams = [];

// Xử lý tìm kiếm
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['di']) && !empty($_GET['den']) && !empty($_GET['ngay'])) {
    $di = $_GET['di'];
    $den = $_GET['den'];
    $ngay = $_GET['ngay'];
    $so_khach = $_GET['so_khach'] ?? 1;
    $hang_ghe = $_GET['hang_ghe'] ?? 'ECON';
    
    $searchParams = compact('di', 'den', 'ngay', 'so_khach', 'hang_ghe');
    
    try {
        // Tìm kiếm chuyến bay thực từ database
        $sql = "
            SELECT cb.*, tb.di, tb.den, sb1.ten as di_ten, sb2.ten as den_ten,
                   cbg.gia_co_ban, cbg.so_ghe_con, hg.ma as hang_ghe, hg.ten as hang_ghe_ten
            FROM chuyen_bay cb
            JOIN tuyen_bay tb ON tb.id = cb.tuyen_bay_id
            JOIN san_bay sb1 ON sb1.ma = tb.di
            JOIN san_bay sb2 ON sb2.ma = tb.den
            JOIN chuyen_bay_gia_hang cbg ON cbg.chuyen_bay_id = cb.id
            JOIN hang_ghe hg ON hg.id = cbg.hang_ghe_id
            WHERE tb.di = ? AND tb.den = ? 
            AND DATE(cb.gio_di) = ?
            AND cb.trang_thai = 'LEN_KE_HOACH'
            AND cbg.so_ghe_con > 0
        ";
        
        $params = [$di, $den, $ngay];
        
        if ($hang_ghe !== 'ALL') {
            $sql .= " AND hg.ma = ?";
            $params[] = $hang_ghe;
        }
        
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $flights = $stmt->fetchAll();
        
        // Thêm thời gian bay tính toán
        foreach ($flights as &$flight) {
            $start = new DateTime($flight['gio_di']);
            $end = new DateTime($flight['gio_den']);
            $diff = $start->diff($end);
            $flight['thoi_gian_bay'] = $diff->h . 'h ' . $diff->i . 'm';
        }
        
    } catch (Exception $e) {
        $error = 'Có lỗi xảy ra khi tìm kiếm: ' . $e->getMessage();
    }
}

// Xử lý đặt vé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_flight'])) {
    require_post_csrf();
    
    $flightId = $_POST['flight_id'] ?? '';
    $so_khach = $_POST['so_khach'] ?? 1;
    
    if ($flightId && $so_khach > 0) {
        // Redirect đến trang thanh toán với thông tin chuyến bay
        $params = http_build_query([
            'flight_id' => $flightId,
            'so_khach' => $so_khach,
            'di' => $_POST['di'] ?? '',
            'den' => $_POST['den'] ?? '',
            'ngay' => $_POST['ngay'] ?? ''
        ]);
        header('Location: index.php?p=payment&' . $params);
        exit;
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tìm chuyến bay | VNAir Ticket</title>
    <link rel="stylesheet" href="assets/home.css">
    <style>
        :root {
            --primary: #0b4f7d;
            --primary-light: #0a6aa7;
            --accent: #f5c242;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --white: #ffffff;
            --border-radius: 12px;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--gray-800);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 20px 0;
            box-shadow: var(--shadow-lg);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 700;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .nav-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-menu a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Main Content */
        .main-content {
            padding: 40px 0;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 15px 0;
        }

        .page-subtitle {
            font-size: 16px;
            color: var(--gray-600);
            margin: 0;
        }

        /* Search Form */
        .search-form-container {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 40px;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input,
        .form-select {
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(11, 79, 125, 0.1);
        }

        .search-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .search-btn:hover {
            background: var(--primary-light);
        }

        /* Results */
        .results-container {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .results-header {
            background: var(--gray-50);
            padding: 20px 30px;
            border-bottom: 1px solid var(--gray-200);
        }

        .results-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .results-count {
            font-size: 14px;
            color: var(--gray-600);
            margin-top: 5px;
        }

        /* Flight Cards */
        .flight-card {
            border-bottom: 1px solid var(--gray-200);
            padding: 25px 30px;
            transition: background-color 0.2s;
        }

        .flight-card:hover {
            background: var(--gray-50);
        }

        .flight-card:last-child {
            border-bottom: none;
        }

        .flight-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .flight-number {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }

        .flight-price {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .flight-route {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 30px;
            align-items: center;
            margin-bottom: 20px;
        }

        .airport-info {
            text-align: center;
        }

        .airport-code {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 5px;
        }

        .airport-name {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 5px;
        }

        .flight-time {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .flight-details {
            text-align: center;
            padding: 15px;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .flight-duration {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 5px;
        }

        .flight-type {
            font-size: 12px;
            color: var(--gray-500);
        }

        .flight-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .flight-class {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .seats-available {
            font-size: 14px;
            color: var(--gray-600);
        }

        .seats-available.low {
            color: var(--warning);
            font-weight: 600;
        }

        .seats-available.critical {
            color: var(--danger);
            font-weight: 600;
        }

        .flight-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: var(--gray-500);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 10px 0;
        }

        .empty-desc {
            font-size: 14px;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .flight-route {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .flight-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .flight-info {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .flight-actions {
                justify-content: stretch;
            }
            
            .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">✈</div>
                    <span>VNAir Ticket</span>
                </div>
                
                <nav class="nav-menu">
                    <a href="index.php?p=customer">Trang chủ</a>
                    <a href="index.php?p=book_search" class="active">Tìm chuyến</a>
                    <a href="index.php?p=my_tickets">Vé của tôi</a>
                    <a href="index.php?p=profile">Thông tin cá nhân</a>
                    <a href="index.php?p=contact">Hỗ trợ</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Tìm chuyến bay</h1>
                <p class="page-subtitle">Tìm kiếm và đặt vé máy bay nhanh chóng, giá tốt nhất</p>
            </div>

            <!-- Search Form -->
            <div class="search-form-container">
                <form method="GET" class="search-form">
                    <div class="form-group">
                        <label class="form-label" for="di">Đi từ</label>
                        <select id="di" name="di" class="form-select" required>
                            <option value="">Chọn sân bay đi</option>
                            <option value="HAN" <?= ($searchParams['di'] ?? '') === 'HAN' ? 'selected' : '' ?>>HAN - Hà Nội (Nội Bài)</option>
                            <option value="SGN" <?= ($searchParams['di'] ?? '') === 'SGN' ? 'selected' : '' ?>>SGN - TP.HCM (Tân Sơn Nhất)</option>
                            <option value="DAD" <?= ($searchParams['di'] ?? '') === 'DAD' ? 'selected' : '' ?>>DAD - Đà Nẵng</option>
                            <option value="HPH" <?= ($searchParams['di'] ?? '') === 'HPH' ? 'selected' : '' ?>>HPH - Hải Phòng (Cát Bi)</option>
                            <option value="CXR" <?= ($searchParams['di'] ?? '') === 'CXR' ? 'selected' : '' ?>>CXR - Nha Trang (Cam Ranh)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="den">Đến</label>
                        <select id="den" name="den" class="form-select" required>
                            <option value="">Chọn sân bay đến</option>
                            <option value="HAN" <?= ($searchParams['den'] ?? '') === 'HAN' ? 'selected' : '' ?>>HAN - Hà Nội (Nội Bài)</option>
                            <option value="SGN" <?= ($searchParams['den'] ?? '') === 'SGN' ? 'selected' : '' ?>>SGN - TP.HCM (Tân Sơn Nhất)</option>
                            <option value="DAD" <?= ($searchParams['den'] ?? '') === 'DAD' ? 'selected' : '' ?>>DAD - Đà Nẵng</option>
                            <option value="HPH" <?= ($searchParams['den'] ?? '') === 'HPH' ? 'selected' : '' ?>>HPH - Hải Phòng (Cát Bi)</option>
                            <option value="CXR" <?= ($searchParams['den'] ?? '') === 'CXR' ? 'selected' : '' ?>>CXR - Nha Trang (Cam Ranh)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="ngay">Ngày đi</label>
                        <input type="date" id="ngay" name="ngay" class="form-input" 
                               value="<?= $searchParams['ngay'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="so_khach">Số khách</label>
                        <select id="so_khach" name="so_khach" class="form-select">
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                                <option value="<?= $i ?>" <?= ($searchParams['so_khach'] ?? 1) == $i ? 'selected' : '' ?>><?= $i ?> khách</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="hang_ghe">Hạng ghế</label>
                        <select id="hang_ghe" name="hang_ghe" class="form-select">
                            <option value="ALL" <?= ($searchParams['hang_ghe'] ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="ECON" <?= ($searchParams['hang_ghe'] ?? '') === 'ECON' ? 'selected' : '' ?>>Phổ thông</option>
                            <option value="PREM" <?= ($searchParams['hang_ghe'] ?? '') === 'PREM' ? 'selected' : '' ?>>Phổ thông đặc biệt</option>
                            <option value="BUSI" <?= ($searchParams['hang_ghe'] ?? '') === 'BUSI' ? 'selected' : '' ?>>Thương gia</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="search-btn">
                            <span>🔍</span>
                            Tìm chuyến
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results -->
            <?php if (!empty($searchParams)): ?>
            <div class="results-container">
                <div class="results-header">
                    <h2 class="results-title">Kết quả tìm kiếm</h2>
                    <div class="results-count">
                        Tìm thấy <?= count($flights) ?> chuyến bay từ <?= $searchParams['di'] ?> đến <?= $searchParams['den'] ?> 
                        ngày <?= date('d/m/Y', strtotime($searchParams['ngay'])) ?>
                    </div>
                </div>

                <?php if (empty($flights)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">✈️</div>
                        <h3 class="empty-title">Không tìm thấy chuyến bay</h3>
                        <p class="empty-desc">Vui lòng thử lại với các tiêu chí khác</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($flights as $flight): ?>
                        <div class="flight-card">
                            <div class="flight-header">
                                <span class="flight-number"><?= $flight['so_hieu'] ?></span>
                                <span class="flight-price"><?= number_format($flight['gia_co_ban']) ?> VND</span>
                            </div>

                            <div class="flight-route">
                                <div class="airport-info">
                                    <div class="airport-code"><?= $flight['di'] ?></div>
                                    <div class="airport-name"><?= $flight['di'] === 'HAN' ? 'Hà Nội' : ($flight['di'] === 'SGN' ? 'TP.HCM' : 'Đà Nẵng') ?></div>
                                    <div class="flight-time"><?= date('H:i', strtotime($flight['gio_di'])) ?></div>
                                </div>

                                <div class="flight-details">
                                    <div class="flight-duration"><?= $flight['thoi_gian_bay'] ?></div>
                                    <div class="flight-type">Bay thẳng</div>
                                </div>

                                <div class="airport-info">
                                    <div class="airport-code"><?= $flight['den'] ?></div>
                                    <div class="airport-name"><?= $flight['den'] === 'HAN' ? 'Hà Nội' : ($flight['den'] === 'SGN' ? 'TP.HCM' : 'Đà Nẵng') ?></div>
                                    <div class="flight-time"><?= date('H:i', strtotime($flight['gio_den'])) ?></div>
                                </div>
                            </div>

                            <div class="flight-info">
                                <span class="flight-class"><?= $flight['hang_ghe_ten'] ?></span>
                                <span class="seats-available <?= $flight['so_ghe_con'] <= 5 ? 'critical' : ($flight['so_ghe_con'] <= 10 ? 'low' : '') ?>">
                                    Còn <?= $flight['so_ghe_con'] ?> ghế
                                </span>
                            </div>

                            <div class="flight-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="flight_id" value="<?= $flight['id'] ?>">
                                    <input type="hidden" name="di" value="<?= $searchParams['di'] ?>">
                                    <input type="hidden" name="den" value="<?= $searchParams['den'] ?>">
                                    <input type="hidden" name="ngay" value="<?= $searchParams['ngay'] ?>">
                                    <input type="hidden" name="so_khach" value="<?= $searchParams['so_khach'] ?>">
                                    <button type="submit" name="book_flight" class="btn btn-primary">
                                        <span>🎫</span>
                                        Đặt vé
                                    </button>
                                </form>
                                <button class="btn btn-secondary" onclick="showFlightDetails(<?= $flight['id'] ?>)">
                                    <span>ℹ️</span>
                                    Chi tiết
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function showFlightDetails(flightId) {
            alert('Chi tiết chuyến bay ' + flightId + ' sẽ được hiển thị ở đây');
        }

        // Set minimum date to today
        document.getElementById('ngay').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
