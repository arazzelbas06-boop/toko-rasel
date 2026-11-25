<?php
require 'config.php';
$title = 'Transaksi';

// Keranjang kasir disimpan di session agar tetap tersedia saat admin mencari produk.
if (!isset($_SESSION['admin_cart'])) $_SESSION['admin_cart'] = [];
$cart =& $_SESSION['admin_cart'];
$errors = [];
$receipt = $_SESSION['checkout_receipt'] ?? null;
unset($_SESSION['checkout_receipt']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_cart') {
        $name = trim($_POST['nama_barang'] ?? '');
        $price = (float)($_POST['harga_satuan'] ?? 0);
        if ($name === '' || $price < 0) $errors[] = 'Produk tidak valid.';
        else {
            $found = false;
            foreach ($cart as &$item) {
                if ($item['nama_barang'] === $name && (float)$item['harga_satuan'] === $price) { $item['jumlah']++; $found = true; break; }
            }
            unset($item);
            if (!$found) $cart[] = ['nama_barang' => $name, 'harga_satuan' => $price, 'jumlah' => 1];
        }
    } elseif ($action === 'update_cart') {
        foreach ($_POST['jumlah'] ?? [] as $index => $qty) {
            $qty = (int)$qty;
            if (isset($cart[$index])) { if ($qty <= 0) unset($cart[$index]); else $cart[$index]['jumlah'] = $qty; }
        }
        $cart = array_values($cart);
    } elseif ($action === 'remove_cart') {
        $index = (int)($_POST['index'] ?? -1);
        if (isset($cart[$index])) array_splice($cart, $index, 1);
    } elseif ($action === 'clear_cart') $cart = [];
    elseif ($action === 'checkout') {
        $customer = (int)($_POST['id_pelanggan'] ?? 0);
        $paymentMethod = $_POST['metode_pembayaran'] ?? 'tunai';
        $cartTotal = 0; foreach ($cart as $item) $cartTotal += $item['harga_satuan'] * $item['jumlah'];
        $payment = $paymentMethod === 'qris' ? $cartTotal : (float)($_POST['pembayaran'] ?? 0);
        if (!$cart) $errors[] = 'Keranjang masih kosong.';
        if (!$customer) $errors[] = 'Pilih pelanggan terlebih dahulu.';
        if ($payment < $cartTotal) $errors[] = 'Pembayaran kurang dari total belanja.';
        if (!$errors) {
            try {
                $proof = save_payment_proof($_FILES['bukti_pembayaran'] ?? null, $paymentMethod);
                $pdo->beginTransaction();
                $insert = $pdo->prepare('INSERT INTO transaksi (id_pelanggan, tanggal, nama_barang, jumlah, harga_satuan, total, metode_pembayaran, bukti_pembayaran) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)');
                foreach ($cart as $item) $insert->execute([$customer, $item['nama_barang'], $item['jumlah'], $item['harga_satuan'], $item['harga_satuan'] * $item['jumlah'], $paymentMethod, $proof]);
                $pdo->commit();
                $customerName = $pdo->prepare('SELECT nama FROM pelanggan WHERE id_pelanggan = ?'); $customerName->execute([$customer]);
                $_SESSION['checkout_receipt'] = ['number' => date('YmdHis'), 'date' => date('d/m/Y H:i'), 'customer' => $customerName->fetchColumn() ?: 'Pelanggan umum', 'items' => $cart, 'total' => $cartTotal, 'payment' => $payment, 'change' => $payment - $cartTotal];
                $cart = []; go('transaksi.php');
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = 'Checkout gagal: ' . ($ex->errorInfo[2] ?? $ex->getMessage());
            }
        }
    }
}

