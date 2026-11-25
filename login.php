<?php
require 'config.php';

if (is_authenticated()) go(admin_role() === 'kasir' ? 'kasir/index.php' : 'index.php');
if (is_customer()) go('belanja.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, username, password_hash, nama, role FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'nama' => $admin['nama'],
            'role' => $admin['role'],
        ];
        $next = basename($_GET['next'] ?? $_POST['next'] ?? 'index.php');
        if ($admin['role'] === 'kasir') go('kasir/index.php');
        $allowed = ['index.php', 'pelanggan.php', 'transaksi.php', 'laporan.php', 'staff.php', 'log.php'];
        go(in_array($next, $allowed, true) ? $next : 'index.php');
    }
    $customerStmt = $pdo->prepare('SELECT cu.id, cu.id_pelanggan, cu.username, cu.password_hash, p.nama FROM customer_users cu JOIN pelanggan p ON p.id_pelanggan = cu.id_pelanggan WHERE cu.username = ? LIMIT 1');
    $customerStmt->execute([$username]);
    $customer = $customerStmt->fetch();
    if ($customer && password_verify($password, $customer['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['customer'] = ['id' => $customer['id'], 'id_pelanggan' => $customer['id_pelanggan'], 'username' => $customer['username'], 'nama' => $customer['nama']];
        go('belanja.php');
    }
    $error = 'Username atau password tidak sesuai.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - TokoKu</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= (int) @filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-intro">
            <div class="brand brand-large">Toko<span>Ku</span></div>
            <p class="eyebrow">TOKOKU CONSOLE</p>
            <h1>Kelola toko dengan lebih sederhana.</h1>
            <p class="login-copy">Pantau pelanggan, transaksi, dan laporan penjualan dari satu ruang kerja yang rapi.</p>
            <div class="login-points"><span>✓ Data tersimpan aman</span><span>✓ Ringkasan real-time</span></div>
        </section>
        <section class="login-card">
            <div class="login-card-heading"><span class="login-icon">↗</span><div><p class="eyebrow">SELAMAT DATANG</p><h2>Masuk ke akun Anda</h2></div></div>
            <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
            <form method="post" autocomplete="on">
                <div class="form-row"><label for="username">Username</label><input id="username" type="text" name="username" placeholder="Masukkan username" autocomplete="username" required autofocus></div>
                <div class="form-row"><label for="password">Password</label><input id="password" type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required></div>
                <button class="btn login-submit" type="submit">Masuk ke dashboard <span>→</span></button>
            </form>
            <p class="login-hint">Admin: <strong>admin</strong> / <strong>admin123</strong><br>Kasir: <strong>kasir</strong> / <strong>kasir123</strong><br>Pelanggan: <strong>pelanggan</strong> / <strong>pelanggan123</strong></p>
        </section>
    </main>
</body>
</html>
