<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Báo cáo & Thống kê | VNAir Ticket</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { background: #f5f7fb; }
    main.container { max-width: 900px; margin: 40px auto; }
    h2 { margin-bottom: 1rem; color: #0b63d6; }
    .filter-form {
      display: flex; flex-wrap: wrap; gap: 16px; align-items: end;
      background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .filter-form .field { flex: 1; min-width: 100px; }
    .filter-form .actions { flex-shrink: 0; }
    .summary {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 16px; margin-top: 24px;
    }
    .summary-card {
      color: white; border-radius: 12px; padding: 20px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1); text-align: center;
    }
    .summary-card h3 { margin-bottom: 8px; font-size: 1.1rem; font-weight: 600; }
    .summary-card p { font-size: 1.8rem; font-weight: bold; margin: 0; }
    .card {
      background: white; border-radius: 12px; padding: 20px;
      margin-top: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .card h3 { margin-bottom: 1rem; color: #333; }
    .chart-row {
      display: flex; flex-wrap: wrap; gap: 20px;
    }
    .chart-container { flex: 1 1 400px; }
    .chart-legend { flex: 1 1 200px; display: flex; flex-direction: column; gap: 8px; }
    .legend-item { display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-radius: 6px; background: #f9fafb; }
    .legend-color { width: 14px; height: 14px; border-radius: 3px; display: inline-block; margin-right: 10px; }
    .page-actions { margin-top: 24px; text-align: right; }
  </style>
</head>


<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="container">
    <h2>📊 Báo cáo & Thống kê hệ thống</h2>

    <!-- Bộ lọc -->
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
        <button class="btn primary" type="submit">📅 Xem báo cáo</button>
      </div>
    </form>

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
      <canvas id="ticketChart" height="100"></canvas>
    </div>

    <!-- Tình trạng chuyến bay -->
    <div class="card flight-status">
      <h3>🛫 Tình trạng chuyến bay</h3>
      <div class="chart-row">
        <div class="chart-container"><canvas id="flightChart"></canvas></div>
        <div class="chart-legend">
          <?php foreach ($flights as $f): ?>
            <div class="legend-item">
              <div style="display:flex;align-items:center;gap:8px;">
                <span class="legend-color" style="background:
                  <?= match ($f['trang_thai']) {
                    'LEN_KE_HOACH' => '#1a73e8',
                    'HUY' => '#dc3545',
                    'TRE' => '#ffc107',
                    'DA_CAT_CANH' => '#28a745',
                    'DA_HA_CANH' => '#6c757d',
                    default => '#999'
                  } ?>;"></span>
                <span><?= htmlspecialchars($f['trang_thai']) ?></span>
              </div>
              <strong><?= (int)$f['so_luong'] ?> chuyến</strong>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Tỷ lệ chọn hạng ghế -->
    <div class="card">
      <h3>💺 Tỷ lệ chọn hạng ghế</h3>
      <canvas id="classChart" height="100"></canvas>
    </div>

    <div class="page-actions">
      <a class="btn secondary" href="index.php?p=admin">⬅ Quay lại trang Admin</a>
    </div>
  </main>

  <script>
    const ticketData = <?= json_encode($tickets) ?>;
    const flightData = <?= json_encode($flights) ?>;
    const classData = <?= json_encode($classes) ?>;

    new Chart(document.getElementById('ticketChart'), {
      type: 'bar',
      data: {
        labels: ticketData.map(r => r.thang),
        datasets: [
          { label: 'Số vé', data: ticketData.map(r => r.so_ve), backgroundColor: '#2196f3' },
          { label: 'Doanh thu (VND)', data: ticketData.map(r => r.doanh_thu), backgroundColor: '#28a745' }
        ]
      },
      options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('flightChart'), {
      type: 'pie',
      data: {
        labels: flightData.map(r => r.trang_thai),
        datasets: [{ data: flightData.map(r => r.so_luong),
          backgroundColor: ['#1a73e8','#dc3545','#ffc107','#28a745','#6c757d'] }]
      },
      options: { plugins: { legend: { position: 'right' } } }
    });

    new Chart(document.getElementById('classChart'), {
      type: 'doughnut',
      data: {
        labels: classData.map(r => r.ten),
        datasets: [{ data: classData.map(r => r.so_ve),
          backgroundColor: ['#17a2b8','#ffc107','#e83e8c','#6f42c1'] }]
      },
      options: { plugins: { legend: { position: 'bottom' } } }
    });
  </script>
</body>

</html>