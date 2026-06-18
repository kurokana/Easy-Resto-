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

$foto_db = $data_user['profile_picture'];
$nama_user = $data_user['nama'];

$foto = !empty($foto_db) && file_exists('../' . $foto_db) 
    ? '../' . $foto_db 
    : 'https://ui-avatars.com/api/?name=' . urlencode($nama_user) . '&background=B7A087&color=fff';

if (isset($_GET['ajax_search'])) {
    $search = $conn->real_escape_string($_GET['ajax_search']);
    $where = "1=1";
    if (!empty($search)) {
        $where .= " AND (nama_pelanggan LIKE '%$search%' OR id_transaksi LIKE '%$search%')";
    }
    
    $query = "SELECT * FROM transaksi WHERE $where ORDER BY tanggal DESC LIMIT 50";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            ?>
            <tr class="table-row-hover transition-colors border-b border-gray-100 last:border-none">
                <td class="px-6 py-4">
                    <span class="font-mono text-sm bg-gray-100 text-gray-600 px-2 py-1 rounded">
                        #<?= $row['id_transaksi'] ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    <div class="font-medium"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                    <div class="text-xs text-gray-400"><?= date('H:i', strtotime($row['tanggal'])) ?> WIB</div>
                </td>
                <td class="px-6 py-4 text-sm font-semibold text-gray-800">
                    <?= htmlspecialchars($row['nama_pelanggan']) ?>
                </td>
                <td class="px-6 py-4 text-sm font-bold text-pale-taupe">
                    Rp <?= number_format($row['total'], 0, ',', '.') ?>
                </td>
                <td class="px-6 py-4 text-center">
                    <button onclick="showReceipt(<?= $row['id_transaksi'] ?>)" 
                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-[#E5D9C8] text-gray-600 hover:bg-pale-taupe hover:text-white hover:border-pale-taupe transition-all shadow-sm" title="Lihat Struk">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="5" class="px-6 py-16 text-center text-gray-400">Tidak ditemukan.</td></tr>';
    }
    exit();
}

$query_awal = "SELECT * FROM transaksi ORDER BY tanggal DESC LIMIT 50";
$result_awal = $conn->query($query_awal);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - EasyResto</title>
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
        .table-row-hover:hover { background-color: rgba(183, 160, 135, 0.1); }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
        input:focus, select:focus, button:focus {
            outline: none !important; box-shadow: none !important; border-color: #B7A087 !important;
        }
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap');
    </style>
