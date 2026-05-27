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
            // GENERATE ID TRANSAKSI UNIK SECARA MANUAL (Mengatasi kendala ketiadaan Auto Increment)
            $unique_trans_id = substr(md5(uniqid(rand(), true)), 0, 10);

            // Masukkan data ke tabel transactions dengan menyertakan parameter :id secara eksplisit
            $stmt = $pdo->prepare("INSERT INTO transactions (id, user_id, tipe, jumlah, kategori, tanggal) VALUES (:id, :user_id, :tipe, :jumlah, :kategori, NOW())");
            
            $stmt->execute([
                'id'       => $unique_trans_id,
                'user_id'  => $user_id,
                'tipe'     => $tipe,
                'jumlah'   => $jumlah,
                'kategori' => $kategori
            ]);

            // Jika berhasil disimpan, kembalikan user ke halaman dashboard utama
            header("Location: dashboard.php");
            exit;

        } catch (PDOException $e) {
            die("Gagal menyimpan transaksi ke database: " . $e->getMessage());
        }
    } else {
        header("Location: dashboard.php");
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}
?>