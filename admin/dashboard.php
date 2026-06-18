<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../config.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit();
}

$total_penjualan = $conn->query("SELECT SUM(total) as total FROM transaksi")->fetch_assoc()['total'] ?? 0;
$total_transaksi = $conn->query("SELECT COUNT(*) as total FROM transaksi")->fetch_assoc()['total'] ?? 0;
$menu_terpopuler = $conn->query("
    SELECT m.nama_menu, SUM(d.jumlah) as total_terjual 
    FROM detail_transaksi d 
    JOIN menu m ON d.id_menu = m.id_menu 
    GROUP BY m.id_menu 
    ORDER BY total_terjual DESC 
    LIMIT 1
")->fetch_assoc();

$kategori_penjualan = $conn->query("
    SELECT k.nama_kategori, SUM(d.subtotal) as total
    FROM detail_transaksi d
    JOIN menu m ON d.id_menu = m.id_menu
    JOIN kategori_menu k ON m.id_kategori = k.id_kategori
    GROUP BY k.id_kategori
");

$recent_transactions = $conn->query("SELECT * FROM transaksi ORDER BY tanggal DESC LIMIT 5");

$admin_id = $_SESSION['id_user'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

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
    <title>Dashboard - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        body { background-color: #F7EBDF; font-family: 'Segoe UI', system-ui, sans-serif; }
        .sidebar { background: linear-gradient(to bottom, #B7A087, #8B7355); }
        .card { background: white; border: 1px solid #E5D9C8; border-radius: 12px; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
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
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
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
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        
        <header class="bg-white shadow-sm border-b border-[#E5D9C8] flex-shrink-0 px-8 py-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-600">Ringkasan kinerja restoran</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-right hidden md:block">
                    <p class="text-sm text-gray-600">Selamat datang</p>
                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($admin['nama'] ?? 'Admin') ?></p>
                </div>
                <a href="profil.php" class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-pale-taupe">
                    <img src="<?= $foto_display ?>" alt="Profil" class="w-full h-full object-cover">
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 scrollbar-hide">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="card shadow-lg p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0"><i class="fas fa-wallet text-2xl text-green-500"></i></div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-600">Total Penjualan</h3>
                                <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0"><i class="fas fa-shopping-cart text-2xl text-blue-500"></i></div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-600">Total Transaksi</h3>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_transaksi, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-lg p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0"><i class="fas fa-star text-2xl text-purple-500"></i></div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-600">Menu Terpopuler</h3>
                                <p class="text-lg font-bold text-gray-900 truncate max-w-[150px]"><?php echo htmlspecialchars($menu_terpopuler['nama_menu'] ?? 'Belum ada data'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white card shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">Penjualan per Kategori</h3>
                <div class="h-80 relative w-full flex justify-center">
                    <div class="w-full h-full max-w-3xl">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Transaksi Terbaru</h3>
                    <a href="manajemen_transaksi.php" class="text-sm text-pale-taupe hover:text-dark-taupe font-medium">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left border-b border-gray-200">
                                <th class="pb-4 font-semibold text-gray-600 text-sm">ID</th>
                                <th class="pb-4 font-semibold text-gray-600 text-sm">Pelanggan</th>
                                <th class="pb-4 font-semibold text-gray-600 text-sm">Tanggal</th>
                                <th class="pb-4 font-semibold text-gray-600 text-sm">Total</th>
                                <th class="pb-4 font-semibold text-gray-600 text-sm">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_transactions->num_rows > 0): ?>
                                <?php while($transaction = $recent_transactions->fetch_assoc()): ?>
                                <tr class="border-b border-gray-100 hover:bg-pale-taupe hover:bg-opacity-10">
                                    <td class="py-4 text-sm text-gray-900">#<?php echo $transaction['id_transaksi']; ?></td>
                                    <td class="py-4 text-sm text-gray-600"><?php echo htmlspecialchars($transaction['nama_pelanggan']); ?></td>
                                    <td class="py-4 text-sm text-gray-600">
                                        <?= date('d M Y', strtotime($transaction['tanggal'])) ?>
                                        <span class="text-xs text-gray-400 ml-1"><?= date('H:i', strtotime($transaction['tanggal'])) ?></span>
                                    </td>
                                    <td class="py-4 text-sm font-semibold text-gray-900">Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?></td>
                                    <td class="py-4"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Selesai</span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="py-8 text-center text-gray-500">Belum ada transaksi</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            const labels = [
                <?php
                $cat_labels = []; $cat_data = [];
                $kategori_penjualan->data_seek(0);
                while ($row = $kategori_penjualan->fetch_assoc()) {
                    $cat_labels[] = "'" . htmlspecialchars($row['nama_kategori']) . "'";
                    $cat_data[] = $row['total'];
                }
                echo empty($cat_labels) ? "'Makanan', 'Minuman'" : implode(', ', $cat_labels);
                ?>
            ];
            const data = [<?php echo empty($cat_data) ? "0, 0" : implode(', ', $cat_data); ?>];
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#B7A087', '#10b981', '#8b5cf6', '#f59e0b'],
                        borderWidth: 0
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
    </script>
</body>
</html>