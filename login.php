<?php
session_start();
include 'config.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'owner') header("Location: owner/dashboard.php");
    else if ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else if ($_SESSION['role'] == 'kasir') header("Location: kasir/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (md5($password) == $user['password'] || $password == $user['password']) {
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] == 'owner') {
                header("Location: owner/dashboard.php");
            } elseif ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php"); 
            } elseif ($user['role'] == 'kasir') {
                header("Location: kasir/dashboard.php");
            }
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EasyResto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'antique-white': '#F7EBDF',
                        'pale-taupe': '#B7A087',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-antique-white h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-md border border-[#E5D9C8]">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#8B7355]">EasyResto</h1>
            <p class="text-gray-500">Sistem Kasir & Manajemen Restoran</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="username" required class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pale-taupe">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" required class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pale-taupe">
                </div>
            </div>
            <button type="submit" class="w-full bg-pale-taupe text-white font-bold py-2 px-4 rounded-lg hover:bg-[#8B7355] transition duration-300">
                Masuk
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-500 space-y-2">
            <p>Khusus Kasir belum punya akun?</p>
            <a href="register.php" class="text-[#8B7355] font-bold hover:underline block">Daftar sebagai Kasir</a>
        </div>
    </div>
</body>
</html>