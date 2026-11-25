<?php
require '../config.php';
require_cashier();
$title = 'Transaksi Kasir';
$q = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($q !== '') { $where = 'WHERE p.nama LIKE ? OR t.nama_barang LIKE ?'; $params = ["%$q%", "%$q%"]; }
$st = $pdo->prepare('SELECT t.id_transaksi, t.tanggal, p.nama AS nama_pelanggan, t.nama_barang, t.jumlah, t.total FROM transaksi t JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan ' . $where . ' ORDER BY t.tanggal DESC, t.id_transaksi DESC');
$st->execute($params);
$rows = $st->fetchAll();
include 'includes/header.php';
?>
<h1>Transaksi Penjualan</h1>
<p class="sub">Cari transaksi atau buat transaksi baru untuk pelanggan.</p>
<div class="card"><div class="toolbar"><form method="get" class="search-form"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari pelanggan atau barang..."><button class="btn" type="submit">Cari</button><?php if ($q !== ''): ?><a class="btn sec" href="transaksi.php">Reset</a><?php endif; ?></form><a class="btn" href="transaksi_form.php">+ Transaksi Baru</a></div>
<?php if (!$rows): ?><p class="empty">Tidak ada transaksi ditemukan.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>ID</th><th>Tanggal</th><th>Pelanggan</th><th>Barang</th><th>Jumlah</th><th>Total</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['id_transaksi']) ?></td><td><?= e($r['tanggal']) ?></td><td><?= e($r['nama_pelanggan']) ?></td><td><?= e($r['nama_barang']) ?></td><td><?= e($r['jumlah']) ?></td><td class="num"><?= rupiah($r['total']) ?></td><td><a class="btn sm" href="transaksi_form.php?id=<?= (int)$r['id_transaksi'] ?>">Edit</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></div>
<?php include 'includes/footer.php'; ?>
