<?php
require_once 'config.php'; 
session_start();

// Jika belum login, tendang balik ke rute login bersih Vercel
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];
$current_month = date('Y-m');

// 1. AGREGASI DATA FINANSIAL (Aman & Akurat memakai PDO)
// Total Pemasukan
$stmt_income = $pdo->prepare("SELECT SUM(jumlah) as total FROM transactions WHERE user_id = :user_id AND tipe = 'pemasukan'");
$stmt_income->execute(['user_id' => $user_id]);
$total_income = $stmt_income->fetch()['total'] ?? 0;

// Total Pengeluaran Bulan Ini
$stmt_expense = $pdo->prepare("SELECT SUM(jumlah) as total FROM transactions WHERE user_id = :user_id AND tipe = 'pengeluaran' AND DATE_FORMAT(tanggal, '%Y-%m') = :bulan_ini");
$stmt_expense->execute(['user_id' => $user_id, 'bulan_ini' => $current_month]);
$total_expense_month = $stmt_expense->fetch()['total'] ?? 0;

// Total Semua Pengeluaran (untuk hitung saldo bersih secara logis)
$stmt_all_expense = $pdo->prepare("SELECT SUM(jumlah) as total FROM transactions WHERE user_id = :user_id AND tipe = 'pengeluaran'");
$stmt_all_expense->execute(['user_id' => $user_id]);
$total_all_expense = $stmt_all_expense->fetch()['total'] ?? 0;

// Hitung Saldo Bersih Realtime Akun Terkait
$net_balance = $total_income - $total_all_expense;

