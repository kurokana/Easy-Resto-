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

$foto_display = !empty($foto_db) && file_exists('../' . $foto_db) 
    ? '../' . $foto_db 
    : 'https://ui-avatars.com/api/?name=' . urlencode($nama_user) . '&background=B7A087&color=fff';

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kasir</title>
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
        body { background-color: #F7EBDF; }
        .sidebar { background: linear-gradient(to bottom, #B7A087, #8B7355); }
        .card { background: white; border: 1px solid #E5D9C8; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-antique-white h-screen flex overflow-hidden font-sans text-gray-800">
    
    <div class="w-64 sidebar shadow-xl flex flex-col justify-between z-20 flex-shrink-0">
        <div>
            <div class="h-16 flex items-center justify-center bg-pale-taupe">
                <div class="text-white text-center">
                    <h1 class="text-xl font-bold">EasyResto</h1>
                    <p class="text-xs text-white opacity-90">Kasir Panel</p>
                </div>
            </div>
            
            <nav class="mt-8">
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
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
                <a href="laporan.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-chart-line w-6"></i>
                    <span class="mx-3 font-medium">Laporan</span>
                </a>
                <a href="profil_kasir.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-user-cog w-6"></i>
                    <span class="mx-3 font-medium">Profil</span>
                </a>
            </nav>
        </div>
        
        <div class="p-4 bg-pale-taupe bg-opacity-80">
            <div class="flex items-center gap-3">
                <img src="<?= $foto_display ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
                <div class="overflow-hidden text-white">
                    <p class="font-bold text-sm truncate leading-tight"><?= htmlspecialchars($nama_user) ?></p>
                    <p class="text-xs opacity-90">Role: Kasir</p>
                    <a href="../logout.php" class="text-xs text-red-200 hover:text-white flex items-center gap-1 mt-1 transition-colors">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        
        <header class="bg-white shadow-sm border-b border-pale-taupe flex-shrink-0">
            <div class="flex items-center justify-between px-8 py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-gray-600">Ringkasan Kinerja Restoran</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm text-gray-600">Selamat datang</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($nama_user) ?></p>
                    </div>
                    <a href="profil_kasir.php" class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-pale-taupe transition-transform hover:scale-105">
                        <img src="<?= $foto_display ?>" class="w-full h-full object-cover">
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8 scrollbar-hide">
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

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');        
            const categoryLabels = [
                <?php
                $cat_labels = [];
                $cat_data = [];
                $kategori_penjualan->data_seek(0);
                while ($row = $kategori_penjualan->fetch_assoc()) {
                    $cat_labels[] = "'" . htmlspecialchars($row['nama_kategori']) . "'";
                    $cat_data[] = $row['total'];
                }
                if (empty($cat_labels)) {
                    echo "'Makanan', 'Minuman', 'Dessert'";
                    $cat_data = [0, 0, 0];
                } else {
                    echo implode(', ', $cat_labels);
                }
                ?>
            ];
            
            const categoryData = [<?php echo implode(', ', $cat_data); ?>];
            
            const categoryChart = new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Pendapatan',
                        data: categoryData,
                        backgroundColor: [
                            'rgba(183, 160, 135, 0.7)',
                            'rgba(139, 115, 85, 0.7)',
                            'rgba(247, 235, 223, 0.7)',
                            '#10b981', '#8b5cf6', '#f59e0b'
                        ],
                        borderColor: [
                            'rgba(183, 160, 135, 1)',
                            'rgba(139, 115, 85, 1)',
                            'rgba(247, 235, 223, 1)',
                            '#10b981', '#8b5cf6', '#f59e0b'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.raw.toLocaleString('id-ID');
                                }
                            }
                        }
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
        });
    </script>
</body>
</html>