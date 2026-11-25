<?php
require 'config.php';
require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($nama === '' || mb_strlen($nama) < 2) $errors[] = 'Nama petugas wajib diisi.';
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) $errors[] = 'Username 3-50 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau strip.';
        if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        if (!$errors) {
            try {
                $check = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ?');
                $check->execute([$username]);
                if ((int)$check->fetchColumn() > 0) $errors[] = 'Username sudah digunakan.';
                else {
                    $insert = $pdo->prepare('INSERT INTO admin_users (username, password_hash, nama, role) VALUES (?, ?, ?, ?)');
                    $insert->execute([$username, password_hash($password, PASSWORD_DEFAULT), $nama, 'kasir']);
                    flash('Petugas ' . $nama . ' berhasil ditambahkan.');
                    go('staff.php');
                }
            } catch (PDOException $ex) { $errors[] = 'Petugas gagal ditambahkan. Username mungkin sudah digunakan.'; }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $delete = $pdo->prepare("DELETE FROM admin_users WHERE id = ? AND role = 'kasir'");
        $delete->execute([$id]);
        flash($delete->rowCount() ? 'Petugas berhasil dihapus.' : 'Petugas tidak ditemukan.', $delete->rowCount() ? 'ok' : 'err');
        go('staff.php');
    }
}
$staff = $pdo->query("SELECT id, username, nama, role, created_at FROM admin_users ORDER BY role DESC, nama ASC")->fetchAll();
$totalStaff = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'kasir'")->fetchColumn();
$title = 'Petugas';
include 'includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow eyebrow-dark">TEAM MANAGEMENT</span><h1>Petugas Toko</h1><p class="sub">Tambah akun kasir dan kelola akses petugas yang melayani transaksi.</p></div><a class="btn heading-action" href="#tambah-petugas">+ Tambah Petugas</a></div>
<?php foreach ($errors as $error): ?><div class="alert err"><?= e($error) ?></div><?php endforeach; ?>
<div class="grid mini-stats"><div class="stat stat-purple"><div class="stat-icon">K</div><div><div class="label">Total petugas</div><div class="value"><?= e($totalStaff) ?></div></div><span class="stat-note">akun kasir aktif</span></div><div class="stat stat-green"><div class="stat-icon">A</div><div><div class="label">Akses aman</div><div class="value">Kasir</div></div><span class="stat-note">tidak bisa membuka menu admin</span></div><div class="stat stat-orange"><div class="stat-icon">+</div><div><div class="label">Tambah akun</div><div class="value">Cepat</div></div><span class="stat-note">form siap digunakan</span></div></div>
<div class="two staff-layout">
<section class="card staff-form-card" id="tambah-petugas"><div class="section-heading compact"><div><h2>Tambah petugas</h2><p>Buat akun login baru untuk kasir.</p></div><span class="staff-form-icon">+</span></div><form method="post" autocomplete="off"><input type="hidden" name="action" value="add"><div class="form-row"><label for="nama">Nama lengkap *</label><input id="nama" type="text" name="nama" placeholder="Contoh: Sari Kasir" value="<?= e($_POST['nama'] ?? '') ?>" required></div><div class="form-row"><label for="username">Username *</label><input id="username" type="text" name="username" placeholder="Contoh: sari.kasir" value="<?= e($_POST['username'] ?? '') ?>" required><small class="field-help">3-50 karakter, tanpa spasi.</small></div><div class="form-row"><label for="password">Password *</label><input id="password" type="password" name="password" placeholder="Minimal 6 karakter" minlength="6" required><small class="field-help">Simpan password ini untuk diberikan kepada petugas.</small></div><button class="btn" type="submit">Simpan Petugas</button></form></section>
<section class="card modern-table-card staff-list-card"><div class="section-heading"><div><h2>Daftar petugas</h2><p>Admin utama dan seluruh akun kasir.</p></div><span class="result-count"><?= e(count($staff)) ?> akun</span></div><div class="table-wrap"><table><thead><tr><th>Petugas</th><th>Username</th><th>Peran</th><th>Dibuat</th><th class="action-head">Aksi</th></tr></thead><tbody><?php foreach ($staff as $person): ?><tr><td><div class="person-cell"><span class="person-avatar"><?= e(strtoupper(substr($person['nama'], 0, 1))) ?></span><div><strong><?= e($person['nama']) ?></strong><small>ID #<?= e($person['id']) ?></small></div></div></td><td><span class="muted-cell">@<?= e($person['username']) ?></span></td><td><span class="staff-role <?= $person['role'] === 'admin' ? 'admin' : 'cashier' ?>"><?= $person['role'] === 'admin' ? 'Admin' : 'Kasir' ?></span></td><td><span class="muted-cell"><?= e(date('d M Y', strtotime($person['created_at']))) ?></span></td><td class="action-head"><?php if ($person['role'] === 'kasir'): ?><form method="post" onsubmit="return confirm('Hapus petugas ini? Akun tidak dapat digunakan lagi.')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$person['id'] ?>"><button class="btn danger sm" type="submit">Hapus</button></form><?php else: ?><span class="current-account">Akun utama</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</div>
<?php include 'includes/footer.php'; ?>
