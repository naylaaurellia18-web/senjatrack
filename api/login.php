<?php
// login.php
require_once 'config.php';
session_start();

// Jika sudah pernah login, langsung bypass ke aplikasi utama
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email dan password tidak boleh kosong.";
    } else {
        // Ambil data user dari DB berdasarkan email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verifikasi password terenkripsi
        if ($user && password_verify($password, $user['password'])) {
            // Pasang session penanda user sukses masuk
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            
            // Redirect langsung ke dashboard utama
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Kombinasi Email atau Password salah, silakan cek kembali.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SenjaTrack</title>
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
                <h1 class="text-2xl font-bold text-indigo-950 tracking-tight">Selamat Datang Kembali</h1>
                <p class="text-xs text-slate-400">Yuk, pantau arus keuangan kosanmu hari ini</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <span>⚠️</span> <p class="font-medium"><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                
                <div class="space-y-1">
                    <label Lifor="email" class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Email </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">✉️</span>
                        <input type="email" id="email" name="email" required autocomplete="on"
                               class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white transition-all" 
                               placeholder="nama@gmail.com">
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <label for="password" class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Password</label>
                        <span class="text-[11px] text-slate-400 italic">Amankan akunmu</span>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">🔒</span>
                        <input type="password" id="password" name="password" required
                               class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white transition-all" 
                               placeholder="Masukkan password kamu">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full text-xs gradient-senja hover:opacity-90 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-indigo-950/20 transform hover:-translate-y-0.5 cursor-pointer mt-2">
                    Masuk ke Aplikasi &rarr;
                </button>

            </form>

            <div class="mt-6 pt-4 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400">
                    Belum punya akun? 
                    <a href="/api/register.php" class="...">Daftar Sekarang</a>
                </p>
            </div>

        </div>
    </main>

    <footer class="bg-indigo-950 text-slate-500 text-[10px] py-4 text-center border-t border-indigo-900/40">
        <p>&copy; 2026 SenjaTrack System &bull; Secured Authorization Gateway</p>
    </footer>

</body>
</html>