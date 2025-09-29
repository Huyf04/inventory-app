<!-- src/index.php -->
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Quản lý vật tư</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-50">
  <div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Quản lý vật tư</h1>

    <!-- Search -->
    <div class="mb-4 flex gap-2">
      <input id="q" class="border px-3 py-2 flex-1" placeholder="Tìm theo tên, SKU..." />
      <button id="btnSearch" class="bg-blue-600 text-white px-4 py-2 rounded">Tìm</button>
      <button id="btnRefresh" class="bg-gray-200 px-4 py-2 rounded">Làm mới</button>
    </div>

    <!-- Message box -->
    <div id="messageBox" class="my-4 hidden p-3 rounded text-white"></div>

    <!-- Table -->
    <div class="bg-white shadow rounded">
      <table class="w-full table-auto" id="productTable">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2">ID</th><th class="p-2">SKU</th><th class="p-2">Tên</th><th class="p-2">SL</th><th class="p-2">Giá</th><th class="p-2">Hành động</th>
          </tr>
        </thead>
        <tbody id="tbody"></tbody>
      </table>
    </div>

    <!-- Form thêm/sửa -->
   <div class="mt-6 bg-white p-4 rounded shadow">
  <h2 class="font-semibold mb-2" id="formTitle">Thêm sản phẩm</h2>
  <form id="productForm" class="space-y-3">
    <input type="hidden" id="id" />

    <!-- Hàng 1: SKU + Tên -->
    <div class="grid grid-cols-2 gap-3">
      <input id="sku" placeholder="SKU" class="border p-2 rounded w-full" required />
      <input id="name" placeholder="Tên sản phẩm" class="border p-2 rounded w-full" required />
    </div>

    <!-- Hàng 2: Số lượng + Đơn giá -->
    <div class="grid grid-cols-2 gap-3">
      <input id="quantity" type="number" placeholder="Số lượng" class="border p-2 rounded w-full" />
      <input id="unit_price" type="number" step="0.01" placeholder="Đơn giá" class="border p-2 rounded w-full" />
    </div>

    <!-- Hàng 3: Mô tả -->
    <textarea id="description" placeholder="Mô tả" class="border p-2 rounded w-full"></textarea>

    <!-- Buttons -->
    <div class="flex gap-2">
      <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Lưu</button>
      <button type="button" id="btnReset" class="bg-gray-200 px-4 py-2 rounded">Hủy</button>
    </div>
  </form>
</div>
  </div>

<script>
const api = '/api/products.php';

async function fetchList(q = '') {
  let url = api;
  if (q) url += '?q=' + encodeURIComponent(q);
  const res = await fetch(url);
  const data = await res.json();
  const tbody = document.getElementById('tbody');
  tbody.innerHTML = '';
  data.forEach(p => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="p-2">${p.id}</td>
      <td class="p-2">${p.sku||''}</td>
      <td class="p-2">${p.name||''}</td>
      <td class="p-2">${p.quantity}</td>
      <td class="p-2">${p.unit_price}</td>
      <td class="p-2">
        <button onclick="edit(${p.id})" class="mr-2 text-blue-600">Sửa</button>
        <button onclick="del(${p.id})" class="text-red-600">Xóa</button>
      </td>`;
    tbody.appendChild(tr);
  });
}

// Hiển thị thông báo
function showMessage(msg, type = 'success') {
  const box = document.getElementById('messageBox');
  box.textContent = msg;
  // xóa các class ẩn / màu cũ
  box.classList.remove('hidden', 'bg-green-600', 'bg-red-600');
  if (type === 'success') {
    box.classList.add('bg-green-600');
  } else if (type === 'error') {
    box.classList.add('bg-red-600');
  }
  // ẩn sau 3 giây
  setTimeout(() => {
    box.classList.add('hidden');
  }, 3000);
}

async function del(id) {
  if (!confirm('Xóa sản phẩm này?')) return;
  const res = await fetch(api + '?id=' + id, { method: 'DELETE' });
  let respData;
  try {
    respData = await res.json();
  } catch (e) {
    showMessage('Phản hồi không hợp lệ khi xóa', 'error');
    return;
  }
  if (res.ok && respData.success) {
    showMessage('Xóa sản phẩm thành công', 'success');
  } else {
    let msg = 'Lỗi khi xóa sản phẩm';
    if (respData.error) msg += ': ' + respData.error;
    showMessage(msg, 'error');
  }
  fetchList();
}

async function edit(id) {
  const res = await fetch(api + '?id=' + id);
  const p = await res.json();
  document.getElementById('id').value = p.id;
  document.getElementById('sku').value = p.sku;
  document.getElementById('name').value = p.name;
  document.getElementById('quantity').value = p.quantity;
  document.getElementById('unit_price').value = p.unit_price;
  document.getElementById('description').value = p.description;
  document.getElementById('formTitle').textContent = 'Sửa sản phẩm';
}

document.getElementById('productForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const id = document.getElementById('id').value;
  const payload = {
    sku: document.getElementById('sku').value,
    name: document.getElementById('name').value,
    quantity: parseInt(document.getElementById('quantity').value || 0),
    unit_price: parseFloat(document.getElementById('unit_price').value || 0),
    description: document.getElementById('description').value
  };

  let res;
  if (id) {
    res = await fetch(api + '?id=' + id, {
      method: 'PUT',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });
  } else {
    res = await fetch(api, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });
  }

  let data;
  try {
    data = await res.json();
  } catch (err) {
    showMessage('Phản hồi không hợp lệ từ server', 'error');
    return;
  }

  if (res.ok && data.success) {
    showMessage(id ? 'Cập nhật sản phẩm thành công' : 'Thêm sản phẩm thành công', 'success');
  } else {
    let msg = 'Có lỗi khi lưu sản phẩm';
    if (data.error) {
      msg += ': ' + data.error;
    }
    showMessage(msg, 'error');
  }

  document.getElementById('productForm').reset();
  document.getElementById('formTitle').textContent = 'Thêm sản phẩm';
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
  document.getElementById('formTitle').textContent = 'Thêm sản phẩm';
});

fetchList();
</script>
</body>
</html>
