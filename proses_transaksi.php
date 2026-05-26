<?php
// proses_transaksi.php

// 1. KONEKSI DATABASE & SESSION
require_once 'config.php';
session_start();

// Proteksi: Jika belum login, tendang balik ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. PROSES INPUT TRANSAKSI
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id  = $_SESSION['user_id'];
    $tipe     = $_POST['tipe']; // bernilai 'pemasukan' atau 'pengeluaran'
    $jumlah   = (float)$_POST['jumlah'];
    $kategori = htmlspecialchars(trim($_POST['kategori']));

    // Validasi data sederhana sebelum masuk database
    if (!empty($tipe) && $jumlah > 0 && !empty($kategori)) {
        try {
            // Masukkan data ke tabel transactions menggunakan prepared statements PDO
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, tipe, jumlah, kategori, tanggal) VALUES (:user_id, :tipe, :jumlah, :kategori, NOW())");
            
            $stmt->execute([
                'user_id'  => $user_id,
                'tipe'     => $tipe,
                'jumlah'   => $jumlah,
                'kategori' => $kategori
            ]);

            // Jika berhasil disimpan, kembalikan user ke halaman dashboard utama
            header("Location: dashboard.php");
            exit;

        } catch (PDOException $e) {
            // Jika ada error pada database, tampilkan pesan error demi kebutuhan debugging lomba
            die("Gagal menyimpan transaksi ke database: " . $e->getMessage());
        }
    } else {
        // Jika inputan tidak valid, kembalikan ke dashboard
        header("Location: dashboard.php");
        exit;
    }
} else {
    // Jika file diakses langsung tanpa POST method, kunci aksesnya
    header("Location: dashboard.php");
    exit;
}
?>