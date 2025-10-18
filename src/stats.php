<?php
header('Content-Type: application/json; charset=utf-8');
// ...
require_once __DIR__ . '/config.php';


// giả sử bạn dùng PostgreSQL như hiện tại
// Ví dụ: tổng sản phẩm
$res1 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM products");
$row1 = pg_fetch_assoc($res1);
$totalProducts = intval($row1['cnt']);

// Ví dụ: số sản phẩm tồn thấp – giả sử bạn có cột reorder_level hoặc bạn đặt ngưỡng 10
$res2 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM products WHERE quantity <= 10");
$row2 = pg_fetch_assoc($res2);
$lowStockCount = intval($row2['cnt']);

// Ví dụ: tổng số danh mục
$res3 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM categories");
$row3 = pg_fetch_assoc($res3);
$totalCategories = intval($row3['cnt']);

echo json_encode([
  'totalProducts' => $totalProducts,
  'lowStockCount' => $lowStockCount,
  'totalCategories' => $totalCategories
], JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Thống kê kho vật tư</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Dashboard Thống kê kho vật tư</h1>

    <!-- Thông số nhanh -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
      <div class="bg-white shadow rounded-lg p-6 text-center">
        <div class="text-2xl font-semibold text-blue-600" id="totalProducts">-</div>
        <div class="mt-2 text-sm text-gray-600">Tổng số sản phẩm</div>
      </div>
      <div class="bg-white shadow rounded-lg p-6 text-center">
        <div class="text-2xl font-semibold text-red-600" id="lowStockCount">-</div>
        <div class="mt-2 text-sm text-gray-600">Sản phẩm tồn kho thấp</div>
      </div>
      <div class="bg-white shadow rounded-lg p-6 text-center">
        <div class="text-2xl font-semibold text-green-600" id="totalCategories">-</div>
        <div class="mt-2 text-sm text-gray-600">Tổng số danh mục</div>
      </div>
    </div>

    <!-- Biểu đồ phân bổ theo danh mục -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Sản phẩm theo danh mục</h2>
      <canvas id="chartByCategory"></canvas>
    </div>

    <!-- Biểu đồ sản phẩm tồn nhiều -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Top 5 sản phẩm tồn nhiều</h2>
      <canvas id="chartTopFive"></canvas>
    </div>
  </div>

  <script>
    const statsApi = './api/stats.php'; // endpoint bạn tạo
    const byCategoryApi = './api/stats_by_category.php'; // endpoint trả dữ liệu phân bổ
    const topFiveApi = './api/stats_top5.php'; // endpoint trả dữ liệu top5 tồn nhiều

    async function loadStats() {
      try {
        const res = await fetch(statsApi);
        if (!res.ok) throw new Error('Status ' + res.status);
        const data = await res.json();
        document.getElementById('totalProducts').textContent = data.totalProducts;
        document.getElementById('lowStockCount').textContent = data.lowStockCount;
        document.getElementById('totalCategories').textContent = data.totalCategories;
      } catch (err) {
        console.error('Error loading stats:', err);
      }
    }

    async function loadChartByCategory() {
      try {
        const res = await fetch(byCategoryApi);
        if (!res.ok) throw new Error('Status ' + res.status);
        const data = await res.json();
        const labels = data.map(item => item.category_name);
        const values = data.map(item => item.count);
        const ctx = document.getElementById('chartByCategory').getContext('2d');
        new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{
              label: 'Số sản phẩm',
              data: values,
              backgroundColor: labels.map((_, i) => `hsl(${i*40 % 360}, 70%, 60%)`)
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      } catch (err) {
        console.error('Error loading by-category chart:', err);
      }
    }

    async function loadChartTopFive() {
      try {
        const res = await fetch(topFiveApi);
        if (!res.ok) throw new Error('Status ' + res.status);
        const data = await res.json();
        const labels = data.map(item => item.name);
        const values = data.map(item => item.quantity);
        const ctx = document.getElementById('chartTopFive').getContext('2d');
        new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Số lượng',
              data: values,
              backgroundColor: 'rgba(59,130,246,0.7)',
              borderColor: 'rgba(59,130,246,1)',
              borderWidth: 1
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
              x: { beginAtZero: true }
            },
            plugins: {
              legend: { display: false }
            }
          }
        });
      } catch (err) {
        console.error('Error loading top-five chart:', err);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadStats();
      loadChartByCategory();
      loadChartTopFive();
    });
  </script>
</body>
</html>