</head>
<body class="bg-antique-white flex h-screen overflow-hidden font-sans text-gray-800">

    <div class="w-64 sidebar shadow-xl flex flex-col h-full relative z-20 flex-shrink-0">
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
                <a href="riwayat.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
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
        
        <header class="bg-white shadow-sm border-b border-pale-taupe flex-shrink-0">
            <div class="flex flex-col sm:flex-row justify-between items-center px-8 py-4 gap-4">
                
                <div class="w-full sm:w-auto">
                    <h1 class="text-2xl font-bold text-gray-800">Riwayat Transaksi</h1>
                    <p class="text-gray-600 text-sm mt-1">Daftar transaksi yang telah selesai</p>
                </div>
                
                <div class="flex items-center gap-4 w-full sm:w-auto justify-end">
                    <div class="relative group flex-1 sm:w-64">
                        <i class="fas fa-search absolute left-4 top-3 text-gray-400 group-hover:text-pale-taupe transition-colors"></i>
                        <input type="text" id="searchInput" 
                               placeholder="Cari ID atau Nama..." autocomplete="off"
                               class="w-full pl-10 pr-4 py-2 rounded-lg border border-[#E5D9C8] text-sm focus:border-pale-taupe transition-all bg-gray-50 focus:bg-white placeholder-gray-400 shadow-sm focus:shadow-md">
                        <div id="searchLoading" class="hidden absolute right-3 top-3">
                            <i class="fas fa-circle-notch fa-spin text-pale-taupe"></i>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4 pl-4 border-l border-gray-200 hidden sm:flex">
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Selamat datang</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($nama_user) ?></p>
                        </div>
                        <a href="profil_kasir.php" class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-pale-taupe cursor-pointer transition-transform hover:scale-105">
                            <img src="<?= $foto ?>" class="w-full h-full object-cover">
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8 scrollbar-hide">
            <div class="card rounded-xl shadow-sm overflow-hidden border border-[#E5D9C8]">
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                        <thead class="bg-gray-50 border-b border-[#E5D9C8]">
                            <tr class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-4">ID Transaksi</th>
                                <th class="px-6 py-4">Waktu</th>
                                <th class="px-6 py-4">Pelanggan</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-gray-100 bg-white">
                            <?php if ($result_awal->num_rows > 0): ?>
                                <?php while($row = $result_awal->fetch_assoc()): ?>
                                <tr class="table-row-hover transition-colors border-b border-gray-100 last:border-none">
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-sm bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                            #<?= $row['id_transaksi'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="font-medium"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                                        <div class="text-xs text-gray-400"><?= date('H:i', strtotime($row['tanggal'])) ?> WIB</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">
                                        <?= htmlspecialchars($row['nama_pelanggan']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-pale-taupe">
                                        Rp <?= number_format($row['total'], 0, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button onclick="showReceipt(<?= $row['id_transaksi'] ?>)" 
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-[#E5D9C8] text-gray-600 hover:bg-pale-taupe hover:text-white hover:border-pale-taupe transition-all shadow-sm" title="Lihat Struk">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-16 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-search text-3xl mb-3 opacity-30"></i>
                                            <p class="text-sm">Belum ada data transaksi.</p>
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

    <div id="receiptModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50" onclick="closeModal()"></div>
        <div class="modal-container bg-white w-11/12 md:w-[350px] mx-auto rounded-xl shadow-2xl z-50 overflow-y-auto max-h-[90vh] flex flex-col transform transition-all scale-95 duration-300">
            <div class="bg-pale-taupe text-white py-3 px-4 flex justify-between items-center rounded-t-xl">
                <h3 class="font-bold text-lg"><i class="fas fa-receipt mr-2"></i>Detail Struk</h3>
                <div class="cursor-pointer z-50 hover:text-red-200" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="p-6 flex-1 bg-gray-50 flex justify-center" id="receiptContent">
                <div class="flex justify-center items-center h-40">
                    <i class="fas fa-circle-notch fa-spin text-3xl text-pale-taupe"></i>
                </div>
            </div>
            <div class="bg-white border-t border-gray-200 px-4 py-3 flex justify-end gap-3 rounded-b-xl">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium transition text-sm">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function showReceipt(id) {
            const modal = document.getElementById('receiptModal');
            const content = document.getElementById('receiptContent');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.classList.add('opacity-100', 'pointer-events-auto');
            modal.querySelector('.modal-container').classList.remove('scale-95');
            modal.querySelector('.modal-container').classList.add('scale-100');
            document.body.classList.add('modal-active');
            
            fetch('cetak_struk.php?id=' + id + '&embed=true')
                .then(res => res.text())
                .then(html => { content.innerHTML = html; })
                .catch(err => { content.innerHTML = '<p class="text-red-500 text-center">Gagal memuat struk.</p>'; });
        }
        function closeModal() {
            const modal = document.getElementById('receiptModal');
            modal.classList.remove('opacity-100', 'pointer-events-auto');
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.querySelector('.modal-container').classList.remove('scale-100');
            modal.querySelector('.modal-container').classList.add('scale-95');
            document.body.classList.remove('modal-active');
            setTimeout(() => {
                document.getElementById('receiptContent').innerHTML = '<div class="flex justify-center items-center h-40"><i class="fas fa-circle-notch fa-spin text-3xl text-pale-taupe"></i></div>';
            }, 300);
        }
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('tableBody');
        const loadingIndicator = document.getElementById('searchLoading');
        let timeout = null;
        searchInput.addEventListener('input', function() {
            const keyword = this.value;
            loadingIndicator.classList.remove('hidden');
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                fetch(`riwayat.php?ajax_search=${encodeURIComponent(keyword)}`)
                    .then(response => response.text())
                    .then(html => {
                        tableBody.innerHTML = html;
                        loadingIndicator.classList.add('hidden');
                    })
                    .catch(error => { console.error('Error:', error); loadingIndicator.classList.add('hidden'); });
            }, 300);
        });
    </script>
</body>
</html>