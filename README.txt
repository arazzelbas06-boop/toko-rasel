SISTEM INFORMASI PENJUALAN TOKO (PHP Native + MySQL/MariaDB)

CARA PASANG (XAMPP)
1. Nyalakan Apache dan MySQL di XAMPP.
2. Import database: phpMyAdmin > Import > pilih file database/toko.sql.
   Database "toko" harus sudah berisi tabel, view, procedure, function, dan trigger.
3. Salin folder toko-web ke C:\xampp\htdocs\
4. Buka http://localhost/toko-web/
5. Jika user/password MySQL berbeda, ubah di config.php.

LOGIN ADMIN
- Buka http://localhost/tokoku/login.php
- Username awal: admin
- Password awal: admin123
- Tabel admin_users dan akun awal dibuat otomatis saat aplikasi pertama kali dibuka.

HALAMAN
- Dashboard       : ringkasan (procedure HitungPelanggan, function HitungPajak, view)
- Pelanggan       : tambah, edit, hapus, cari, detail (procedure CariPelanggan & LaporanTransaksi)
- Transaksi       : tambah, edit, hapus, filter (function HitungTotal, trigger validasi)
- Laporan         : view, JOIN, subquery, nested query
- Log Aktivitas   : isi log yang dicatat trigger

AREA KASIR
- Folder kasir/ memiliki dashboard kasir, pencarian transaksi, dan form transaksi.
- Login kasir: username kasir, password kasir123.
- Login admin tetap menggunakan username admin, password admin123.
