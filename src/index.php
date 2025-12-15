<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Quản lý vật tư</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen bg-gray-100 p-4">
<div class="max-w-7xl mx-auto">

<h1 class="text-2xl font-bold mb-6">Quản lý vật tư</h1>

<!-- SEARCH -->
<div class="mb-4 flex gap-2">
  <input id="q" class="border p-2 flex-1 rounded" placeholder="Tìm theo SKU hoặc tên">
  <button onclick="fetchList(q.value)" class="bg-blue-600 text-white px-4 rounded">Tìm</button>
  <button onclick="resetSearch()" class="bg-gray-300 px-4 rounded">Làm mới</button>
</div>

<!-- MESSAGE -->
<div id="msg" class="hidden mb-4 p-3 rounded text-white"></div>

<!-- TABLE -->
<table class="w-full bg-white border rounded mb-6">
<thead class="bg-gray-200">
<tr>
  <th class="p-2">ID</th>
  <th class="p-2">SKU</th>
  <th class="p-2">Tên</th>
  <th class="p-2">Danh mục</th>
  <th class="p-2 text-right">SL</th>
  <th class="p-2 text-right">Giá</th>
  <th class="p-2">Hành động</th>
</tr>
</thead>
<tbody id="tbody"></tbody>
</table>

<!-- FORM -->
<form id="productForm" class="bg-white p-4 rounded shadow space-y-3">
<h2 id="formTitle" class="font-semibold">Thêm sản phẩm</h2>

<input type="hidden" id="sku_old">

<div class="grid grid-cols-2 gap-3">
  <input id="sku" class="border p-2 rounded" placeholder="SKU" required>
  <input id="name" class="border p-2 rounded" placeholder="Tên sản phẩm" required>
</div>

<div class="grid grid-cols-2 gap-3">
  <input id="quantity" type="number" class="border p-2 rounded" placeholder="Số lượng">
  <input id="unit_price" type="number" class="border p-2 rounded" placeholder="Đơn giá">
</div>

<select id="category_id" class="border p-2 rounded w-full"></select>
<textarea id="description" class="border p-2 rounded w-full" placeholder="Mô tả"></textarea>

<div class="flex gap-2">
  <button class="bg-green-600 text-white px-4 py-2 rounded">Lưu</button>
  <button type="button" onclick="resetForm()" class="bg-gray-300 px-4 py-2 rounded">Hủy</button>
</div>
</form>

</div>

<script>
/* ================= API ================= */
const apiProducts = './api/products.php';
const apiCategories = './api/categories.php';

/* ================= DOM ================= */
const elSku = document.getElementById('sku');
const elSkuOld = document.getElementById('sku_old');
const elName = document.getElementById('name');
const elQty = document.getElementById('quantity');
const elPrice = document.getElementById('unit_price');
const elDesc = document.getElementById('description');
const elCat = document.getElementById('category_id');
const tbody = document.getElementById('tbody');
const formTitle = document.getElementById('formTitle');
const productForm = document.getElementById('productForm');
const q = document.getElementById('q');

/* ================= LIST ================= */
async function fetchList(keyword='') {
  let url = apiProducts;
  if (keyword) url += '?q=' + encodeURIComponent(keyword);

  const res = await fetch(url);
  const data = await res.json();

  tbody.innerHTML = '';

  data.forEach(p => {
    tbody.innerHTML += `
      <tr>
        <td class="p-2">${p.id}</td>
        <td class="p-2">${p.sku}</td>
        <td class="p-2">${p.name}</td>
        <td class="p-2">${p.category_name || ''}</td>
        <td class="p-2 text-right">${p.quantity}</td>
        <td class="p-2 text-right">${Number(p.unit_price).toLocaleString()} ₫</td>
        <td class="p-2">
          <button onclick="edit('${p.sku}')" class="text-blue-600 mr-2">Sửa</button>
          <button onclick="delProduct('${p.sku}')" class="text-red-600">Xóa</button>
        </td>
      </tr>
    `;
  });
}

/* ================= EDIT ================= */
async function edit(sku) {
  const res = await fetch(apiProducts + '?sku=' + encodeURIComponent(sku));
  const p = await res.json();

  elSkuOld.value = p.sku;
  elSku.value = p.sku;
  elSku.disabled = true;

  elName.value = p.name;
  elQty.value = p.quantity ?? 0;
  elPrice.value = p.unit_price ?? 0;
  elDesc.value = p.description ?? '';
  elCat.value = p.category_id ?? '';

  formTitle.innerText = 'Sửa sản phẩm';
}

/* ================= DELETE ================= */
async function delProduct(sku) {
  if (!confirm('Xóa sản phẩm này?')) return;
  await fetch(apiProducts + '?sku=' + encodeURIComponent(sku), { method: 'DELETE' });
  fetchList();
}

/* ================= SUBMIT ================= */
productForm.addEventListener('submit', async e => {
  e.preventDefault();

  const payload = {
    sku: elSku.value,
    name: elName.value,
    quantity: Number(elQty.value),
    unit_price: Number(elPrice.value),
    description: elDesc.value,
    category_id: elCat.value
  };

  const method = elSkuOld.value ? 'PUT' : 'POST';
  const url = elSkuOld.value ? apiProducts : apiProducts;

  await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

  resetForm();
  fetchList();
});

/* ================= HELPERS ================= */
function resetForm() {
  productForm.reset();
  elSku.disabled = false;
  elSkuOld.value = '';
  formTitle.innerText = 'Thêm sản phẩm';
}

function resetSearch() {
  q.value = '';
  fetchList();
}

/* ================= CATEGORIES ================= */
async function fetchCategories() {
  const res = await fetch(apiCategories);
  const cats = await res.json();
  elCat.innerHTML = '<option value="">Chọn danh mục</option>';
  cats.forEach(c => {
    elCat.innerHTML += `<option value="${c.id}">${c.name}</option>`;
  });
}

/* ================= INIT ================= */
fetchCategories();
fetchList();
</script>
</body>
</html>
