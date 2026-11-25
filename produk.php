<?php
require 'config.php';
$title = 'Produk';
$q = trim($_GET['q'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$sort = $_GET['sort'] ?? 'nama';
$where = []; $params = [];
if ($q !== '') { $where[] = '(nama_produk LIKE ? OR kode_produk LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($kategori !== '') { $where[] = 'kategori = ?'; $params[] = $kategori; }
$order = ['murah' => 'harga_jual ASC', 'mahal' => 'harga_jual DESC', 'nama' => 'nama_produk ASC'][$sort] ?? 'nama_produk ASC';
$st = $pdo->prepare('SELECT * FROM produk' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY ' . $order);
$st->execute($params); $products = $st->fetchAll();
$categories = $pdo->query('SELECT DISTINCT kategori FROM produk ORDER BY kategori')->fetchAll(PDO::FETCH_COLUMN);
include 'includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow eyebrow-dark">PRODUCT CATALOG</span><h1>Produk Toko</h1><p class="sub">Kelola katalog, harga, stok, kategori, dan foto produk.</p></div><a class="btn heading-action" href="produk_form.php">+ Tambah Produk</a></div>
<div class="card product-toolbar"><form method="get" class="product-filters" id="productSearchForm"><div class="barcode-search"><input type="search" id="barcodeSearch" name="q" value="<?= e($q) ?>" placeholder="Cari nama produk atau kode..."><button class="btn sec camera-search-btn" type="button" id="productCameraButton">Scan Kamera</button><span id="productScanStatus" hidden></span></div><select name="kategori"><option value="">Semua kategori</option><?php foreach ($categories as $cat): ?><option value="<?= e($cat) ?>" <?= $kategori === $cat ? 'selected' : '' ?>><?= e($cat) ?></option><?php endforeach; ?></select><select name="sort"><option value="nama" <?= $sort === 'nama' ? 'selected' : '' ?>>Urutkan: Nama</option><option value="murah" <?= $sort === 'murah' ? 'selected' : '' ?>>Harga termurah</option><option value="mahal" <?= $sort === 'mahal' ? 'selected' : '' ?>>Harga termahal</option></select><button class="btn" type="submit">Cari</button><a class="btn sec" href="produk.php">Reset</a></form><video id="productCameraVideo" class="barcode-video product-camera-video" muted playsinline hidden></video></div>
<div class="product-catalog"><?php foreach ($products as $product): ?><article class="catalog-card"><div class="catalog-image"><?php if ($product['gambar']): ?><img src="uploads/produk/<?= e($product['gambar']) ?>" alt="<?= e($product['nama_produk']) ?>"><?php else: ?><span>📦</span><?php endif; ?><span class="stock-label <?= (int)$product['stok'] > 0 ? 'available' : 'empty-stock' ?>"><?= (int)$product['stok'] > 0 ? 'Stok Tersedia' : 'Stok Habis' ?></span></div><div class="catalog-body"><span class="catalog-category"><?= e($product['kategori']) ?></span><h2><?= e($product['nama_produk']) ?></h2><p class="product-code">Kode: <?= e($product['kode_produk']) ?></p><div class="catalog-meta"><strong><?= rupiah($product['harga_jual']) ?></strong><span>Stok <?= (int)$product['stok'] ?></span></div><a class="btn detail-btn" href="produk_detail.php?id=<?= (int)$product['id_produk'] ?>">Detail Produk</a></div></article><?php endforeach; ?><?php if (!$products): ?><div class="card empty catalog-empty">Produk tidak ditemukan.</div><?php endif; ?></div>
<?php include 'includes/footer.php'; ?>
<script>
const searchForm=document.getElementById('productSearchForm'), searchInput=document.getElementById('barcodeSearch'), cameraBtn=document.getElementById('productCameraButton'), cameraVideo=document.getElementById('productCameraVideo'), scanStatus=document.getElementById('productScanStatus');
let productStream=null, productDetector=null, productScanning=false;
searchInput.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();searchForm.submit()}});
async function startProductCamera(){if(!('BarcodeDetector' in window)){scanStatus.textContent='Browser belum mendukung scan kamera.';scanStatus.className='barcode-status is-error';return}try{if(!productStream){productStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}}});cameraVideo.srcObject=productStream;cameraVideo.hidden=false;await cameraVideo.play();productDetector=new BarcodeDetector()}productScanning=true;cameraBtn.textContent='Berhenti';scanStatus.textContent='Arahkan kamera ke barcode produk...';detectProductBarcode()}catch(error){scanStatus.textContent='Kamera tidak dapat dibuka. Izinkan akses kamera terlebih dahulu.';scanStatus.className='barcode-status is-error'}}
async function detectProductBarcode(){if(!productScanning)return;try{const codes=await productDetector.detect(cameraVideo);if(codes.length){searchInput.value=codes[0].rawValue;stopProductCamera();searchForm.submit();return}}catch(error){}requestAnimationFrame(detectProductBarcode)}
function stopProductCamera(){productScanning=false;if(productStream){productStream.getTracks().forEach(track=>track.stop());productStream=null}cameraVideo.hidden=true;cameraBtn.textContent='Scan Kamera'}
cameraBtn.addEventListener('click',()=>productScanning?stopProductCamera():startProductCamera());window.addEventListener('beforeunload',stopProductCamera);
</script>
