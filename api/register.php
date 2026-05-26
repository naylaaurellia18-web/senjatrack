<?php
require_once 'config.php';
session_start();

// Jika user sudah login, langsung bypass ke dashboard
if (isset($_SESSION['user_id'])) { 
    header("Location: dashboard.php"); 
    exit; 
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = htmlspecialchars(trim($_POST['nama']));
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    // FIX: Validasi format email dengan filter_var
    if (!empty($nama) && !empty($email) && !empty($pass)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format alamat email tidak valid!";
        } else {
            $email = htmlspecialchars($email);

            // Cek apakah email sudah terdaftar
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt_check->execute(['email' => $email]);
            
            if ($stmt_check->rowCount() > 0) {
                $error = "Email ini sudah terdaftar di sistem kami!";
            } else {
                // Hash password untuk keamanan data pengguna
                $hashed_password = password_hash($pass, PASSWORD_BCRYPT);
                $stmt_ins = $pdo->prepare("INSERT INTO users (nama, email, password) VALUES (:nama, :email, :pass)");
                $stmt_ins->execute(['nama' => $nama, 'email' => $email, 'pass' => $hashed_password]);
                
                $success = "Akun berhasil dibuat! Silakan masuk ke halaman login.";
            }
        }
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru - SenjaTrack</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        .gradient-senja {
            background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #f97316 100%);
        }
    </style>
</head>
<body class="bg-amber-50/40 min-h-screen flex flex-col justify-between font-sans text-slate-800">

    <main class="flex-grow flex items-center justify-center px-4 py-12 relative overflow-hidden">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-orange-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>

        <div class="bg-white p-8 rounded-3xl shadow-xl border border-slate-100 w-full max-w-md relative z-10 transition-all">
            
            <div class="text-center space-y-2 mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-orange-100 rounded-2xl text-2xl mb-1 shadow-sm">
                    🌅
                </div>
                <h1 class="text-2xl font-bold text-indigo-950 tracking-tight">Buat Akun Baru</h1>
                <p class="text-xs text-slate-400">Langkah awal kelola keuangan kosan secara cerdas</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <span>⚠️</span> <p class="font-medium"><?= $error ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs flex items-center gap-2">
                    <span>✅</span> <p class="font-medium"><?= $success ?></p>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-4">
                
                <div class="space-y-1">
                    <label for="nama" class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Nama Lengkap</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">👤</span>
                        <input type="text" id="nama" name="nama" required autocomplete="name"
                               class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white transition-all" 
                               placeholder="Contoh: nayla">
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="email" class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Alamat Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">✉️</span>
                        <input type="email" id="email" name="email" required autocomplete="email"
                               class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white transition-all" 
                               placeholder="nama@gmail.com">
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="password" class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Kata Sandi (Password)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">🔒</span>
                        <input type="password" id="password" name="password" required autocomplete="new-password"
                               class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white transition-all" 
                               placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full text-xs gradient-senja hover:opacity-90 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-indigo-950/20 transform hover:-translate-y-0.5 cursor-pointer mt-2">
                    Daftar Akun &rarr;
                </button>

            </form>

            <div class="mt-6 pt-4 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400">
                    Sudah punya akun? 
                    <a href="login.php" class="text-orange-500 font-bold hover:underline ml-0.5">Masuk Di Sini</a>
                </p>
            </div>

        </div>
    </main>

    <footer class="bg-indigo-950 text-slate-500 text-[10px] py-4 text-center border-t border-indigo-900/40">
        <p>&copy; 2026 SenjaTrack System &bull; Secured Identity Provider Gateway</p>
    </footer>

</body>
</html>