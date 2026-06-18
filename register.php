<?php
session_start();
include 'config.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'owner') header("Location: owner/dashboard.php");
    else if ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else if ($_SESSION['role'] == 'kasir') header("Location: kasir/dashboard.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = $_POST['phone_number'];

    if (empty($nama) || empty($username) || empty($password)) {
        $error = "Semua kolom wajib diisi!";
    } else if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        $check = $conn->prepare("SELECT id_user FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $hashed_password = md5($password);
            $role = 'kasir';

            $stmt = $conn->prepare("INSERT INTO users (nama, username, password, phone_number, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $username, $hashed_password, $phone, $role);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Gagal mendaftar: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kasir - EasyResto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { 'antique-white': '#F7EBDF', 'pale-taupe': '#B7A087' } }
            }
        }
    </script>
</head>
<body class="bg-antique-white min-h-screen flex items-center justify-center py-10">
    <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-md border border-[#E5D9C8]">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-[#8B7355]">Daftar Kasir</h1>
            <p class="text-gray-500 text-sm">Buat akun baru khusus Kasir</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm"><?= $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4 text-center">
                <p class="mb-2"><?= $success; ?></p>
                <a href="login.php" class="font-bold underline">Login disini</a>
            </div>
        <?php else: ?>

        <form method="POST">
            <div class="space-y-4">
                <input type="text" name="nama" required class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-pale-taupe" placeholder="Nama Lengkap">
                <input type="text" name="phone_number" class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-pale-taupe" placeholder="Nomor HP">
                <input type="text" name="username" required class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-pale-taupe" placeholder="Username">
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-pale-taupe" placeholder="Password">
                <input type="password" name="confirm_password" required class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-pale-taupe" placeholder="Ulangi Password">
            </div>
            <button type="submit" class="w-full mt-6 bg-pale-taupe text-white font-bold py-3 rounded hover:bg-[#8B7355]">Daftar</button>
        </form>
        <?php endif; ?>
        
        <div class="mt-4 text-center text-sm">
            <a href="login.php" class="text-[#8B7355] font-bold">Kembali ke Login</a>
        </div>
    </div>
</body>
</html>