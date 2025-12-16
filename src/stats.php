<?php
// src/stats.php

require_once 'assets/auth.php';  // Require file auth helper

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập']);
    exit;
}
if (!hasRole('accountant') && !hasRole('admin')) {  // Ví dụ: Chỉ accountant/admin xem stats
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền']);
    exit;
}
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
    <div class="flex justify-end gap-2 mb-4">
      <a href="./api/export_stats_csv.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Tải CSV</a>
      <a href="./api/export_stats_pdf.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">Tải PDF</a>
    </div>
    <!-- Thông số nhanh -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
      <div class="bg-white shadow rounded-lg p-6 text-center">
        <div class="text-2xl font-semibold text-blue-600" id="totalProducts"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div></div>
        <div class="mt-2 text-sm text-gray-600">Tổng số sản phẩm</div>
      </div>
      <div class="bg-white shadow rounded-lg p-6 text-center">
        <div class="text-2xl font-semibold text-red-600" id="lowStockCount"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-red-600 mx-auto"></div></div>
        <div class="mt-2 text-sm text-gray-600">Sản phẩm tồn kho thấp</div>
      </div>
      <div class="bg-white shadow rounded-lg p-6 text-center">
        <div class="text-2xl font-semibold text-green-600" id="totalCategories"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-green-600 mx-auto"></div></div>
        <div class="mt-2 text-sm text-gray-600">Tổng số danh mục</div>
      </div>
    </div>

    <!-- Biểu đồ phân bố theo danh mục -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Sản phẩm theo danh mục</h2>
      <div class="w-full max-w-md mx-auto" style="height:200px;">
        <canvas id="chartByCategory"></canvas>
      </div>
    </div>

    <!-- Biểu đồ Top 5 sản phẩm tồn nhiều -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Top 5 sản phẩm tồn nhiều</h2>
      <canvas id="chartTopFive" height="150"></canvas>
    </div>
  </div>

  <script>
    const statsApi = './api/stats.php';  // Đúng nếu chạy từ src/
    const byCategoryApi = './api/stats_by_category.php';
    const topFiveApi = './api/stats_top5.php';

    async function loadStats() {
      try {
        console.log('Loading stats from:', statsApi);  // Debug
        const res = await fetch(statsApi);
        if (!res.ok) throw new Error('HTTP ' + res.status + ': ' + res.statusText);
        const data = await res.json();
        console.log('Stats data:', data);  // Debug
        if (data.error) throw new Error(data.error);
        document.getElementById('totalProducts').textContent = data.totalProducts || 0;
        document.getElementById('lowStockCount').textContent = data.lowStockCount || 0;
        document.getElementById('totalCategories').textContent = data.totalCategories || 0;
      } catch (err) {
        console.error('Error loading stats:', err);
        // Hiển thị lỗi trên UI
        document.getElementById('totalProducts').textContent = 'Lỗi';
        document.getElementById('lowStockCount').textContent = 'Lỗi';
        document.getElementById('totalCategories').textContent = 'Lỗi';
      }
    }

    async function loadChartByCategory() {
      try {
        console.log('Loading chart by category from:', byCategoryApi);  // Debug
        const res = await fetch(byCategoryApi);
        if (!res.ok) throw new Error('HTTP ' + res.status + ': ' + res.statusText);
        const data = await res.json();
        console.log('By category data:', data);  // Debug
        if (!Array.isArray(data) || data.length === 0) {
          console.warn('No data for category chart');
          return;
        }
        const labels = data.map(item => item.category_name || item.name);  // Linh hoạt nếu key khác
        const values = data.map(item => item.count || item.product_count);
        const ctx = document.getElementById('chartByCategory').getContext('2d');
        new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{
              data: values,
              backgroundColor: labels.map((_, i) => `hsl(${(i * 360 / labels.length) % 360}, 70%, 60%)`)
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      } catch (err) {
        console.error('Error loading category chart:', err);
      }
    }

    async function loadChartTopFive() {
      try {
        console.log('Loading top 5 chart from:', topFiveApi);  // Debug
        const res = await fetch(topFiveApi);
        if (!res.ok) throw new Error('HTTP ' + res.status + ': ' + res.statusText);
        const data = await res.json();
        console.log('Top 5 data:', data);  // Debug
        if (!Array.isArray(data) || data.length === 0) {
          console.warn('No data for top 5 chart');
          return;
        }
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