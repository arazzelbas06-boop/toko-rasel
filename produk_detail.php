<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM produk WHERE id_produk = ?'); $st->execute([$id]); $product = $st->fetch();
if (!$product) { flash('Produk tidak ditemukan.', 'err'); go('produk.php'); }
$title = $product['nama_produk']; include 'includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow eyebrow-dark">PRODUCT DETAIL</span><h1>Detail Produk</h1><p class="sub">Informasi lengkap produk <?= e($product['kode_produk']) ?>.</p></div><a class="btn sec" href="produk.php">← Kembali ke Produk</a></div>
<div class="card product-detail"><div class="detail-image"><?php if ($product['gambar']): ?><img src="uploads/produk/<?= e($product['gambar']) ?>" alt="<?= e($product['nama_produk']) ?>"><?php else: ?><span>📦</span><?php endif; ?></div><div class="detail-info"><span class="catalog-category"><?= e($product['kategori']) ?></span><h2><?= e($product['nama_produk']) ?></h2><p class="detail-code">Kode produk: <strong><?= e($product['kode_produk']) ?></strong></p><div class="detail-price"><?= rupiah($product['harga_jual']) ?></div><div class="detail-stock <?= (int)$product['stok'] > 0 ? 'stock-ok' : 'stock-zero' ?>"><?= (int)$product['stok'] > 0 ? 'Stok Tersedia' : 'Stok Habis' ?><span><?= (int)$product['stok'] ?> unit</span></div><div class="detail-actions"><a class="btn" href="produk_form.php?id=<?= (int)$product['id_produk'] ?>">Edit Produk</a><form method="post" action="produk_hapus.php" onsubmit="return confirm('Hapus produk ini?')"><input type="hidden" name="id" value="<?= (int)$product['id_produk'] ?>"><button class="btn danger" type="submit">Hapus</button></form></div></div></div>
<?php include 'includes/footer.php'; ?>
