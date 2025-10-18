<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Thống kê kho vật tư</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
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

    <!-- Bạn có thể thêm biểu đồ ở đây nếu muốn -->
    <!-- Ví dụ: phân bố sản phẩm theo danh mục, top sản phẩm tồn nhiều, v.v. -->

  </div>

  <script>
    const statsApi = './api/stats.php';

    async function loadStats() {
      try {
        const res = await fetch(statsApi);
        if (!res.ok) throw new Error('Status ' + res.status);
        const data = await res.json();
        document.getElementById('totalProducts').textContent = data.totalProducts;
        document.getElementById('lowStockCount').textContent  = data.lowStockCount;
        document.getElementById('totalCategories').textContent= data.totalCategories;
      } catch (err) {
        console.error('Error loading stats:', err);
        alert('Lỗi khi tải dữ liệu thống kê. Xin kiểm tra console.');
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadStats();
    });
  </script>
</body>
</html>
