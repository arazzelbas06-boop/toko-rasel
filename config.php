<?php
// ===== Konfigurasi database (sesuaikan jika perlu) =====
$DB_HOST = '127.0.0.1';
$DB_NAME = 'toko';
$DB_USER = 'root';
$DB_PASS = '';

session_start();

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]
    );

    // Akun admin disiapkan otomatis agar fitur login bisa langsung digunakan.
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        nama VARCHAR(100) NOT NULL DEFAULT 'Administrator',
        role ENUM('admin','kasir') NOT NULL DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migrasi ringan untuk database lama yang sudah memiliki admin_users.
    $roleColumn = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'role'")->fetch();
    if (!$roleColumn) $pdo->exec("ALTER TABLE admin_users ADD role ENUM('admin','kasir') NOT NULL DEFAULT 'admin' AFTER nama");

    if ((int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 0) {
        $seed = $pdo->prepare('INSERT INTO admin_users (username, password_hash, nama, role) VALUES (?, ?, ?, ?)');
        $seed->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator', 'admin']);
    }
    $pdo->exec("UPDATE admin_users SET role = 'admin' WHERE username = 'admin' AND (role IS NULL OR role = '')");
    $cashier = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ?');
    $cashier->execute(['kasir']);
    if ((int) $cashier->fetchColumn() === 0) {
        $seed = $pdo->prepare('INSERT INTO admin_users (username, password_hash, nama, role) VALUES (?, ?, ?, ?)');
        $seed->execute(['kasir', password_hash('kasir123', PASSWORD_DEFAULT), 'Kasir Toko', 'kasir']);
    }

    // Katalog produk dipisahkan dari tabel transaksi agar stok, kategori, kode, dan gambar dapat dikelola.
    $pdo->exec("CREATE TABLE IF NOT EXISTS produk (
        id_produk INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        kode_produk VARCHAR(30) NOT NULL UNIQUE,
        nama_produk VARCHAR(100) NOT NULL,
        kategori VARCHAR(60) NOT NULL DEFAULT 'Umum',
        harga_beli DECIMAL(12,2) NOT NULL DEFAULT 0,
        harga_jual DECIMAL(12,2) NOT NULL DEFAULT 0,
        stok INT NOT NULL DEFAULT 0,
        gambar VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_produk_nama (nama_produk),
        INDEX idx_produk_kategori (kategori)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $buyPriceColumn = $pdo->query("SHOW COLUMNS FROM produk LIKE 'harga_beli'")->fetch();
    if (!$buyPriceColumn) $pdo->exec("ALTER TABLE produk ADD harga_beli DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER kategori");
    if ((int)$pdo->query('SELECT COUNT(*) FROM produk')->fetchColumn() === 0) {
        $seedProducts = [['PRD001','Indomie Goreng','Makanan',2500,3500,100],['PRD002','Aqua 600ml','Minuman',2500,4000,80],['PRD003','Teh Pucuk','Minuman',3000,4500,60],['PRD004','Roti Coklat','Makanan',4000,6000,40],['PRD005','Kopi Good Day','Minuman',1500,2500,75],['PRD006','Sabun Lifebuoy','Kebutuhan',3500,5000,50],['PRD007','Chitato','Snack',8000,11000,35],['PRD008','Susu Ultra Milk','Minuman',4500,6000,45]];
        $seedProduct = $pdo->prepare('INSERT IGNORE INTO produk (kode_produk, nama_produk, kategori, harga_beli, harga_jual, stok) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($seedProducts as $product) $seedProduct->execute($product);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_pelanggan INT NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_customer_user (id_pelanggan)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if ((int)$pdo->query('SELECT COUNT(*) FROM customer_users')->fetchColumn() === 0) {
        $firstCustomer = $pdo->query('SELECT id_pelanggan FROM pelanggan ORDER BY id_pelanggan LIMIT 1')->fetchColumn();
        if ($firstCustomer) {
            $customerSeed = $pdo->prepare('INSERT INTO customer_users (id_pelanggan, username, password_hash) VALUES (?, ?, ?)');
            $customerSeed->execute([$firstCustomer, 'pelanggan', password_hash('pelanggan123', PASSWORD_DEFAULT)]);
        }
    }

    // Kolom pembayaran dibuat otomatis untuk mendukung QRIS dan bukti transfer.
    foreach ([
        'id_petugas' => "ALTER TABLE transaksi ADD id_petugas INT UNSIGNED NULL AFTER id_pelanggan",
        'metode_pembayaran' => "ALTER TABLE transaksi ADD metode_pembayaran ENUM('tunai','qris') NOT NULL DEFAULT 'tunai' AFTER total",
        'bukti_pembayaran' => "ALTER TABLE transaksi ADD bukti_pembayaran VARCHAR(255) DEFAULT NULL AFTER metode_pembayaran",
        'status_pesanan' => "ALTER TABLE transaksi ADD status_pesanan ENUM('pending','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'selesai' AFTER total",
    ] as $column => $alter) {
        if (!$pdo->query("SHOW COLUMNS FROM transaksi LIKE '" . $column . "'")->fetch()) $pdo->exec($alter);
    }
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()) .
        '<br>Pastikan MySQL menyala dan database "toko" sudah diimpor.');
}