$q = trim($_GET['produk'] ?? '');
$productSql = 'SELECT nama_produk AS nama_barang, harga_jual AS harga_satuan, stok FROM produk'; $productParams = [];
if ($q !== '') { $productSql .= ' WHERE nama_produk LIKE ?'; $productParams[] = "%$q%"; }
$productSql .= ' ORDER BY nama_produk';
$productStmt = $pdo->prepare($productSql); $productStmt->execute($productParams); $products = $productStmt->fetchAll();
$cartTotal = 0; $cartItems = 0; foreach ($cart as $item) { $cartTotal += $item['harga_satuan'] * $item['jumlah']; $cartItems += $item['jumlah']; }
$pelanggan = $pdo->query('SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama')->fetchAll();

$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$idp = (int)($_GET['pelanggan'] ?? 0);

$where = [];
$params = [];
if ($dari !== '')   { $where[] = 't.tanggal >= ?'; $params[] = $dari; }
if ($sampai !== '') { $where[] = 't.tanggal <= ?'; $params[] = $sampai; }
if ($idp)           { $where[] = 't.id_pelanggan = ?'; $params[] = $idp; }
$sql = 'SELECT t.id_transaksi, t.tanggal, p.nama AS nama_pelanggan, t.nama_barang, t.jumlah, t.harga_satuan, t.total
        FROM transaksi t JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY t.tanggal DESC, t.id_transaksi DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
$jumlah_total = array_sum(array_column($rows, 'total'));

$pelanggan = $pdo->query('SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama')->fetchAll();
$today_count = (int) $pdo->query('SELECT COUNT(*) FROM transaksi WHERE tanggal = CURDATE()')->fetchColumn();

include 'includes/header.php';
?>
<?php foreach ($errors as $error): ?><div class="alert err"><?= e($error) ?></div><?php endforeach; ?>
<?php if ($receipt): ?>
<div class="receipt-print" id="receipt"><div class="receipt-head"><strong>TOKOKU</strong><span>STRUK PEMBAYARAN</span></div><p>No. #<?= e($receipt['number']) ?><br><?= e($receipt['date']) ?><br>Pelanggan: <?= e($receipt['customer']) ?></p><hr><?php foreach ($receipt['items'] as $item): ?><div class="receipt-line"><span><?= e($item['nama_barang']) ?> x<?= (int)$item['jumlah'] ?></span><strong><?= rupiah($item['harga_satuan'] * $item['jumlah']) ?></strong></div><?php endforeach; ?><hr><div class="receipt-line"><span>Total</span><strong><?= rupiah($receipt['total']) ?></strong></div><div class="receipt-line"><span>Bayar</span><strong><?= rupiah($receipt['payment']) ?></strong></div><div class="receipt-line"><span>Kembalian</span><strong><?= rupiah($receipt['change']) ?></strong></div><p class="receipt-thanks">Terima kasih sudah berbelanja.</p><button class="btn" type="button" onclick="window.print()">Cetak Ulang</button></div>
<script>window.addEventListener('load', function(){ window.setTimeout(function(){ window.print(); }, 300); });</script>
<?php endif; ?>
<div class="cashier-layout">
    <section class="card product-picker"><div class="section-heading compact"><div><h2>Pilih Produk</h2><p><?= e(count($products)) ?> produk tersedia</p></div><span class="search-symbol">⌕</span></div>
        <form method="get" class="product-search"><input type="search" name="produk" value="<?= e($q) ?>" placeholder="Cari nama produk..." autofocus><button class="btn" type="submit">Cari</button><?php if ($q !== ''): ?><a class="btn sec" href="transaksi.php">Reset</a><?php endif; ?></form>
        <div class="product-grid"><?php foreach ($products as $product): ?><form method="post" class="product-item"><input type="hidden" name="action" value="add_cart"><input type="hidden" name="nama_barang" value="<?= e($product['nama_barang']) ?>"><input type="hidden" name="harga_satuan" value="<?= e($product['harga_satuan']) ?>"><div><strong><?= e($product['nama_barang']) ?></strong><span><?= rupiah($product['harga_satuan']) ?></span></div><button class="btn sm" type="submit">+ Tambah</button></form><?php endforeach; ?><?php if (!$products): ?><p class="empty">Produk tidak ditemukan.</p><?php endif; ?></div>
    </section>
    <aside class="card cart-panel"><div class="cart-title"><div><h2>Keranjang</h2><p><?= $cartItems ?> barang belum checkout</p></div><span class="cart-badge"><?= $cartItems ?></span></div>
        <?php if (!$cart): ?><div class="cart-empty">Keranjang masih kosong.<br><small>Pilih produk di sebelah kiri untuk mulai.</small></div><?php else: ?><div class="cart-list"><?php foreach ($cart as $index => $item): ?><div class="cart-line"><div><strong><?= e($item['nama_barang']) ?></strong><small><?= rupiah($item['harga_satuan']) ?> / pcs</small></div><form method="post" class="qty-form"><input type="hidden" name="action" value="update_cart"><input type="hidden" name="jumlah[<?= $index ?>]" value="<?= (int)$item['jumlah'] ?>"><span><?= (int)$item['jumlah'] ?>x</span></form><strong><?= rupiah($item['harga_satuan'] * $item['jumlah']) ?></strong><form method="post"><input type="hidden" name="action" value="remove_cart"><input type="hidden" name="index" value="<?= $index ?>"><button class="cart-remove" type="submit">×</button></form></div><?php endforeach; ?></div><form method="post" class="clear-cart"><input type="hidden" name="action" value="clear_cart"><button class="link-button" type="submit">Kosongkan keranjang</button></form><?php endif; ?>
        <div class="cart-total"><span>Total keranjang</span><strong><?= rupiah($cartTotal) ?></strong></div>
        <form method="post" class="checkout-form"><input type="hidden" name="action" value="checkout"><label>Pelanggan<select name="id_pelanggan" required><option value="">Pilih pelanggan</option><?php foreach ($pelanggan as $p): ?><option value="<?= (int)$p['id_pelanggan'] ?>"><?= e($p['nama']) ?></option><?php endforeach; ?></select></label><label>Pembayaran<input type="number" name="pembayaran" min="<?= $cartTotal ?>" step="1" placeholder="Jumlah uang diterima" required></label><button class="btn checkout-btn" type="submit" <?= !$cart ? 'disabled' : '' ?>>Lanjutkan Pembayaran →</button></form>
    </aside>
