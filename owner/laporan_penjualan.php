<?php
session_start();
include '../config.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$sql = "SELECT * FROM laporan_penjualan WHERE DATE(tanggal) BETWEEN ? AND ? ORDER BY tanggal DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$summary_sql = "SELECT 
    SUM(subtotal) as total_subtotal,
    SUM(ppn) as total_ppn,
    SUM(service) as total_service,
    SUM(total_permenu) as total_final,
    COUNT(DISTINCT id_transaksi) as total_transaksi,
    SUM(jumlah) as total_item_terjual
    FROM laporan_penjualan
    WHERE DATE(tanggal) BETWEEN ? AND ?";
    
$stmt_summary = $conn->prepare($summary_sql);
$stmt_summary->bind_param("ss", $start_date, $end_date);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();

$avg_transaction = $summary['total_transaksi'] > 0 ? $summary['total_final'] / $summary['total_transaksi'] : 0;

$owner_result = $conn->query("SELECT * FROM users WHERE role = 'owner' LIMIT 1");
$owner = $owner_result->fetch_assoc();

if (!$owner) {
    $user_result = $conn->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    $owner = $user_result->fetch_assoc();
}

if (!$owner) {
    $user_result = $conn->query("SELECT * FROM users LIMIT 1");
    $owner = $user_result->fetch_assoc();
}

