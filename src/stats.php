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
  <!-- nội dung giao diện như mẫu bạn có -->
  <script>
    const statsApi = './api/stats.php';
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
    document.addEventListener('DOMContentLoaded', () => {
      loadStats();
      // load các biểu đồ nếu có
    });
  </script>
</body>
</html>
