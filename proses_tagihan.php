<?php
// proses_tagihan.php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    if ($_GET['action'] === 'lunas') {
        // 1. Ambil info tagihan untuk dicatat ke transaksi
        $stmt_info = $pdo->prepare("SELECT * FROM bills WHERE id = :id AND user_id = :user_id");
        $stmt_info->execute(['id' => $id, 'user_id' => $user_id]);
        $bill = $stmt_info->fetch();

        if ($bill) {
            $pdo->beginTransaction();
            // Ubah status tagihan jadi lunas
            $stmt_update = $pdo->prepare("UPDATE bills SET status_bayar = 'lunas' WHERE id = :id");
            $stmt_update->execute(['id' => $id]);

            // Otomatis kurangi saldo dengan mencatatnya di tabel transaksi pengeluaran
            $stmt_tx = $pdo->prepare("INSERT INTO transactions (user_id, tipe, jumlah, kategori, tanggal) VALUES (:user_id, 'pengeluaran', :jumlah, :kategori, NOW())");
            $stmt_tx->execute([
                'user_id' => $user_id,
                'jumlah' => $bill['nominal'],
                'kategori' => "Bayar Tagihan: " . $bill['nama_tagihan']
            ]);
            $pdo->commit();
        }
    } elseif ($_GET['action'] === 'hapus') {
        $stmt_del = $pdo->prepare("DELETE FROM bills WHERE id = :id AND user_id = :user_id");
        $stmt_del->execute(['id' => $id, 'user_id' => $user_id]);
    }
}

header("Location: fitur_plus.php");
exit;