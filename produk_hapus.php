<?php
require 'config.php'; require_admin();
$id = (int)($_POST['id'] ?? 0);
if ($id) { $st = $pdo->prepare('DELETE FROM produk WHERE id_produk = ?'); $st->execute([$id]); flash('Produk berhasil dihapus.'); }
go('produk.php');
