<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Báo cáo & Thống kê | VNAir Ticket</title>
    <link rel="stylesheet" href="assets/report.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <h2>📊 Báo cáo & Thống kê hệ thống</h2>

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
                    <button class="btn" type="submit">📅 Xem báo cáo</button>
                </div>
            </form>
        </div>

        <!-- Số liệu tổng quan -->
        <div class="summary">
            <div class="summary-card" style="background: linear-gradient(135deg,#007bff,#00bcd4);">
                <h3>Tổng vé bán</h3>
                <p><?= array_sum(array_column($tickets, 'so_ve')) ?></p>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg,#28a745,#66bb6a);">
                <h3>Tổng doanh thu</h3>
                <p><?= number_format(array_sum(array_column($tickets, 'doanh_thu'))) ?> ₫</p>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg,#fbc02d,#ffa000);">
                <h3>Số chuyến bay</h3>
                <p><?= array_sum(array_column($flights, 'so_luong')) ?></p>
            </div>
        </div>

        <!-- Biểu đồ vé & doanh thu -->
        <div class="card">
            <h3>📈 Vé bán & Doanh thu theo tháng</h3>
            <canvas id="ticketChart"></canvas>
        </div>

        <div class="card flight-status">
            <div class="card-header">
                <h3>🛫 Tình trạng chuyến bay</h3>
            </div>
            <div class="chart-row">
                <div class="chart-container">
                    <canvas id="flightChart"></canvas>
                </div>
                <div class="chart-legend">
                    <?php foreach ($flights as $f): ?>
                        <div class="legend-item">
                            <span class="legend-color" style="background: <?= match ($f['trang_thai']) {
                                                                                'LEN_KE_HOACH' => '#1a73e8',
                                                                                'HUY' => '#dc3545',
                                                                                'TRE' => '#ffc107',
                                                                                'DA_CAT_CANH' => '#28a745',
                                                                                'DA_HA_CANH' => '#6c757d',
                                                                                default => '#999'
                                                                            } ?>;"></span>
                            <span class="legend-label"><?= htmlspecialchars($f['trang_thai']) ?></span>
                            <span class="legend-value"><?= (int)$f['so_luong'] ?> chuyến</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Biểu đồ tỷ lệ hạng ghế -->
        <div class="card">
            <h3>💺 Tỷ lệ chọn hạng ghế</h3>
            <canvas id="classChart"></canvas>
        </div>
        <div class="page-actions">
            <a class="btn " href="index.php?p=admin">Quay lại trang admin</a>
        </div>
    </main>

    <script>
        // Dữ liệu PHP → JS
        const ticketData = <?= json_encode($tickets) ?>;
        const flightData = <?= json_encode($flights) ?>;
        const classData = <?= json_encode($classes) ?>;

        // Biểu đồ vé & doanh thu
        new Chart(document.getElementById('ticketChart'), {
            type: 'bar',
            data: {
                labels: ticketData.map(r => r.thang),
                datasets: [{
                        label: 'Số vé',
                        data: ticketData.map(r => r.so_ve),
                        backgroundColor: 'rgba(26,115,232,0.7)'
                    },
                    {
                        label: 'Doanh thu (VND)',
                        data: ticketData.map(r => r.doanh_thu),
                        backgroundColor: 'rgba(40,167,69,0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: false
                    }
                }
            }
        });

        // Biểu đồ trạng thái chuyến bay
        new Chart(document.getElementById('flightChart'), {
            type: 'pie',
            data: {
                labels: flightData.map(r => r.trang_thai),
                datasets: [{
                    data: flightData.map(r => r.so_luong),
                    backgroundColor: ['#1a73e8', '#dc3545', '#ffc107', '#28a745', '#6c757d']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Biểu đồ tỷ lệ chọn hạng ghế
        new Chart(document.getElementById('classChart'), {
            type: 'doughnut',
            data: {
                labels: classData.map(r => r.ten),
                datasets: [{
                    data: classData.map(r => r.so_ve),
                    backgroundColor: ['#17a2b8', '#ffc107', '#e83e8c', '#6f42c1']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>