<?php
// login.php
require_once 'config.php';
session_start();

// Jika sudah pernah login, langsung bypass ke aplikasi utama menggunakan rute bersih Vercel
if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard");
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
            
            // Redirect langsung ke dashboard utama menggunakan rute bersih Vercel
            header("Location: /dashboard");
            exit;
        } else {
            $error = "Kombinasi Email atau Password salah, silakan cek kembali.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SenjaTrack Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-senja { background: linear-gradient(135deg, #f97316 0%, #4f46e5 100%); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-between">

    <main class="flex-1 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-sm rounded-3xl p-8 border border-slate-100 shadow-2xl shadow-indigo-950/5">
            
            <div class="text-center mb-6">
                <span class="text-3xl">🌅</span>
                <h1 class="text-xl font-extrabold text-indigo-950 tracking-tight mt-2">Selamat Datang Kembali</h1>
                <p class="text-[11px] text-slate-400 mt-1">Kelola pencatatan finansial SenjaTrack secara cerdas</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-rose-50 border border-rose-100 text-rose-600 text-xs py-3 px-4 rounded-xl mb-4 font-medium flex items-center gap-2">
                    <span>⚠️</span> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                
                <div>
                    <label for="email" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Alamat Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">📧</span>
                        <input type="email" id="email" name="email" required
                               class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white transition-all" 
                               placeholder="nama@email.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Kata Sandi</label>
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
                    <a href="/register" class="text-orange-500 font-bold hover:underline ml-0.5">Daftar Sekarang</a>
                </p>
            </div>

        </div>
    </main>

    <footer class="bg-indigo-950 text-slate-500 text-[10px] py-4 text-center border-t border-indigo-900/40">
        <p>&copy; 2026 SenjaTrack Workspace System &bull; Panel Core v4.0</p>
    </footer>

</body>
</html>