$foto_display = 'https://ui-avatars.com/api/?name=' . urlencode($owner['nama'] ?? 'Owner') . '&background=B7A087&color=fff';
if (!empty($owner['profile_picture']) && file_exists($owner['profile_picture'])) {
    $foto_display = $owner['profile_picture'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - EasyResto Owner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'antique-white': '#F7EBDF',
                        'pale-taupe': '#B7A087',
                        'primary': '#B7A087',
                        'secondary': '#F7EBDF'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #F7EBDF;
        }
        .sidebar {
            background: linear-gradient(to bottom, #B7A087, #8B7355);
        }
        .card {
            background: white;
            border: 1px solid #E5D9C8;
        }
        .btn-primary {
            background-color: #B7A087;
            color: white;
        }
        .btn-primary:hover {
            background-color: #8B7355;
        }
        .print-only {
            display: none;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            body {
                background-color: white !important;
            }
            .sidebar {
                display: none !important;
            }
            .ml-64 {
                margin-left: 0 !important;
            }
            table {
                font-size: 10px !important;
            }
        }
    </style>
</head>
<body class="bg-antique-white">
    <div class="fixed inset-y-0 left-0 w-64 sidebar shadow-xl no-print">
        <div class="flex items-center justify-center h-16 bg-pale-taupe">
            <div class="text-white">
                <h1 class="text-xl font-bold">EasyResto</h1>
                <p class="text-xs text-white opacity-90">Owner Panel</p>
            </div>
        </div>
        
        <nav class="mt-8">
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                <i class="fas fa-chart-line w-6"></i>
                <span class="mx-3 font-medium">Dashboard</span>
            </a>
            <a href="laporan_penjualan.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white">
                <i class="fas fa-file-invoice-dollar w-6"></i>
                <span class="mx-3 font-medium">Laporan Penjualan</span>
            </a>
            <a href="manajemen_menu.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                <i class="fas fa-utensils w-6"></i>
                <span class="mx-3 font-medium">Manajemen Menu</span>
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
        
        <div class="absolute bottom-0 w-full p-4 bg-pale-taupe bg-opacity-80">
            <div class="flex items-center gap-3">
                <img src="<?= $foto_display ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
                <div class="overflow-hidden text-white">
                    <p class="font-bold text-sm truncate leading-tight"><?= htmlspecialchars($owner['nama'] ?? 'Owner') ?></p>
                    <p class="text-xs opacity-90"><?= ucfirst($owner['role'] ?? 'Admin') ?></p>
                    <a href="../logout.php" class="text-xs text-red-200 hover:text-white flex items-center gap-1 mt-1 transition-colors">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="ml-64 no-print">
        <header class="bg-white shadow-sm border-b border-pale-taupe">
            <div class="flex items-center justify-between px-8 py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Laporan Penjualan</h1>
                    <p class="text-gray-600">Detail laporan transaksi dan penjualan</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Selamat datang</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($owner['nama'] ?? 'Owner') ?></p>
                    </div>
                    <a href="profil.php" class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-pale-taupe">
                        <img src="<?= $foto_display ?>" alt="Profil" class="w-full h-full object-cover">
                    </a>
                </div>
            </div>
        </header>

        <main class="p-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Laporan</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pale-taupe focus:border-pale-taupe transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pale-taupe focus:border-pale-taupe transition-colors">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full btn-primary px-4 py-2 rounded-lg focus:ring-2 focus:ring-pale-taupe transition-colors">
                            <i class="fas fa-filter mr-2"></i>
                            Filter Data
                        </button>
                    </div>
                    <div class="flex items-end">
                        <a href="laporan_penjualan.php" class="w-full px-4 py-2 text-center text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-refresh mr-2"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-pale-taupe to-amber-800 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white text-sm font-medium">Total Transaksi</p>
                            <p class="text-2xl font-bold"><?php echo number_format($summary['total_transaksi'] ?? 0, 0); ?></p>
                        </div>
                        <i class="fas fa-shopping-cart text-2xl opacity-80"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Item Terjual</p>
                            <p class="text-2xl font-bold"><?php echo number_format($summary['total_item_terjual'] ?? 0, 0); ?></p>
                        </div>
                        <i class="fas fa-boxes text-2xl opacity-80"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Rata-rata/Transaksi</p>
                            <p class="text-2xl font-bold">Rp <?php echo number_format($avg_transaction, 0, ',', '.'); ?></p>
                        </div>
                        <i class="fas fa-chart-bar text-2xl opacity-80"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100 text-sm font-medium">Total Pendapatan</p>
                            <p class="text-2xl font-bold">Rp <?php echo number_format($summary['total_final'] ?? 0, 0, ',', '.'); ?></p>
                        </div>
                        <i class="fas fa-money-bill-wave text-2xl opacity-80"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Detail Laporan Penjualan</h3>
                            <p class="text-sm text-gray-600">Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></p>
                        </div>
                        <div class="flex space-x-2">
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Transaksi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Menu</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jumlah</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Subtotal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">PPN (11%)</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Service (5%)</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($result->num_rows > 0): ?>
                                <?php 
                                $counter = 1;
                                while ($row = $result->fetch_assoc()): 
                                ?>
                                <tr class="hover:bg-pale-taupe hover:bg-opacity-10 transition-colors group">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $counter++; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900 bg-pale-taupe bg-opacity-20 px-2 py-1 rounded">#<?php echo $row['id_transaksi']; ?></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($row['tanggal'])); ?></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $row['nama_menu']; ?></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php echo $row['nama_kategori'] == 'Makanan' ? 'bg-green-100 text-green-800 border border-green-200' : 
                                                  ($row['nama_kategori'] == 'Minuman' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-purple-100 text-purple-800 border border-purple-200'); ?>">
                                            <?php echo $row['nama_kategori']; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?php echo $row['jumlah']; ?> pcs
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            Rp <?php echo number_format($row['ppn'], 0, ',', '.'); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            Rp <?php echo number_format($row['service'], 0, ',', '.'); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-green-600">
                                            Rp <?php echo number_format($row['total_permenu'], 0, ',', '.'); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada data transaksi</h3>
                                            <p class="text-gray-600 mb-4">Tidak ada transaksi pada periode yang dipilih.</p>
                                            <a href="laporan_penjualan.php" class="px-4 py-2 text-sm text-pale-taupe hover:text-amber-800 font-medium">
                                                <i class="fas fa-refresh mr-2"></i>
                                                Tampilkan semua data
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($result->num_rows > 0): ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between text-sm text-gray-600 space-y-3 md:space-y-0">
                        <div>
                            Menampilkan <span class="font-semibold"><?php echo $result->num_rows; ?></span> item transaksi dari 
                            <span class="font-semibold"><?php echo $summary['total_transaksi'] ?? 0; ?></span> transaksi
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-6">
                                <div>
                                    <span class="font-semibold text-gray-800">Subtotal:</span>
                                    <span class="ml-2 font-medium text-gray-700">
                                        Rp <?php echo number_format($summary['total_subtotal'] ?? 0, 0, ',', '.'); ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="font-semibold text-gray-800">PPN:</span>
                                    <span class="ml-2 font-medium text-green-600">
                                        Rp <?php echo number_format($summary['total_ppn'] ?? 0, 0, ',', '.'); ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="font-semibold text-gray-800">Service:</span>
                                    <span class="ml-2 font-medium text-blue-600">
                                        Rp <?php echo number_format($summary['total_service'] ?? 0, 0, ',', '.'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="font-semibold text-gray-800">Grand Total:</span>
                                <span class="text-lg font-bold text-green-600">
                                    Rp <?php echo number_format($summary['total_final'] ?? 0, 0, ',', '.'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div class="print-only">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold">EasyResto</h1>
            <p class="text-gray-600">Laporan Penjualan</p>
            <p class="text-sm text-gray-600">Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></p>
            <p class="text-sm text-gray-600">Dicetak pada: <?php echo date('d/m/Y H:i'); ?></p>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
        <table class="w-full border-collapse border border-gray-300 mb-6">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">No</th>
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">ID Transaksi</th>
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">Tanggal</th>
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">Nama Menu</th>
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">Kategori</th>
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">Jumlah</th>
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">Subtotal</th>
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">PPN</th>
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">Service</th>
                    <th class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                $result->data_seek(0); // Reset pointer
                while ($row = $result->fetch_assoc()): 
                ?>
                <tr>
                    <td class="border border-gray-300 px-2 py-1 text-xs"><?php echo $counter++; ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs">#<?php echo $row['id_transaksi']; ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs"><?php echo $row['nama_menu']; ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs"><?php echo $row['nama_kategori']; ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs text-center"><?php echo $row['jumlah']; ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs text-right">Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs text-right">Rp <?php echo number_format($row['ppn'], 0, ',', '.'); ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs text-right">Rp <?php echo number_format($row['service'], 0, ',', '.'); ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs text-right font-bold">Rp <?php echo number_format($row['total_permenu'], 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 font-bold">
                    <td colspan="6" class="border border-gray-300 px-2 py-1 text-xs text-right">TOTAL:</td>
                    <td class="border border-gray-300 px-2 py-1 text-xs text-right">Rp <?php echo number_format($summary['total_subtotal'] ?? 0, 0, ',', '.'); ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs text-right">Rp <?php echo number_format($summary['total_ppn'] ?? 0, 0, ',', '.'); ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs text-right">Rp <?php echo number_format($summary['total_service'] ?? 0, 0, ',', '.'); ?></td>
                    <td class="border border-gray-300 px-2 py-1 text-xs text-right">Rp <?php echo number_format($summary['total_final'] ?? 0, 0, ',', '.'); ?></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="text-xs mt-8">
            <p>Rangkuman:</p>
            <ul class="list-disc pl-5">
                <li>Total Transaksi: <?php echo number_format($summary['total_transaksi'] ?? 0, 0); ?></li>
                <li>Total Item Terjual: <?php echo number_format($summary['total_item_terjual'] ?? 0, 0); ?></li>
                <li>Rata-rata per Transaksi: Rp <?php echo number_format($avg_transaction, 0, ',', '.'); ?></li>
                <li>Total Pendapatan: Rp <?php echo number_format($summary['total_final'] ?? 0, 0, ',', '.'); ?></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.querySelector('form');
            if (filterForm) {
                filterForm.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
                        submitBtn.disabled = true;
                    }
                });
            }
        });
    </script>
</body>
</html>