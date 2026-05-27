<?php
require_once 'config.php';
session_start();

// Jika user sudah login, langsung bypass ke dashboard menggunakan rute bersih Vercel
if (isset($_SESSION['user_id'])) { 
    header("Location: /dashboard"); 
    exit; 
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = htmlspecialchars(trim($_POST['nama']));
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    if (!empty($nama) && !empty($email) && !empty($pass)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format alamat email tidak valid!";
        } else {
            $email = htmlspecialchars($email);

            try {
                // Cek apakah email sudah terdaftar
                $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                $stmt_check->execute(['email' => $email]);
                
                if ($stmt_check->rowCount() > 0) {
                    $error = "Email ini sudah terdaftar di sistem kami!";
                } else {
                    // ---------------------------------------------------------
                    // PLAN C: MEMBUAT STRING UNIK (ID ACAK) SEBAGAI PRIMARY KEY
                    // ---------------------------------------------------------
                    // Membuat ID acak berbasis waktu agar database tidak mendeteksi Auto Increment
                    $unique_id = substr(md5(uniqid(rand(), true)), 0, 10); // Menghasilkan 10 karakter acak unik
                    // ---------------------------------------------------------

                    // Hash password untuk keamanan data pengguna
                    $hashed_password = password_hash($pass, PASSWORD_BCRYPT);
                    
                    // Kita masukkan ID unik string ke database
                    $stmt_ins = $pdo->prepare("INSERT INTO users (id, nama, email, password) VALUES (:id, :nama, :email, :pass)");
                    
                    if ($stmt_ins->execute(['id' => $unique_id, 'nama' => $nama, 'email' => $email, 'pass' => $hashed_password])) {
                        $success = "Akun berhasil dibuat! Silakan masuk.";
                        // Reset input form jika sukses
                        $_POST = array();
                    } else {
                        $error = "Gagal memproses pendaftaran data pendaftaran.";
                    }
                }
            } catch (PDOException $e) {
                // Memberikan pesan error yang lebih informatif jika database bermasalah
                $error = "Pesan Sistem: " . $e->getMessage();
            }
        }
    } else {
        $error = "Semua kolom pendaftaran wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - SenjaTrack</title>
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
                <span class="text-3xl">✨</span>
                <h1 class="text-xl font-extrabold text-indigo-950 tracking-tight mt-2">Buat Akun Baru</h1>
                <p class="text-[11px] text-slate-400 mt-1">Bergabung untuk pencatatan keuangan mandiri otomatis</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-rose-50 border border-rose-100 text-rose-600 text-xs py-3 px-4 rounded-xl mb-4 font-medium flex items-center gap-2">
                    <span>⚠️</span> <span class="break-all"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="bg-emerald-50 border border-emerald-100 text-emerald-600 text-xs py-3 px-4 rounded-xl mb-4 font-medium flex items-center gap-2">
                    <span>✅</span> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                
                <div>
                    <label for="nama" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400">👤</span>
                        <input type="text" id="nama" name="nama" required
                               class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:bg-white transition-all" 
                               placeholder="Nama lengkap kamu" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">
                    </div>
                </div>

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
                    <label for="password" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Kata Sandi Baru</label>
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
                    <a href="/login" class="text-orange-500 font-bold hover:underline ml-0.5">Masuk Di Sini</a>
                </p>
            </div>

        </div>
    </main>

    <footer class="bg-indigo-950 text-slate-500 text-[10px] py-4 text-center border-t border-indigo-900/40">
        <p>&copy; 2026 SenjaTrack Workspace System &bull; Panel Core v4.0</p>
    </footer>

</body>
</html>