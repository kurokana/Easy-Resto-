<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header("Location: ../login.php");
    exit();
}

$id_user_active = $_SESSION['id_user']; 

$stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user_active);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['update_profil'])) {
        $nama = $_POST['nama'];
        $username = $_POST['username'];
        $phone_number = $_POST['phone_number'] ?? '';
        
        $check = $conn->prepare("SELECT id_user FROM users WHERE username = ? AND id_user != ?");
        $check->bind_param("si", $username, $id_user_active);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama = ?, username = ?, phone_number = ? WHERE id_user = ?");
            $stmt->bind_param("sssi", $nama, $username, $phone_number, $id_user_active);
            
            if ($stmt->execute()) {
                $success = "Profil berhasil diperbarui!";
                $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
                $stmt->bind_param("i", $id_user_active);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Gagal memperbarui profil!";
            }
        }
    }

    if (isset($_POST['update_password'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi = $_POST['konfirmasi_password'];

        if (md5($password_lama) == $user['password'] || $password_lama == $user['password']) {
            if ($password_baru == $konfirmasi) {
                if (strlen($password_baru) >= 6) {
                    $hashed = md5($password_baru);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                    $stmt->bind_param("si", $hashed, $id_user_active);
                    if ($stmt->execute()) $success = "Password berhasil diubah!";
                } else {
                    $error = "Password baru minimal 6 karakter!";
                }
            } else {
                $error = "Konfirmasi password tidak cocok!";
            }
        } else {
            $error = "Password lama salah!";
        }
    }

    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profil/'; 
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_info = pathinfo($_FILES['foto_profil']['name']);
        $ext = strtolower($file_info['extension']);
        $file_name = uniqid() . '_' . time() . '.' . $ext;
        $target_file = $upload_dir . $file_name;
        $db_path = 'uploads/profil/' . $file_name; 
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
                if (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture'])) {
                    unlink('../' . $user['profile_picture']);
                }
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id_user = ?");
                $stmt->bind_param("si", $db_path, $id_user_active);
                if ($stmt->execute()) {
                    $success = "Foto berhasil diupload!";
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
                    $stmt->bind_param("i", $id_user_active);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                }
            } else {
                $error = "Gagal upload file.";
            }
        } else {
            $error = "Format file tidak didukung.";
        }
    }

    if (isset($_POST['hapus_foto'])) {
        if (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture'])) {
            unlink('../' . $user['profile_picture']);
        }
        $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id_user = ?");
        $stmt->bind_param("i", $id_user_active);
        if ($stmt->execute()) {
            $success = "Foto profil berhasil dihapus.";
            $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
            $stmt->bind_param("i", $id_user_active);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        }
    }
}

