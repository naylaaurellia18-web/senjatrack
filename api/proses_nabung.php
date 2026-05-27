<?php
// proses_nabung.php

// 1. KONEKSI DATABASE & SESSION
require_once 'config.php';
session_start();

// Proteksi: Jika belum login, tendang balik ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

// 2. PROSES UPDATE CELENGAN TARGET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal_id       = $_POST['goal_id'];
    $nominal_setor = (float)$_POST['nominal_setor'];
    $user_id       = $_SESSION['user_id'];

    if (!empty($goal_id) && $nominal_setor > 0) {
        try {
            // Mulai database transaction agar kedua query di bawah wajib sukses bersamaan (menghindari data corrupt)
            $pdo->beginTransaction();

            // QUERY 1: Update nominal yang terkumpul di tabel saving_goals milik user
            $stmt_goal = $pdo->prepare("UPDATE saving_goals SET nominal_terkumpul = nominal_terkumpul + :setor WHERE id = :id AND user_id = :user_id");
            $stmt_goal->execute([
                'setor'   => $nominal_setor,
                'id'      => $goal_id,
                'user_id' => $user_id
            ]);

            // Ambil nama target untuk dicatat di deskripsi aktivitas keuangan bulanan
            $stmt_info = $pdo->prepare("SELECT nama_target FROM saving_goals WHERE id = :id");
            $stmt_info->execute(['id' => $goal_id]);
            $goal_info = $stmt_info->fetch();
            $nama_target = $goal_info ? $goal_info['nama_target'] : 'Target';

            // QUERY 2: Otomatis catat ke tabel transactions sebagai aliran 'pengeluaran'
            // Langkah ini krusial agar sisa saldo dompet utama di dashboard ikut berkurang secara otomatis
            $keterangan_transaksi = "Alokasi Celengan: " . htmlspecialchars($nama_target);
            $stmt_tx = $pdo->prepare("INSERT INTO transactions (user_id, tipe, jumlah, kategori, tanggal) VALUES (:user_id, 'pengeluaran', :jumlah, :kategori, NOW())");
            $stmt_tx->execute([
                'user_id'  => $user_id,
                'jumlah'   => $nominal_setor,
                'kategori' => $keterangan_transaksi
            ]);

            // Jika kedua query di atas sukses tanpa kendala, kunci perubahan ke dalam database
            $pdo->commit();

            // Kembalikan ke halaman fitur plus dengan status sukses
            header("Location: /fitur_plus");
            exit;

        } catch (PDOException $e) {
            // Jika salah satu query gagal, batalkan semua perubahan data (rollback)
            $pdo->rollBack();
            die("Gagal memproses alokasi tabungan celengan: " . $e->getMessage());
        }
    } else {
        header("Location: /fitur_plus");
        exit;
    }
} else {
    header("Location: /fitur_plus");
    exit;
}
?>