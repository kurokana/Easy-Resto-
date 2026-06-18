<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header("Location: ../login.php");
    exit();
}

$menus = $conn->query("SELECT * FROM menu ORDER BY id_kategori ASC, nama_menu ASC");
$kategoris = $conn->query("SELECT * FROM kategori_menu");

$id_user_active = $_SESSION['id_user'];
$query_user = $conn->query("SELECT profile_picture, nama FROM users WHERE id_user = '$id_user_active'");
$data_user = $query_user->fetch_assoc();

$nama_user = $data_user['nama'];
$foto_db = $data_user['profile_picture'];

$foto = !empty($foto_db) && file_exists('../' . $foto_db) 
    ? '../' . $foto_db 
    : 'https://ui-avatars.com/api/?name=' . urlencode($nama_user) . '&background=B7A087&color=fff';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - EasyResto</title>
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
        .btn-primary { background-color: #B7A087; color: white; }
        .btn-primary:hover { background-color: #8B7355; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        input:focus, select:focus, textarea:focus, button:focus {outline: none !important; box-shadow: none !important; border-color: #B7A087 !important;}
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
        * { -webkit-tap-highlight-color: transparent; }
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
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-white hover:bg-pale-taupe hover:bg-opacity-30 transition-colors">
                    <i class="fas fa-home w-6"></i>
                    <span class="mx-3 font-medium">Dashboard</span>
                </a>
                <a href="transaksi.php" class="flex items-center px-6 py-3 text-white bg-pale-taupe bg-opacity-40 border-l-4 border-white transition-all">
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
                <img src="<?= $foto ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
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
            <div class="flex flex-col sm:flex-row justify-between items-center px-8 py-4 gap-4">
                
                <div class="flex flex-1 gap-4 items-center w-full sm:w-auto">
                    <div class="relative flex-1 group max-w-md">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 transition-colors"></i>
                        <input type="text" id="searchMenu" placeholder="Cari menu makanan..." autocomplete="off"
                               class="w-full pl-12 pr-4 py-2 rounded-lg border border-[#E5D9C8] appearance-none focus:outline-none bg-white text-gray-700 focus:border-pale-taupe transition-colors">
                    </div>
                    
                    <div class="relative w-48 hidden sm:block">
                        <i class="fas fa-filter absolute left-3 top-3.5 text-gray-400 z-10"></i>
                        <select id="filterKategori" class="w-full pl-10 pr-8 py-2 rounded-lg border border-[#E5D9C8] bg-white cursor-pointer text-gray-700 font-medium appearance-none focus:border-pale-taupe transition-colors">
                            <option value="all">Semua Kategori</option>
                            <?php while($kat = $kategoris->fetch_assoc()): ?>
                                <option value="<?= $kat['id_kategori'] ?>"><?= $kat['nama_kategori'] ?></option>
                            <?php endwhile; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-4 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div class="flex items-center space-x-4 flex-shrink-0">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm text-gray-600">Selamat datang</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($nama_user) ?></p>
                    </div>
                    <a href="profil_kasir.php" class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden border-2 border-pale-taupe cursor-pointer transition-transform hover:scale-105">
                        <img src="<?= $foto ?>" class="w-full h-full object-cover">
                    </a>
                </div>

            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scrollbar-hide">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php while($menu = $menus->fetch_assoc()): 
                    $gambar_path = '../uploads/menu/' . $menu['foto'];
                    $has_image = !empty($menu['foto']) && file_exists($gambar_path);
                    $display_img = $has_image ? $gambar_path : '';
                ?>
                <div class="card rounded-xl p-0 overflow-hidden cursor-pointer group menu-item select-none transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl"
                     onclick="addToCart(this)"
                     data-id="<?= $menu['id_menu'] ?>"
                     data-name="<?= htmlspecialchars($menu['nama_menu'], ENT_QUOTES) ?>"
                     data-price="<?= $menu['harga'] ?>"
                     data-category="<?= $menu['id_kategori'] ?>">
                    
                    <div class="h-32 bg-gradient-to-br from-antique-white to-orange-100 flex items-center justify-center relative overflow-hidden">
                        <?php if($has_image): ?>
                            <img src="<?= $display_img ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <?php else: ?>
                            <i class="fas fa-utensils text-4xl text-pale-taupe/50 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500"></i>
                        <?php endif; ?>

                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300"></div>
                        <div class="absolute bottom-2 right-2 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 z-10">
                            <i class="fas fa-plus text-pale-taupe"></i>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <h3 class="font-bold text-gray-800 text-sm h-10 line-clamp-2 leading-tight group-hover:text-pale-taupe transition-colors"><?= $menu['nama_menu'] ?></h3>
                        <div class="mt-2 flex justify-between items-end">
                            <span class="text-pale-taupe font-extrabold text-lg">Rp <?= number_format($menu['harga'],0,',','.') ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-96 bg-white shadow-2xl flex flex-col h-full z-10 border-l border-[#E5D9C8] flex-shrink-0">
        <div class="p-6 border-b border-[#E5D9C8] bg-white flex-shrink-0">
            <h2 class="font-bold text-xl text-gray-800 flex items-center gap-2 mb-4">
                <i class="fas fa-shopping-basket text-pale-taupe"></i> Pesanan
            </h2>
            <div class="bg-white p-1 rounded-lg border border-[#E5D9C8] shadow-sm">
                <div class="flex items-center px-3">
                    <i class="fas fa-user text-gray-400 mr-2 text-sm"></i>
                    <input type="text" id="namaPelanggan" placeholder="Nama Pelanggan (Wajib)" autocomplete="off"
                           class="w-full py-2 bg-transparent border-none focus:outline-none focus:ring-0 text-gray-800 font-semibold placeholder-gray-400 text-sm">
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3 min-h-0 bg-[#F9F9F9]" id="cartContainer">
            <div id="emptyCart" class="h-full flex flex-col items-center justify-center text-gray-400 opacity-60">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-receipt text-3xl text-gray-300"></i>
                </div>
                <p class="text-center text-sm font-medium">Keranjang kosong<br><span class="text-xs font-normal">Pilih menu untuk memulai pesanan</span></p>
            </div>
        </div>

        <div class="p-6 bg-white border-t border-[#E5D9C8] shadow-[0_-10px_40px_rgba(0,0,0,0.03)] flex-shrink-0 z-20">
            <div class="space-y-3 mb-6 text-sm">
                <div class="flex justify-between text-gray-500">
                    <span>Subtotal</span><span class="font-medium text-gray-900" id="subtotalDisplay">Rp 0</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>PPN (10%)</span><span class="font-medium text-gray-900" id="ppnDisplay">Rp 0</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>Service (2.5%)</span><span class="font-medium text-gray-900" id="serviceDisplay">Rp 0</span>
                </div>
                <div class="flex justify-between items-center pt-4 border-t border-dashed border-gray-200">
                    <span class="font-bold text-gray-800 text-lg">Total</span>
                    <span class="font-extrabold text-2xl text-pale-taupe" id="totalDisplay">Rp 0</span>
                </div>
            </div>
            
            <button type="button" onclick="processCheckout()" id="btnBayar"
                    class="w-full btn-primary py-4 rounded-xl font-bold shadow-lg transition-all transform active:scale-[0.98] flex items-center justify-center gap-2 group">
                <span id="btnText">Bayar Sekarang</span>
                <i class="fas fa-check-circle group-hover:animate-pulse"></i>
            </button>
        </div>
    </div>

    <div id="receiptModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50" onclick="closeModal()"></div>
        
        <div class="modal-container bg-white w-11/12 md:w-96 mx-auto rounded-xl shadow-2xl z-50 overflow-y-auto max-h-[90vh] flex flex-col animate-bounce-in">
            <div class="bg-pale-taupe text-white py-3 px-4 flex justify-between items-center rounded-t-xl">
                <h3 class="font-bold text-lg"><i class="fas fa-check-circle mr-2"></i>Transaksi Sukses</h3>
                <div class="cursor-pointer z-50" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </div>
            </div>

            <div class="p-6 flex-1 bg-gray-50" id="receiptContent">
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
        let cart = [];
        window.addEventListener('DOMContentLoaded', () => {
            const inputNama = document.getElementById('namaPelanggan');
            if (inputNama) inputNama.focus();
        });

        document.getElementById('namaPelanggan').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('searchMenu').focus();
            }
        });

        function addToCart(element) {
            const id = String(element.dataset.id); 
            const name = element.dataset.name;
            const price = parseInt(element.dataset.price);
            const category = element.dataset.category;
            const existingItem = cart.find(item => item.id === id);
            if (existingItem) { existingItem.qty++; } else {
                cart.push({ id: id, name: name, price: price, qty: 1, category: category });
            }
            renderCart();
        }

        function updateQty(id, change) {
            const idStr = String(id);
            const item = cart.find(item => item.id === idStr);
            if (item) {
                item.qty += change;
                if (item.qty <= 0) cart = cart.filter(i => i.id !== idStr);
                renderCart();
            }
        }

        function renderCart() {
            const container = document.getElementById('cartContainer');
            const emptyMsg = document.getElementById('emptyCart');
            container.innerHTML = '';
            if (cart.length === 0) {
                if (emptyMsg) { container.appendChild(emptyMsg); emptyMsg.style.display = 'flex'; }
                updateTotals(0); return;
            }
            if (emptyMsg) emptyMsg.style.display = 'none';
            let subtotal = 0;
            cart.forEach(item => {
                subtotal += (item.price * item.qty);
                const div = document.createElement('div');
                div.className = 'card flex items-center justify-between p-3 rounded-xl shadow-sm mb-2 flex-shrink-0 group';
                div.innerHTML = `
                    <div class="flex-1 overflow-hidden mr-2">
                        <h4 class="font-bold text-gray-800 text-sm truncate" title="${item.name}">${item.name}</h4>
                        <div class="text-[11px] text-gray-500 mt-0.5 font-mono">@ Rp ${item.price.toLocaleString('id-ID')}</div>
                    </div>
                    <div class="flex items-center bg-gray-50 rounded-lg p-1 border border-gray-200 flex-shrink-0">
                        <button type="button" onclick="updateQty('${item.id}', -1)" class="w-6 h-6 flex items-center justify-center text-red-500 hover:bg-white hover:shadow-sm rounded transition-all cursor-pointer"><i class="fas fa-minus text-[10px]"></i></button>
                        <span class="w-8 text-center font-bold text-sm text-gray-700 select-none">${item.qty}</span>
                        <button type="button" onclick="updateQty('${item.id}', 1)" class="w-6 h-6 flex items-center justify-center text-green-600 hover:bg-white hover:shadow-sm rounded transition-all cursor-pointer"><i class="fas fa-plus text-[10px]"></i></button>
                    </div>`;
                container.appendChild(div);
            });
            updateTotals(subtotal);
        }

        function updateTotals(subtotal) {
            const ppn = Math.round(subtotal * 0.10);
            const service = Math.round(subtotal * 0.025);
            const total = subtotal + ppn + service;
            const fmt = (num) => 'Rp ' + num.toLocaleString('id-ID');
            document.getElementById('subtotalDisplay').innerText = fmt(subtotal);
            document.getElementById('ppnDisplay').innerText = fmt(ppn);
            document.getElementById('serviceDisplay').innerText = fmt(service);
            document.getElementById('totalDisplay').innerText = fmt(total);
        }

        function processCheckout() {
            const nama = document.getElementById('namaPelanggan').value.trim();
            const btn = document.getElementById('btnBayar');
            const btnText = document.getElementById('btnText');

            if (!nama) {
                alert('MOHON ISI NAMA PELANGGAN DULU!');
                const inputEl = document.getElementById('namaPelanggan');
                inputEl.focus(); inputEl.style.borderColor = 'red';
                setTimeout(() => inputEl.style.borderColor = '', 2000);
                return;
            }
            if (cart.length === 0) { alert('Keranjang masih kosong!'); return; }
            if (!confirm(`Proses transaksi untuk pelanggan: ${nama}?`)) return;

            btn.disabled = true;
            btnText.innerText = "Memproses...";
            
            const formData = new FormData();
            formData.append('cart_data', JSON.stringify(cart));
            formData.append('nama_pelanggan', nama);

            fetch('proses_transaksi.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) 
            .then(html => {
                const modal = document.getElementById('receiptModal');
                const content = document.getElementById('receiptContent');
                content.innerHTML = html; 
                modal.classList.remove('opacity-0', 'pointer-events-none');
                modal.classList.add('opacity-100', 'pointer-events-auto');
                document.body.classList.add('modal-active');
                cart = [];
                renderCart();
                document.getElementById('namaPelanggan').value = '';
                btn.disabled = false;
                btnText.innerText = "Bayar Sekarang";
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses transaksi.');
                btn.disabled = false;
                btnText.innerText = "Bayar Sekarang";
            });
        }

        function closeModal() {
            const modal = document.getElementById('receiptModal');
            modal.classList.remove('opacity-100', 'pointer-events-auto');
            modal.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('modal-active');
        }
        const searchInput = document.getElementById('searchMenu');
        const filterSelect = document.getElementById('filterKategori');
        function filterMenu() {
            const term = searchInput.value.toLowerCase();
            const catId = filterSelect.value;
            document.querySelectorAll('.menu-item').forEach(item => {
                const name = item.dataset.name.toLowerCase();
                const cat = item.dataset.category;
                const matchName = name.includes(term);
                const matchCat = catId === 'all' || cat === catId;
                item.style.display = (matchName && matchCat) ? 'block' : 'none';
            });
        }
        searchInput.addEventListener('input', filterMenu);
        filterSelect.addEventListener('change', filterMenu);
    </script>
</body>
</html>