$nama_user = $user['nama'];
$foto_db = $user['profile_picture'];
$foto_display = !empty($foto_db) && file_exists('../' . $foto_db) 
    ? '../' . $foto_db 
    : 'https://ui-avatars.com/api/?name=' . urlencode($nama_user) . '&background=B7A087&color=fff';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Kasir</title>
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
        .card {
            background: white;
            border: 1px solid #E5D9C8;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
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
        .photo-container {
            transition: transform 0.3s ease;
        }
        .photo-container:hover {
            transform: scale(1.02);
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .info-item {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        @media print {
            .no-print { display: none !important; }
            .ml-64 { margin-left: 0 !important; }
        }
    </style>
</head>
<body class="bg-antique-white">

    <div class="fixed inset-y-0 left-0 w-64 sidebar shadow-xl no-print">
        <div class="flex items-center justify-center h-16 bg-pale-taupe">
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
            <a href="laporan.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                <i class="fas fa-chart-line w-6"></i>
                <span class="mx-3 font-medium">Laporan</span>
            </a>
            <a href="profil_kasir.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white">
                <i class="fas fa-user-cog w-6"></i>
                <span class="mx-3 font-medium">Profil</span>
            </a>
        </nav>
        
        <div class="absolute bottom-0 w-full p-4 bg-pale-taupe bg-opacity-80">
            <div class="flex items-center gap-3">
                <img src="<?= $foto_display ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
                <div class="overflow-hidden text-white">
                    <p class="font-bold text-sm truncate leading-tight"><?= htmlspecialchars($user['nama']) ?></p>
                    <p class="text-xs opacity-90">Role: Kasir</p>
                    <a href="../logout.php" class="text-xs text-red-200 hover:text-white flex items-center gap-1 mt-1 transition-colors">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="ml-64 no-print flex flex-col h-screen">
        
        <header class="bg-white shadow-sm border-b border-pale-taupe flex-shrink-0 px-8 py-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Pengaturan Profil</h1>
                <p class="text-gray-500 text-sm mt-1">Kelola informasi akun Anda</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm text-gray-600">Selamat datang</p>
                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($nama_user) ?></p>
                </div>
                <a href="profil_kasir.php" class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-pale-taupe cursor-pointer transition-transform hover:scale-105">
                    <img src="<?= $foto_display ?>" class="w-full h-full object-cover">
                </a>
            </div>
        </header>

        <main class="p-8 flex-1 overflow-y-auto scrollbar-hide">
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-1 h-full">
                    <div class="bg-white card p-6 h-full flex flex-col justify-center">
                        <div class="text-center mb-8">
                            <div class="relative w-36 h-36 mx-auto mb-6 group cursor-pointer photo-container" onclick="document.getElementById('fotoInput').click()">
                                <img src="<?= $foto_display ?>" class="w-full h-full rounded-full object-cover border-4 border-[#F7EBDF] shadow-lg">
                                <div class="absolute inset-0 bg-black bg-opacity-30 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <div class="text-center p-4">
                                        <i class="fas fa-camera text-white text-3xl mb-2"></i>
                                        <p class="text-white text-sm font-medium">Ganti Foto</p>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="file" name="foto_profil" id="fotoInput" class="hidden" accept="image/*" onchange="this.form.submit()">
                            </form>
                            
                            <h2 class="font-bold text-xl text-gray-800 mb-1"><?= htmlspecialchars($user['nama']) ?></h2>
                            <p class="text-gray-500 text-sm mb-1">Role: Kasir</p>
                            <p class="text-gray-400 text-xs mb-6">User ID: #<?= $user['id_user'] ?></p>
                            
                            <?php if (!empty($user['profile_picture'])): ?>
                            <form method="POST" class="mb-4">
                                <button type="submit" name="hapus_foto" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium text-sm w-full">
                                    <i class="fas fa-trash mr-2"></i>Hapus Foto
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <p class="text-xs text-gray-400">Klik foto untuk mengganti</p>
                        </div>

                        <div class="border-t border-gray-100 pt-6">
                            <h3 class="font-bold text-gray-800 mb-4 text-lg">Informasi Akun</h3>
                            <div class="space-y-1">
                                <div class="info-item">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 text-sm">Username</span>
                                        <span class="font-medium text-gray-800"><?= htmlspecialchars($user['username']) ?></span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 text-sm">Telepon</span>
                                        <span class="font-medium text-gray-800"><?= !empty($user['phone_number']) ? htmlspecialchars($user['phone_number']) : 'Belum diatur' ?></span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 text-sm">User ID</span>
                                        <span class="font-medium text-gray-800">#<?= $user['id_user'] ?></span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 text-sm">Status</span>
                                        <span class="font-medium text-green-600 flex items-center">
                                            <span class="status-dot bg-green-500"></span>Aktif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-8 h-full flex flex-col">
                    <div class="bg-white card p-6">
                        <h3 class="font-bold text-gray-800 mb-6 text-lg flex items-center">
                            <i class="fas fa-user-edit text-pale-taupe mr-2"></i> Edit Informasi Profil
                        </h3>
                        <form method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" 
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors"
                                           placeholder="Masukkan nama lengkap" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" 
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors"
                                           placeholder="Masukkan username" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">No. Telepon</label>
                                    <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number']) ?>" 
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors"
                                           placeholder="Contoh: 081234567890">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                    <input type="text" value="Kasir" 
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 text-gray-500 cursor-not-allowed" 
                                           disabled readonly>
                                </div>
                            </div>
                            <div class="mt-6 pt-5 border-t border-gray-100 flex justify-end">
                                <button type="submit" name="update_profil" class="btn-primary px-6 py-3 rounded-lg font-semibold shadow-sm">
                                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white card p-6 mt-auto">
                        <h3 class="font-bold text-gray-800 mb-6 text-lg flex items-center">
                            <i class="fas fa-key text-pale-taupe mr-2"></i> Ganti Password
                        </h3>
                        <form method="POST">
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Lama</label>
                                    <input type="password" name="password_lama" 
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors" 
                                           placeholder="Masukkan password lama" required>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                                        <input type="password" name="password_baru" 
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors" 
                                               placeholder="Minimal 6 karakter" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                                        <input type="password" name="konfirmasi_password" 
                                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors" 
                                               placeholder="Konfirmasi password baru" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 pt-5 border-t border-gray-100 flex justify-end">
                                <button type="submit" name="update_password" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-3 rounded-lg font-semibold shadow-sm transition-all">
                                    <i class="fas fa-key mr-2"></i>Ubah Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="h-8"></div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.querySelector('input[name="password_baru"]');
            const confirmInput = document.querySelector('input[name="konfirmasi_password"]');
            
            if (passwordInput && confirmInput) {
                function checkPasswordMatch() {
                    const password = passwordInput.value;
                    const confirm = confirmInput.value;
                    
                    if (confirm && password !== confirm) {
                        confirmInput.classList.add('border-red-500');
                        confirmInput.classList.remove('border-green-500');
                    } else if (confirm && password === confirm) {
                        confirmInput.classList.remove('border-red-500');
                        confirmInput.classList.add('border-green-500');
                    } else {
                        confirmInput.classList.remove('border-red-500', 'border-green-500');
                    }
                }
                
                passwordInput.addEventListener('input', checkPasswordMatch);
                confirmInput.addEventListener('input', checkPasswordMatch);
            }

            const hapusFotoBtn = document.querySelector('button[name="hapus_foto"]');
            if (hapusFotoBtn) {
                hapusFotoBtn.addEventListener('click', function(e) {
                    if (!confirm('Apakah Anda yakin ingin menghapus foto profil?\nFoto akan dihapus permanen.')) {
                        e.preventDefault();
                    }
                });
            }
            
            const logoutLink = document.querySelector('a[href="../logout.php"]');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    if (!confirm('Apakah Anda yakin ingin keluar dari sistem?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>