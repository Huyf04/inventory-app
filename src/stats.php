<?php
// src/stats.php
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Thống kê kho vật tư</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
  <div class="max-w-5xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Dashboard Thống kê kho vật tư</h1>

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

    <!-- Biểu đồ phân bố theo danh mục -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Sản phẩm theo danh mục</h2>
      <canvas id="chartByCategory" height="150"></canvas>
    </div>

    <!-- Biểu đồ Top 5 sản phẩm tồn nhiều -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Top 5 sản phẩm tồn nhiều</h2>
      <canvas id="chartTopFive" height="150"></canvas>
    </div>
  </div>

  <script>
    const statsApi = './api/stats.php';
    const byCategoryApi = './api/stats_by_category.php';
    const topFiveApi = './api/stats_top5.php';

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

        new Chart(document.getElementById('chartByCategory'), {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{
              label: 'Số lượng',
              data: values,
              backgroundColor: labels.map((_, i) => `hsl(${i * 60}, 70%, 60%)`)
            }]
          },
          options: {
            plugins: { legend: { position: 'bottom' } }
          }
        });
      } catch (err) {
        console.error('Error loading category chart:', err);
      }
    }

    async function loadChartTopFive() {
      try {
        const res = await fetch(topFiveApi);
        if (!res.ok) throw new Error('Status ' + res.status);
        const data = await res.json();
        const labels = data.map(item => item.name);
        const values = data.map(item => item.quantity);

        new Chart(document.getElementById('chartTopFive'), {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Số lượng tồn',
              data: values,
              backgroundColor: 'rgba(59,130,246,0.7)',
              borderColor: 'rgba(59,130,246,1)',
              borderWidth: 1
            }]
          },
          options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
          }
        });
      } catch (err) {
        console.error('Error loading top 5 chart:', err);
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
