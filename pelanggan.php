<?php
require 'config.php';
$title = 'Pelanggan';
$q = trim($_GET['q'] ?? '');

$st = $pdo->prepare(
    'SELECT p.id_pelanggan, p.nama, p.kota, p.no_hp, p.email,
            v.jumlah_transaksi, v.total_belanja,
            KategoriBelanja(v.total_belanja) AS kategori
     FROM pelanggan p
     JOIN v_total_belanja_pelanggan v ON v.id_pelanggan = p.id_pelanggan
     WHERE p.nama LIKE :q OR p.kota LIKE :q
     ORDER BY p.id_pelanggan'
);
$st->execute([':q' => "%$q%"]);
$rows = $st->fetchAll();
$total_pelanggan = (int) $pdo->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
$pelanggan_aktif = (int) $pdo->query('SELECT COUNT(DISTINCT id_pelanggan) FROM transaksi')->fetchColumn();

include 'includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow eyebrow-dark">CUSTOMER MANAGEMENT</span><h1>Data Pelanggan</h1><p class="sub">Kelola informasi pelanggan dan lihat aktivitas belanjanya.</p></div><a class="btn heading-action" href="pelanggan_form.php">+ Tambah Pelanggan</a></div>
<div class="grid mini-stats"><div class="stat stat-purple"><div class="stat-icon">◎</div><div><div class="label">Total pelanggan</div><div class="value"><?= e($total_pelanggan) ?></div></div><span class="stat-note">semua pelanggan</span></div><div class="stat stat-green"><div class="stat-icon">↗</div><div><div class="label">Pelanggan aktif</div><div class="value"><?= e($pelanggan_aktif) ?></div></div><span class="stat-note">pernah bertransaksi</span></div><div class="stat stat-orange"><div class="stat-icon">⌕</div><div><div class="label">Hasil pencarian</div><div class="value"><?= e(count($rows)) ?></div></div><span class="stat-note"><?= $q !== '' ? 'dengan kata kunci' : 'data ditampilkan' ?></span></div></div>

<div class="card modern-table-card">
    <div class="section-heading"><div><h2>Daftar pelanggan</h2><p><?= $q !== '' ? 'Menampilkan hasil untuk “' . e($q) . '”' : 'Semua pelanggan yang terdaftar di toko' ?></p></div><span class="result-count"><?= e(count($rows)) ?> data</span></div>
    <div class="filter-bar">
        <form method="get" class="search-form">
            <span class="search-symbol">⌕</span><input type="text" name="q" placeholder="Cari nama atau kota..." value="<?= e($q) ?>">
            <button class="btn" type="submit">Cari pelanggan</button>
            <?php if ($q !== ''): ?><a class="btn sec" href="pelanggan.php">Reset</a><?php endif; ?>
        </form>
    </div>

    <?php if (!$rows): ?>
        <p class="empty">Tidak ada pelanggan ditemukan.</p>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Pelanggan</th><th>Lokasi</th><th>Kontak</th><th>Transaksi</th><th>Total Belanja</th><th>Kategori</th><th class="action-head">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><div class="person-cell"><span class="person-avatar"><?= e(strtoupper(substr($r['nama'], 0, 1))) ?></span><div><strong><?= e($r['nama']) ?></strong><small>ID #<?= e($r['id_pelanggan']) ?></small></div></div></td>
                <td><span class="muted-cell">⌖ <?= e($r['kota'] ?: 'Belum diisi') ?></span></td>
                <td><span class="muted-cell"><?= e($r['no_hp'] ?: 'Belum diisi') ?></span></td>
                <td><span class="number-pill"><?= e($r['jumlah_transaksi']) ?></span></td>
                <td class="num"><?= rupiah($r['total_belanja']) ?></td>
                <td><?= badge($r['kategori']) ?></td>
                <td><div class="actions row-actions">
                    <a class="btn sec sm" href="pelanggan_detail.php?id=<?= (int)$r['id_pelanggan'] ?>">Detail</a>
                    <a class="btn sm" href="pelanggan_form.php?id=<?= (int)$r['id_pelanggan'] ?>">Edit</a>
                    <form method="post" action="pelanggan_hapus.php" onsubmit="return confirm('Hapus pelanggan ini beserta seluruh transaksinya?')">
                        <input type="hidden" name="id" value="<?= (int)$r['id_pelanggan'] ?>">
                        <button class="btn danger sm" type="submit">Hapus</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
