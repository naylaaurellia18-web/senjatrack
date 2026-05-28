<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// Tambah Rencana Belanja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_belanja') {
    $nama_barang = htmlspecialchars(trim($_POST['nama_barang']));
    $estimasi_harga = (float)$_POST['estimasi_harga'];
    if (!empty($nama_barang) && $estimasi_harga > 0) {
        $stmt = $pdo->prepare("INSERT INTO shopping_plans (user_id, nama_barang, estimasi_harga, status_beli) VALUES (:user_id, :nama, :harga, 'belum')");
        $stmt->execute(['user_id' => $user_id, 'nama' => $nama_barang, 'harga' => $estimasi_harga]);
        header("Location: rencana_belanja.php");
        exit;
    }
}

// Proses Hapus & Beli
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'check') {
        $stmt_info = $pdo->prepare("SELECT * FROM shopping_plans WHERE id = :id AND user_id = :uid AND status_beli = 'belum'");
        $stmt_info->execute(['id' => $id, 'uid' => $user_id]);
        $item = $stmt_info->fetch();
        if ($item) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE shopping_plans SET status_beli = 'sudah' WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $user_id]);
            $pdo->prepare("INSERT INTO transactions (user_id, tipe, jumlah, kategori, tanggal) VALUES (:uid, 'pengeluaran', :jumlah, :kategori, NOW())")->execute([
                'uid' => $user_id, 'jumlah' => $item['estimasi_harga'], 'kategori' => "Belanja: " . $item['nama_barang']
            ]);
            $pdo->commit();
        }
    } elseif ($_GET['action'] === 'hapus') {
        $pdo->prepare("DELETE FROM shopping_plans WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $user_id]);
    }
    header("Location: rencana_belanja.php");
    exit;
}

$shopping_plans = $pdo->prepare("SELECT * FROM shopping_plans WHERE user_id = :uid ORDER BY status_beli ASC, id DESC");
$shopping_plans->execute(['uid' => $user_id]);
$plans = $shopping_plans->fetchAll();

// Total Estimasi yang Belum Dibeli
$stmt_total = $pdo->prepare("SELECT SUM(estimasi_harga) as total FROM shopping_plans WHERE user_id = :uid AND status_beli = 'belum'");
$stmt_total->execute(['uid' => $user_id]);
$estimasi_total = $stmt_total->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rencana Belanja - SenjaTrack</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen pb-24">
    <div class="max-w-md mx-auto bg-white min-h-screen shadow-xl relative pb-4 flex flex-col">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-black text-indigo-950 tracking-tight">🛒 Rencana Belanja</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Susun daftar belanja kebutuhan content creator.</p>
            </div>
            <button onclick="document.getElementById('formBelanjaModal').classList.remove('hidden')" class="text-[10px] bg-indigo-950 text-white font-bold px-3 py-2 rounded-lg cursor-pointer">
                + Item
            </button>
        </div>

        <div class="p-5 space-y-4 flex-1 text-xs">
            <div class="bg-orange-50 p-3.5 border border-orange-100 rounded-xl flex justify-between items-center">
                <span class="font-bold text-slate-600">Total Sisa Rencana Belanja:</span>
                <span class="font-black text-orange-600 text-sm">Rp <?= number_format($estimasi_total, 0, ',', '.') ?></span>
            </div>

            <div class="space-y-2">
                <?php if (empty($plans)): ?>
                    <p class="text-slate-400 italic text-center py-12">Daftar rencana belanja masih kosong.</p>
                <?php endif; ?>

                <?php foreach ($plans as $plan): ?>
                    <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex justify-between items-center">
                        <div>
                            <p class="font-bold text-xs <?= $plan['status_beli'] === 'sudah' ? 'line-through text-slate-300' : 'text-slate-700' ?>">
                                <?= htmlspecialchars($plan['nama_barang']) ?>
                            </p>
                            <p class="text-[10px] font-extrabold text-slate-400">Rp <?= number_format($plan['estimasi_harga'], 0, ',', '.') ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($plan['status_beli'] === 'belum'): ?>
                                <a href="rencana_belanja.php?action=check&id=<?= $plan['id'] ?>" class="bg-emerald-600 text-white px-2.5 py-1 rounded text-[10px] font-bold no-underline">Selesai</a>
                            <?php else: ?>
                                <span class="bg-emerald-50 text-emerald-600 font-bold border border-emerald-100 px-2 py-0.5 rounded text-[9px]">Dibeli ✓</span>
                            <?php endif; ?>
                            <a href="rencana_belanja.php?action=hapus&id=<?= $plan['id'] ?>" onclick="return confirm('Hapus item?')" class="text-slate-400 text-xs px-1 hover:text-rose-600">✕</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- MODAL POPUP ADD ITEM -->
        <div id="formBelanjaModal" class="hidden fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl p-5 w-full max-w-xs shadow-2xl border border-slate-100 space-y-4">
                <div class="border-b pb-2 flex justify-between items-center">
                    <h3 class="font-bold text-indigo-950 text-xs">🛒 Tambah Daftar Rencana</h3>
                    <button onclick="document.getElementById('formBelanjaModal').classList.add('hidden')" class="text-slate-400 font-bold hover:text-slate-600">✕</button>
                </div>
                <form action="rencana_belanja.php" method="POST" class="space-y-3 text-xs">
                    <input type="hidden" name="action" value="tambah_belanja">
                    <div>
                        <label class="font-bold text-slate-600 block mb-1">Nama Barang</label>
                        <input type="text" name="nama_barang" required placeholder="Contoh: Ringlight + Tripod" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                    </div>
                    <div>
                        <label class="font-bold text-slate-600 block mb-1">Estimasi Harga (Rp)</label>
                        <input type="number" name="estimasi_harga" required placeholder="Contoh: 150000" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                    </div>
                    <button type="submit" class="w-full bg-indigo-950 text-white font-bold py-2.5 rounded-xl transition cursor-pointer">Masukkan Rencana ✨</button>
                </form>
            </div>
        </div>

        <?php include 'navbar.php'; ?>
    </div>
</body>
</html>