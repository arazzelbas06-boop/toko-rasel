<?php
require '../config.php';
require_cashier();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    $customerId = (int)($_POST['id_pelanggan'] ?? 0);
    $method = ($_POST['metode_pembayaran'] ?? 'tunai') === 'qris' ? 'qris' : 'tunai';
    $cart = json_decode($_POST['cart'] ?? '[]', true);
    if (!$customerId) $errors[] = 'Pilih pelanggan terlebih dahulu.';
    if (!is_array($cart) || !$cart) $errors[] = 'Keranjang masih kosong.';
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $find = $pdo->prepare('SELECT id_produk, nama_produk, harga_jual, stok FROM produk WHERE id_produk = ? FOR UPDATE');
            $insert = $pdo->prepare('INSERT INTO transaksi (id_pelanggan, id_petugas, tanggal, nama_barang, jumlah, harga_satuan, total, metode_pembayaran, status_pesanan) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?)');
            $reduce = $pdo->prepare('UPDATE produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?');
            foreach ($cart as $item) {
                $productId = (int)($item['id'] ?? 0);
                $qty = (int)($item['qty'] ?? 0);
                if ($productId < 1 || $qty < 1) throw new RuntimeException('Data keranjang tidak valid.');
                $find->execute([$productId]);
                $product = $find->fetch();
                if (!$product) throw new RuntimeException('Produk tidak ditemukan.');
                if ($qty > (int)$product['stok']) throw new RuntimeException('Stok ' . $product['nama_produk'] . ' tidak mencukupi.');
                $price = (float)$product['harga_jual'];
                $insert->execute([$customerId, (int)($_SESSION['admin']['id'] ?? 0) ?: null, $product['nama_produk'], $qty, $price, $price * $qty, $method, 'selesai']);
                $reduce->execute([$qty, $productId, $qty]);
            }
            $pdo->commit();
            flash('Transaksi berhasil disimpan. Stok produk otomatis diperbarui.');
            go('index.php');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $ex->getMessage();
        }
    }
}
$title = 'Dashboard Kasir';
$jumlah_transaksi = (int) $pdo->query('SELECT COUNT(*) FROM transaksi')->fetchColumn();
$total_penjualan = $pdo->query('SELECT COALESCE(SUM(total),0) FROM transaksi')->fetchColumn();
$jumlah_pelanggan = (int) $pdo->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
$hari_ini = $pdo->prepare('SELECT COUNT(*), COALESCE(SUM(total),0) FROM transaksi WHERE tanggal = CURDATE()');
$hari_ini->execute();
$hari = $hari_ini->fetch(PDO::FETCH_NUM);
$produk = $pdo->query('SELECT id_produk, kode_produk, nama_produk, kategori, harga_jual, stok FROM produk ORDER BY nama_produk')->fetchAll();
$kategori = $pdo->query('SELECT DISTINCT kategori FROM produk ORDER BY kategori')->fetchAll(PDO::FETCH_COLUMN);
$pelanggan = $pdo->query('SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama')->fetchAll();
$terbaru = $pdo->query("SELECT MAX(t.id_transaksi) AS id_transaksi, t.tanggal, p.nama AS nama_pelanggan, GROUP_CONCAT(CONCAT(t.nama_barang, ' (', t.jumlah, ')') ORDER BY t.id_transaksi DESC SEPARATOR ', ') AS nama_barang, SUM(t.total) AS total FROM transaksi t JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan GROUP BY t.tanggal, t.id_pelanggan, p.nama ORDER BY MAX(t.id_transaksi) DESC LIMIT 6")->fetchAll();
include 'includes/header.php';
?>
<h1>Dashboard Kasir</h1>
<p class="sub">Selamat datang, <?= e(admin_name()) ?>. Kelola transaksi penjualan dengan cepat.</p>
<?php foreach ($errors as $error): ?><div class="alert err"><?= e($error) ?></div><?php endforeach; ?>

