<?php
session_start();
include '../config.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();

// Hanya owner dan admin yang bisa akses
if ($owner['role'] != 'owner' && $owner['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Setup upload directory
$upload_dir = '../uploads/menu/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Inisialisasi variabel
$success = '';
$error = '';
$edit_data = null;

// Query untuk statistik (dihapus dari tampilan tapi tetap di kode untuk kebutuhan lain)
$kategori_result = $conn->query("SELECT * FROM kategori_menu");

$total_result = $conn->query("SELECT COUNT(*) as total FROM menu");
$total_row = $total_result->fetch_assoc();
$total_menu = $total_row['total'];

// Handle Tambah Menu dengan Foto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_menu'])) {
    $nama_menu = $_POST['nama_menu'];
    $harga = $_POST['harga'];
    $id_kategori = $_POST['id_kategori'];
    $foto_nama = null;

    // Handle file upload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $dest = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                $foto_nama = $new_filename;
            } else {
                $error = "Gagal mengupload foto.";
            }
        } else {
            $error = "Format foto tidak valid (hanya jpg, jpeg, png, webp).";
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO menu (nama_menu, harga, id_kategori, foto) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $nama_menu, $harga, $id_kategori, $foto_nama);
        if ($stmt->execute()) {
            $success = "Menu berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan menu ke database.";
        }
    }
}

// Handle Edit Menu dengan Foto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_menu'])) {
    $id_menu = $_POST['id_menu'];
    $nama_menu = $_POST['nama_menu'];
    $harga = $_POST['harga'];
    $id_kategori = $_POST['id_kategori'];
    
    // Get old foto
    $stmt_old = $conn->prepare("SELECT foto FROM menu WHERE id_menu = ?");
    $stmt_old->bind_param("i", $id_menu);
    $stmt_old->execute();
    $old_foto = $stmt_old->get_result()->fetch_assoc()['foto'];
    $foto_nama = $old_foto;

    // Handle new file upload if exists
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '_edit.' . $ext;
            $dest = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                // Delete old photo if exists
                if ($old_foto && file_exists($upload_dir . $old_foto)) {
                    unlink($upload_dir . $old_foto);
                }
                $foto_nama = $new_filename;
            } else {
                $error = "Gagal mengupload foto baru.";
            }
        } else {
            $error = "Format foto tidak valid.";
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare("UPDATE menu SET nama_menu=?, harga=?, id_kategori=?, foto=? WHERE id_menu=?");
        $stmt->bind_param("siisi", $nama_menu, $harga, $id_kategori, $foto_nama, $id_menu);
        if ($stmt->execute()) {
            $success = "Menu berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui menu.";
        }
    }
}

// Handle Delete Menu dengan hapus foto
if (isset($_GET['hapus'])) {
    $id_menu = $_GET['hapus'];
    
    // Get menu data first to delete photo
    $stmt = $conn->prepare("SELECT foto FROM menu WHERE id_menu = ?");
    $stmt->bind_param("i", $id_menu);
    $stmt->execute();
    $menu = $stmt->get_result()->fetch_assoc();

    if ($menu) {
        // Delete photo if exists
        if ($menu['foto'] && file_exists($upload_dir . $menu['foto'])) {
            unlink($upload_dir . $menu['foto']);
        }
        
        // Delete from database
        $stmt_del = $conn->prepare("DELETE FROM menu WHERE id_menu = ?");
        $stmt_del->bind_param("i", $id_menu);
        if ($stmt_del->execute()) {
            $success = "Menu berhasil dihapus!";
            
            // Reset AUTO_INCREMENT
            $max_result = $conn->query("SELECT MAX(id_menu) as max_id FROM menu");
            $max_row = $max_result->fetch_assoc();
            $next_ai = ($max_row && $max_row['max_id']) ? (intval($max_row['max_id']) + 1) : 1;
            $conn->query("ALTER TABLE menu AUTO_INCREMENT = " . $next_ai);
        } else {
            $error = "Gagal menghapus menu dari database.";
        }
    }
    
    header("Location: manajemen_menu.php" . ($success ? "?success=".urlencode($success) : "") . ($error ? "?error=".urlencode($error) : ""));
    exit();
}

