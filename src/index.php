<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Quản lý vật tư</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Custom styles for better table responsiveness */
    @media (max-width: 768px) {
      .table-container {
        overflow-x: auto;
      }
    }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <i class="fas fa-boxes text-3xl text-blue-600"></i>
          <h1 class="text-3xl font-bold text-gray-900">Quản lý vật tư</h1>
          <a href="categories.php" class="text-blue-600 hover:text-blue-900 font-medium">Quản lý Danh mục</a>
        </div>
        <div class="hidden md:block text-sm text-gray-500">
          Quản lý sản phẩm dễ dàng và hiệu quả
        </div>
      </div>
    </div>

    <!-- Search Section -->
    <div class="mb-6 bg-white shadow-sm rounded-lg p-4 border border-gray-200">
      <div class="flex flex-col md:flex-row gap-3 items-start md:items-center">
        <div class="flex-1 relative">
          <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          <input
            id="q"
            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            placeholder="Tìm theo tên, SKU..."
          />
        </div>
        <div class="flex gap-2 flex-wrap">
          <button id="btnSearch" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
            <i class="fas fa-search"></i>
            Tìm
          </button>
          <button id="btnRefresh" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
            <i class="fas fa-refresh"></i>
            Làm mới
          </button>
        </div>
      </div>
    </div>

    <!-- Message Box -->
    <div id="messageBox" class="my-4 hidden p-4 rounded-lg text-white shadow-lg transform transition-all duration-300"></div>

    <!-- Products Table -->
    <div class="mb-8 bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
      <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
          <i class="fas fa-list"></i>
          Danh sách sản phẩm
        </h2>
      </div>
      <div class="table-container">
        <table class="w-full table-auto" id="productTable">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên sản phẩm</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Danh mục</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn giá</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
            </tr>
          </thead>
          <tbody id="tbody" class="bg-white divide-y divide-gray-200">
            <!-- Rows sẽ được JS chèn vào -->
          </tbody>
        </table>
      </div>
      <!-- Empty state -->
      <div id="emptyState" class="hidden text-center py-12">
        <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-500 text-lg">Chưa có sản phẩm nào. Hãy thêm sản phẩm đầu tiên!</p>
      </div>
    </div>

    <!-- Product Form -->
    <div class="bg-white shadow-lg rounded-lg p-6 border border-gray-200">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2" id="formTitle">
          <i class="fas fa-plus-circle text-green-600"></i>
          Thêm sản phẩm mới
        </h2>
        <button id="btnToggleForm" class="md:hidden bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
          <i class="fas fa-chevron-up"></i>
        </button>
      </div>
      <form id="productForm" class="space-y-6">
        <input type="hidden" id="id" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
            <input id="sku" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required />
          </div>
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Tên sản phẩm</label>
            <input id="name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required />
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Số lượng</label>
            <input id="quantity" type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" min="0" />
          </div>
          <div>
            <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-2">Đơn giá (VND)</label>
            <input
              id="unit_price"
              type="text"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              placeholder="Nhập đơn giá..."
              oninput="formatCurrency(this)"
            />
          </div>
        </div>
        <div>
          <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Danh mục</label>
          <select id="category_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            <option value="">Chọn danh mục</option>
            <!-- Các option sẽ được JS chèn vào -->
          </select>
        </div>
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Mô tả</label>
          <textarea id="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-vertical"></textarea>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 flex-1 sm:flex-none">
            <i class="fas fa-save"></i>
            Lưu sản phẩm
          </button>
          <button type="button" id="btnReset" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 flex-1 sm:flex-none">
            <i class="fas fa-times"></i>
            Hủy
          </button>
        </div>
      </form>
    </div>
  </div>
  <script>
    const apiProducts = '/api/products.php';
    const apiCategories = '/api/categories.php';

    function formatCurrency(input) {
      let value = input.value.replace(/[^\d]/g, '');
      if (!value) {
        input.value = '';
        return;
      }
      value = new Intl.NumberFormat('vi-VN').format(value);
      input.value = value + ' ₫';
    }

    async function fetchCategories() {
      const res = await fetch(apiCategories);
      const categories = await res.json();
      const select = document.getElementById('category_id');
      select.innerHTML = '<option value="">Chọn danh mục</option>';
      if (Array.isArray(categories)) {
        categories.forEach(cat => {
          const option = document.createElement('option');
          option.value = cat.id;
          option.textContent = cat.name;
          select.appendChild(option);
        });
      }
    }

    async function fetchList(q = '') {
      let url = apiProducts;
      if (q) url += '?q=' + encodeURIComponent(q);
      const res = await fetch(url);
      const data = await res.json();
      const tbody = document.getElementById('tbody');
      const emptyState = document.getElementById('emptyState');
      tbody.innerHTML = '';
      if (!Array.isArray(data) || data.length === 0) {
        emptyState.classList.remove('hidden');
        return;
      }
      emptyState.classList.add('hidden');
      data.forEach(p => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition-colors';
        const priceNum = Number(p.unit_price || 0);
        const priceFormatted = priceNum.toLocaleString('vi-VN') + ' ₫';
        const categoryName = p.category_name || 'Không có';
        tr.innerHTML = `
          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${p.id}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${p.sku || ''}</td>
          <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate" title="${p.name || ''}">${p.name || ''}</td>
          <td class="px-6 py-4 text-sm text-gray-500">${categoryName}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${p.quantity || 0}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">${priceFormatted}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
            <button onclick="edit(${p.id})" class="mr-3 text-blue-600 hover:text-blue-900 transition-colors">
              <i class="fas fa-edit"></i> Sửa
            </button>
            <button onclick="del(${p.id})" class="text-red-600 hover:text-red-900 transition-colors">
              <i class="fas fa-trash"></i> Xóa
            </button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    function showMessage(msg, type = 'success') {
      const box = document.getElementById('messageBox');
      box.textContent = msg;
      box.classList.remove('hidden', 'bg-green-500', 'bg-red-500', 'scale-95');
      box.classList.add('scale-100');
      if (type === 'success') box.classList.add('bg-green-500');
      else box.classList.add('bg-red-500');
      setTimeout(() => {
        box.classList.remove('scale-100');
        box.classList.add('scale-95');
        setTimeout(() => box.classList.add('hidden'), 300);
      }, 3000);
    }

    async function del(id) {
      if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) return;
      const res = await fetch(apiProducts + '?id=' + id, {
        method: 'DELETE'
      });
      const data = await res.json();
      if (res.ok && data.success) showMessage('Xóa sản phẩm thành công', 'success');
      else showMessage('Lỗi khi xóa: ' + (data.error || ''), 'error');
      fetchList();
    }

    async function edit(id) {
      const res = await fetch(apiProducts + '?id=' + id);
      const p = await res.json();
      document.getElementById('id').value = p.id;
      document.getElementById('sku').value = p.sku || '';
      document.getElementById('name').value = p.name || '';
      document.getElementById('quantity').value = p.quantity || '';
      document.getElementById('unit_price').value = p.unit_price ? Number(p.unit_price).toLocaleString('vi-VN') + ' ₫' : '';
      document.getElementById('description').value = p.description || '';
      document.getElementById('category_id').value = p.category_id || '';
      document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-blue-600"></i> Sửa sản phẩm';
    }

    document.getElementById('productForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = document.getElementById('id').value;
      const unitPriceInput = document.getElementById('unit_price');
      const cleanValue = unitPriceInput.value.replace(/[^\d]/g, '');
      const unit_price = parseFloat(cleanValue || 0);
      const category_id = document.getElementById('category_id').value;
      const payload = {
        sku: document.getElementById('sku').value,
        name: document.getElementById('name').value,
        quantity: parseInt(document.getElementById('quantity').value || 0),
        unit_price,
        description: document.getElementById('description').value,
        category_id
      };
      let res = id ? await fetch(apiProducts + '?id=' + id, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }) : await fetch(apiProducts, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (res.ok && data.success) {
        showMessage(id ? 'Cập nhật sản phẩm thành công' : 'Thêm sản phẩm thành công', 'success');
        document.getElementById('productForm').reset();
        document.getElementById('id').value = '';
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle text-green-600"></i> Thêm sản phẩm mới';
      } else showMessage('Lỗi: ' + (data.error || ''), 'error');
      fetchList();
    });

    document.getElementById('btnSearch').addEventListener('click', () => {
      const q = document.getElementById('q').value.trim();
      fetchList(q);
    });

    document.getElementById('btnRefresh').addEventListener('click', () => {
      document.getElementById('q').value = '';
      fetchList();
    });

    document.getElementById('btnReset').addEventListener('click', () => {
      document.getElementById('productForm').reset();
      document.getElementById('id').value = '';
      document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle text-green-600"></i> Thêm sản phẩm mới';
    });

    document.addEventListener('DOMContentLoaded', () => {
      fetchCategories();
      fetchList();
    });
  </script>
</body>
</html>
