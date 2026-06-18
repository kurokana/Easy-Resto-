<?php
session_start();
include '../config.php';

if (!isset($_SESSION['id_user'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'owner' LIMIT 1");
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc();
    
    if (!$owner) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $owner = $stmt->get_result()->fetch_assoc();
    }
    
    if (!$owner) {
        $stmt = $conn->prepare("SELECT * FROM users LIMIT 1");
        $stmt->execute();
        $owner = $stmt->get_result()->fetch_assoc();
    }
    
    $user_id = $owner['id_user'];
} else {
    $user_id = $_SESSION['id_user'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profil'])) {
        $nama = $_POST['nama'];
        $username = $_POST['username'];
        $phone_number = $_POST['phone_number'] ?? '';
        
        $check_username = $conn->prepare("SELECT id_user FROM users WHERE username = ? AND id_user != ?");
        $check_username->bind_param("si", $username, $user_id);
        $check_username->execute();
        $check_result = $check_username->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama = ?, username = ?, phone_number = ? WHERE id_user = ?");
            $stmt->bind_param("sssi", $nama, $username, $phone_number, $user_id);
            
            if ($stmt->execute()) {
                $success = "Profil berhasil diperbarui!";

                if (isset($_SESSION['nama'])) {
                    $_SESSION['nama'] = $nama;
                }
                
                $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $owner = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Gagal memperbarui profil!";
            }
        }
    }
    
    if (isset($_POST['update_password'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi_password = $_POST['konfirmasi_password'];
        
        if (md5($password_lama) == $owner['password'] || $password_lama == $owner['password']) {
            if ($password_baru == $konfirmasi_password) {
                if (strlen($password_baru) >= 6) {
                    $hashed_password = md5($password_baru);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt->execute()) {
                        $success = "Password berhasil diubah!";
                        $owner['password'] = $hashed_password;
                    } else {
                        $error = "Gagal mengubah password!";
                    }
                } else {
                    $error = "Password baru minimal 6 karakter!";
                }
            } else {
                $error = "Password baru dan konfirmasi tidak cocok!";
            }
        } else {
            $error = "Password lama salah!";
        }
    }
    
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profil/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_info = pathinfo($_FILES['foto_profil']['name']);
        $ext = strtolower($file_info['extension']);
        $file_name = uniqid() . '_' . time() . '.' . $ext;
        
        $target_file = $upload_dir . $file_name;
        $db_path = '../uploads/profil/' . $file_name;
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $allowed)) {
            if ($_FILES['foto_profil']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
                    if (!empty($owner['profile_picture']) && file_exists($owner['profile_picture'])) {
                        unlink($owner['profile_picture']);
                    }
                
                    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id_user = ?");
                    $stmt->bind_param("si", $db_path, $user_id);
                    
                    if ($stmt->execute()) {
                        $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $owner = $stmt->get_result()->fetch_assoc();
                    } else {
                        $error = "Gagal menyimpan ke database: " . $conn->error;
                    }
                } else {
                    $error = "Gagal memindahkan file upload.";
                }
            } else {
                $error = "Ukuran file terlalu besar! Maksimal 5MB.";
            }
        } else {
            $error = "Format file tidak didukung! Hanya JPG, JPEG, PNG, dan GIF.";
        }
    }
}

// 
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
    <title>Profil Saya - Owner</title>
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
    </style>
</head>
<body class="bg-antique-white h-screen flex overflow-hidden">

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
                <a href="manajemen_menu.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-utensils w-6"></i>
                    <span class="mx-3 font-medium">Manajemen Menu</span>
                </a>
                <a href="manajemen_pengguna.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-users w-6"></i>
                    <span class="mx-3 font-medium">Manajemen Pengguna</span>
                </a>
                <a href="profil.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
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
                    <p class="text-xs opacity-90"><?= ucfirst($owner['role'] ?? 'Admin') ?></p>
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
                    <h1 class="text-2xl font-bold text-gray-800">Pengaturan Profil</h1>
                    <p class="text-gray-600">Kelola informasi akun dan pengaturan</p>
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
                    <i class="fas fa-check-circle mr-3 text-green-500"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 auto-rows-fr">

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
                            
                            <h2 class="font-bold text-xl text-gray-800 mb-1"><?= htmlspecialchars($owner['nama'] ?? 'Owner') ?></h2>
                            <p class="text-gray-500 text-sm mb-1"><?= ucfirst($owner['role'] ?? 'Owner') ?></p>
                            <p class="text-gray-400 text-xs mb-6">User ID: #<?= $owner['id_user'] ?? '-' ?></p>
                            
                            <?php if (!empty($owner['profile_picture'])): ?>
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
                                        <span class="font-medium text-gray-800"><?= htmlspecialchars($owner['username'] ?? '-') ?></span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 text-sm">Telepon</span>
                                        <span class="font-medium text-gray-800"><?= !empty($owner['phone_number']) ? htmlspecialchars($owner['phone_number']) : 'Belum diatur' ?></span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 text-sm">User ID</span>
                                        <span class="font-medium text-gray-800">#<?= $owner['id_user'] ?? '-' ?></span>
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
                                    <input type="text" name="nama" value="<?= htmlspecialchars($owner['nama'] ?? '') ?>" 
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors"
                                           placeholder="Masukkan nama lengkap" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                    <input type="text" name="username" value="<?= htmlspecialchars($owner['username'] ?? '') ?>" 
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors"
                                           placeholder="Masukkan username" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">No. Telepon</label>
                                    <input type="text" name="phone_number" value="<?= htmlspecialchars($owner['phone_number'] ?? '') ?>" 
                                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-pale-taupe transition-colors"
                                           placeholder="Contoh: 081234567890">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                    <input type="text" value="<?= ucfirst($owner['role'] ?? 'Owner') ?>" 
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
            
            const logoutLink = document.querySelector('a[href="logout.php"]');
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