<div class="report-banner cashier-banner"><div><span class="eyebrow">CASHIER DESK</span><h2>Siap melayani pelanggan hari ini?</h2><p>Catat transaksi baru dan pantau penjualan toko dari sini.</p></div><a class="btn" href="transaksi_form.php">+ Transaksi Baru</a></div>
<div class="grid"><div class="stat"><div class="label">Transaksi hari ini</div><div class="value"><?= e($hari[0]) ?></div><span class="stat-note"><?= rupiah($hari[1]) ?> nilai penjualan</span></div><div class="stat"><div class="label">Total transaksi</div><div class="value"><?= e($jumlah_transaksi) ?></div><span class="stat-note">seluruh periode</span></div><div class="stat"><div class="label">Pelanggan</div><div class="value"><?= e($jumlah_pelanggan) ?></div><span class="stat-note">terdaftar di sistem</span></div><div class="stat"><div class="label">Penjualan</div><div class="value value-small"><?= rupiah($total_penjualan) ?></div><span class="stat-note">akumulasi keseluruhan</span></div></div>

<div class="cashier-layout">
<section class="card product-picker"><div class="section-heading compact"><div><h2>Kasir cepat</h2><p>Pilih produk untuk memasukkannya ke keranjang.</p></div><span class="result-count" id="productCount"><?= count($produk) ?> produk</span></div><div class="pos-toolbar"><div class="pos-search"><span>⌕</span><input type="search" id="productSearch" placeholder="Cari nama atau kode produk..." autocomplete="off"></div><select id="categoryFilter"><option value="">Semua kategori</option><?php foreach ($kategori as $cat): ?><option value="<?= e($cat) ?>"><?= e($cat) ?></option><?php endforeach; ?></select></div>
<div class="product-grid pos-product-grid" id="productGrid"><?php foreach ($produk as $p): ?><article class="product-item pos-product <?= (int)$p['stok'] < 1 ? 'out-of-stock' : '' ?>" data-name="<?= e(strtolower($p['nama_produk'] . ' ' . $p['kode_produk'])) ?>" data-category="<?= e($p['kategori']) ?>" data-id="<?= (int)$p['id_produk'] ?>" data-product="<?= e($p['nama_produk']) ?>" data-price="<?= e($p['harga_jual']) ?>" data-stock="<?= (int)$p['stok'] ?>"><div><small class="product-code"><?= e($p['kode_produk']) ?></small><strong><?= e($p['nama_produk']) ?></strong><span><?= rupiah($p['harga_jual']) ?></span><small class="stock-info"><?= (int)$p['stok'] > 0 ? 'Stok ' . (int)$p['stok'] : 'Stok habis' ?></small></div><button type="button" class="btn sm add-product" <?= (int)$p['stok'] < 1 ? 'disabled' : '' ?>>Tambah</button></article><?php endforeach; ?></div><p class="empty" id="noProducts" hidden>Produk tidak ditemukan.</p></section>
<aside class="card cart-panel pos-cart"><div class="cart-title"><div><h2>Keranjang belanja</h2><p id="cartHint">Belum ada produk dipilih</p></div><span class="cart-badge" id="cartCount">0</span></div><div class="cart-list" id="cartList"><div class="cart-empty\"><div class="cart-empty-icon">🛒</div><strong>Keranjang masih kosong</strong><small>Pilih produk di sebelah kiri untuk mulai transaksi.</small></div></div><div class="clear-cart"><button type="button" class="link-button" id="clearCart" hidden>Kosongkan keranjang</button></div><div class="cart-total"><span>Total pembayaran</span><strong id="cartTotal">Rp0</strong></div><form method="post" class="checkout-form"><input type="hidden" name="action" value="checkout"><input type="hidden" name="cart" id="cartInput"><label>Pelanggan<select name="id_pelanggan" required><option value="">Pilih pelanggan...</option><?php foreach ($pelanggan as $p): ?><option value="<?= (int)$p['id_pelanggan'] ?>"><?= e($p['nama']) ?></option><?php endforeach; ?></select></label><label>Metode pembayaran<select name="metode_pembayaran"><option value="tunai">Tunai</option><option value="qris">QRIS</option></select></label><button class="btn checkout-btn" type="submit" id="checkoutButton" disabled>Bayar &amp; Simpan Transaksi</button></form></aside></div>

