<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// Logika Tambah Celengan Baru Langsung di File Ini
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_target') {
    $nama_target = htmlspecialchars(trim($_POST['nama_target']));
    $nominal_target = (float)($_POST['nominal_target'] ?? 0);
    if (!empty($nama_target) && $nominal_target > 0) {
        $stmt = $pdo->prepare("INSERT INTO saving_goals (user_id, nama_target, nominal_target, nominal_terkumpul) VALUES (:user_id, :nama, :target, 0)");
        $stmt->execute(['user_id' => $user_id, 'nama' => $nama_target, 'target' => $nominal_target]);
        header("Location: celengan.php");
        exit;
    }
}

$stmt_sg = $pdo->prepare("SELECT * FROM saving_goals WHERE user_id = :uid ORDER BY id DESC");
$stmt_sg->execute(['uid' => $user_id]);
$saving_goals = $stmt_sg->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Celengan Target - SenjaTrack</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen pb-24">
    <div class="max-w-md mx-auto bg-white min-h-screen shadow-xl relative pb-4 flex flex-col">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-black text-indigo-950 tracking-tight">🎯 Celengan Target Impian</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Kelola alokasi tabungan berjangka kamu.</p>
            </div>
            <button onclick="document.getElementById('formTargetModal').classList.remove('hidden')" class="text-[10px] bg-orange-500 hover:bg-orange-600 text-white font-bold px-3 py-2 rounded-lg transition-all cursor-pointer">
                + Pasang Target
            </button>
        </div>

        <div class="p-5 space-y-4 flex-1 text-xs">
            <?php if (empty($saving_goals)): ?>
                <p class="text-slate-400 italic text-center py-12">Belum ada target impian yang dipasang.</p>
            <?php endif; ?>

            <?php foreach ($saving_goals as $goal): 
                $persen = $goal['nominal_target'] > 0 ? round(($goal['nominal_terkumpul'] / $goal['nominal_target']) * 100) : 0;
            ?>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-3">
                    <div class="flex justify-between font-bold text-slate-700">
                        <span class="text-sm text-indigo-950 font-black"><?= htmlspecialchars($goal['nama_target']) ?></span>
                        <span class="text-orange-500 font-black text-sm"><?= $persen ?>%</span>
                    </div>
                    <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-950 to-orange-500 h-2.5 rounded-full" style="width: <?= $persen ?>%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400">Terkumpul: <strong class="text-indigo-950">Rp <?= number_format($goal['nominal_terkumpul'], 0, ',', '.') ?></strong> / Rp <?= number_format($goal['nominal_target'], 0, ',', '.') ?></p>
                    
                    <form action="proses_nabung.php" method="POST" class="flex gap-1.5 pt-1">
                        <input type="hidden" name="goal_id" value="<?= $goal['id'] ?>">
                        <input type="number" name="nominal_setor" required placeholder="Masukkan nominal isi celengan (Rp)" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-[10px] focus:outline-none focus:border-orange-400 font-semibold">
                        <button type="submit" class="bg-indigo-950 hover:bg-indigo-900 text-white font-bold px-4 rounded-lg text-[10px] transition cursor-pointer">Setor</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- MODAL POPUP FORM TARGET -->
        <div id="formTargetModal" class="hidden fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl p-5 w-full max-w-xs shadow-2xl border border-slate-100 space-y-4">
                <div class="border-b pb-2 flex justify-between items-center">
                    <h3 class="font-bold text-indigo-950 text-xs">🎯 Pasang Target Tabungan</h3>
                    <button onclick="document.getElementById('formTargetModal').classList.add('hidden')" class="text-slate-400 font-bold hover:text-slate-600 cursor-pointer">✕</button>
                </div>
                <form action="celengan.php" method="POST" class="space-y-3 text-xs">
                    <input type="hidden" name="action" value="tambah_target">
                    <div>
                        <label class="font-bold text-slate-600 block mb-1">Nama Barang / Impian</label>
                        <input type="text" name="nama_target" required placeholder="Contoh: Beli Kamera Vlog" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                    </div>
                    <div>
                        <label class="font-bold text-slate-600 block mb-1">Nominal Target (Rp)</label>
                        <input type="number" name="nominal_target" required placeholder="Contoh: 3500000" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                    </div>
                    <button type="submit" class="w-full bg-indigo-950 text-white font-bold py-2.5 rounded-xl transition cursor-pointer">Simpan Rencana 🚀</button>
                </form>
            </div>
        </div>

        <?php include 'navbar.php'; ?>
    </div>
</body>
</html>