</div>
<div class="page-heading"><div><span class="eyebrow eyebrow-dark">SALES ACTIVITY</span><h1>Data Transaksi</h1><p class="sub">Pantau seluruh aktivitas penjualan dan kelola transaksi toko.</p></div><a class="btn heading-action" href="transaksi_form.php">+ Tambah Transaksi</a></div>
<div class="grid mini-stats"><div class="stat stat-purple"><div class="stat-icon">▣</div><div><div class="label">Total transaksi</div><div class="value"><?= e(count($rows)) ?></div></div><span class="stat-note">sesuai filter aktif</span></div><div class="stat stat-green"><div class="stat-icon">◷</div><div><div class="label">Hari ini</div><div class="value"><?= e($today_count) ?></div></div><span class="stat-note">transaksi baru</span></div><div class="stat stat-orange"><div class="stat-icon">Rp</div><div><div class="label">Nilai penjualan</div><div class="value value-small"><?= rupiah($jumlah_total) ?></div></div><span class="stat-note">dari hasil filter</span></div></div>

<div class="card modern-table-card">
    <div class="section-heading"><div><h2>Riwayat transaksi</h2><p><?= ($dari || $sampai || $idp) ? 'Data yang sesuai dengan filter pilihan' : 'Semua transaksi penjualan toko' ?></p></div><span class="result-count"><?= e(count($rows)) ?> transaksi</span></div>
    <div class="filter-bar transaction-filter">
        <form method="get" class="filter-form">
            <select name="pelanggan">
                <option value="0">Semua pelanggan</option>
                <?php foreach ($pelanggan as $p): ?>
                    <option value="<?= (int)$p['id_pelanggan'] ?>" <?= $idp === (int)$p['id_pelanggan'] ? 'selected' : '' ?>><?= e($p['nama']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="dari" value="<?= e($dari) ?>" title="Dari tanggal">
            <input type="date" name="sampai" value="<?= e($sampai) ?>" title="Sampai tanggal">
            <button class="btn" type="submit">Terapkan filter</button>
            <a class="btn sec" href="transaksi.php">Reset</a>
        </form>
    </div>

    <?php if (!$rows): ?>
        <p class="empty">Tidak ada transaksi.</p>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Transaksi</th><th>Tanggal</th><th>Pelanggan</th><th>Barang</th><th>Qty</th><th>Harga Satuan</th><th>Total</th><th class="action-head">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong class="transaction-id">#<?= e($r['id_transaksi']) ?></strong></td>
                <td><span class="muted-cell"><?= e(date('d M Y', strtotime($r['tanggal']))) ?></span></td>
                <td><strong><?= e($r['nama_pelanggan']) ?></strong></td>
                <td><span class="product-cell"><?= e($r['nama_barang']) ?></span></td>
                <td><span class="number-pill"><?= e($r['jumlah']) ?></span></td>
                <td class="num"><?= rupiah($r['harga_satuan']) ?></td>
                <td class="num"><?= rupiah($r['total']) ?></td>
                <td><div class="actions row-actions">
                    <a class="btn sec sm" href="transaksi_detail.php?id=<?= (int)$r['id_transaksi'] ?>">Detail</a>
                    <a class="btn sm" href="transaksi_detail.php?id=<?= (int)$r['id_transaksi'] ?>&print=1">Cetak</a>
                    <a class="btn sec sm" href="transaksi_form.php?id=<?= (int)$r['id_transaksi'] ?>">Edit</a>
                    <form method="post" action="transaksi_hapus.php" onsubmit="return confirm('Hapus transaksi ini?')">
                        <input type="hidden" name="id" value="<?= (int)$r['id_transaksi'] ?>">
                        <button class="btn danger sm" type="submit">Hapus</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="6" style="text-align:right">Total (<?= count($rows) ?> transaksi)</th><th class="num"><?= rupiah($jumlah_total) ?></th><th></th></tr></tfoot>
    </table></div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){const form=document.querySelector('.checkout-form');if(!form)return;form.enctype='multipart/form-data';const label=document.createElement('label');label.textContent='Metode Pembayaran';const select=document.createElement('select');select.name='metode_pembayaran';select.innerHTML='<option value="tunai">Tunai</option><option value="qris">QRIS</option>';label.appendChild(select);const panel=document.createElement('div');panel.className='qris-panel';panel.innerHTML='<strong>Bayar dengan QRIS</strong><p>Scan QRIS toko menggunakan aplikasi pembayaran Anda.</p><p class="qris-timer">Batas waktu pembayaran: <strong>15:00</strong></p><label class="qris-proof-label">Upload bukti transfer<input type="file" name="bukti_pembayaran" accept="image/jpeg,image/png,image/webp"></label><img src="assets/qris.jpg" alt="QRIS TokoKu">';panel.hidden=true;const proofInput=panel.querySelector('input[type="file"]');const paymentInput=form.querySelector('input[name="pembayaran"]');const paymentLabel=paymentInput.closest('label');const timer=panel.querySelector('.qris-timer strong');let timerId;function updatePayment(){const isQris=select.value==='qris';panel.hidden=!isQris;proofInput.required=isQris;paymentLabel.hidden=isQris;paymentInput.required=!isQris;if(isQris){const totalText=form.closest('.cart-panel').querySelector('.cart-total strong').textContent;paymentInput.value=totalText.replace(/[^0-9]/g,'');let seconds=900;clearInterval(timerId);const tick=()=>{timer.textContent=String(Math.floor(seconds/60)).padStart(2,'0')+':'+String(seconds%60).padStart(2,'0');if(seconds>0)seconds--;else clearInterval(timerId)};tick();timerId=setInterval(tick,1000)}else{paymentInput.value='';clearInterval(timerId)}}select.addEventListener('change',updatePayment);updatePayment();form.insertBefore(label,form.querySelector('label'));form.insertBefore(panel,label.nextSibling)});
</script>
<?php include 'includes/footer.php'; ?>
