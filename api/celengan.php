<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

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
    <style>
        .gradient-senja { background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #f97316 100%); }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen pb-24">
    <div class="max-w-md mx-auto bg-white min-h-screen shadow-xl relative pb-4 flex flex-col">
        
        <!-- Header -->
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h2 class="text-xl font-black text-indigo-950 tracking-tight flex items-center gap-2">
                    <span>🎯</span> Celengan Target Impian
                </h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Kelola alokasi tabungan berjangka kamu.</p>
            </div>
            <button onclick="document.getElementById('formTargetModal').classList.remove('hidden')" class="text-[10px] bg-orange-500 hover:bg-orange-600 text-white font-extrabold px-3 py-2.5 rounded-xl shadow-md shadow-orange-500/10 transition-all cursor-pointer transform active:scale-95">
                + Pasang Target
            </button>
        </div>

        <!-- List Celengan -->
        <div class="p-5 space-y-4 flex-1 text-xs">
            <?php if (empty($saving_goals)): ?>
                <div class="text-center py-16 space-y-2">
                    <span class="text-4xl block">🐷</span>
                    <p class="text-slate-400 italic">Belum ada target impian yang dipasang.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($saving_goals as $goal): 
                $persen = $goal['nominal_target'] > 0 ? round(($goal['nominal_terkumpul'] / $goal['nominal_target']) * 100) : 0;
                $persen_lebar = $persen > 100 ? 100 : $persen; // Mencegah bar jebol kalau tabungan over-target
            ?>
                <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm space-y-3.5">
                    
                    <!-- Judul & Persentase -->
                    <div class="flex justify-between items-center font-bold">
                        <span class="text-sm text-indigo-950 font-black tracking-tight"><?= htmlspecialchars($goal['nama_target']) ?></span>
                        <span class="text-orange-500 font-black text-xs bg-orange-50 px-2 py-0.5 rounded-md border border-orange-100"><?= $persen ?>%</span>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden border border-slate-200/20">
                        <div class="gradient-senja h-2.5 rounded-full transition-all duration-500" style="width: <?= $persen_lebar ?>%"></div>
                    </div>
                    
                    <!-- Detail Informasi Angka -->
                    <div class="flex justify-between text-[10px] text-slate-400 bg-slate-50 p-2 rounded-xl border border-slate-100">
                        <p>Terkumpul: <strong class="text-indigo-950">Rp <?= number_format($goal['nominal_terkumpul'], 0, ',', '.') ?></strong></p>
                        <p>Target: <strong class="text-slate-600">Rp <?= number_format($goal['nominal_target'], 0, ',', '.') ?></strong></p>
                    </div>
                    
                    <!-- Form Setor Kilat -->
                    <form action="proses_nabung.php" method="POST" class="flex gap-2 pt-1">
                        <input type="hidden" name="goal_id" value="<?= $goal['id'] ?>">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 font-bold text-[10px]">Rp</span>
                            <input type="number" name="nominal_setor" required placeholder="Masukkan nominal isi celengan" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pl-7 pr-3 text-[11px] focus:outline-none focus:border-orange-400 focus:bg-white font-semibold text-slate-700 transition-all">
                        </div>
                        <button type="submit" class="bg-indigo-950 hover:bg-indigo-900 text-white font-bold px-4 rounded-xl text-[11px] transition-all shadow shadow-indigo-950/10 cursor-pointer shrink-0">
                            Setor
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- MODAL POPUP FORM TARGET -->
        <div id="formTargetModal" class="hidden fixed inset-0 bg-indigo-950/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-3xl p-5 w-full max-w-xs shadow-2xl border border-slate-100 space-y-4 transform scale-100 transition-all">
                <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                    <h3 class="font-black text-indigo-950 text-sm flex items-center gap-1.5"><span>🎯</span> Pasang Target</h3>
                    <button onclick="document.getElementById('formTargetModal').classList.add('hidden')" class="text-slate-400 font-bold hover:text-slate-600 cursor-pointer w-6 h-6 flex items-center justify-center bg-slate-50 rounded-full">&times;</button>
                </div>
                
                <form action="celengan.php" method="POST" class="space-y-3.5 text-xs">
                    <input type="hidden" name="action" value="tambah_target">
                    
                    <div>
                        <label class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block mb-1.5">Nama Barang / Impian</label>
                        <input type="text" name="nama_target" required placeholder="Contoh: Beli Kamera Vlog, Sepatu" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white font-medium transition-all text-slate-700">
                    </div>
                    
                    <div>
                        <label class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block mb-1.5">Nominal Target (Rp)</label>
                        <input type="number" name="nominal_target" required placeholder="Contoh: 3500000" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white font-medium transition-all text-slate-700">
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit" class="w-full gradient-senja text-white font-bold py-3 rounded-xl shadow-md transition-all cursor-pointer hover:opacity-95 text-xs">
                            Simpan Rencana 🚀
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php include 'navbar.php'; ?>
    </div>
</body>
</html>