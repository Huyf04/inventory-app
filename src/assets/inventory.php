<?php
// src/assets/inventory.php

require_once 'auth.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('warehouse_staff'))) {
    header('Location: ../index.php');
    exit;
}

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Nhập/Xuất Kho</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-boxes text-3xl text-blue-600"></i>
                    <h1 class="text-3xl font-bold text-gray-900">Nhập/Xuất Kho</h1>
                    <a href="../index.php" class="text-blue-600 hover:text-blue-900 font-medium">Quay lại</a>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-500">
                        Xin chào, <strong><?php echo htmlspecialchars($user['username']); ?></strong> (<?php echo ucfirst($user['role']); ?>)
                    </div>
                    <a href="?logout=1" class="bg-red-500 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">Đăng xuất</a>
                </div>
            </div>
        </div>

        <!-- Message Box -->
        <div id="messageBox" class="my-4 hidden p-4 rounded-lg text-white shadow-lg transform transition-all duration-300"></div>

        <!-- Inventory Form -->
        <div class="bg-white shadow-lg rounded-lg p-6 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-6">
                <i class="fas fa-exchange-alt text-blue-600"></i>
                Thực hiện nhập/xuất kho
            </h2>
            <form id="inventoryForm" class="space-y-6">
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">Chọn sản phẩm</label>
                    <select id="product_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                        <option value="">Chọn sản phẩm</option>
                        <!-- Options sẽ được JS chèn -->
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Số lượng</label>
                        <input id="quantity" type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" min="1" required />
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Loại</label>
                        <select id="type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                            <option value="in">Nhập kho</option>
                            <option value="out">Xuất kho</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                    <textarea id="note" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-vertical"></textarea>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 flex-1 sm:flex-none">
                        <i class="fas fa-save"></i>
                        Thực hiện
                    </button>
                    <button type="button" id="btnReset" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 flex-1 sm:flex-none">
                        <i class="fas fa-times"></i>
                        Hủy
                    </button>
                </div>
            </form>
        </div>

        <!-- History Table (tùy chọn, hiển thị lịch sử) -->
        <div class="mt-8 bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-history"></i>
                    Lịch sử nhập/xuất gần đây
                </h2>
            </div>
            <div class="table-container">
                <table class="w-full table-auto" id="historyTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody id="historyTbody" class="bg-white divide-y divide-gray-200">
                        <!-- Rows sẽ được JS chèn -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const apiProducts = '../api/products.php';
        const apiInventory = '../api/inventory.php';  // API cho nhập/xuất (cần tạo)

        async function fetchProducts() {
            try {
                const res = await fetch(apiProducts);
                if (!res.ok) return;
                const products = await res.json();
                const select = document.getElementById('product_id');
                select.innerHTML = '<option value="">Chọn sản phẩm</option>';
                if (Array.isArray(products)) {
                    products.forEach(p => {
                        const option = document.createElement('option');
                        option.value = p.id;
                        option.textContent = p.name + ' (Tồn: ' + (p.quantity || 0) + ')';
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error fetching products:', error);
            }
        }

        async function fetchHistory() {
            try {
                const res = await fetch(apiInventory);
                if (!res.ok) return;
                const history = await res.json();
                const tbody = document.getElementById('historyTbody');
                tbody.innerHTML = '';
                if (Array.isArray(history)) {
                    history.slice(0, 10).forEach(h => {  // Hiển thị 10 bản ghi gần nhất
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="px-6 py-4 text-sm text-gray-900">${h.product_name || 'N/A'}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${h.quantity}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${h.type === 'in' ? 'Nhập' : 'Xuất'}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${h.created_at}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (error) {
                console.error('Error fetching history:', error);
            }
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

        document.getElementById('inventoryForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const product_id = document.getElementById('product_id').value;
            const quantity = parseInt(document.getElementById('quantity').value);
            const type = document.getElementById('type').value;
            const note = document.getElementById('note').value;
            const payload = { product_id, quantity, type, note };
            try {
                const res = await fetch(apiInventory, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showMessage('Thực hiện thành công', 'success');
                    document.getElementById('inventoryForm').reset();
                    fetchProducts();  // Refresh tồn kho
                    fetchHistory();  // Refresh lịch sử
                } else {
                    showMessage('Lỗi: ' + (data.error || ''), 'error');
                }
            } catch (error) {
                console.error('Error submitting inventory:', error);
                showMessage('Lỗi mạng', 'error');
            }
        });

        document.getElementById('btnReset').addEventListener('click', () => {
            document.getElementById('inventoryForm').reset();
        });

        document.addEventListener('DOMContentLoaded', () => {
            fetchProducts();
            fetchHistory();
        });
    </script>
</body>
</html>

<?php
if (isset($_GET['logout'])) {
    logout();
}
?>