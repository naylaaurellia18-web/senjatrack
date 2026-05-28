<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];

// Hitung Statistik Data Pengguna untuk Ditampilkan di Profil
$stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM transactions WHERE user_id = :uid");
$stmt_count->execute(['uid' => $user_id]);
$total_transaksi = $stmt_count->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - SenjaTrack</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-senja { background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #f97316 100%); }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen pb-24">
    <div class="max-w-md mx-auto bg-white min-h-screen shadow-xl relative pb-4 flex flex-col">
        <div class="p-5 border-b border-slate-100">
            <h2 class="text-xl font-black text-indigo-950 tracking-tight">👤 Profil Akun</h2>
            <p class="text-[11px] text-slate-400 mt-0.5">Informasi keanggotaan dan pengaturan ekosistem SenjaTrack.</p>
        </div>

        <div class="p-5 flex-1 space-y-6 text-xs">
            <!-- Kartu Profil User -->
            <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <div class="w-14 h-14 gradient-senja rounded-full flex items-center justify-center text-white text-xl font-black shadow-md shrink-0">
                    <?= strtoupper(mb_substr($user_nama, 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <h3 class="font-black text-base text-indigo-950 truncate"><?= htmlspecialchars($user_nama) ?></h3>
                    <p class="text-[10px] text-slate-400 font-medium">Mahasiswa / Content Creator</p>
                    <span class="inline-block mt-1 text-[9px] bg-orange-100 text-orange-700 font-bold px-2 py-0.5 rounded-full">✓ Verified Account</span>
                </div>
            </div>

            <!-- Kartu Statistik Ringkas -->
            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="bg-slate-50/60 p-3 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-bold text-slate-400 uppercase">Total Entri</p>
                    <p class="font-black text-indigo-950 text-sm mt-0.5"><?= $total_transaksi ?> Log</p>
                </div>
                <div class="bg-slate-50/60 p-3 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-bold text-slate-400 uppercase">Tipe Server</p>
                    <p class="font-black text-indigo-950 text-sm mt-0.5">Production v4.0</p>
                </div>
            </div>

            <!-- List Opsi Navigasi Sistem Keamanan -->
            <div class="space-y-1">
                <div class="flex justify-between items-center py-3.5 px-2 border-b border-slate-100 hover:bg-slate-50 rounded-lg cursor-pointer transition-colors">
                    <div class="flex items-center gap-3 text-slate-700 font-bold">
                        <i class="fa-solid fa-shield-halved text-slate-400 text-sm w-4"></i> Ubah Password Akun
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
                </div>
                <div class="flex justify-between items-center py-3.5 px-2 border-b border-slate-100 hover:bg-slate-50 rounded-lg cursor-pointer transition-colors">
                    <div class="flex items-center gap-3 text-slate-700 font-bold">
                        <i class="fa-solid fa-bell text-slate-400 text-sm w-4"></i> Pengingat Alarm Harian
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
                </div>
                
                <div class="pt-6">
                    <a href="logout.php" class="flex items-center justify-center gap-2 bg-rose-50 text-rose-600 font-bold py-3 rounded-xl border border-rose-100 text-xs no-underline hover:bg-rose-100 transition-colors">
                        <i class="fa-solid fa-right-from-bracket"></i> Keluar dari Workspace
                    </a>
                </div>
            </div>
        </div>

        <?php include 'navbar.php'; ?>
    </div>
</body>
</html>