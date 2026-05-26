<?php
require_once 'config.php'; 
session_start();

// Proteksi Halaman: Jika belum login, tendang balik ke login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

// Saldo Aktif Saat Ini = Total Pemasukan - Semua Pengeluaran
$saldo_aktif = $total_income - $total_all_expense;

// 2. AMBIL 5 AKTIVITAS TRANSAKSI TERAKHIR
$stmt_recent = $pdo->prepare("SELECT kategori, tipe, jumlah, tanggal FROM transactions WHERE user_id = :user_id ORDER BY tanggal DESC LIMIT 5");
$stmt_recent->execute(['user_id' => $user_id]);
$recent_transactions = $stmt_recent->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utama - SenjaTrack</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        .gradient-senja {
            background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #f97316 100%);
        }
    </style>
</head>
<body class="bg-amber-50/40 text-slate-800 font-sans min-h-screen flex flex-col justify-between">

    <nav class="bg-indigo-950 text-white px-6 py-4 shadow-md sticky top-0 z-50">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="text-2xl">🌅</span>
                <span class="font-bold text-xl tracking-wider text-orange-400">Senja<span class="text-white">Track</span></span>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <span class="bg-white/10 px-3 py-1.5 rounded-lg border border-white/10 hidden sm:inline">
                    👋 Halo, <span class="text-orange-300"><?= htmlspecialchars($user_nama) ?></span>
                </span>
                <a href="logout.php" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl transition-all shadow-md">
                    Keluar Keluar 🚪
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-6xl w-full mx-auto p-4 sm:p-6 space-y-6">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-indigo-950">Workspace Finansial Anda</h1>
                <p class="text-xs text-slate-400">Kelola uang saku kuliah harian dengan presisi otomatis.</p>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <a href="dashboard.php" class="flex-1 sm:flex-none text-center text-xs bg-orange-500 text-white font-bold px-4 py-2.5 rounded-xl shadow-md transition-all">
                    🏠 Dashboard Utama
                </a>
                <a href="fitur_plus.php" class="flex-1 sm:flex-none text-center text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-950 font-bold px-4 py-2.5 rounded-xl border border-indigo-100 transition-all">
                    ✨ Fitur Pro (Nabung & Struk)
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
            <div class="gradient-senja text-white p-6 rounded-2xl shadow-lg relative overflow-hidden flex flex-col justify-between h-32">
                <div class="absolute -right-6 -bottom-6 text-7xl opacity-10">💰</div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-orange-200 block">Sisa Saldo Aktif Dompet</span>
                <h2 class="text-2xl font-extrabold tracking-tight">
                    Rp <?= number_format($saldo_aktif, 0, ',', '.') ?>
                </h2>
                <span class="text-[10px] text-slate-300 italic">*Siap dialokasikan kapan saja</span>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between h-32 relative">
                <div class="absolute right-4 top-4 w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600 text-sm">📈</div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Pemasukan Kumulatif</span>
                <h2 class="text-2xl font-extrabold text-emerald-600 tracking-tight">
                    Rp <?= number_format($total_income, 0, ',', '.') ?>
                </h2>
                <span class="text-[10px] text-slate-400">Semua dana masuk terkumpul</span>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between h-32 relative">
                <div class="absolute right-4 top-4 w-8 h-8 bg-rose-50 rounded-lg flex items-center justify-center text-rose-600 text-sm">📉</div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Pengeluaran Bulan Ini (<?= date('M') ?>)</span>
                <h2 class="text-2xl font-extrabold text-rose-600 tracking-tight">
                    Rp <?= number_format($total_expense_month, 0, ',', '.') ?>
                </h2>
                <span class="text-[10px] text-slate-400">Total belanja & nongkrong bulanan</span>
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
            
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm md:col-span-2 space-y-4">
                <div class="border-b border-slate-100 pb-2">
                    <h3 class="font-bold text-sm text-indigo-950 flex items-center gap-1.5">⚡ Catat Transaksi Cepat</h3>
                    <p class="text-[11px] text-slate-400">Data otomatis terhubung ke kalkulator saldo utama.</p>
                </div>

                <form action="proses_transaksi.php" method="POST" class="space-y-4 text-xs">
                    
                    <div class="space-y-1">
                        <label class="font-bold text-slate-600 block">Jenis Aliran Kas</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="radio" name="tipe" value="pemasukan" required checked class="accent-orange-500">
                                <span class="font-semibold text-emerald-600">Pemasukan 💰</span>
                            </label>
                            <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="radio" name="tipe" value="pengeluaran" required class="accent-orange-500">
                                <span class="font-semibold text-rose-600">Pengeluaran 💸</span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="jumlah" class="font-bold text-slate-600 block">Nominal Uang (Rp)</label>
                        <input type="number" id="jumlah" name="jumlah" min="100" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white text-sm font-semibold tracking-wide transition-all" 
                               placeholder="Contoh: 50000">
                    </div>

                    <div class="space-y-1">
                        <label for="kategori" class="font-bold text-slate-600 block">Kategori / Keperluan</label>
                        <input type="text" id="kategori" name="kategori" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white transition-all" 
                               placeholder="Contoh: Kiriman Bulanan, Bayar Kos, Makan Malam">
                    </div>

                    <button type="submit" 
                            class="w-full gradient-senja text-white font-bold py-3 rounded-xl hover:opacity-90 shadow-md transform hover:-translate-y-0.5 cursor-pointer transition-all">
                        Simpan Transaksi Ke Database &rarr;
                    </button>
                </form>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm md:col-span-3 space-y-3 flex flex-col justify-between">
                <div class="space-y-1">
                    <h3 class="font-bold text-sm text-indigo-950 flex items-center gap-1.5">📜 5 Aktivitas Transaksi Terakhir</h3>
                    <p class="text-[11px] text-slate-400">Riwayat pengeluaran atau pemasukan yang baru saja dimasukkan user.</p>
                </div>

                <div class="overflow-x-auto flex-grow flex flex-col justify-start">
                    <table class="w-full text-left text-xs text-slate-600 border-collapse">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider">
                                <th class="pb-3 pt-1">Tanggal</th>
                                <th class="pb-3 pt-1">Kategori Keperluan</th>
                                <th class="pb-3 pt-1 text-right">Jumlah Uang</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if (empty($recent_transactions)): ?>
                                <tr>
                                    <td colspan="3" class="p-8 text-center text-slate-400 italic">
                                        Belum ada rekaman aktivitas finansial masuk.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $tx): ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-3.5 text-slate-400 text-[11px]">
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