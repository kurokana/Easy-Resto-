<?php
session_start();
include '../config.php';

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
    <title>Dashboard - EasyResto Owner</title>
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
        .btn-secondary {
            background-color: #F7EBDF;
            color: #8B7355;
            border: 1px solid #B7A087;
        }
        .btn-secondary:hover {
            background-color: #E5D9C8;
        }
        .preview-image {
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body class="bg-antique-white">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 sidebar shadow-xl">
        <div class="flex items-center justify-center h-16 bg-pale-taupe">
            <div class="text-white">
                <h1 class="text-xl font-bold">EasyResto</h1>
                <p class="text-xs text-white opacity-90">Owner Panel</p>
            </div>
        </div>
        
        <nav class="mt-8">
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white">
                <i class="fas fa-chart-line w-6"></i>
                <span class="mx-3 font-medium">Dashboard</span>
            </a>
            <a href="laporan_penjualan.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
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

    <div class="ml-64">
        <header class="bg-white shadow-sm border-b border-pale-taupe">
            <div class="flex items-center justify-between px-8 py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-gray-600">Ringkasan kinerja restoran</p>
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-wallet text-2xl text-green-500"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-600">Total Penjualan</h3>
                                <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center text-sm text-green-600">
                                <i class="fas fa-arrow-up mr-1"></i>
                                <span>12%</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">vs bulan lalu</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-shopping-cart text-2xl text-blue-500"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-600">Total Transaksi</h3>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_transaksi, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center text-sm text-blue-600">
                                <i class="fas fa-chart-line mr-1"></i>
                                <span>8%</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">peningkatan</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-star text-2xl text-purple-500"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-600">Menu Terpopuler</h3>
                                <p class="text-lg font-bold text-gray-900 truncate max-w-[150px]"><?php echo htmlspecialchars($menu_terpopuler['nama_menu'] ?? 'Belum ada data'); ?></p>
                                <p class="text-sm text-gray-500">Terjual: <?php echo $menu_terpopuler['total_terjual'] ?? '0'; ?> pcs</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <i class="fas fa-fire mr-1"></i>
                                Hot
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Penjualan per Kategori</h3>
                    </div>
                    <div class="h-80">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Transaksi Terbaru</h3>
                        <a href="laporan_penjualan.php" class="text-sm text-pale-taupe hover:text-amber-800 font-medium flex items-center">
                            Lihat Semua
                            <i class="fas fa-chevron-right ml-1 text-xs"></i>
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left border-b-2 border-gray-200">
                                    <th class="pb-4 font-semibold text-gray-600 text-sm">ID Transaksi</th>
                                    <th class="pb-4 font-semibold text-gray-600 text-sm">Pelanggan</th>
                                    <th class="pb-4 font-semibold text-gray-600 text-sm">Tanggal</th>
                                    <th class="pb-4 font-semibold text-gray-600 text-sm">Total</th>
                                    <th class="pb-4 font-semibold text-gray-600 text-sm">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_transactions->num_rows > 0): ?>
                                    <?php while($transaction = $recent_transactions->fetch_assoc()): ?>
                                    <tr class="border-b border-gray-100 hover:bg-pale-taupe hover:bg-opacity-10 transition-colors group">
                                        <td class="py-4 text-sm font-medium text-gray-900">
                                            <span class="bg-pale-taupe bg-opacity-20 px-2 py-1 rounded text-gray-700">#<?php echo $transaction['id_transaksi']; ?></span>
                                        </td>
                                        <td class="py-4 text-sm text-gray-600"><?php echo htmlspecialchars($transaction['nama_pelanggan']); ?></td>
                                        <td class="py-4 text-sm text-gray-600">
                                            <div><?php echo date('d M Y', strtotime($transaction['tanggal'])); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($transaction['tanggal'])); ?></div>
                                        </td>
                                        <td class="py-4 text-sm font-semibold text-gray-900">Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?></td>
                                        <td class="py-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 flex items-center w-fit">
                                                <i class="fas fa-check mr-1 text-xs"></i>
                                                Selesai
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="py-8 text-center">
                                            <div class="flex flex-col items-center justify-center text-gray-500">
                                                <i class="fas fa-receipt text-3xl mb-3 opacity-50"></i>
                                                <p class="text-sm">Belum ada transaksi</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistik Tambahan</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-pale-taupe bg-opacity-10 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-utensils text-pale-taupe mr-3"></i>
                                    <span class="text-sm font-medium text-gray-700">Total Menu</span>
                                </div>
                                <span class="text-lg font-bold text-pale-taupe">
                                    <?php echo $conn->query("SELECT COUNT(*) as total FROM menu")->fetch_assoc()['total']; ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-users text-green-500 mr-3"></i>
                                    <span class="text-sm font-medium text-gray-700">Total Pengguna</span>
                                </div>
                                <span class="text-lg font-bold text-green-600">
                                    <?php echo $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total']; ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-tags text-purple-500 mr-3"></i>
                                    <span class="text-sm font-medium text-gray-700">Kategori Menu</span>
                                </div>
                                <span class="text-lg font-bold text-purple-600">
                                    <?php echo $conn->query("SELECT COUNT(*) as total FROM kategori_menu")->fetch_assoc()['total']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');        
                const categoryLabels = [
                <?php
                $cat_labels = [];
                $cat_data = [];
                $kategori_penjualan->data_seek(0); // Reset pointer
                while ($row = $kategori_penjualan->fetch_assoc()) {
                    $cat_labels[] = "'" . htmlspecialchars($row['nama_kategori']) . "'";
                    $cat_data[] = $row['total'];
                }
                if (empty($cat_labels)) {
                    echo "'Makanan', 'Minuman', 'Dessert'";
                    $cat_data = [100000, 50000, 30000];
                } else {
                    echo implode(', ', $cat_labels);
                }
                ?>
            ];
            
            const categoryData = [<?php echo implode(', ', $cat_data); ?>];
            
            const categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: [
                            '#B7A087',
                            '#10b981',
                            '#8b5cf6',
                            '#f59e0b',
                            '#ef4444',
                            '#3b82f6',
                            '#ec4899'
                        ],
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12,
                                    family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                                },
                                color: '#374151'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    cutout: '65%',
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 2000,
                        easing: 'easeOutQuart'
                    }
                }
            });

            const cards = document.querySelectorAll('.bg-white');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            const exportBtn = document.querySelector('button:has(.fa-download)');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check mr-1"></i>Downloaded!';
                    this.classList.remove('bg-gray-100', 'hover:bg-gray-200');
                    this.classList.add('bg-green-100', 'text-green-700');
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('bg-green-100', 'text-green-700');
                        this.classList.add('bg-gray-100', 'hover:bg-gray-200');
                    }, 2000);
                });
            }
        });
    </script>
</body>
</html>