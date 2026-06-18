<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('DB_HOST')) {
    include '../config.php';
}

$id_transaksi = $_GET['id'] ?? $id_transaksi ?? null;

if (!$id_transaksi) {
    die("ID Transaksi tidak valid.");
}

$stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_transaksi = ?");
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();

if (!$transaksi) die("Transaksi tidak ditemukan.");

$stmt_detail = $conn->prepare("SELECT d.*, m.nama_menu, m.harga FROM detail_transaksi d JOIN menu m ON d.id_menu = m.id_menu WHERE d.id_transaksi = ?");
$stmt_detail->bind_param("i", $id_transaksi);
$stmt_detail->execute();
$items = $stmt_detail->get_result();

$is_embed = isset($_GET['embed']) || isset($embed_mode);
?>

<?php if (!$is_embed): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk #<?= $transaksi['id_transaksi'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { 'pale-taupe': '#B7A087', 'dark-taupe': '#8B7355' } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap');
        body { font-family: 'Courier Prime', monospace; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center py-10" onload="window.print()">
<?php endif; ?>

    <div class="ticket bg-white w-[300px] p-5 shadow-2xl relative mb-8 text-xs leading-relaxed mx-auto font-mono text-black" style="font-family: 'Courier Prime', monospace;">
        
        <div class="text-center mb-4">
            <h2 class="text-xl font-bold uppercase tracking-widest text-gray-900">EasyResto</h2>
            <p class="text-[10px] text-gray-600 mt-1">Jl. Kuliner No. 123, Bandar Lampung</p>
            <div class="border-b-2 border-dashed border-gray-300 my-3"></div>
        </div>

        <div class="mb-3 space-y-1">
            <div class="flex justify-between">
                <span class="text-gray-500">No. Struk</span>
                <span class="font-bold text-gray-800">#<?= $transaksi['id_transaksi'] ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal</span>
                <span class="text-gray-800"><?= date('d/m/y H:i', strtotime($transaksi['tanggal'])) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Pelanggan</span>
                <span class="font-bold text-gray-800"><?= htmlspecialchars($transaksi['nama_pelanggan']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Kasir</span>
                <span class="text-gray-800"><?= htmlspecialchars($_SESSION['nama'] ?? 'Kasir') ?></span>
            </div>
        </div>
        
        <div class="border-b border-dashed border-gray-300 my-3"></div>

        <div class="space-y-2 mb-3">
            <?php 
            $items->data_seek(0); 
            while($item = $items->fetch_assoc()): 
            ?>
            <div>
                <div class="font-bold text-gray-800"><?= $item['nama_menu'] ?></div>
                <div class="flex justify-between">
                    <span class="text-gray-500"><?= $item['jumlah'] ?> x <?= number_format($item['harga'],0,',','.') ?></span>
                    <span class="text-gray-800"><?= number_format($item['subtotal'],0,',','.') ?></span>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="border-b-2 border-dashed border-gray-300 my-3"></div>

        <div class="space-y-1 mb-4">
            <div class="flex justify-between">
                <span class="text-gray-600">Subtotal</span>
                <span class="font-bold text-gray-800"><?= number_format($transaksi['subtotal'],0,',','.') ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">PPN (10%)</span>
                <span class="text-gray-800"><?= number_format($transaksi['ppn'],0,',','.') ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Service (2.5%)</span>
                <span class="text-gray-800"><?= number_format($transaksi['service'],0,',','.') ?></span>
            </div>
            
            <div class="border-t border-gray-300 my-2 pt-2 flex justify-between items-center">
                <span class="font-bold text-sm text-gray-900">TOTAL</span>
                <span class="font-bold text-lg text-gray-900">Rp <?= number_format($transaksi['total'],0,',','.') ?></span>
            </div>
        </div>

        <div class="text-center mt-6">
            <p class="text-gray-500 mb-1">Terima Kasih atas kunjungan Anda</p>
            <p class="text-[10px] text-gray-400">Wifi: makanenak123</p>
        </div>
    </div>

<?php if (!$is_embed): ?>
</body>
</html>
<?php endif; ?>