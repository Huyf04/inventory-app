<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Quản lý Danh mục</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Custom styles */
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
          <i class="fas fa-folder text-3xl text-purple-600"></i> <!-- Icon cho categories -->
          <h1 class="text-3xl font-bold text-gray-900">Quản lý Danh mục</h1>
        </div>
        <div class="hidden md:block text-sm text-gray-500">
          Quản lý danh mục sản phẩm
        </div>
      </div>
    </div>

    <!-- Message Box -->
    <div id="messageBox" class="my-4 hidden p-4 rounded-lg text-white shadow-lg transform transition-all duration-300"></div>

    <!-- Categories Table -->
    <div class="mb-8 bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
      <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
          <i class="fas fa-list"></i>
          Danh sách danh mục
        </h2>
      </div>
      <div class="table-container">
        <table class="w-full table-auto" id="categoryTable">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên danh mục</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mô tả</th>
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
        <p class="text-gray-500 text-lg">Chưa có danh mục nào. Hãy thêm danh mục đầu tiên!</p>
      </div>
    </div>

    <!-- Category Form -->
    <div class="bg-white shadow-lg rounded-lg p-6 border border-gray-200">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2" id="formTitle">
          <i class="fas fa-plus-circle text-green-600"></i>
          Thêm danh mục mới
        </h2>
        <button id="btnToggleForm" class="md:hidden bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
          <i class="fas fa-chevron-up"></i>
        </button>
      </div>
      <form id="categoryForm" class="space-y-6">
        <input type="hidden" id="id" />
        
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Tên danh mục</label>
          <input id="name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required />
        </div>

        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Mô tả</label>
          <textarea id="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-vertical"></textarea>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 flex-1 sm:flex-none">
            <i class="fas fa-save"></i>
            Lưu danh mục
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
  const api = '/api/categories.php';

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

  async function fetchList() {
    const res = await fetch(api);
    const data = await res.json();
    const tbody = document.getElementById('tbody');
    const emptyState = document.getElementById('emptyState');
    tbody.innerHTML = '';
    if (!Array.isArray(data) || data.length === 0) {
      emptyState.classList.remove('hidden');
      return;
    }
    emptyState.classList.add('hidden');
    data.forEach(cat => {
      const tr = document.createElement('tr');
      tr.className = 'hover:bg-gray-50 transition-colors';
      tr.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${cat.id}</td>
        <td class="px-6 py-4 text-sm text-gray-900">${cat.name || ''}</td>
        <td class="px-6 py-4 text-sm text-gray-500">${cat.description || ''}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
          <button onclick="edit(${cat.id})" class="mr-3 text-blue-600 hover:text-blue-900">Sửa</button>
          <button onclick="del(${cat.id})" class="text-red-600 hover:text-red-900">Xóa</button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  async function del(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa danh mục này?')) return;
    const res = await fetch(api + '?id=' + id, { method: 'DELETE' });
    const data = await res.json();
    if (res.ok && data.success) {
      showMessage('Xóa danh mục thành công', 'success');
      fetchList();
    } else {
      showMessage('Lỗi khi xóa: ' + (data.error || ''), 'error');
    }
  }

  async function edit(id) {
    const res = await fetch(api + '?id=' + id);
    const cat = await res.json();
    document.getElementById('id').value = cat.id;
    document.getElementById('name').value = cat.name || '';
    document.getElementById('description').value = cat.description || '';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-blue-600"></i> Sửa danh mục';
  }

  document.getElementById('categoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('id').value;
    const payload = {
      name: document.getElementById('name').value,
      description: document.getElementById('description').value
    };
    let res;
    if (id) {
      res = await fetch(api + '?id=' + id, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
    } else {
      res = await fetch(api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
    }
    const data = await res.json();
    if (res.ok && data.success) {
      showMessage(id ? 'Cập nhật danh mục thành công' : 'Thêm danh mục thành công', 'success');
      document.getElementById('categoryForm').reset();
      document.getElementById('id').value = '';
      document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle text-green-600"></i> Thêm danh mục mới';
      fetchList();
    } else {
      showMessage('Lỗi: ' + (data.error || ''), 'error');
    }
  });

  document.getElementById('btnReset').addEventListener('click', () => {
    document.getElementById('categoryForm').reset();
    document.getElementById('id').value = '';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle text-green-600"></i> Thêm danh mục mới';
  });

  // Khởi đầu load
  fetchList();
  </script>
</body>
</html>