<div class="card"><div class="toolbar"><div><h2>Transaksi terbaru</h2><p class="sub">Aktivitas transaksi terakhir di toko.</p></div><a class="btn sec" href="transaksi.php">Lihat semua</a></div>
<?php tbl($terbaru, ['tanggal' => 'Tanggal', 'nama_pelanggan' => 'Pelanggan', 'nama_barang' => 'Barang dalam transaksi', 'total' => 'Total gabungan'], ['total']); ?>
</div>
<div class="cashier-qris" id="cashierQris" hidden><strong>Scan QRIS untuk membayar</strong><small>Pastikan nominal pembayaran sesuai total di atas.</small><img src="../assets/qris.jpg" alt="QRIS TokoKu"></div>
<script>
const cart = new Map(), products = [...document.querySelectorAll('.pos-product')];
const money = value => 'Rp' + Math.round(value).toLocaleString('id-ID');
function renderCart() { let total=0, count=0; const list=document.getElementById('cartList'); list.innerHTML=''; cart.forEach(item=>{ total+=item.price*item.qty; count+=item.qty; const row=document.createElement('div'); row.className='cart-line'; row.innerHTML='<div><strong>'+item.name+'</strong><small>'+money(item.price)+'</small></div><div class="qty-control"><button type="button" data-action="minus">−</button><span>'+item.qty+'</span><button type="button" data-action="plus">+</button></div><strong>'+money(item.price*item.qty)+'</strong><button type="button" class="cart-remove" aria-label="Hapus">×</button>'; row.querySelector('[data-action="minus"]').onclick=()=>changeQty(item.id,-1); row.querySelector('[data-action="plus"]').onclick=()=>changeQty(item.id,1); row.querySelector('.cart-remove').onclick=()=>{cart.delete(item.id);renderCart()}; list.appendChild(row)}); if(!count) list.innerHTML='<div class="cart-empty"><div class="cart-empty-icon">🛒</div><strong>Keranjang masih kosong</strong><small>Pilih produk di sebelah kiri untuk mulai transaksi.</small></div>'; document.getElementById('cartCount').textContent=count; document.getElementById('cartHint').textContent=count ? count+' item dalam keranjang' : 'Belum ada produk dipilih'; document.getElementById('cartTotal').textContent=money(total); document.getElementById('clearCart').hidden=!count; document.getElementById('checkoutButton').disabled=!count; document.getElementById('cartInput').value=JSON.stringify([...cart.values()].map(({id,qty})=>({id,qty})));}
function changeQty(id, amount) { const item=cart.get(id), product=document.querySelector('.pos-product[data-id="'+id+'"]'), stock=Number(product.dataset.stock); item.qty=Math.max(0,Math.min(stock,item.qty+amount)); if(!item.qty) cart.delete(id); renderCart(); }
products.forEach(product=>product.querySelector('.add-product').onclick=()=>{ const id=product.dataset.id, item=cart.get(id); if(item) {if(item.qty<Number(product.dataset.stock)) item.qty++;} else cart.set(id,{id,name:product.dataset.product,price:Number(product.dataset.price),qty:1}); renderCart(); });
function filterProducts(){const q=document.getElementById('productSearch').value.toLowerCase(), cat=document.getElementById('categoryFilter').value; let visible=0; products.forEach(p=>{const show=p.dataset.name.includes(q)&&(!cat||p.dataset.category===cat);p.hidden=!show;if(show)visible++;});document.getElementById('productCount').textContent=visible+' produk';document.getElementById('noProducts').hidden=visible>0;}
document.getElementById('productSearch').oninput=filterProducts; document.getElementById('categoryFilter').onchange=filterProducts; document.getElementById('clearCart').onclick=()=>{cart.clear();renderCart();}; renderCart();
const paymentMethod=document.querySelector('select[name="metode_pembayaran"]'), cashierQris=document.getElementById('cashierQris');
paymentMethod.onchange=()=>{cashierQris.hidden=paymentMethod.value!=='qris';};
</script>
<?php include 'includes/footer.php'; ?>