// ===== Fungsi bantu =====
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function rupiah($n) { return 'Rp' . number_format((float)$n, 0, ',', '.'); }

function save_payment_proof($file, $method) {
    if ($method !== 'qris') return null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Bukti pembayaran QRIS wajib diunggah.');
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Ukuran bukti pembayaran maksimal 5 MB.');
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = function_exists('finfo_open') ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) throw new RuntimeException('Bukti pembayaran harus berupa gambar JPG, PNG, atau WEBP.');
    $dir = __DIR__ . '/uploads/bukti_pembayaran/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Folder bukti pembayaran tidak dapat dibuat.');
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) throw new RuntimeException('Bukti pembayaran gagal disimpan.');
    return 'uploads/bukti_pembayaran/' . $name;
}

function flash($msg, $type = 'ok') { $_SESSION['flash'] = [$msg, $type]; }

function go($url) { header("Location: $url"); exit; }

function is_authenticated() { return !empty($_SESSION['admin']); }
function is_customer() { return !empty($_SESSION['customer']); }

function admin_role() { return $_SESSION['admin']['role'] ?? 'admin'; }

function is_admin() { return is_authenticated() && admin_role() === 'admin'; }

function require_admin() {
    if (is_admin()) return;
    $next = basename($_SERVER['PHP_SELF']);
    go('login.php?next=' . rawurlencode($next));
}

function require_auth() {
    if (is_authenticated()) return;
    $next = basename($_SERVER['PHP_SELF']);
    $login = strpos(str_replace('\\', '/', $_SERVER['PHP_SELF']), '/kasir/') !== false ? '../login.php' : 'login.php';
    go($login . '?next=' . rawurlencode($next));
}

function require_customer() {
    if (is_customer()) return;
    go('login.php?next=belanja.php');
}

function require_cashier() {
    if (is_authenticated() && in_array(admin_role(), ['admin', 'kasir'], true)) return;
    go('../login.php?next=kasir%2Findex.php');
}

function admin_name() { return $_SESSION['admin']['nama'] ?? 'Administrator'; }

// Semua halaman aplikasi (kecuali login/logout) wajib melewati autentikasi.
$public_pages = ['login.php', 'logout.php', 'belanja.php'];
if (!in_array(basename($_SERVER['PHP_SELF']), $public_pages, true)) require_auth();

// Menampilkan tabel. $cols = ['kolom' => 'Label'], $money = kolom berformat rupiah
function tbl(array $rows, array $cols, array $money = []) {
    if (!$rows) { echo '<p class="empty">Tidak ada data.</p>'; return; }
    echo '<div class="table-wrap"><table><thead><tr>';
    foreach ($cols as $label) echo '<th>' . e($label) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($cols as $key => $label) {
            $v = $r[$key] ?? '';
            if (in_array($key, $money, true)) echo '<td class="num">' . rupiah($v) . '</td>';
            else echo '<td>' . e($v) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function badge($kategori) {
    $cls = ['Pelanggan Gold' => 'gold', 'Pelanggan Silver' => 'silver'][$kategori] ?? 'reguler';
    return '<span class="badge ' . $cls . '">' . e($kategori) . '</span>';
}
