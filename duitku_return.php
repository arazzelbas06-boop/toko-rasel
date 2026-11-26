<?php
$order = trim((string)($_GET['order'] ?? $_GET['merchantOrderId'] ?? ''));
$duitkuSuccess = (string)($_GET['resultCode'] ?? '') === '00';
ob_start();
register_shutdown_function(static function () use ($order, $duitkuSuccess) {
    if (strpos(ob_get_contents(), '<!doctype') !== false) return;
    ob_end_clean();
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Status Pembayaran</title><style>body{font-family:Arial;background:#f4f7fb;padding:32px;color:#172033}.box{max-width:560px;margin:auto;padding:32px;background:#fff;border-radius:16px;text-align:center}h1{color:' . ($duitkuSuccess ? '#15803d' : '#b45309') . '}.btn{display:inline-block;margin:8px;padding:12px 18px;border-radius:8px;background:#1d4ed8;color:#fff!important;text-decoration:none}</style></head><body><main class="box"><h1>' . ($duitkuSuccess ? 'Pembayaran berhasil' : 'Status Pembayaran') . '</h1><p>Nomor order: ' . htmlspecialchars($order ?: '-', ENT_QUOTES, 'UTF-8') . '</p><a class="btn" href="belanja.php">Kembali ke TokoKu</a></main></body></html>';
});
require __DIR__ . '/config.php';
$rows = [];
 $loadError = '';
if ($order !== '') {
    try {
        $st = $pdo->prepare('SELECT t.id_transaksi,t.nama_barang,t.jumlah,t.total,t.metode_pembayaran,t.status_pesanan,t.duitku_reference,t.duitku_paid_at,t.tanggal,p.nama AS nama_pelanggan FROM transaksi t LEFT JOIN pelanggan p ON p.id_pelanggan=t.id_pelanggan WHERE t.duitku_order_id=? ORDER BY t.id_transaksi');
        $st->execute([$order]);
        $rows = $st->fetchAll();
    } catch (Throwable $error) {
        $loadError = 'Detail transaksi belum dapat dimuat. Silakan kembali ke toko atau coba lagi.';
    }
}
$transaction = $rows[0] ?? null;
$paid = $transaction !== null;
$confirmed = $transaction && $transaction['status_pesanan'] === 'selesai';
$total = array_sum(array_map(static fn($row) => (float)$row['total'], $rows));
$date = $transaction ? ($transaction['duitku_paid_at'] ?: $transaction['tanggal']) : date('Y-m-d H:i:s');
$duitkuReference = trim((string)($_GET['reference'] ?? ($transaction['duitku_reference'] ?? '')));
$barcodeUrl = 'https://barcode.tec-it.com/barcode.ashx?data=' . rawurlencode($order) . '&code=Code128&translate-esc=false&unit=Fit&dpi=96';
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $paid ? 'Struk Pembayaran' : 'Status Pembayaran' ?> - TokoKu</title><style>*{box-sizing:border-box}body{margin:0;padding:32px 16px;background:#f4f7fb;color:#172033;font-family:Arial,sans-serif}.box{max-width:580px;margin:auto;background:#fff;border-radius:16px;padding:30px;box-shadow:0 8px 30px #dce5f1}h1{text-align:center;margin:0 0 8px;font-size:24px}.intro{text-align:center;color:#64748b;margin:0 0 22px}.ok{color:#15803d}.wait{color:#b45309}.receipt{width:min(100%,360px);margin:20px auto 0;padding:18px 16px;border:1px dashed #aeb8c8;font-family:monospace}.receipt-head{text-align:center}.receipt-head strong{display:block;font-size:18px}.receipt-head span{font-size:11px}.meta{font-size:11px;line-height:1.5;margin:12px 0}.line{display:flex;justify-content:space-between;gap:10px;font-size:11px;margin:6px 0}.line span{overflow-wrap:anywhere}.line strong{white-space:nowrap}.total{font-weight:bold;font-size:13px}.barcode{display:block;width:100%;height:68px;object-fit:contain;margin:15px 0 2px}.barcode-number{text-align:center;font-size:10px;letter-spacing:1px}.thanks{text-align:center;font-size:11px;margin:14px 0}.actions{text-align:center;margin-top:22px}.btn{display:inline-block;margin:4px;padding:11px 16px;border:0;border-radius:8px;background:#1d4ed8;color:#fff;text-decoration:none;cursor:pointer;font-size:14px}.btn.secondary{background:#e5e7eb;color:#172033!important;border:1px solid #cbd5e1}@media print{@page{size:80mm auto;margin:0}body{padding:0;background:#fff}.box{width:80mm;max-width:80mm;padding:5mm;border-radius:0;box-shadow:none}.box>h1,.box>.intro,.actions{display:none}.receipt{width:80mm;margin:0;padding:4mm 3mm;border:0}.barcode{height:62px}}</style></head><body><main class="box"><h1 class="<?= $paid ? 'ok' : 'wait' ?>"><?= $paid ? 'Pembayaran berhasil' : 'Pembayaran sedang diproses' ?></h1><p class="intro"><?= $paid ? 'Nota pembayaran siap dicetak.' : 'Jika pembayaran sudah dilakukan, status akan diperbarui otomatis oleh Duitku.' ?></p><?php if ($paid): ?><section class="receipt" aria-label="Nota pembayaran"><div class="receipt-head"><strong>TOKOKU</strong><span>NOTA PEMBAYARAN</span></div><div class="meta">No. Transaksi: #<?= e($transaction['id_transaksi']) ?><br>No. Order: <?= e($order) ?><br>Tanggal: <?= e(date('d/m/Y H:i', strtotime($date))) ?><br>Pelanggan: <?= e($transaction['nama_pelanggan'] ?: 'Pelanggan') ?><br>Metode: <?= e(strtoupper($transaction['metode_pembayaran'])) ?></div><hr><?php foreach ($rows as $row): ?><div class="line"><span><?= e($row['nama_barang']) ?> x<?= (int)$row['jumlah'] ?></span><strong><?= rupiah($row['total']) ?></strong></div><?php endforeach; ?><hr><div class="line total"><span>Total</span><strong><?= rupiah($total) ?></strong></div><img class="barcode" src="<?= e($barcodeUrl) ?>" alt="Barcode order <?= e($order) ?>" loading="eager"><div class="barcode-number"><?= e($order) ?></div><p class="thanks">Terima kasih sudah berbelanja.</p></section><?php endif; ?><div class="actions"><button class="btn" type="button" onclick="window.print()">Cetak Nota</button><a class="btn secondary" href="belanja.php">Kembali ke TokoKu</a></div></main><?php if ($paid): ?><script>window.addEventListener('load',()=>setTimeout(()=>window.print(),400));</script><?php endif; ?></body></html>

