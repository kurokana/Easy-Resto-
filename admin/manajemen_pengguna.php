<?php
session_start();
include '../config.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id_user'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if ($admin['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$users_result = $conn->query("SELECT * FROM users ORDER BY id_user");

$total_users = $users_result->num_rows;
$owner_count = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'owner'")->fetch_assoc()['total'];
$admin_count = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];
$kasir_count = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'kasir'")->fetch_assoc()['total'];

$users_result->data_seek(0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $nama = $_POST['nama'];
    $phone_number = $_POST['phone_number'] ?? NULL;
    
    $hashed_password = md5($password);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, nama, phone_number) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hashed_password, $role, $nama, $phone_number);
    
    if ($stmt->execute()) {
        $success = "Pengguna berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan pengguna!";
    }
    header("Location: manajemen_pengguna.php");
    exit();
}

if (isset($_GET['hapus'])) {
    $id_user = $_GET['hapus'];
    $check_user = $conn->query("SELECT role FROM users WHERE id_user = $id_user")->fetch_assoc();
    if ($check_user['role'] != 'owner') {
        $conn->query("DELETE FROM users WHERE id_user = $id_user");
    }
    header("Location: manajemen_pengguna.php");
    exit();
}

$foto_display = 'https://ui-avatars.com/api/?name=' . urlencode($admin['nama'] ?? 'Admin') . '&background=B7A087&color=fff';
if (!empty($admin['profile_picture']) && file_exists($admin['profile_picture'])) {
    $foto_display = $admin['profile_picture'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - EasyResto Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'antique-white': '#F7EBDF',
                        'pale-taupe': '#B7A087',
                        'dark-taupe': '#8B7355',
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            background-color: #F7EBDF; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .sidebar { 
            background: linear-gradient(to bottom, #B7A087, #8B7355); 
        }
        .btn-primary { 
            background-color: #B7A087; 
            color: white; 
            transition: all 0.2s ease;
        }
        .btn-primary:hover { 
            background-color: #8B7355; 
            transform: translateY(-1px);
        }
        input:focus { 
            outline: none !important; 
            border-color: #B7A087 !important; 
            box-shadow: 0 0 0 3px rgba(183, 160, 135, 0.1) !important; 
        }
        .card {
            border: 1px solid #E5D9C8;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
        }
        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        .info-item {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .table-row:hover {
            background-color: rgba(183, 160, 135, 0.08);
        }
    </style>
</head>
<body class="bg-antique-white h-screen flex overflow-hidden">

    <div class="w-64 sidebar shadow-xl flex flex-col justify-between z-20 flex-shrink-0">
        <div>
            <div class="h-16 flex items-center justify-center bg-pale-taupe">
                <div class="text-white text-center">
                    <h1 class="text-xl font-bold">EasyResto</h1>
                    <p class="text-xs text-white opacity-90">Admin Panel</p>
                </div>
            </div>
            
            <nav class="mt-8">
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-chart-line w-6"></i>
                    <span class="mx-3 font-medium">Dashboard</span>
                </a>
                <a href="manajemen_menu.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-utensils w-6"></i>
                    <span class="mx-3 font-medium">Manajemen Menu</span>
                </a>
                <a href="laporan_penjualan.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-file-invoice-dollar w-6"></i>
                    <span class="mx-3 font-medium">Laporan Penjualan</span>
                </a>
                <a href="manajemen_transaksi.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-cash-register w-6"></i>
                    <span class="mx-3 font-medium">Manajemen Transaksi</span>
                </a>
                <a href="manajemen_pengguna.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
                    <i class="fas fa-users w-6"></i>
                    <span class="mx-3 font-medium">Manajemen Pengguna</span>
                </a>
                <a href="profil.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-user-cog w-6"></i>
                    <span class="mx-3 font-medium">Profil</span>
                </a>
            </nav>
        </div>
        
        <div class="p-4 bg-pale-taupe bg-opacity-80">
            <div class="flex items-center gap-3">
                <img src="<?= $foto_display ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
                <div class="overflow-hidden text-white">
                    <p class="font-bold text-sm truncate leading-tight"><?= htmlspecialchars($admin['nama'] ?? 'Admin') ?></p>
                    <p class="text-xs opacity-90"><?= ucfirst($admin['role'] ?? 'Admin') ?></p>
                    <a href="../logout.php" class="text-xs text-red-200 hover:text-white flex items-center gap-1 mt-1 transition-colors">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <header class="bg-white shadow-sm border-b border-[#E5D9C8] flex-shrink-0 px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manajemen Pengguna</h1>
                    <p class="text-gray-600">Kelola akses pengguna sistem</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden md:block">
                        <p class="text-sm text-gray-600">Selamat datang</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($admin['nama'] ?? 'Admin') ?></p>
                    </div>
                    <a href="profil.php" class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-pale-taupe">
                        <img src="<?= $foto_display ?>" alt="Profil" class="w-full h-full object-cover">
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <?php if (isset($success)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-500"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-pale-taupe to-amber-800 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white text-sm font-medium">Total Pengguna</p>
                            <p class="text-2xl font-bold"><?php echo $total_users; ?></p>
                        </div>
                        <i class="fas fa-users text-2xl opacity-80"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-100 text-sm font-medium">Owner</p>
                            <p class="text-2xl font-bold"><?php echo $owner_count; ?></p>
                        </div>
                        <i class="fas fa-crown text-2xl opacity-80"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Admin</p>
                            <p class="text-2xl font-bold"><?php echo $admin_count; ?></p>
                        </div>
                        <i class="fas fa-user-shield text-2xl opacity-80"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Kasir</p>
                            <p class="text-2xl font-bold"><?php echo $kasir_count; ?></p>
                        </div>
                        <i class="fas fa-cash-register text-2xl opacity-80"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white card p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Daftar Pengguna</h3>
                        <p class="text-sm text-gray-600">Semua pengguna yang memiliki akses ke sistem (Total: <?php echo $total_users; ?> pengguna)</p>
                    </div>
                    <button onclick="openAddModal()" class="btn-primary px-4 py-2 rounded-lg font-semibold shadow-sm flex items-center">
                        <i class="fas fa-user-plus mr-2"></i>
                        Tambah Pengguna
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pengguna</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Telepon</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($total_users > 0): ?>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr class="table-row transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900 bg-gray-100 px-2 py-1 rounded">#<?php echo $user['id_user']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-r from-pale-taupe to-amber-800 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-white text-sm"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo $user['nama'] ?: 'User #' . $user['id_user']; ?></div>
                                                <div class="text-xs text-gray-500">ID: <?php echo $user['id_user']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium"><?php echo $user['username']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $role_data = [
                                            'owner' => ['color' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'icon' => 'fa-crown'],
                                            'admin' => ['color' => 'bg-green-100 text-green-800 border-green-200', 'icon' => 'fa-user-shield'],
                                            'kasir' => ['color' => 'bg-purple-100 text-purple-800 border-purple-200', 'icon' => 'fa-cash-register']
                                        ];
                                        $role_info = $role_data[$user['role']] ?? $role_data['kasir'];
                                        ?>
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full border <?php echo $role_info['color']; ?>">
                                            <i class="fas <?php echo $role_info['icon']; ?> mr-1"></i>
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $user['phone_number'] ?: '-'; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                            <i class="fas fa-check mr-1"></i>Aktif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($user['role'] != 'owner'): ?>
                                        <button onclick="hapusUser(<?php echo $user['id_user']; ?>)" class="text-red-600 hover:text-red-900 transition-colors">
                                            <i class="fas fa-trash mr-1"></i>Hapus
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-users text-4xl text-gray-400 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada pengguna</h3>
                                            <p class="text-gray-600 mb-4">Tambahkan pengguna pertama Anda untuk memulai.</p>
                                            <button onclick="openAddModal()" class="btn-primary px-4 py-2 rounded-lg font-semibold shadow-sm flex items-center">
                                                <i class="fas fa-user-plus mr-2"></i>
                                                Tambah Pengguna Pertama
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-800">Tambah Pengguna Baru</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                        <input type="text" name="nama" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors" placeholder="Contoh: John Doe">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                        <input type="text" name="username" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors" placeholder="Contoh: johndoe">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                        <input type="password" name="password" required minlength="6" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors" placeholder="Minimal 6 karakter">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Telepon</label>
                        <input type="tel" name="phone_number" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors" placeholder="Contoh: 081234567890">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <select name="role" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors">
                            <option value="">Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="kasir">Kasir</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Batal</button>
                    <button type="submit" name="tambah_user" class="btn-primary px-4 py-2 rounded-lg font-semibold shadow-sm">
                        <i class="fas fa-user-plus mr-2"></i>Tambah Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addModal').classList.remove('flex');
        }

        function hapusUser(id) {
            if (confirm('Apakah Anda yakin ingin menghapus pengguna ini?\nTindakan ini tidak dapat dibatalkan.')) {
                window.location.href = 'manajemen_pengguna.php?hapus=' + id;
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target === modal) {
                closeAddModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAddModal();
            }
        });
    </script>
</body>
</html>