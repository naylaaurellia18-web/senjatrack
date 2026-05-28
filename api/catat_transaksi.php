<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catat Transaksi - SenjaTrack</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-senja { background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #f97316 100%); }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen pb-24">
    <div class="max-w-md mx-auto bg-white min-h-screen shadow-xl relative pb-4 flex flex-col">
        
        <!-- Header -->
        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-xl font-black text-indigo-950 tracking-tight flex items-center gap-2">
                <span>📝</span> Catat Transaksi Baru
            </h2>
            <p class="text-[11px] text-slate-400 mt-0.5">Catat aliran kas keuanganmu secara realtime.</p>
        </div>

        <!-- Form Transaksi -->
        <form action="proses_transaksi.php" method="POST" class="p-5 space-y-5 flex-1 text-xs">
            
            <!-- Jenis Aliran Kas dengan Peer-Checked Effect -->
            <div class="space-y-2">
                <label class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block">Jenis Aliran Kas</label>
                <div class="grid grid-cols-2 gap-3">
                    
                    <label class="relative cursor-pointer">
                        <input type="radio" name="tipe" value="pemasukan" required checked class="peer sr-only">
                        <div class="border border-slate-200 rounded-2xl p-4 flex flex-col items-center justify-center gap-1 bg-slate-50 text-slate-400 font-bold transition-all hover:bg-slate-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50/60 peer-checked:text-emerald-700">
                            <span class="text-lg">💰</span>
                            <span>Pemasukan</span>
                        </div>
                    </label>

                    <label class="relative cursor-pointer">
                        <input type="radio" name="tipe" value="pengeluaran" required class="peer sr-only">
                        <div class="border border-slate-200 rounded-2xl p-4 flex flex-col items-center justify-center gap-1 bg-slate-50 text-slate-400 font-bold transition-all hover:bg-slate-100 peer-checked:border-rose-500 peer-checked:bg-rose-50/60 peer-checked:text-rose-700">
                            <span class="text-lg">💸</span>
                            <span>Pengeluaran</span>
                        </div>
                    </label>

                </div>
            </div>

            <!-- Input Nominal -->
            <div class="space-y-1.5">
                <label Lifor="jumlah" class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block">Nominal Angka (Rp)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 font-extrabold text-slate-400 text-sm">Rp</span>
                    <input type="number" id="jumlah" name="jumlah" min="100" required 
                           class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white text-lg font-black tracking-tight text-indigo-950 transition-all" 
                           placeholder="0">
                </div>
            </div>

            <!-- Input Kategori -->
            <div class="space-y-1.5">
                <label class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block">Kategori Keperluan</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-slate-400">🏷️</span>
                    <input type="text" name="kategori" required 
                           class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-3.5 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white text-xs font-semibold transition-all text-slate-700" 
                           placeholder="Contoh: Belanja Skincare, Uang Saku Bulanan">
                </div>
            </div>

            <!-- Tombol Submit -->
            <div class="pt-4">
                <button type="submit" class="w-full gradient-senja text-white font-bold py-4 rounded-2xl hover:opacity-95 shadow-lg shadow-indigo-950/20 transform active:scale-[0.99] transition-all text-xs cursor-pointer">
                    Simpan & Update Saldo Dompet &rarr;
                </button>
            </div>
        </form>

        <?php include 'navbar.php'; ?>
    </div>
</body>
</html>