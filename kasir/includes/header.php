<?php
require_cashier();
$cur = basename($_SERVER['PHP_SELF']);
function kasir_nav($file, $label, $cur) {
    $active = $cur === $file ? 'active' : '';
    echo '<a class="' . $active . '" href="' . $file . '">' . $label . '</a>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Kasir') ?> - TokoKu</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?= (int) @filemtime(__DIR__ . '/../../assets/style.css') ?>">
</head>
<body class="cashier-page">
<header class="topbar cashier-bar">
    <div class="brand-wrap"><div class="brand">Toko<span>Ku</span></div><span class="brand-caption">CASHIER DESK</span></div>
    <nav>
        <?php kasir_nav('index.php', 'Dashboard', $cur); ?>
        <?php kasir_nav('transaksi.php', 'Transaksi', $cur); ?>
    </nav>
    <div class="account"><span class="avatar">K</span><span class="account-name"><?= e(admin_name()) ?></span><a class="logout" href="../logout.php">Keluar</a></div>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): [$fm, $ft] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert <?= $ft === 'err' ? 'err' : 'ok' ?>"><?= e($fm) ?></div>
<?php endif; ?>
