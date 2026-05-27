<?php
require_once 'config.php'; // FIX: Wajib ada agar database session handler terdaftar
session_start();
// Jika sudah login, berikan opsi ke dashboard langsung di landing page
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SenjaTrack - Finansial Teratur, Kuliah Teratur</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        .gradient-senja {
            background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #f97316 100%);
        }
        .text-gradient-senja {
            background: linear-gradient(to right, #f97316, #fdba74);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-amber-50/40 text-slate-800 font-sans min-h-screen flex flex-col justify-between">

    <nav class="bg-indigo-950/95 backdrop-blur-md text-white px-6 py-4 shadow-md sticky top-0 z-50 border-b border-indigo-900/50">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="text-2xl">🌅</span>
                <span class="font-bold text-xl tracking-wider text-orange-400">Senja<span class="text-white">Track</span></span>
            </div>
            <div class="flex items-center gap-4">
                <?php if ($is_logged_in): ?>
                    <a href="/dashboard" class="text-xs bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-xl font-bold transition-all shadow-md">
                        Ke Dashboard 🚀
                    </a>
                <?php else: ?>
                    <a href="/login" class="text-xs text-slate-300 hover:text-white font-medium transition-colors">Masuk</a>
                    <a href="/register" class="text-xs bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-xl font-bold transition-all shadow-md">
                        Daftar Sekarang
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        
        <section class="gradient-senja text-white py-20 px-6 text-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
            
            <div class="max-w-3xl mx-auto space-y-6 relative z-10">
                <span class="text-xs bg-white/10 text-orange-300 px-4 py-1.5 rounded-full font-bold uppercase tracking-wider border border-white/10">
                    🏆 Project Inovasi Aplikasi Finansial Mahasiswa
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight leading-tight">
                    Kelola Pengeluaran Kosan Tanpa Takut <span class="text-gradient-senja">Sakit Kepala</span>
                </h1>
                <p class="text-sm md:text-base text-slate-300 max-w-xl mx-auto font-light leading-relaxed">
                    Aplikasi pencatat keuangan adaptif yang dirancang khusus untuk mahasiswa. Pantau arus kas harian, kejar target tabungan semesteran, dan amankan arsip struk belanja kos dalam satu ekosistem terintegrasi.
                </p>
                <div class="pt-4 flex justify-center gap-4">
                    <?php if ($is_logged_in): ?>
                        <a href="/dashboard" class="bg-orange-500 hover:bg-orange-600 text-white font-bold text-sm px-6 py-3 rounded-xl transition-all shadow-lg shadow-orange-500/20 transform hover:-translate-y-0.5">
                            Buka Workspace Anda
                        </a>
                    <?php else: ?>
                        <a href="/register" class="bg-orange-500 hover:bg-orange-600 text-white font-bold text-sm px-6 py-3 rounded-xl transition-all shadow-lg shadow-orange-500/20 transform hover:-translate-y-0.5">
                            Mulai Catat Gratis
                        </a>
                        <a href="#fitur" class="bg-white/10 hover:bg-white/20 text-white font-medium text-sm px-6 py-3 rounded-xl transition-all border border-white/20">
                            Lihat Fitur Pro ✨
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="max-w-5xl mx-auto px-6 -mt-8 relative z-20">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6 grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                <div class="space-y-1">
                    <p class="text-2xl">⚡</p>
                    <h3 class="text-base font-bold text-indigo-950">Realtime Tracking</h3>
                    <p class="text-xs text-slate-400">Arus kas langsung dihitung otomatis sistem.</p>
                </div>
                <div class="space-y-1 border-y sm:border-y-0 sm:border-x border-slate-100 py-4 sm:py-0">
                    <p class="text-2xl">📸</p>
                    <h3 class="text-base font-bold text-indigo-950">Cloud Receipts</h3>
                    <p class="text-xs text-slate-400">Struk fisik aman dari noda dan risiko hilang.</p>
                </div>
                <div class="space-y-1">
                    <p class="text-2xl">🎯</p>
                    <h3 class="text-base font-bold text-indigo-950">Goal Oriented</h3>
                    <p class="text-xs text-slate-400">Target menabung berbasis persentase visual.</p>
                </div>
            </div>
        </section>

        <section id="fitur" class="max-w-6xl mx-auto px-6 py-16 space-y-12">
            <div class="text-center space-y-2">
                <h2 class="text-2xl font-bold text-indigo-950">Fitur Unggulan Berorientasi Mahasiswa</h2>
                <p class="text-xs text-slate-500 max-w-md mx-auto">Kami menyelesaikan masalah klasik finansial mahasiswa dengan pendekatan teknologi yang ringkas dan fungsional.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-3 hover:shadow-md transition-shadow">
                    <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-lg">📊</div>
                    <h4 class="font-bold text-sm text-slate-800">Catat Transaksi Cepat & Pintar</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Input pemasukan bulanan atau pengeluaran nongkrong dalam hitungan detik. Sistem langsung mengalkulasi sisa saldo aktif secara dinamis.
                    </p>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-3 hover:shadow-md transition-shadow">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-lg">🎯</div>
                    <h4 class="font-bold text-sm text-slate-800">Target Nabung Kampus (Goals)</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Punya target beli laptop baru, buku referensi, atau biaya seminar? Pasang target anggaran dan setor celengan digitalmu secara bertahap dengan progress bar interaktif.
                    </p>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-3 hover:shadow-md transition-shadow">
                    <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center text-lg">📷</div>
                    <h4 class="font-bold text-sm text-slate-800">Scanner & Arsip Struk Digital</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Gunakan kamera smartphone untuk mengambil foto struk pembayaran kos, fotokopi, atau belanja logistik. File disimpan rapi dan aman di server internal aplikasi.
                    </p>
                </div>
            </div>

            <div class="bg-indigo-950 rounded-3xl p-6 md:p-8 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl relative overflow-hidden">
                <div class="space-y-3 max-w-xl">
                    <span class="text-[10px] bg-orange-500 text-white font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">Fitur Eksklusif</span>
                    <h3 class="text-xl font-bold">Analisis Boros Harian lewat Kalender Arus Kas 📅</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Jangan kaget kalau mendadak bokek! SenjaTrack memetakan akumulasi pengeluaran harianmu tepat di atas kalender bulan berjalan. Memudahkanmu melihat di tanggal berapa saja kamu melakukan pengeluaran terbesar.
                    </p>
                </div>
                <div class="w-full md:w-auto shrink-0">
                    <a href="/login" class="block text-center text-xs bg-white text-indigo-950 font-bold px-5 py-3 rounded-xl hover:bg-orange-400 hover:text-white transition-all shadow-md">
                        Coba Demo Kalender &rarr;
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-indigo-950 text-slate-400 text-xs py-6 border-t border-indigo-900/40 px-6">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4 text-center sm:text-left">
            <div>
                <p class="font-bold text-slate-300">&copy; 2026 SenjaTrack System.</p>
                <p class="text-[11px] text-slate-500">Dikembangkan untuk Kompetisi Inovasi Teknologi & Perangkat Lunak.</p>
            </div>
            <div class="flex gap-4 text-[11px]">
                <span class="text-orange-400 font-semibold">✓ Server Side Session Security Encription</span>
                <span class="text-slate-600">|</span>
                <span class="text-orange-400 font-semibold">✓ Tailwind CSS v4 Integrated</span>
            </div>
        </div>
    </footer>

</body>
</html>