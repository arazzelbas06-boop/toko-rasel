<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
$data = ['kode_produk' => '', 'nama_produk' => '', 'kategori' => 'Umum', 'harga_beli' => '', 'harga_jual' => '', 'stok' => 0, 'gambar' => null];
if ($id) { $st = $pdo->prepare('SELECT * FROM produk WHERE id_produk = ?'); $st->execute([$id]); $data = $st->fetch() ?: $data; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['kode_produk'] = trim($_POST['kode_produk'] ?? ''); $data['nama_produk'] = trim($_POST['nama_produk'] ?? ''); $data['kategori'] = trim($_POST['kategori'] ?? ''); $data['harga_beli'] = trim($_POST['harga_beli'] ?? ''); $data['harga_jual'] = trim($_POST['harga_jual'] ?? ''); $data['stok'] = (int)($_POST['stok'] ?? 0);
    if ($data['kode_produk'] === '') $errors[] = 'Kode produk wajib diisi.';
    if ($data['nama_produk'] === '') $errors[] = 'Nama produk wajib diisi.';
    if ($data['kategori'] === '') $errors[] = 'Kategori wajib diisi.';
    if (!is_numeric($data['harga_beli']) || (float)$data['harga_beli'] < 0) $errors[] = 'Harga beli tidak valid.';
    if (!is_numeric($data['harga_jual']) || (float)$data['harga_jual'] < 0) $errors[] = 'Harga jual tidak valid.';
    if ($data['stok'] < 0) $errors[] = 'Stok tidak boleh negatif.';
    $newImage = $data['gambar'];
    if (!empty($_FILES['gambar']['name'])) {
        $file = $_FILES['gambar']; $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = function_exists('finfo_open') ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : mime_content_type($file['tmp_name']);
        if ($file['error'] !== UPLOAD_ERR_OK) $errors[] = 'Upload gambar gagal.';
        elseif ($file['size'] > 2 * 1024 * 1024) $errors[] = 'Ukuran gambar maksimal 2 MB.';
        elseif (!isset($allowed[$mime])) $errors[] = 'Format gambar harus JPG, JPEG, PNG, atau WEBP.';
        else { $newImage = bin2hex(random_bytes(12)) . '.' . $allowed[$mime]; $uploadDir = __DIR__ . '/uploads/produk/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true); if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newImage)) $errors[] = 'Gambar tidak dapat disimpan.'; }
    }
    if (!$errors) { try { if ($id) { $st = $pdo->prepare('UPDATE produk SET kode_produk=?, nama_produk=?, kategori=?, harga_beli=?, harga_jual=?, stok=?, gambar=? WHERE id_produk=?'); $st->execute([$data['kode_produk'], $data['nama_produk'], $data['kategori'], $data['harga_beli'], $data['harga_jual'], $data['stok'], $newImage, $id]); } else { $st = $pdo->prepare('INSERT INTO produk (kode_produk,nama_produk,kategori,harga_beli,harga_jual,stok,gambar) VALUES (?,?,?,?,?,?,?)'); $st->execute([$data['kode_produk'], $data['nama_produk'], $data['kategori'], $data['harga_beli'], $data['harga_jual'], $data['stok'], $newImage]); } flash($id ? 'Produk berhasil diperbarui.' : 'Produk berhasil ditambahkan.'); go('produk.php'); } catch (PDOException $ex) { $errors[] = 'Data gagal disimpan. Pastikan kode produk unik.'; } }
}
$title = $id ? 'Edit Produk' : 'Tambah Produk'; include 'includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow eyebrow-dark">PRODUCT MANAGEMENT</span><h1><?= e($title) ?></h1><p class="sub">Lengkapi informasi produk dan pilih gambar dari komputer.</p></div><a class="btn sec" href="produk.php">Batal</a></div>
<?php foreach ($errors as $error): ?><div class="alert err"><?= e($error) ?></div><?php endforeach; ?>
<div class="card form-card product-form-card"><form method="post" enctype="multipart/form-data"><div class="form-row"><label>Kode Produk *</label><input name="kode_produk" value="<?= e($data['kode_produk']) ?>" placeholder="Contoh: PRD001" required></div><div class="form-row"><label>Nama Produk *</label><input name="nama_produk" value="<?= e($data['nama_produk']) ?>" required></div><div class="form-row"><label>Kategori *</label><input name="kategori" value="<?= e($data['kategori']) ?>" placeholder="Contoh: Makanan" required></div><div class="form-row"><label>Harga Beli (Rp) *</label><input type="number" name="harga_beli" min="0" step="1" value="<?= e($data['harga_beli']) ?>" required></div><div class="form-row"><label>Harga Jual (Rp) *</label><input type="number" name="harga_jual" min="0" step="1" value="<?= e($data['harga_jual']) ?>" required></div><div class="form-row"><label>Jumlah Stok *</label><input type="number" name="stok" min="0" step="1" value="<?= e($data['stok']) ?>" required></div><div class="form-row"><label>Foto Produk <small>(JPG, JPEG, PNG, WEBP · maksimal 2 MB)</small></label><input type="file" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><div class="image-preview" id="preview"><?php if ($data['gambar']): ?><img src="uploads/produk/<?= e($data['gambar']) ?>" alt="Preview produk"><?php else: ?><span>Preview gambar akan tampil di sini</span><?php endif; ?></div></div><button class="btn" type="submit">Simpan Produk</button></form></div>
<script>document.getElementById('gambar').addEventListener('change',function(){var f=this.files[0],p=document.getElementById('preview');if(!f)return;if(f.size>2097152){alert('Ukuran gambar maksimal 2 MB.');this.value='';return;}var r=new FileReader();r.onload=function(e){p.innerHTML='<img src="'+e.target.result+'" alt="Preview produk">';};r.readAsDataURL(f);});</script>
<?php include 'includes/footer.php'; ?>
