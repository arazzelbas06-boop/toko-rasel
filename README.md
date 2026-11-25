# TokoKu

Sistem Informasi Penjualan Toko berbasis PHP Native dan MySQL/MariaDB.

## Pengerjaan hari ini — 25 November 2026

### Fitur kasir

- Dashboard kasir dengan ringkasan transaksi dan penjualan.
- Kasir cepat dengan katalog produk.
- Pencarian produk berdasarkan nama atau kode.
- Filter produk berdasarkan kategori.
- Keranjang multi-produk.
- Tambah, kurangi, dan hapus barang dari keranjang.
- Validasi stok sebelum transaksi disimpan.
- Stok otomatis berkurang setelah transaksi berhasil.
- Pilihan pembayaran Tunai dan QRIS.
- QRIS otomatis tampil saat metode pembayaran QRIS dipilih.
- Transaksi baru otomatis menyimpan petugas yang sedang login.
- Daftar transaksi dashboard digabung berdasarkan pelanggan dan tanggal.

### Manajemen petugas

- Admin dapat menambah akun petugas kasir.
- Daftar admin dan seluruh petugas kasir.
- Validasi username unik dan password minimal 6 karakter.
- Admin dapat menghapus akun kasir.
- Akun admin utama tidak dapat dihapus.
- Petugas kasir hanya dapat mengakses area kasir.

### Laporan admin

- Filter tanggal dan status pesanan.
- Filter metode pembayaran.
- Filter petugas kasir.
- Periode cepat: hari ini, 7 hari terakhir, dan bulan ini.
- Ringkasan total transaksi, pendapatan, rata-rata transaksi, barang terjual, dan pelanggan aktif.
- Grafik tren penjualan harian.
- Daftar produk terlaris.
- Daftar pelanggan dengan nilai belanja terbesar.
- Ringkasan pendapatan setiap petugas.
- Ekspor laporan ke CSV.
- Cetak laporan.

## Instalasi

1. Nyalakan Apache dan MySQL melalui XAMPP.
2. Buat database bernama toko.
3. Import file database/toko.sql melalui phpMyAdmin.
4. Pastikan folder proyek berada di:

   C:\xampp\htdocs\tokoku

5. Jika konfigurasi MySQL berbeda, ubah nilai database di config.php.
6. Buka:

   http://localhost/tokoku/login.php

Kolom tambahan seperti id_petugas, metode pembayaran, bukti pembayaran, dan status pesanan dibuat otomatis oleh config.php saat aplikasi dijalankan.

## Akun demo

| Peran | Username | Password |
|---|---|---|
| Admin | admin | admin123 |
| Kasir | kasir | kasir123 |

Akun petugas tambahan dapat dibuat melalui menu Petugas setelah login sebagai admin.

## Alur transaksi kasir

1. Login menggunakan akun kasir.
2. Pilih produk dari katalog atau cari produk.
3. Atur jumlah barang di keranjang.
4. Pilih pelanggan.
5. Pilih metode pembayaran.
6. Jika memilih QRIS, arahkan pelanggan untuk memindai QRIS yang tampil.
7. Klik Bayar & Simpan Transaksi.

Transaksi otomatis dikaitkan dengan akun yang sedang login. Transaksi lama yang dibuat sebelum pencatatan petugas tersedia akan tampil sebagai Belum tercatat, karena tidak memiliki informasi kasir.

## Struktur halaman utama

- index.php — dashboard admin.
- staff.php — tambah dan daftar petugas.
- produk.php — katalog dan manajemen produk.
- pelanggan.php — manajemen pelanggan.
- transaksi.php — daftar transaksi admin.
- laporan.php — laporan dan analitik penjualan.
- kasir/index.php — dashboard dan kasir cepat.
- kasir/transaksi.php — transaksi kasir.

## Teknologi

- PHP Native
- MySQL/MariaDB
- PDO Prepared Statement
- HTML, CSS, dan JavaScript tanpa framework frontend
- XAMPP untuk lingkungan lokal
