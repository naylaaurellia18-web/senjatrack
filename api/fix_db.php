<?php
// 1. INCLUDE KONEKSI DATABASE
require_once 'config.php';
session_start();

$status = '';
$message = '';
$debug_info = '';

// 2. PROSES PERBAIKAN STRUKTUR TABEL USERS
try {
    // Mengecek apakah koneksi $pdo tersedia
    if (!isset($pdo)) {
        throw new Exception("Variabel koneksi database (\$pdo) tidak ditemukan. Pastikan file config.php sudah benar.");
    }

    // Eksekusi perintah SQL untuk memaksa kolom ID menjadi AUTO_INCREMENT dan PRIMARY KEY
    // Menggunakan ALTER TABLE yang kompatibel dengan MySQL / TiDB / PlanetScale
    $pdo->exec("ALTER TABLE users MODIFY id INT AUTO_INCREMENT PRIMARY KEY;");
    
    $status = 'success';
    $message = "Struktur database berhasil diperbaiki secara otomatis! Kolom 'id' pada tabel 'users' sekarang sudah memiliki fitur AUTO_INCREMENT.";
} catch (PDOException $e) {
    $status = 'error';
    $message = "Gagal memperbarui struktur tabel melalui perintah SQL Standard.";
    $debug_info = "Pesan Error Database: " . $e->getMessage() . " (Kode: " . $e->getCode() . ")";
} catch (Exception $e) {
    $status = 'error';
    $message = "Terjadi kesalahan sistem atau konfigurasi pada file PHP.";
    $debug_info = "Detail: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Fixer - SenjaTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-senja { background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #f97316 100%); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-between">

    <nav class="bg-indigo-950 text-white px-6 py-4 shadow-sm">
        <div class="max-w-4xl mx-auto flex items-center gap-2">
            <span>🌅</span>
            <span class="font-bold text-lg text-orange-400 tracking-wide">SenjaTrack Database Engine</span>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-3xl p-8 border border-slate-100 shadow-2xl shadow-indigo-950/5 text-center space-y-6">
            
            <div>
                <?php if ($status === 'success'): ?>
                    <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center text-3xl mx-auto shadow-sm animate-pulse">
                        ✅
                    </div>
                    <h1 class="text-xl font-extrabold text-indigo-950 tracking-tight mt-4">Perbaikan Berhasil!</h1>
                <?php else: ?>
                    <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center text-3xl mx-auto shadow-sm">
                        ❌
                    </div>
                    <h1 class="text-xl font-extrabold text-indigo-950 tracking-tight mt-4">Perbaikan Gagal</h1>
                <?php endif; ?>
            </div>

            <div class="text-xs text-slate-600 leading-relaxed bg-slate-50/80 p-4 rounded-2xl border border-slate-100">
                <?= htmlspecialchars($message) ?>
            </div>

            <?php if (!empty($debug_info)): ?>
                <div class="text-left bg-zinc-900 text-rose-400 font-mono text-[10px] p-3 rounded-xl overflow-x-auto border border-zinc-800 break-all">
                    <strong>Informasi Sistem:</strong><br>
                    <?= htmlspecialchars($debug_info) ?>
                </div>
            <?php endif; ?>

            <div class="pt-2">
                <?php if ($status === 'success'): ?>
                    <a href="/register" 
                       class="block w-full text-center text-xs gradient-senja hover:opacity-90 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-indigo-950/10">
                        &larr; Kembali ke Pendaftaran Akun
                    </a>
                    <p class="text-[10px] text-slate-400 mt-2.5">Sekarang kamu bisa mencoba mendaftarkan akun baru lagi.</p>
                <?php else: ?>
                    <a href="https://phpmyadmin.net/" target="_blank"
                       class="block w-full text-center text-xs bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl transition-all shadow-md">
                        Buka Panel Database Manual 🛠️
                    </a>
                    <p class="text-[10px] text-rose-500 font-medium mt-2.5">Silakan periksa konfigurasi kolom AI (Auto_Increment) langsung pada dashboard layanan hosting database kamu.</p>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <footer class="bg-indigo-950 text-slate-500 text-[10px] py-4 text-center border-t border-indigo-900/40">
        <p>&copy; 2026 SenjaTrack Workspace System &bull; Database Diagnostic Core</p>
    </footer>

</body>
</html>