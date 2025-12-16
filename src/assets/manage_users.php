<?php
// src/assets/manage_users.php

require_once 'auth.php';

if (!hasRole('admin')) {
    header('Location: ../index.php');
    exit;
}

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản lý người dùng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
                    <i class="fas fa-users text-3xl text-blue-600"></i>
                    <h1 class="text-3xl font-bold text-gray-900">Quản lý người dùng</h1>
                    <a href="../index.php" class="text-blue-600 hover:text-blue-900 font-medium">Quay lại</a>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-500">
                        Xin chào, <strong><?php echo htmlspecialchars($user['username']); ?></strong> (Admin)
                    </div>
                    <a href="?logout=1" class="bg-red-500 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">Đăng xuất</a>
                </div>
            </div>
        </div>

        <!-- Message Box -->
        <div id="messageBox" class="my-4 hidden p-4 rounded-lg text-white shadow-lg transform transition-all duration-300"></div>

        <!-- Users Table -->
        <div class="mb-8 bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-list"></i>
                    Danh sách người dùng
                </h2>
            </div>
            <div class="table-container">
                <table class="w-full table-auto" id="userTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
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
                <i class="fas fa-user-times text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 text-lg">Chưa có người dùng nào.</p>
            </div>
        </div>

        <!-- User Form -->
        <div class="bg-white shadow-lg rounded-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2" id="formTitle">
                    <i class="fas fa-user-plus text-green-600"></i>
                    Thêm người dùng mới
                </h2>
                <button id="btnToggleForm" class="md:hidden bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
            <form id="userForm" class="space-y-6">
                <input type="hidden" id="id" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input id="username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required />
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input id="password" type="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required />
                    </div>
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select id="role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="warehouse_staff">Nhân viên kho</option>
                        <option value="accountant">Kế toán</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 flex-1 sm:flex-none">
                        <i class="fas fa-save"></i>
                        Lưu người dùng
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
        const apiUsers = '../api/users.php';  // API cho users (cần tạo)

        async function fetchList() {
            try {
                const res = await fetch(apiUsers);
                if (!res.ok) {
                    showMessage('Lỗi server: ' + res.status, 'error');
                    return;
                }
                const data = await res.json();
                const tbody = document.getElementById('tbody');
                const emptyState = document.getElementById('emptyState');
                tbody.innerHTML = '';
                if (Array.isArray(data) && data.length > 0) {
                    emptyState.classList.add('hidden');
                    data.forEach(u => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-gray-50 transition-colors';
                        tr.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${u.id}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${u.username}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${u.role}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${u.created_at}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="edit(${u.id})" class="mr-3 text-blue-600 hover:text-blue-900 transition-colors">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button onclick="del(${u.id})" class="text-red-600 hover:text-red-900 transition-colors">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    emptyState.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error fetching users:', error);
                showMessage('Lỗi mạng khi tải danh sách người dùng', 'error');
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

        async function del(id) {
            if (!confirm('Bạn có chắc chắn muốn xóa người dùng này?')) return;
            try {
                const res = await fetch(apiUsers + '?id=' + id, {
                    method: 'DELETE'
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showMessage('Xóa người dùng thành công', 'success');
                } else {
                    showMessage('Lỗi khi xóa: ' + (data.error || ''), 'error');
                }
                fetchList();
            } catch (error) {
                console.error('Error deleting user:', error);
                showMessage('Lỗi mạng khi xóa người dùng', 'error');
            }
        }

        async function edit(id) {
            try {
                const res = await fetch(apiUsers + '?id=' + id);
                if (!res.ok) {
                    showMessage('Lỗi khi tải người dùng để sửa', 'error');
                    return;
                }
                const u = await res.json();
                document.getElementById('id').value = u.id;
                document.getElementById('username').value = u.username;
                document.getElementById('password').value = '';  // Không hiển thị password cũ
                document.getElementById('role').value = u.role;
                document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-blue-600"></i> Sửa người dùng';
            } catch (error) {
                console.error('Error editing user:', error);
                showMessage('Lỗi mạng khi tải người dùng', 'error');
            }
        }

        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('id').value;
            const payload = {
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
                role: document.getElementById('role').value
            };
            try {
                let res;
                if (id) {
                    res = await fetch(apiUsers + '?id=' + id, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                } else {
                    res = await fetch(apiUsers, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                }
                const data = await res.json();
                if (res.ok && data.success) {
                    showMessage(id ? 'Cập nhật người dùng thành công' : 'Thêm người dùng thành công', 'success');
                    document.getElementById('userForm').reset();
                    document.getElementById('id').value = '';
                    document.getElementById('formTitle').innerHTML = '<i class="fas fa-user-plus text-green-600"></i> Thêm người dùng mới';
                    fetchList();
                } else {
                    showMessage('Lỗi: ' + (data.error || ''), 'error');
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                showMessage('Lỗi mạng khi lưu người dùng', 'error');
            }
        });

        document.getElementById('btnReset').addEventListener('click', () => {
            document.getElementById('userForm').reset();
            document.getElementById('id').value = '';
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-user-plus text-green-600"></i> Thêm người dùng mới';
        });

        document.addEventListener('DOMContentLoaded', () => {
            fetchList();
        });
    </script>
</body>
</html>

<?php
if (isset($_GET['logout'])) {
    logout();
}
?>
