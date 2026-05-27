<?php
// proses_transaksi.php

require_once 'config.php';
session_start();

// Proteksi: Jika belum login, tendang balik ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id  = $_SESSION['user_id'];
    $tipe     = $_POST['tipe']; // 'pemasukan' atau 'pengeluaran'
    $jumlah   = (float)$_POST['jumlah'];
    $kategori = htmlspecialchars(trim($_POST['kategori']));

    if (!empty($tipe) && $jumlah > 0 && !empty($kategori)) {
        try {
            // ID tidak dimasukkan manual karena database TiDB sudah dikonfigurasi AUTO_INCREMENT
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, tipe, jumlah, kategori, tanggal) VALUES (:user_id, :tipe, :jumlah, :kategori, NOW())");
            
            $stmt->execute([
                'user_id'  => $user_id,
                'tipe'     => $tipe,
                'jumlah'   => $jumlah,
                'kategori' => $kategori
            ]);

            header("Location: /dashboard");
            exit;

        } catch (PDOException $e) {
            die("Gagal menyimpan transaksi ke database: " . $e->getMessage());
        }
    } else {
        header("Location: /dashboard");
        exit;
    }
} else {
    header("Location: /dashboard");
    exit;
}
?>