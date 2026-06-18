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

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$sql = "SELECT t.*, 
        (SELECT GROUP_CONCAT(CONCAT(m.nama_menu, ' (', d.jumlah, 'x)') SEPARATOR ', ') 
         FROM detail_transaksi d JOIN menu m ON d.id_menu = m.id_menu WHERE d.id_transaksi = t.id_transaksi) as items
        FROM transaksi t WHERE DATE(t.tanggal) BETWEEN ? AND ? ORDER BY t.tanggal DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$summary_sql = "SELECT COUNT(*) as total_transaksi, SUM(total) as total_pendapatan, AVG(total) as rata_rata FROM transaksi WHERE DATE(tanggal) BETWEEN ? AND ?";
$stmt_summary = $conn->prepare($summary_sql);
$stmt_summary->bind_param("ss", $start_date, $end_date);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();

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
    <title>Manajemen Transaksi - EasyResto Admin</title>
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
                <a href="manajemen_transaksi.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
                    <i class="fas fa-cash-register w-6"></i>
                    <span class="mx-3 font-medium">Manajemen Transaksi</span>
                </a>
                <a href="manajemen_pengguna.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
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
                    <h1 class="text-2xl font-bold text-gray-800">Manajemen Transaksi</h1>
                    <p class="text-gray-600">Pantau dan kelola semua transaksi</p>
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
            <div class="bg-white card p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-filter text-pale-taupe mr-2"></i> Filter Transaksi
                </h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" 
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" 
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full btn-primary px-4 py-3 rounded-lg font-semibold shadow-sm">
                            <i class="fas fa-filter mr-2"></i>Terapkan Filter
                        </button>
                    </div>
                    <div class="flex items-end">
                        <a href="manajemen_transaksi.php" 
                           class="w-full px-4 py-3 text-center bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                            <i class="fas fa-refresh mr-2"></i>Reset Filter
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-r from-pale-taupe to-amber-800 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Total Transaksi</p>
                            <p class="text-2xl font-bold mt-1"><?= number_format($summary['total_transaksi'] ?? 0, 0, ',', '.') ?></p>
                        </div>
                        <i class="fas fa-shopping-cart text-2xl opacity-80"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Total Pendapatan</p>
                            <p class="text-2xl font-bold mt-1">Rp <?= number_format($summary['total_pendapatan'] ?? 0, 0, ',', '.') ?></p>
                        </div>
                        <i class="fas fa-money-bill-wave text-2xl opacity-80"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Rata-rata/Transaksi</p>
                            <p class="text-2xl font-bold mt-1">Rp <?= number_format($summary['rata_rata'] ?? 0, 0, ',', '.') ?></p>
                        </div>
                        <i class="fas fa-chart-bar text-2xl opacity-80"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white card overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Riwayat Transaksi</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                <i class="far fa-calendar-alt mr-1"></i>
                                Periode: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?>
                            </p>
                        </div>
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm font-medium rounded-full">
                            <?= number_format($result->num_rows, 0, ',', '.') ?> transaksi
                        </span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pelanggan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="table-row hover:bg-pale-taupe hover:bg-opacity-10 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="bg-pale-taupe bg-opacity-20 px-3 py-1 rounded text-sm font-medium text-gray-700">
                                            #<?= $row['id_transaksi'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['nama_pelanggan']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></div>
                                        <div class="text-xs text-gray-500"><?= date('H:i', strtotime($row['tanggal'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate" title="<?= htmlspecialchars($row['items'] ?: '-') ?>">
                                            <?= htmlspecialchars($row['items'] ?: '-') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-green-600">
                                            Rp <?= number_format($row['total'], 0, ',', '.') ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Subtotal: Rp <?= number_format($row['subtotal'], 0, ',', '.') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800 font-medium">
                                            <i class="fas fa-check-circle mr-1"></i>Selesai
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-gray-500 font-medium">Tidak ada transaksi</p>
                                            <p class="text-sm text-gray-400 mt-1">Tidak ada transaksi dalam periode yang dipilih</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 border-t bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            Menampilkan <span class="font-medium"><?= number_format($result->num_rows, 0, ',', '.') ?></span> transaksi
                        </div>
                        <div class="text-sm text-gray-600">
                            Total pendapatan: <span class="font-semibold text-green-600">Rp <?= number_format($summary['total_pendapatan'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        });
    </script>
</body>
</html>