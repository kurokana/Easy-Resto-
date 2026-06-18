<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header("Location: ../login.php");
    exit();
}

$id_user_active = $_SESSION['id_user'];
$query_user = $conn->query("SELECT profile_picture, nama FROM users WHERE id_user = '$id_user_active'");
$data_user = $query_user->fetch_assoc();

$nama_user = $data_user['nama'];
$foto_db = $data_user['profile_picture'];

$foto = !empty($foto_db) && file_exists('../' . $foto_db) 
    ? '../' . $foto_db 
    : 'https://ui-avatars.com/api/?name=' . urlencode($nama_user) . '&background=B7A087&color=fff';

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Kasir</title>
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
        body { background-color: #F7EBDF; }
        .sidebar { background: linear-gradient(to bottom, #B7A087, #8B7355); }
        .card { background: white; border: 1px solid #E5D9C8; }
        .btn-primary { background-color: #B7A087; color: white; }
        .btn-primary:hover { background-color: #8B7355; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        .print-only { display: none; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background-color: white !important; }
            .sidebar { display: none !important; }
            .flex-1 { margin: 0 !important; overflow: visible !important; }
            .ml-64 { margin-left: 0 !important; }
            table { font-size: 10px !important; }
        }
    </style>
</head>
<body class="bg-antique-white flex h-screen overflow-hidden font-sans text-gray-800">

    <div class="w-64 sidebar shadow-xl flex flex-col h-full relative z-20 flex-shrink-0 no-print">
        <div>
            <div class="h-16 flex items-center justify-center bg-pale-taupe">
                <div class="text-white text-center">
                    <h1 class="text-xl font-bold">EasyResto</h1>
                    <p class="text-xs text-white opacity-90">Kasir Panel</p>
                </div>
            </div>
            
            <nav class="mt-8">
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-home w-6"></i>
                    <span class="mx-3 font-medium">Dashboard</span>
                </a>
                <a href="transaksi.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-cash-register w-6"></i>
                    <span class="mx-3 font-medium">Transaksi</span>
                </a>
                <a href="riwayat.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-history w-6"></i>
                    <span class="mx-3 font-medium">Riwayat</span>
                </a>
                <a href="laporan.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
                    <i class="fas fa-chart-line w-6"></i>
                    <span class="mx-3 font-medium">Laporan</span>
                </a>
                <a href="profil_kasir.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-user-cog w-6"></i>
                    <span class="mx-3 font-medium">Profil</span>
                </a>
            </nav>
        </div>
        
        <div class="absolute bottom-0 w-full p-4 bg-pale-taupe bg-opacity-80">
            <div class="flex items-center gap-3">
                <a href="profil_kasir.php" class="shrink-0">
                    <img src="<?= $foto ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover hover:opacity-80 transition-opacity">
                </a>
                <div class="overflow-hidden text-white">
                    <a href="profil_kasir.php" class="group block">
                        <p class="font-bold text-sm truncate leading-tight group-hover:underline"><?= htmlspecialchars($nama_user) ?></p>
                        <p class="text-xs opacity-90">Role: Kasir</p>
                    </a>
                    <a href="../logout.php" class="text-xs text-red-200 hover:text-white flex items-center gap-1 mt-1 transition-colors w-max">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        
        <header class="bg-white shadow-sm border-b border-pale-taupe flex-shrink-0 no-print">
            <div class="flex items-center justify-between px-8 py-4">
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Laporan Penjualan</h1>
                    <p class="text-gray-600 text-sm mt-1">Ringkasan Transaksi</p>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm text-gray-600">Selamat datang</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($nama_user) ?></p>
                    </div>
                    <a href="profil_kasir.php" class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-pale-taupe cursor-pointer transition-transform hover:scale-105">
                        <img src="<?= $foto ?>" class="w-full h-full object-cover">
                    </a>
                </div>

            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8 scrollbar-hide">
            
            <div class="card rounded-xl shadow-sm p-6 mb-8 no-print">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Laporan</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-[#E5D9C8] text-sm focus:border-pale-taupe transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-[#E5D9C8] text-sm focus:border-pale-taupe transition-colors">
                    </div>
                    <div>
                        <button type="submit" class="w-full btn-primary px-4 py-2 rounded-lg font-medium shadow-sm transition-transform active:scale-95 text-sm">
                            <i class="fas fa-filter mr-2"></i> Filter Data
                        </button>
                    </div>
                    <div>
                         <a href="laporan.php" class="flex items-center justify-center w-full px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition text-sm">
                            <i class="fas fa-redo-alt mr-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 no-print">
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

            <div class="card rounded-xl shadow-sm overflow-hidden border border-[#E5D9C8] no-print">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">Detail Laporan Penjualan</h3>
                    <p class="text-sm text-gray-600">Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                        <thead class="bg-gray-50 border-b border-[#E5D9C8]">
                            <tr class="text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <th class="px-6 py-4">No</th>
                                <th class="px-6 py-4">ID Transaksi</th>
                                <th class="px-6 py-4">Tanggal</th>
                                <th class="px-6 py-4">Menu</th>
                                <th class="px-6 py-4">Kategori</th>
                                <th class="px-6 py-4 text-center">Jml</th>
                                <th class="px-6 py-4 text-right">Subtotal</th>
                                <th class="px-6 py-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($result->num_rows > 0): ?>
                                <?php 
                                $counter = 1;
                                while ($row = $result->fetch_assoc()): 
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-3 text-sm text-gray-500"><?= $counter++ ?></td>
                                    <td class="px-6 py-3">
                                        <span class="text-sm font-medium text-gray-900 bg-gray-100 px-2 py-1 rounded">#<?= $row['id_transaksi'] ?></span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <div class="text-sm text-gray-900"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></div>
                                        <div class="text-xs text-gray-500"><?= date('H:i', strtotime($row['tanggal'])) ?></div>
                                    </td>
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900"><?= $row['nama_menu'] ?></td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?= $row['nama_kategori'] == 'Makanan' ? 'bg-green-100 text-green-800' : 
                                               ($row['nama_kategori'] == 'Minuman' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') ?>">
                                            <?= $row['nama_kategori'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-center font-bold text-gray-700"><?= $row['jumlah'] ?></td>
                                    <td class="px-6 py-3 text-sm text-right text-gray-600">Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                                    <td class="px-6 py-3 text-sm text-right font-bold text-pale-taupe">Rp <?= number_format($row['total_permenu'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-inbox text-4xl mb-3 opacity-30"></i>
                                            <p>Tidak ada data penjualan pada periode ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($result->num_rows > 0): ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col md:flex-row items-center justify-end space-y-2 md:space-y-0 text-sm">
                        <div class="flex items-center space-x-6">
                            <div><span class="font-semibold text-gray-700">Subtotal:</span> Rp <?= number_format($summary['total_subtotal'] ?? 0, 0, ',', '.') ?></div>
                            <div><span class="font-semibold text-gray-700">PPN:</span> Rp <?= number_format($summary['total_ppn'] ?? 0, 0, ',', '.') ?></div>
                            <div><span class="font-semibold text-gray-700">Service:</span> Rp <?= number_format($summary['total_service'] ?? 0, 0, ',', '.') ?></div>
                            <div class="text-lg font-bold text-green-700 ml-4">Total: Rp <?= number_format($summary['total_final'] ?? 0, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div class="print-only p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold uppercase">Laporan Penjualan</h1>
            <p class="text-sm text-gray-600 mt-1">Periode: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?></p>
            <p class="text-xs text-gray-500">Dicetak oleh: <?= $nama_user ?> pada <?= date('d/m/Y H:i') ?></p>
        </div>

        <?php if ($result->num_rows > 0): ?>
        <table class="w-full border-collapse border border-gray-300 text-xs mb-6">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border p-2">No</th>
                    <th class="border p-2">ID</th>
                    <th class="border p-2">Tanggal</th>
                    <th class="border p-2">Menu</th>
                    <th class="border p-2 text-center">Jml</th>
                    <th class="border p-2 text-right">Subtotal</th>
                    <th class="border p-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $result->data_seek(0); $no=1; while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="border p-2 text-center"><?= $no++ ?></td>
                    <td class="border p-2">#<?= $row['id_transaksi'] ?></td>
                    <td class="border p-2"><?= date('d/m H:i', strtotime($row['tanggal'])) ?></td>
                    <td class="border p-2"><?= $row['nama_menu'] ?></td>
                    <td class="border p-2 text-center"><?= $row['jumlah'] ?></td>
                    <td class="border p-2 text-right"><?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                    <td class="border p-2 text-right font-bold"><?= number_format($row['total_permenu'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 font-bold">
                    <td colspan="6" class="border p-2 text-right">TOTAL PENDAPATAN</td>
                    <td class="border p-2 text-right">Rp <?= number_format($summary['total_final'] ?? 0, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="text-xs">
            <p class="font-bold mb-1">Rangkuman:</p>
            <ul class="list-disc pl-5">
                <li>Total Transaksi: <?= number_format($summary['total_transaksi'] ?? 0, 0) ?></li>
                <li>Item Terjual: <?= number_format($summary['total_item_terjual'] ?? 0, 0) ?></li>
                <li>Rata-rata: Rp <?= number_format($avg_transaction, 0, ',', '.') ?></li>
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