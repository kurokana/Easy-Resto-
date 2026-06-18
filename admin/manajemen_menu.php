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

$upload_dir = '../uploads/menu/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_menu'])) {
    $nama_menu = $_POST['nama_menu'];
    $harga = $_POST['harga'];
    $id_kategori = $_POST['id_kategori'];
    $foto_nama = null;

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_menu'])) {
    $id_menu = $_POST['id_menu_edit'];
    $nama_menu = $_POST['nama_menu_edit'];
    $harga = $_POST['harga_edit'];
    $id_kategori = $_POST['id_kategori_edit'];
    
    $stmt_old = $conn->prepare("SELECT foto FROM menu WHERE id_menu = ?");
    $stmt_old->bind_param("i", $id_menu);
    $stmt_old->execute();
    $old_foto = $stmt_old->get_result()->fetch_assoc()['foto'];
    $foto_nama = $old_foto;

    if (isset($_FILES['foto_edit']) && $_FILES['foto_edit']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['foto_edit']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '._edit.' . $ext;
            $dest = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['foto_edit']['tmp_name'], $dest)) {
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

if (isset($_GET['hapus'])) {
    $id_menu = $_GET['hapus'];
    $stmt = $conn->prepare("SELECT foto FROM menu WHERE id_menu = ?");
    $stmt->bind_param("i", $id_menu);
    $stmt->execute();
    $menu = $stmt->get_result()->fetch_assoc();

    if ($menu) {
        if ($menu['foto'] && file_exists($upload_dir . $menu['foto'])) {
            unlink($upload_dir . $menu['foto']);
        }
        $stmt_del = $conn->prepare("DELETE FROM menu WHERE id_menu = ?");
        $stmt_del->bind_param("i", $id_menu);
        if ($stmt_del->execute()) {
            $success = "Menu berhasil dihapus!";
        } else {
            $error = "Gagal menghapus menu dari database (mungkin sedang digunakan dalam transaksi).";
        }
    }
    header("Location: manajemen_menu.php" . ($success ? "?success=".urlencode($success) : "") . ($error ? "?error=".urlencode($error) : ""));
    exit();
}

if (isset($_GET['success'])) $success = $_GET['success'];
if (isset($_GET['error'])) $error = $_GET['error'];

$kategori_result = $conn->query("SELECT * FROM kategori_menu");
$menu_result = $conn->query("SELECT m.*, k.nama_kategori FROM menu m LEFT JOIN kategori_menu k ON m.id_kategori = k.id_kategori ORDER BY m.id_menu DESC");

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
    <title>Manajemen Menu - EasyResto Admin</title>
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
        .card {
            border: 1px solid #E5D9C8;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
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
                <a href="manajemen_menu.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
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

    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <header class="bg-white shadow-sm border-b border-[#E5D9C8] flex-shrink-0 px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manajemen Menu</h1>
                    <p class="text-gray-600">Kelola daftar menu makanan dan minuman</p>
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
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-500"></i><span><?= $success ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-500"></i><span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-white card p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Daftar Menu</h3>
                        <p class="text-sm text-gray-600">Total: <?php echo $menu_result->num_rows; ?> item</p>
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
                            <?php while ($menu = $menu_result->fetch_assoc()): ?>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

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
                <form method="POST" enctype="multipart/form-data">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Menu *</label>
                            <input type="text" name="nama_menu" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-pale-taupe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
                            <select name="id_kategori" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-pale-taupe">
                                <option value="">Pilih Kategori</option>
                                <?php 
                                $kategori_result->data_seek(0);
                                while($kat = $kategori_result->fetch_assoc()): ?>
                                    <option value="<?= $kat['id_kategori'] ?>"><?= $kat['nama_kategori'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp) *</label>
                            <input type="number" name="harga" required min="0" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-pale-taupe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Menu</label>
                            <input type="file" name="foto" accept="image/*" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-pale-taupe text-sm">
                            <p class="text-xs text-gray-500 mt-1">Format: jpg, jpeg, png, webp.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-6 space-x-3">
                        <button type="button" onclick="toggleModal('addModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Batal</button>
                        <button type="submit" name="tambah_menu" class="btn-primary px-4 py-2 rounded-lg font-semibold">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_menu_edit" id="edit_id_menu">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Menu *</label>
                            <input type="text" name="nama_menu_edit" id="edit_nama_menu" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-pale-taupe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
                            <select name="id_kategori_edit" id="edit_id_kategori" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-pale-taupe">
                                <option value="">Pilih Kategori</option>
                                <?php 
                                $kategori_result->data_seek(0);
                                while($kat = $kategori_result->fetch_assoc()): ?>
                                    <option value="<?= $kat['id_kategori'] ?>"><?= $kat['nama_kategori'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp) *</label>
                            <input type="number" name="harga_edit" id="edit_harga" required min="0" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-pale-taupe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Foto Menu Saat Ini</label>
                            <div id="current_photo_container" class="mb-2 hidden">
                                <img id="current_photo_img" src="" alt="Foto saat ini" class="w-24 h-24 object-cover rounded-lg border">
                            </div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ganti Foto (Opsional)</label>
                            <input type="file" name="foto_edit" accept="image/*" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-pale-taupe text-sm">
                            <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ingin mengubah foto.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-6 space-x-3">
                        <button type="button" onclick="toggleModal('editModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Batal</button>
                        <button type="submit" name="edit_menu" class="btn-primary px-4 py-2 rounded-lg font-semibold">Update</button>
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
    </script>
</body>
</html>