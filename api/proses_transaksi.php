<?php
// proses_transaksi.php

// 1. KONEKSI DATABASE & SESSION
require_once 'config.php';
session_start();

// Proteksi: Jika belum login, tendang balik ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
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
            // 🔥 TRIK PENYELAMAT: Membuat ID acak berupa string angka unik secara manual
            // Ini membebaskan kita dari ketergantungan AUTO_INCREMENT di TiDB Cloud!
            $manual_id = rand(100000, 999999) . rand(1000, 9999);

            // Masukkan data ke tabel dengan menyertakan parameter :id secara eksplisit
            $stmt = $pdo->prepare("INSERT INTO transactions (id, user_id, tipe, jumlah, kategori, tanggal) VALUES (:id, :user_id, :tipe, :jumlah, :kategori, NOW())");
            
            $stmt->execute([
                'id'       => $manual_id,
                'user_id'  => $user_id,
                'tipe'     => $tipe,
                'jumlah'   => $jumlah,
                'kategori' => $kategori
            ]);

            // Jika berhasil disimpan, kembalikan user ke halaman dashboard utama
            header("Location: /dashboard");
            exit;

        } catch (PDOException $e) {
            // Menampilkan pesan error detail untuk kebutuhan debugging
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