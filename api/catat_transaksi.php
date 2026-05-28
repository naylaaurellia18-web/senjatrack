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
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen pb-24">
    <div class="max-w-md mx-auto bg-white min-h-screen shadow-xl relative pb-4 flex flex-col">
        <div class="p-5 border-b border-slate-100">
            <h2 class="text-xl font-black text-indigo-950 tracking-tight">📝 Catat Transaksi Baru</h2>
            <p class="text-[11px] text-slate-400 mt-0.5">Catat aliran kas keuanganmu secara realtime.</p>
        </div>

        <form action="proses_transaksi.php" method="POST" class="p-5 space-y-5 flex-1 text-xs">
            <div class="space-y-1.5">
                <label class="font-bold text-slate-600 block">Jenis Aliran Kas</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 cursor-pointer bg-slate-50 hover:bg-white transition-all">
                        <input type="radio" name="tipe" value="pemasukan" required checked class="accent-orange-500">
                        <span class="font-bold text-emerald-600">Pemasukan 💰</span>
                    </label>
                    <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 cursor-pointer bg-slate-50 hover:bg-white transition-all">
                        <input type="radio" name="tipe" value="pengeluaran" required class="accent-orange-500">
                        <span class="font-bold text-rose-600">Pengeluaran 💸</span>
                    </label>
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="jumlah" class="font-bold text-slate-600 block">Nominal Angka (Rp)</label>
                <input type="number" id="jumlah" name="jumlah" min="100" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3.5 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white text-base font-black tracking-tight transition-all" placeholder="0">
            </div>

            <div class="space-y-1.5">
                <label class="font-bold text-slate-600 block">Kategori Keperluan</label>
                <input type="text" name="kategori" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white text-xs font-semibold transition-all" placeholder="Contoh: Belanja Skincare, Uang Saku Bulanan">
            </div>

            <button type="submit" class="w-full bg-indigo-950 text-white font-bold py-3.5 rounded-xl hover:opacity-90 shadow-md transition-all text-xs cursor-pointer mt-6">
                Simpan & Update Saldo Dompet &rarr;
            </button>
        </form>

        <?php include 'navbar.php'; ?>
    </div>
</body>
</html>