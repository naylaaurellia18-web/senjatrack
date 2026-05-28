<?php
// proses_transaksi.php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id  = $_SESSION['user_id'];
    $tipe     = $_POST['tipe'];
    $jumlah   = (float)$_POST['jumlah'];
    $kategori = htmlspecialchars(trim($_POST['kategori']));
    // Gunakan tanggal dari form jika ada, fallback ke hari ini
    $tanggal  = !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d H:i:s');

    if (!empty($tipe) && $jumlah > 0 && !empty($kategori)) {
        try {
            // Biarkan AUTO_INCREMENT bekerja — JANGAN isi id manual
            // (rand() berbahaya karena bisa tabrakan/duplicate key)
            $stmt = $pdo->prepare(
                "INSERT INTO transactions (user_id, tipe, jumlah, kategori, tanggal)
                 VALUES (:user_id, :tipe, :jumlah, :kategori, :tanggal)"
            );
            $stmt->execute([
                'user_id'  => $user_id,
                'tipe'     => $tipe,
                'jumlah'   => $jumlah,
                'kategori' => $kategori,
                'tanggal'  => $tanggal,
            ]);
            header("Location: /dashboard");
            exit;
        } catch (PDOException $e) {
            die("Gagal menyimpan transaksi: " . $e->getMessage());
        }
    } else {
        header("Location: /dashboard");
        exit;
    }
} else {
    header("Location: /dashboard");
    exit;
}