// 2. QUERY HISTORI TRANSAKSI TERBARU (Limit 5 untuk optimasi dashboard)
$stmt_tx = $pdo->prepare("SELECT * FROM transactions WHERE user_id = :user_id ORDER BY tanggal DESC, id DESC LIMIT 5");
$stmt_tx->execute(['user_id' => $user_id]);
$recent_transactions = $stmt_tx->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SenjaTrack Finansial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-senja { background: linear-gradient(135deg, #f97316 0%, #4f46e5 100%); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <nav class="bg-indigo-950 text-white shadow-xl px-6 py-4 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <span class="text-2xl">🌅</span>
            <span class="font-extrabold text-base tracking-tight">SenjaTrack<span class="text-orange-400 font-medium text-xs ml-1">Finansial</span></span>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <p class="text-xs font-bold text-slate-100"><?= htmlspecialchars($user_nama) ?></p>
                <p class="text-[10px] text-slate-400">ID Member #<?= sprintf('%04d', $user_id) ?></p>
            </div>
            <a href="/logout" class="bg-white/10 hover:bg-rose-600 hover:text-white px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5">
                Keluar 🚪
            </a>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-8">
        
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
            <div>
                <h2 class="text-xl font-extrabold text-indigo-950 tracking-tight">Halo, Beraktivitas Kembali 👋</h2>
                <p class="text-xs text-slate-400 mt-0.5">Berikut ikhtisar ringkas status arus kas keuangan kamu hari ini.</p>
            </div>
            <div class="text-xs text-slate-500 bg-slate-50 px-3 py-2 rounded-xl border border-slate-100 font-medium self-start sm:self-center">
                📅 Tanggal: <span class="font-bold text-indigo-950"><?= date('d F Y') ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-md shadow-indigo-950/5 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-50 rounded-bl-full -z-0 opacity-40 transition-all group-hover:scale-110"></div>
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2 relative z-10">Total Saldo Bersih</p>
                <h3 class="text-xl font-black text-indigo-950 tracking-tight relative z-10">
                    Rp <?= number_format($net_balance, 0, ',', '.') ?>
                </h3>
                <div class="mt-3 flex items-center gap-1.5 text-[10px] text-slate-500 font-medium relative z-10">
                    <span class="text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded font-bold">Safe</span> Akumulasi laba berjalan berjalan.
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-md shadow-indigo-950/5 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-bl-full -z-0 opacity-40 transition-all group-hover:scale-110"></div>
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2 relative z-10">Semua Pemasukan</p>
                <h3 class="text-xl font-black text-emerald-600 tracking-tight relative z-10">
                    Rp <?= number_format($total_income, 0, ',', '.') ?>
                </h3>
                <div class="mt-3 flex items-center gap-1.5 text-[10px] text-slate-500 font-medium relative z-10">
                    <span class="text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded font-bold">IN</span> Dana masuk yang terekam sistem.
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-md shadow-indigo-950/5 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-rose-50 rounded-bl-full -z-0 opacity-40 transition-all group-hover:scale-110"></div>
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2 relative z-10">Pengeluaran Bulan Ini</p>
                <h3 class="text-xl font-black text-rose-600 tracking-tight relative z-10">
                    Rp <?= number_format($total_expense_month, 0, ',', '.') ?>
                </h3>
                <div class="mt-3 flex items-center gap-1.5 text-[10px] text-slate-500 font-medium relative z-10">
                    <span class="text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded font-bold">OUT</span> Konsumsi operasional s.d saat ini.
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm lg:col-span-2 self-start">
                <div class="border-b border-slate-100 pb-3 mb-4">
                    <h4 class="text-xs font-extrabold text-indigo-950 uppercase tracking-wider">Catat Mutasi Baru</h4>
                    <p class="text-[10px] text-slate-400 mt-0.5">Tambahkan arus masuk/keluar ke database</p>
                </div>

                <form action="/proses_transaksi" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tipe Mutasi</label>
                        <select name="tipe" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-orange-400">
                            <option value="pemasukan">📈 Pemasukan (Uang Masuk)</option>
                            <option value="pengeluaran">📉 Pengeluaran (Uang Keluar)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nominal (Rupiah)</label>
                        <input type="number" name="jumlah" placeholder="Contoh: 50000" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-orange-400">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Kategori</label>
                        <input type="text" name="kategori" placeholder="Gaji, Makanan, Transport, dll" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-orange-400">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Transaksi</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-orange-400">
                    </div>

                    <button type="submit" class="w-full text-[11px] font-extrabold text-white gradient-senja py-3 rounded-xl transition-all shadow-md shadow-indigo-900/10 cursor-pointer">
                        Simpan Transaksi Realtime
                    </button>
                </form>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm lg:col-span-3 flex flex-col justify-between min-h-[400px]">
                <div>
                    <div class="border-b border-slate-100 pb-3 mb-4 flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-extrabold text-indigo-950 uppercase tracking-wider">Histori Jurnal Keuangan</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">Menampilkan maksimal 5 riwayat transaksi terakhir</p>
                        </div>
                        <span class="text-xs">📑</span>
                    </div>

                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                                <th class="pb-2">Waktu</th>
                                <th class="pb-2">Kategori</th>
                                <th class="pb-2 text-right">Jumlah Mutasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if (empty($recent_transactions)): ?>
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-slate-400 font-medium">
                                        Belem ada rekaman transaksi. Silakan input mutasi kas pertamamu!
                                    </td>
                                endforeach; ?>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $tx): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-3.5 text-slate-500 font-medium">
                                            <?= date('d M Y', strtotime($tx['tanggal'])) ?>
                                        </td>
                                        <td class="py-3.5 font-semibold text-slate-700">
                                            <?= htmlspecialchars($tx['kategori']) ?>
                                        </td>
                                        <td class="py-3.5 text-right font-extrabold text-sm <?= $tx['tipe'] === 'pemasukan' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                            <?= $tx['tipe'] === 'pemasukan' ? '+' : '-' ?> Rp <?= number_format($tx['jumlah'], 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pt-2 border-t border-slate-100 text-right">
                    <span class="text-[10px] text-slate-400 bg-slate-50 px-2 py-1 rounded border border-slate-100">
                        ✓ Sinkronisasi Database Realtime Terjamin
                    </span>
                </div>
            </div>

        </div>
    </main>

    <footer class="bg-indigo-950 text-slate-500 text-[10px] py-4 text-center border-t border-indigo-900/40 mt-12">
        <p>&copy; 2026 SenjaTrack Workspace System &bull; Panel Core v4.0</p>
    </footer>

</body>
</html>