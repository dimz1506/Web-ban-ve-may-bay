<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo & Thống kê | VNAir Ticket</title>
    <link rel="stylesheet" href="assets/admin.css">
    <link rel="stylesheet" href="assets/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <h2>Báo cáo & Thống kê</h2>

        <!-- Bộ lọc -->
        <div class="card">
            <form method="get" class="filter-form">
                <div class="field">
                    <label for="from">Từ ngày</label>
                    <input type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>">
                </div>
                <div class="field">
                    <label for="to">Đến ngày</label>
                    <input type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>">
                </div>
                <div class="actions">
                    <button class="btn" type="submit">📊 Xem báo cáo</button>
                </div>
            </form>
        </div>

        <!-- Số liệu nhanh -->
        <div class="summary">
            <div class="summary-card">
                <h3>Tổng vé bán</h3>
                <p><?= array_sum(array_column($tickets, 'so_ve')) ?></p>
            </div>
            <div class="summary-card">
                <h3>Tổng doanh thu</h3>
                <p><?= number_format(array_sum(array_column($tickets, 'doanh_thu'))) ?> VND</p>
            </div>
            <div class="summary-card">
                <h3>Số chuyến bay</h3>
                <p><?= array_sum(array_column($flights, 'so_luong')) ?></p>
            </div>
        </div>

        <!-- Vé bán & doanh thu -->
        <div class="card">
            <h3>📈 Vé bán & Doanh thu theo tháng</h3>
            <canvas id="ticketChart" height="120"></canvas>
        </div>

        <!-- Tình trạng chuyến bay -->
        <div class="card">
            <h3>🛫 Tình trạng chuyến bay</h3>
            <canvas id="flightChart" height="120"></canvas>
        </div>

        <!-- Hạng ghế -->
        <div class="card">
            <h3>💺 Tỷ lệ chọn hạng ghế</h3>
            <canvas id="classChart" height="120"></canvas>
        </div>
    </main>

    <script>
        // Vé & doanh thu
        const ticketData = <?= json_encode($tickets) ?>;
        new Chart(document.getElementById('ticketChart'), {
            type: 'bar',
            data: {
                labels: ticketData.map(r => r.thang),
                datasets: [
                    {
                        label: 'Số vé',
                        data: ticketData.map(r => r.so_ve),
                        backgroundColor: '#007bff'
                    },
                    {
                        label: 'Doanh thu (VND)',
                        data: ticketData.map(r => r.doanh_thu),
                        backgroundColor: '#28a745'
                    }
                ]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        // Trạng thái chuyến bay
        const flightData = <?= json_encode($flights) ?>;
        new Chart(document.getElementById('flightChart'), {
            type: 'pie',
            data: {
                labels: flightData.map(r => r.trang_thai),
                datasets: [{
                    data: flightData.map(r => r.so_luong),
                    backgroundColor: ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6c757d']
                }]
            }
        });

        // Hạng ghế
        const classData = <?= json_encode($classes) ?>;
        new Chart(document.getElementById('classChart'), {
            type: 'doughnut',
            data: {
                labels: classData.map(r => r.ten),
                datasets: [{
                    data: classData.map(r => r.so_ve),
                    backgroundColor: ['#17a2b8', '#ffc107', '#e83e8c']
                }]
            }
        });
    </script>
</body>
</html>
