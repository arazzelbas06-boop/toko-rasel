<?php
require_admin();
$cur = basename($_SERVER['PHP_SELF']);
function nav($file, $label, $cur, $also = []) {
    $active = ($cur === $file || in_array($cur, $also, true)) ? 'active' : '';
    echo '<a class="' . $active . '" href="' . $file . '">' . $label . '</a>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Toko') ?> - Sistem Informasi Penjualan Toko</title>
<link rel="stylesheet" href="assets/style.css?v=<?= (int) @filemtime(__DIR__ . '/../assets/style.css') ?>">
</head>
<body class="admin-page">
<header class="topbar">
    <div class="brand-wrap"><div class="brand">Toko<span>Ku</span></div><span class="brand-caption">ADMIN CONSOLE</span></div>
    <nav>
        <?php
        nav('index.php', 'Dashboard', $cur);
        nav('pelanggan.php', 'Pelanggan', $cur, ['pelanggan_form.php', 'pelanggan_detail.php']);
        nav('produk.php', 'Produk', $cur, ['produk_form.php', 'produk_detail.php']);
        nav('transaksi.php', 'Transaksi', $cur, ['transaksi_form.php']);
        nav('laporan.php', 'Laporan', $cur);
        nav('staff.php', 'Petugas', $cur);
        nav('log.php', 'Log Aktivitas', $cur);
        ?>
    </nav>
    <div class="account"><span class="avatar"><?= e(strtoupper(substr(admin_name(), 0, 1))) ?></span><span class="account-name"><?= e(admin_name()) ?></span><a class="logout" href="logout.php">Keluar</a></div>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): [$fm, $ft] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert <?= $ft === 'err' ? 'err' : 'ok' ?>"><?= e($fm) ?></div>
<?php endif; ?>