// Get data for edit
if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $edit_result = $conn->query("
        SELECT m.*, k.nama_kategori 
        FROM menu m 
        LEFT JOIN kategori_menu k ON m.id_kategori = k.id_kategori 
        WHERE m.id_menu = $id_edit
    ");
    if ($edit_result->num_rows > 0) {
        $edit_data = $edit_result->fetch_assoc();
    }
}

// Get all menu for display
$menu_display_result = $conn->query("
    SELECT m.*, k.nama_kategori 
    FROM menu m 
    LEFT JOIN kategori_menu k ON m.id_kategori = k.id_kategori 
    ORDER BY m.id_menu DESC
");

// Get success/error from URL
if (isset($_GET['success'])) $success = $_GET['success'];
if (isset($_GET['error'])) $error = $_GET['error'];

// Profile picture setup
$foto_display = 'https://ui-avatars.com/api/?name=' . urlencode($owner['nama'] ?? 'Owner') . '&background=B7A087&color=fff';
if (!empty($owner['profile_picture']) && file_exists($owner['profile_picture'])) {
    $foto_display = $owner['profile_picture'];
}

// Reset pointer untuk kategori
$kategori_result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Menu - EasyResto Owner</title>
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
                        'secondary': '#F7EBDF',
                        'dark-taupe': '#8B7355'
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
        .card {
            background: white;
            border: 1px solid #E5D9C8;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
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
        .table-row:hover {
            background-color: rgba(183, 160, 135, 0.08);
        }
        .modal {
            transition: opacity 0.25s ease;
        }
        body.modal-active {
            overflow-x: hidden;
            overflow-y: visible !important;
        }
    </style>
</head>
<body class="bg-antique-white h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <div class="w-64 sidebar shadow-xl flex flex-col justify-between z-20 flex-shrink-0">
        <div>
            <div class="h-16 flex items-center justify-center bg-pale-taupe">
                <div class="text-white text-center">
                    <h1 class="text-xl font-bold">EasyResto</h1>
                    <p class="text-xs text-white opacity-90">Owner Panel</p>
                </div>
            </div>
            
            <nav class="mt-8">
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-chart-line w-6"></i>
                    <span class="mx-3 font-medium">Dashboard</span>
                </a>
                <a href="laporan_penjualan.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-file-invoice-dollar w-6"></i>
                    <span class="mx-3 font-medium">Laporan Penjualan</span>
                </a>
                <a href="manajemen_menu.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
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
        </div>
        
        <div class="p-4 bg-pale-taupe bg-opacity-80">
            <div class="flex items-center gap-3">
                <img src="<?= $foto_display ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
                <div class="overflow-hidden text-white">
                    <p class="font-bold text-sm truncate leading-tight"><?= htmlspecialchars($owner['nama'] ?? 'Owner') ?></p>
                    <p class="text-xs opacity-90"><?= ucfirst($owner['role'] ?? 'Owner') ?></p>
                    <a href="../logout.php" class="text-xs text-red-200 hover:text-white flex items-center gap-1 mt-1 transition-colors">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <header class="bg-white shadow-sm border-b border-pale-taupe flex-shrink-0 px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manajemen Menu</h1>
                    <p class="text-gray-600">Kelola daftar menu makanan dan minuman</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden md:block">
                        <p class="text-sm text-gray-600">Selamat datang</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($owner['nama'] ?? 'Owner') ?></p>
                    </div>
                    <a href="profil.php" class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-pale-taupe">
                        <img src="<?= $foto_display ?>" alt="Profil" class="w-full h-full object-cover">
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-500"></i><span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-500"></i><span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Daftar Menu (tanpa statistik cards) -->
            <div class="bg-white card p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Daftar Menu</h3>
                        <p class="text-sm text-gray-600">Total: <?php echo $total_menu; ?> item</p>
                    </div>
                    <button onclick="toggleModal('addModal')" class="btn-primary px-4 py-2 rounded-lg font-semibold shadow-sm flex items-center">
                        <i class="fas fa-plus mr-2"></i> Tambah Menu
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Foto</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nama Menu</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Harga</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($total_menu > 0): ?>
                                <?php while ($menu = $menu_display_result->fetch_assoc()): ?>
                                <tr class="table-row transition-colors">
                                    <td class="px-6 py-4">
                                        <?php if($menu['foto'] && file_exists($upload_dir . $menu['foto'])): ?>
                                            <img src="<?= $upload_dir . $menu['foto'] ?>" alt="<?= htmlspecialchars($menu['nama_menu']) ?>" class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                                        <?php else: ?>
                                            <div class="w-16 h-16 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($menu['nama_menu']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-pale-taupe bg-opacity-20 text-dark-taupe">
                                            <?= htmlspecialchars($menu['nama_kategori'] ?? 'Tanpa Kategori') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                        Rp <?= number_format($menu['harga'], 0, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium space-x-3">
                                        <button onclick='openEditModal(<?= json_encode($menu) ?>)' class="text-blue-600 hover:text-blue-900 transition-colors">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <button onclick="hapusMenu(<?= $menu['id_menu'] ?>, '<?= htmlspecialchars($menu['nama_menu'], ENT_QUOTES) ?>')" class="text-red-600 hover:text-red-900 transition-colors">
                                            <i class="fas fa-trash mr-1"></i>Hapus
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-utensils text-4xl text-gray-400 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada menu</h3>
                                            <p class="text-gray-600 mb-4">Tambahkan menu pertama Anda untuk memulai.</p>
                                            <button onclick="toggleModal('addModal')" class="btn-primary px-4 py-2 rounded-lg font-semibold shadow-sm flex items-center">
                                                <i class="fas fa-plus mr-2"></i> Tambah Menu Pertama
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

    <!-- Modal Tambah Menu -->
    <div id="addModal" class="modal fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50 overflow-y-auto my-10">
            <div class="modal-content py-4 text-left px-6">
                <div class="flex justify-between items-center pb-3">
                    <p class="text-2xl font-bold text-gray-800">Tambah Menu Baru</p>
                    <button onclick="toggleModal('addModal')" class="modal-close cursor-pointer z-50">
                        <i class="fas fa-times text-gray-500 hover:text-gray-700"></i>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data" onsubmit="return validateAddForm()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Menu *</label>
                            <input type="text" name="nama_menu" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pale-taupe transition-colors"
                                   placeholder="Contoh: Nasi Goreng Spesial">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
                            <select name="id_kategori" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pale-taupe transition-colors">
                                <option value="">Pilih Kategori</option>
                                <?php 
                                $kategori_result->data_seek(0);
                                while ($kategori = $kategori_result->fetch_assoc()): ?>
                                    <option value="<?php echo $kategori['id_kategori']; ?>">
                                        <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp) *</label>
                            <input type="number" name="harga" required min="1000" step="500"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pale-taupe transition-colors"
                                   placeholder="Contoh: 25000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Menu</label>
                            <input type="file" name="foto" accept="image/*" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pale-taupe transition-colors text-sm">
                            <p class="text-xs text-gray-500 mt-1">Format: jpg, jpeg, png, webp. (Opsional)</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-6 space-x-3">
                        <button type="button" onclick="toggleModal('addModal')" 
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Batal
                        </button>
                        <button type="submit" name="tambah_menu" 
                                class="btn-primary px-4 py-2 rounded-lg font-semibold">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Menu -->
    <div id="editModal" class="modal fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50 overflow-y-auto my-10">
            <div class="modal-content py-4 text-left px-6">
                <div class="flex justify-between items-center pb-3">
                    <p class="text-2xl font-bold text-gray-800">Edit Menu</p>
                    <button onclick="toggleModal('editModal')" class="modal-close cursor-pointer z-50">
                        <i class="fas fa-times text-gray-500 hover:text-gray-700"></i>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data" onsubmit="return validateEditForm()">
                    <input type="hidden" name="id_menu" id="edit_id_menu">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Menu *</label>
                            <input type="text" name="nama_menu" id="edit_nama_menu" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pale-taupe transition-colors"
                                   placeholder="Contoh: Nasi Goreng Spesial">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
                            <select name="id_kategori" id="edit_id_kategori" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pale-taupe transition-colors">
                                <option value="">Pilih Kategori</option>
                                <?php 
                                $kategori_result->data_seek(0);
                                while ($kategori = $kategori_result->fetch_assoc()): ?>
                                    <option value="<?php echo $kategori['id_kategori']; ?>">
                                        <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp) *</label>
                            <input type="number" name="harga" id="edit_harga" required min="1000" step="500"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pale-taupe transition-colors"
                                   placeholder="Contoh: 25000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Foto Menu Saat Ini</label>
                            <div id="current_photo_container" class="mb-2 hidden">
                                <img id="current_photo_img" src="" alt="Foto saat ini" class="w-24 h-24 object-cover rounded-lg border border-gray-300">
                            </div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ganti Foto (Opsional)</label>
                            <input type="file" name="foto" accept="image/*" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pale-taupe transition-colors text-sm">
                            <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ingin mengubah foto.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-6 space-x-3">
                        <button type="button" onclick="toggleModal('editModal')" 
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Batal
                        </button>
                        <button type="submit" name="edit_menu" 
                                class="btn-primary px-4 py-2 rounded-lg font-semibold">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
            document.body.classList.toggle('modal-active');
        }

        function openEditModal(data) {
            document.getElementById('edit_id_menu').value = data.id_menu;
            document.getElementById('edit_nama_menu').value = data.nama_menu;
            document.getElementById('edit_harga').value = data.harga;
            document.getElementById('edit_id_kategori').value = data.id_kategori;
            
            const photoContainer = document.getElementById('current_photo_container');
            const photoImg = document.getElementById('current_photo_img');
            
            if (data.foto) {
                photoImg.src = '<?= $upload_dir ?>' + data.foto;
                photoContainer.classList.remove('hidden');
            } else {
                photoContainer.classList.add('hidden');
            }
            
            toggleModal('editModal');
        }

        function hapusMenu(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus menu "' + nama + '"?\nTindakan ini tidak dapat dibatalkan dan akan menghapus foto terkait.')) {
                window.location.href = 'manajemen_menu.php?hapus=' + id;
            }
        }

        function validateAddForm() {
            const namaMenu = document.querySelector('#addModal input[name="nama_menu"]').value;
            const harga = document.querySelector('#addModal input[name="harga"]').value;
            const kategori = document.querySelector('#addModal select[name="id_kategori"]').value;
            
            if (!namaMenu || !harga || !kategori) {
                alert('Semua field wajib diisi!');
                return false;
            }
            
            if (harga < 1000) {
                alert('Harga minimal Rp 1.000');
                return false;
            }
            
            return true;
        }

        function validateEditForm() {
            const namaMenu = document.getElementById('edit_nama_menu').value;
            const harga = document.getElementById('edit_harga').value;
            const kategori = document.getElementById('edit_id_kategori').value;
            
            if (!namaMenu || !harga || !kategori) {
                alert('Semua field wajib diisi!');
                return false;
            }
            
            if (harga < 1000) {
                alert('Harga minimal Rp 1.000');
                return false;
            }
            
            return true;
        }

        document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function() {
                toggleModal(overlay.closest('.modal').id);
            });
        });

        document.onkeydown = function(evt) {
            evt = evt || window.event;
            var isEscape = false;
            if ("key" in evt) {
                isEscape = (evt.key === "Escape" || evt.key === "Esc");
            } else {
                isEscape = (evt.keyCode === 27);
            }
            if (isEscape && document.body.classList.contains('modal-active')) {
                document.querySelectorAll('.modal.flex').forEach(function(modal) {
                    toggleModal(modal.id);
                });
            }
        };

        // Auto open edit modal if edit data exists
        <?php if ($edit_data): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openEditModal(<?= json_encode($edit_data) ?>);
        });
        <?php endif; ?>
    </script>
</body>
</html>