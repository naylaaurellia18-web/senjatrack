<?php
require_once 'config.php'; 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];
$current_month = date('Y-m');

$stmt_income = $pdo->prepare("SELECT SUM(jumlah) as total FROM transactions WHERE user_id = :user_id AND tipe = 'pemasukan'");
$stmt_income->execute(['user_id' => $user_id]);
$total_income = $stmt_income->fetch()['total'] ?? 0;

$stmt_expense = $pdo->prepare("SELECT SUM(jumlah) as total FROM transactions WHERE user_id = :user_id AND tipe = 'pengeluaran' AND DATE_FORMAT(tanggal, '%Y-%m') = :bulan_ini");
$stmt_expense->execute(['user_id' => $user_id, 'bulan_ini' => $current_month]);
$total_expense_month = $stmt_expense->fetch()['total'] ?? 0;

$stmt_all_expense = $pdo->prepare("SELECT SUM(jumlah) as total FROM transactions WHERE user_id = :user_id AND tipe = 'pengeluaran'");
$stmt_all_expense->execute(['user_id' => $user_id]);
$total_all_expense = $stmt_all_expense->fetch()['total'] ?? 0;

$saldo_aktif = $total_income - $total_all_expense;

$stmt_recent = $pdo->prepare("SELECT kategori, tipe, jumlah, tanggal FROM transactions WHERE user_id = :user_id ORDER BY tanggal DESC LIMIT 5");
$stmt_recent->execute(['user_id' => $user_id]);
$recent_transactions = $stmt_recent->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SenjaTrack</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        .gradient-senja { background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #f97316 100%); }
        .sidebar-link { display:flex; align-items:center; gap:12px; padding:10px 16px; border-radius:14px; font-size:13px; font-weight:600; color:#64748b; transition:all .18s; cursor:pointer; text-decoration:none; }
        .sidebar-link:hover { background:#f1f5f9; color:#1e1b4b; }
        .sidebar-link.active { background:linear-gradient(135deg,#1e1b4b,#311042,#f97316); color:#fff; box-shadow:0 4px 14px rgba(79,70,229,.18); }
        .sidebar-link.active span { filter:brightness(10); }
    </style>
</head>
<body class="bg-slate-100 font-sans min-h-screen flex">

    <div id="overlay" onclick="tutupSidebar()" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden"></div>

    <aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-white border-r border-slate-100 shadow-xl z-40 flex flex-col
                               -translate-x-full lg:translate-x-0 transition-transform duration-300">

        <div class="px-6 py-5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <span class="text-2xl">🌅</span>
                <div>
                    <p class="font-extrabold text-lg text-indigo-950 leading-none tracking-tight">Senja<span class="text-orange-500">Track</span></p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Workspace Finansial</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-4 py-5 space-y-1 overflow-y-auto">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-2 mb-3">Menu Utama</p>

            <a href="/dashboard" class="sidebar-link active">
                <span class="text-base">🏠</span>
                <span>Beranda & Catat</span>
            </a>

            <a href="/fitur_plus" class="sidebar-link">
                <span class="text-base">✨</span>
                <span>Fitur Pro Finansial</span>
            </a>

            <div class="pt-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-2 mb-3">Akun</p>
                <a href="/logout" class="sidebar-link text-rose-500 hover:!bg-rose-50 hover:!text-rose-600">
                    <span class="text-base">🚪</span>
                    <span>Keluar</span>
                </a>
            </div>
        </nav>

        <div class="px-4 py-4 border-t border-slate-100 bg-slate-50/60">
            <div class="flex items-center gap-3 px-2">
                <div class="w-9 h-9 rounded-full gradient-senja flex items-center justify-center text-white font-extrabold text-sm shrink-0">
                    <?= strtoupper(mb_substr($user_nama, 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="font-bold text-xs text-slate-800 truncate"><?= htmlspecialchars($user_nama) ?></p>
                    <p class="text-[10px] text-slate-400">Pengguna Aktif ✓</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-h-screen lg:ml-64">

        <header class="bg-white border-b border-slate-100 px-4 py-3 flex items-center justify-between lg:hidden sticky top-0 z-20 shadow-sm">
            <button onclick="bukaSidebar()" class="p-2 rounded-xl hover:bg-slate-100 transition">
                <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex items-center gap-1.5">
                <span>🌅</span>
                <span class="font-extrabold text-sm text-indigo-950">Senja<span class="text-orange-500">Treack</span></span>
            </div>
            <div class="w-8 h-8 rounded-full gradient-senja flex items-center justify-center text-white font-bold text-xs">
                <?= strtoupper(mb_substr($user_nama, 0, 1)) ?>
            </div>
        </header>

        <main class="flex-grow p-4 sm:p-6 space-y-6 max-w-5xl w-full mx-auto">

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h1 class="text-xl font-extrabold text-indigo-950 tracking-tight">👋 Halo, <?= htmlspecialchars(explode(' ', $user_nama)[0]) ?>!</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Kelola uang saku kuliahmu hari ini dengan cerdas.</p>
                </div>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <div class="gradient-senja text-white p-5 rounded-2xl shadow-lg relative overflow-hidden flex flex-col justify-between min-h-[110px]">
                    <div class="absolute -right-4 -bottom-4 text-6xl opacity-10">💰</div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-orange-200 block">Saldo Aktif Dompet</span>
                    <h2 class="text-2xl font-extrabold tracking-tight mt-2">
                        Rp <?= number_format($saldo_aktif, 0, ',', '.') ?>
                    </h2>
                    <span class="text-[10px] text-white/60 italic">*Siap dialokasikan</span>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between min-h-[110px] relative">
                    <div class="absolute right-4 top-4 w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center text-sm">📈</div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Pemasukan</span>
                    <h2 class="text-xl font-extrabold text-emerald-600 tracking-tight mt-2">
                        Rp <?= number_format($total_income, 0, ',', '.') ?>
                    </h2>
                    <span class="text-[10px] text-slate-400">Semua dana masuk</span>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between min-h-[110px] relative">
                    <div class="absolute right-4 top-4 w-8 h-8 bg-rose-50 rounded-lg flex items-center justify-center text-sm">📉</div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Pengeluaran <?= date('M') ?></span>
                    <h2 class="text-xl font-extrabold text-rose-600 tracking-tight mt-2">
                        Rp <?= number_format($total_expense_month, 0, ',', '.') ?>
                    </h2>
                    <span class="text-[10px] text-slate-400">Belanja bulan ini</span>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-5">

                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm md:col-span-2 space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="font-bold text-sm text-indigo-950">⚡ Catat Transaksi Cepat</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Data langsung terhubung ke saldo utama.</p>
                    </div>

                    <form action="/proses_transaksi" method="POST" class="space-y-4 text-xs">
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
                            <label for="jumlah" class="font-bold text-slate-600 block">Nominal (Rp)</label>
                            <input type="number" id="jumlah" name="jumlah" min="100" required
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white text-sm font-semibold transition-all"
                                   placeholder="Contoh: 50000">
                        </div>

                        <div class="space-y-1">
                            <label for="kategori" class="font-bold text-slate-600 block">Kategori / Keperluan</label>
                            <input type="text" id="kategori" name="kategori" required
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white transition-all"
                                   placeholder="Contoh: Kiriman Bulanan, Makan Malam">
                        </div>

                        <button type="submit"
                                class="w-full gradient-senja text-white font-bold py-3 rounded-xl hover:opacity-90 shadow-md transform hover:-translate-y-0.5 cursor-pointer transition-all text-xs">
                            Simpan Transaksi &rarr;
                        </button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm md:col-span-3 space-y-3 flex flex-col">
                    <div>
                        <h3 class="font-bold text-sm text-indigo-950">📜 5 Transaksi Terakhir</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Riwayat pemasukan & pengeluaran terbaru.</p>
                    </div>

                    <div class="overflow-x-auto flex-grow">
                        <table class="w-full text-left text-xs text-slate-600 border-collapse">
                            <thead>
                                <tr class="text-slate-400 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider">
                                    <th class="pb-3 pt-1">Tanggal</th>
                                    <th class="pb-3 pt-1">Kategori</th>
                                    <th class="pb-3 pt-1 text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (empty($recent_transactions)): ?>
                                    <tr>
                                        <td colspan="3" class="p-8 text-center text-slate-400 italic">
                                            Belum ada rekaman transaksi.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_transactions as $tx): ?>
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td class="py-3.5 text-slate-400 text-[11px] whitespace-nowrap">
                                                <?= date('d M Y', strtotime($tx['tanggal'])) ?>
                                            </td>
                                            <td class="py-3.5 font-semibold text-slate-700">
                                                <?= htmlspecialchars($tx['kategori']) ?>
                                            </td>
                                            <td class="py-3.5 text-right font-extrabold text-sm whitespace-nowrap <?= $tx['tipe'] === 'pemasukan' ? 'text-emerald-600' : 'text-rose-600' ?>">
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
                            ✓ Sinkronisasi Realtime
                        </span>
                    </div>
                </div>

            </div>
        </main>

        <footer class="bg-indigo-950 text-slate-500 text-[10px] py-3 text-center border-t border-indigo-900/40">
            <p>&copy; 2026 SenjaTrack Workspace System &bull; Panel Core v4.0</p>
        </footer>
    </div>

    <script>
        function bukaSidebar() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('overlay').classList.remove('hidden');
        }
        function tutupSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('overlay').classList.add('hidden');
        }
    </script>
</body>
</html>