# 🍽️ EasyResto — Sistem Kasir & Manajemen Restoran

EasyResto adalah aplikasi web berbasis PHP untuk manajemen restoran yang mencakup sistem kasir, pengelolaan menu, laporan penjualan, dan manajemen pengguna. Dibangun dengan PHP native, MySQL, Tailwind CSS, dan Chart.js.

---

## ✨ Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| 🔐 Multi-Role Login | Tiga level akses: **Owner**, **Admin**, dan **Kasir** |
| 🛒 Transaksi Kasir | Input pesanan, hitung total otomatis, cetak struk |
| 🍜 Manajemen Menu | Tambah, edit, hapus menu beserta foto dan kategori |
| 👥 Manajemen Pengguna | Admin dapat mengelola akun kasir |
| 📊 Laporan Penjualan | Rekap penjualan harian/bulanan dengan filter tanggal |
| 🖨️ Cetak Struk | Struk transaksi siap cetak langsung dari browser |
| 👤 Profil Pengguna | Upload foto profil untuk setiap pengguna |

---

## 🛠️ Teknologi yang Digunakan

- **Backend:** PHP (native, tanpa framework)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, [Tailwind CSS](https://tailwindcss.com/) (via CDN), [Font Awesome 6](https://fontawesome.com/)
- **Chart:** [Chart.js](https://www.chartjs.org/) (untuk dashboard grafik penjualan)
- **Session:** PHP Session untuk autentikasi
- **Timezone:** Asia/Jakarta (WIB, UTC+7)

---

## 📁 Struktur Direktori

```
Easy-Resto-main/
│
├── config.php              # Konfigurasi koneksi database & session
├── login.php               # Halaman login (semua role)
├── logout.php              # Proses logout
├── register.php            # Registrasi akun kasir baru
│
├── admin/                  # Panel Admin
│   ├── dashboard.php           # Dashboard & statistik
│   ├── manajemen_menu.php      # Kelola menu makanan/minuman
│   ├── manajemen_pengguna.php  # Kelola akun pengguna
│   ├── manajemen_transaksi.php # Lihat semua transaksi
│   ├── laporan_penjualan.php   # Laporan penjualan
│   └── profil.php              # Profil admin
│
├── kasir/                  # Panel Kasir
│   ├── dashboard.php           # Dashboard kasir
│   ├── transaksi.php           # Input transaksi baru
│   ├── proses_transaksi.php    # Proses & simpan transaksi
│   ├── cetak_struk.php         # Cetak struk transaksi
│   ├── riwayat.php             # Riwayat transaksi kasir
│   ├── laporan.php             # Laporan kasir
│   └── profil_kasir.php        # Profil kasir
│
├── owner/                  # Panel Owner
│   ├── dashboard.php           # Dashboard owner
│   ├── manajemen_menu.php      # Kelola menu
│   ├── manajemen_pengguna.php  # Kelola pengguna
│   ├── laporan_penjualan.php   # Laporan penjualan lengkap
│   └── profil.php              # Profil owner
│
└── uploads/
    ├── menu/               # Foto menu (auto-created)
    └── profil/             # Foto profil pengguna (auto-created)
```

---

## ⚙️ Cara Instalasi & Menjalankan

### Prasyarat

Pastikan komputer kamu sudah terinstal:
- **PHP** versi 7.4 atau lebih baru
- **MySQL** atau **MariaDB**
- **Web server lokal** (disarankan XAMPP atau Laragon)

---

### Langkah 1 — Download & Letakkan Project

1. Download atau clone repository ini.
2. Ekstrak folder `Easy-Resto-main` ke dalam direktori htdocs web server kamu:
   - **XAMPP:** `C:/xampp/htdocs/Easy-Resto`
   - **Laragon:** `C:/laragon/www/Easy-Resto`

---

### Langkah 2 — Buat Database

1. Buka **phpMyAdmin** (biasanya di `http://localhost/phpmyadmin`)
2. Buat database baru dengan nama:
   ```
   easyresto
   ```
3. Import file SQL schema (jika tersedia), atau buat tabel secara manual sesuai struktur berikut:

**Tabel yang dibutuhkan:**
- `users` — data pengguna (id_user, nama, username, password, role, profile_picture)
- `menu` — data menu (id_menu, nama_menu, harga, id_kategori, gambar, status)
- `kategori_menu` — kategori menu (id_kategori, nama_kategori)
- `transaksi` — header transaksi (id_transaksi, id_user, total, tanggal, dll.)
- `detail_transaksi` — detail item per transaksi (id_detail, id_transaksi, id_menu, jumlah, subtotal)
- `laporan_penjualan` — view/tabel laporan gabungan

---

### Langkah 3 — Konfigurasi Koneksi Database

Buka file `config.php` dan sesuaikan dengan pengaturan lokal kamu:

```php
$host     = "localhost";   // Host database
$username = "root";        // Username MySQL (default XAMPP: root)
$password = "";            // Password MySQL (default XAMPP: kosong)
$database = "easyresto";   // Nama database
```

---

### Langkah 4 — Jalankan Aplikasi

1. Pastikan **Apache** dan **MySQL** sudah berjalan di XAMPP/Laragon.
2. Buka browser dan akses:
   ```
   http://localhost/Easy-Resto/login.php
   ```

---

## 🔑 Akun Default & Role

Buat akun awal langsung lewat database (tabel `users`). Password disimpan dalam format **MD5**.

| Role | Akses |
|------|-------|
| `owner` | Dashboard owner, semua laporan, manajemen menu & pengguna |
| `admin` | Manajemen menu, pengguna, transaksi, laporan |
| `kasir` | Input transaksi, cetak struk, riwayat, laporan kasir |

**Contoh insert akun owner via SQL:**
```sql
INSERT INTO users (nama, username, password, role)
VALUES ('Owner Utama', 'owner', MD5('password123'), 'owner');
```

> Kasir baru juga bisa mendaftar sendiri lewat halaman `register.php` dan menunggu aktivasi dari admin.

---

## 📸 Alur Penggunaan

```
Login → Pilih Role
         │
         ├── Owner    → Lihat laporan & statistik penjualan
         ├── Admin    → Kelola menu, pengguna, & transaksi
         └── Kasir    → Input pesanan → Proses transaksi → Cetak struk
```

---

## 🔒 Keamanan (Catatan Pengembang)

- Password saat ini menggunakan **MD5** — disarankan diganti dengan `password_hash()` / `password_verify()` untuk produksi.
- Pastikan direktori `uploads/` **tidak dapat diakses langsung** dari browser di lingkungan produksi.
- Tambahkan validasi input lebih ketat untuk mencegah SQL Injection (beberapa query sudah menggunakan `prepared statement`).

---

## 🤝 Kontribusi

Pull request sangat terbuka. Untuk perubahan besar, buka Issue terlebih dahulu untuk mendiskusikan apa yang ingin diubah.

---

## 📄 Lisensi

Proyek ini dibuat untuk keperluan pembelajaran. Silakan digunakan dan dimodifikasi sesuai kebutuhan.
