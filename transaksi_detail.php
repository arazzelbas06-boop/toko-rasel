<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT t.*, p.nama AS nama_pelanggan, p.kota FROM transaksi t JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan WHERE t.id_transaksi = ?');
$st->execute([$id]);
$transaction = $st->fetch();
if (!$transaction) { flash('Transaksi tidak ditemukan.', 'err'); go('transaksi.php'); }
$title = 'Detail Transaksi';
include 'includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow eyebrow-dark">TRANSACTION DETAIL</span><h1>Detail Transaksi #<?= e($transaction['id_transaksi']) ?></h1><p class="sub">Periksa informasi transaksi dan cetak struk pembayaran.</p></div><div class="heading-action"><a class="btn" href="transaksi_detail.php?id=<?= (int)$id ?>&print=1">Cetak Struk</a><a class="btn sec" href="transaksi.php">Kembali</a></div></div>
<div class="card transaction-detail-card">
    <div class="detail-summary"><div><span class="detail-label">Pelanggan</span><strong><?= e($transaction['nama_pelanggan']) ?></strong><small><?= e($transaction['kota'] ?: 'Kota belum diisi') ?></small></div><div><span class="detail-label">Tanggal</span><strong><?= e(date('d M Y', strtotime($transaction['tanggal']))) ?></strong><small>Transaksi #<?= e($transaction['id_transaksi']) ?></small></div><div><span class="detail-label">Total</span><strong class="detail-total"><?= rupiah($transaction['total']) ?></strong><small>Pembayaran tercatat</small></div><div><span class="detail-label">Metode Pembayaran</span><strong><?= strtoupper(e($transaction['metode_pembayaran'] ?? 'tunai')) ?></strong><small><?= ($transaction['metode_pembayaran'] ?? 'tunai') === 'qris' ? 'Perlu verifikasi bukti transfer' : 'Pembayaran tunai' ?></small></div></div>
    <?php if (($transaction['metode_pembayaran'] ?? 'tunai') === 'qris' && !empty($transaction['bukti_pembayaran'])): ?><div class="payment-proof"><span class="detail-label">Bukti Pembayaran QRIS</span><a href="<?= e($transaction['bukti_pembayaran']) ?>" target="_blank" rel="noopener"><img src="<?= e($transaction['bukti_pembayaran']) ?>" alt="Bukti pembayaran QRIS"></a><small>Klik gambar untuk melihat ukuran penuh.</small></div><?php endif; ?>
    <div class="transaction-detail-table"><table><thead><tr><th>Nama Barang</th><th>Jumlah</th><th>Harga Satuan</th><th>Total</th></tr></thead><tbody><tr><td><strong><?= e($transaction['nama_barang']) ?></strong></td><td><?= e($transaction['jumlah']) ?></td><td><?= rupiah($transaction['harga_satuan']) ?></td><td class="num"><strong><?= rupiah($transaction['total']) ?></strong></td></tr></tbody><tfoot><tr><th colspan="3">Total Pembayaran</th><th class="num"><?= rupiah($transaction['total']) ?></th></tr></tfoot></table></div>
</div>
<div class="receipt-print transaction-receipt"><div class="receipt-head"><strong>TOKOKU</strong><span>STRUK PEMBAYARAN</span></div><p>No. #<?= e($transaction['id_transaksi']) ?><br><?= e(date('d/m/Y', strtotime($transaction['tanggal']))) ?><br>Pelanggan: <?= e($transaction['nama_pelanggan']) ?></p><hr><div class="receipt-line"><span><?= e($transaction['nama_barang']) ?> x<?= (int)$transaction['jumlah'] ?></span><strong><?= rupiah($transaction['total']) ?></strong></div><hr><div class="receipt-line"><span>Total</span><strong><?= rupiah($transaction['total']) ?></strong></div><p class="receipt-thanks">Terima kasih sudah berbelanja.</p><button class="btn" type="button" onclick="window.print()">Cetak Struk</button></div>
<?php if (isset($_GET['print'])): ?><script>window.addEventListener('load',()=>setTimeout(()=>window.print(),300));</script><?php endif; ?>
<?php include 'includes/footer.php'; ?>
