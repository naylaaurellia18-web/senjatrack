<?php
// 1. KONEKSI DATABASE & INITIALIZATION
require_once 'config.php'; 
session_start();

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$status = '';

// --- AUTOMATIC TABLE CHECKER ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS saving_goals (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, nama_target VARCHAR(255) NOT NULL, nominal_target FLOAT NOT NULL, nominal_terkumpul FLOAT NOT NULL DEFAULT 0)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS receipts (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, file_name VARCHAR(255) NOT NULL, tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS bills (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, nama_tagihan VARCHAR(255) NOT NULL, nominal FLOAT NOT NULL, jatuh_tempo DATE NOT NULL, status_bayar ENUM('belum', 'lunas') DEFAULT 'belum')");
    $pdo->exec("CREATE TABLE IF NOT EXISTS shopping_plans (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, nama_barang VARCHAR(255) NOT NULL, estimasi_harga FLOAT NOT NULL, status_beli ENUM('belum', 'sudah') DEFAULT 'belum')");
} catch (PDOException $e) {}

// ==========================================
// 2. LOGIKA PROSES FORM POST & GET (BACKEND)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. Tambah Target Nabung
    if ($action === 'tambah_target') {
        $nama_target = htmlspecialchars(trim($_POST['nama_target']));
        $nominal_target = (float)($_POST['nominal_target'] ?? 0);

        if (!empty($nama_target) && $nominal_target > 0) {
            $stmt = $pdo->prepare("INSERT INTO saving_goals (user_id, nama_target, nominal_target, nominal_terkumpul) VALUES (:user_id, :nama, :target, 0)");
            $stmt->execute(['user_id' => $user_id, 'nama' => $nama_target, 'target' => $nominal_target]);
            $message = "Target menabung baru berhasil dipasang! 🎯";
            $status = "success";
        }
    }

    // B. Tambah Pengingat Tagihan Baru
    if ($action === 'tambah_tagihan') {
        $nama_tagihan = htmlspecialchars(trim($_POST['nama_tagihan']));
        $nominal_tagihan = (float)$_POST['nominal_tagihan'];
        $jatuh_tempo = $_POST['jatuh_tempo'];

        if (!empty($nama_tagihan) && $nominal_tagihan > 0 && !empty($jatuh_tempo)) {
            $stmt = $pdo->prepare("INSERT INTO bills (user_id, nama_tagihan, nominal, jatuh_tempo, status_bayar) VALUES (:user_id, :nama, :nominal, :tempo, 'belum')");
            $stmt->execute(['user_id' => $user_id, 'nama' => $nama_tagihan, 'nominal' => $nominal_tagihan, 'tempo' => $jatuh_tempo]);
            $message = "Pengingat tagihan berhasil disimpan! 🔔";
            $status = "success";
        }
    }

    // C. Upload Struk Belanja Mandiri
    if ($action === 'upload_struk') {
        if (isset($_FILES['struk_file']) && $_FILES['struk_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['struk_file']['tmp_name'];
            $file_size = $_FILES['struk_file']['size'];
            $file_ext  = strtolower(pathinfo($_FILES['struk_file']['name'], PATHINFO_EXTENSION));
            
            if ($file_size > 2 * 1024 * 1024) {
                $message = "Gagal! Ukuran file melebihi batas maksimal 2MB.";
                $status = "error";
            } elseif (!in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                $message = "Gagal! Format file tidak didukung. Gunakan JPG, JPEG, atau PNG.";
                $status = "error";
            } else {
                // SOLUSI SERVERLESS: Simpan ke folder /tmp/ sistem yang open-write
                $upload_dir = '/tmp/'; 
                $new_file_name = 'struk_' . time() . '_' . uniqid() . '.' . $file_ext;
                
                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    $stmt = $pdo->prepare("INSERT INTO receipts (user_id, file_name) VALUES (:user_id, :file_name)");
                    $stmt->execute(['user_id' => $user_id, 'file_name' => $new_file_name]);
                    $message = "Struk belanja berhasil diarsipkan! 📸";
                    $status = "success";
                } else {
                    $message = "Gagal menyimpan file ke temporary space.";
                    $status = "error";
                }
            }
        } else {
            $message = "Tidak ada file yang dipilih atau terjadi kesalahan upload.";
            $status = "error";
        }
    }

    // D. Bayar Tagihan + Upload Struk Bukti Bayar
    if ($action === 'bayar_tagihan_struk') {
        $id_tagihan = (int)$_POST['id_tagihan'];
        
        if (isset($_FILES['struk_file']) && $_FILES['struk_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['struk_file']['tmp_name'];
            $file_size = $_FILES['struk_file']['size'];
            $file_ext  = strtolower(pathinfo($_FILES['struk_file']['name'], PATHINFO_EXTENSION));
            
            if ($file_size > 2 * 1024 * 1024) {
                $message = "Gagal! Ukuran bukti bayar melebihi 2MB.";
                $status = "error";
            } elseif (!in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                $message = "Gagal! Format bukti bayar wajib JPG, JPEG, atau PNG.";
                $status = "error";
            } else {
                // SOLUSI SERVERLESS: Simpan ke folder /tmp/ agar tidak memicu Read-only error
                $upload_dir = '/tmp/';
                $new_file_name = 'struk_bayar_' . time() . '_' . uniqid() . '.' . $file_ext;
                
                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    try {
                        $pdo->beginTransaction();

                        // 1. Ubah status pembayaran tagihan menjadi lunas
                        $stmtUpdate = $pdo->prepare("UPDATE bills SET status_bayar = 'lunas' WHERE id = :id AND user_id = :uid");
                        $stmtUpdate->execute(['id' => $id_tagihan, 'uid' => $user_id]);

                        // 2. Arsipkan ke tabel riwayat receipts
                        $stmtReceipt = $pdo->prepare("INSERT INTO receipts (user_id, file_name) VALUES (:uid, :file_name)");
                        $stmtReceipt->execute(['uid' => $user_id, 'file_name' => $new_file_name]);

                        $pdo->commit();
                        $message = "Pembayaran tagihan berhasil dikonfirmasi dan struk disimpan! 💸";
                        $status = "success";
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        if (file_exists($upload_dir . $new_file_name)) unlink($upload_dir . $new_file_name);
                        $message = "Gagal memproses transaksi: " . $e->getMessage();
                        $status = "error";
                    }
                } else {
                    $message = "Gagal memindahkan berkas pembayaran ke temporary space.";
                    $status = "error";
                }
            }
        } else {
            $message = "Wajib melampirkan berkas struk/nota sebagai bukti bayar.";
            $status = "error";
        }
    }

    // E. Tambah Rencana Belanja Baru
    if ($action === 'tambah_belanja') {
        $nama_barang = htmlspecialchars(trim($_POST['nama_barang']));
        $estimasi_harga = (float)$_POST['estimasi_harga'];

        if (!empty($nama_barang) && $estimasi_harga > 0) {
            $stmt = $pdo->prepare("INSERT INTO shopping_plans (user_id, nama_barang, estimasi_harga, status_beli) VALUES (:user_id, :nama, :harga, 'belum')");
            $stmt->execute(['user_id' => $user_id, 'nama' => $nama_barang, 'harga' => $estimasi_harga]);
            $message = "Item rencana belanja berhasil dimasukkan daftar! 🛒";
            $status = "success";
        }
    }
}

// Logika Proses Aksi GET Ekstra untuk Rencana Belanja & Hapus Tagihan
if (isset($_GET['action_belanja']) && isset($_GET['id'])) {
    $b_id = (int)$_GET['id'];
    if ($_GET['action_belanja'] === 'check') {
        $stmt = $pdo->prepare("UPDATE shopping_plans SET status_beli = 'sudah' WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $b_id, 'uid' => $user_id]);
    } elseif ($_GET['action_belanja'] === 'hapus') {
        $stmt = $pdo->prepare("DELETE FROM shopping_plans WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $b_id, 'uid' => $user_id]);
    }
    header("Location: /fitur_plus");
    exit;
}

// Handler GET khusus hapus tagihan
if (isset($_GET['action_tagihan']) && $_GET['action_tagihan'] === 'hapus' && isset($_GET['id'])) {
    $t_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM bills WHERE id = :id AND user_id = :uid");
    $stmt->execute(['id' => $t_id, 'uid' => $user_id]);
    header("Location: /fitur_plus");
    exit;
}

// ==========================================
// 3. AMBIL DATA DARI DATABASE (AGREGASI)
// ==========================================
$stmt_sg = $pdo->prepare("SELECT * FROM saving_goals WHERE user_id = :uid ORDER BY id DESC");
$stmt_sg->execute(['uid' => $user_id]);
$saving_goals = $stmt_sg->fetchAll();

$stmt_rc = $pdo->prepare("SELECT * FROM receipts WHERE user_id = :uid ORDER BY id DESC");
$stmt_rc->execute(['uid' => $user_id]);
$uploaded_receipts = $stmt_rc->fetchAll();

$stmt_bl = $pdo->prepare("SELECT * FROM bills WHERE user_id = :uid ORDER BY status_bayar ASC, jatuh_tempo ASC");
$stmt_bl->execute(['uid' => $user_id]);
$all_bills = $stmt_bl->fetchAll();

$stmt_sp = $pdo->prepare("SELECT * FROM shopping_plans WHERE user_id = :uid ORDER BY status_beli ASC, id DESC");
$stmt_sp->execute(['uid' => $user_id]);
$shopping_plans = $stmt_sp->fetchAll();

// Hitung Skor Kesehatan Finansial
$current_month = date('Y-m');
$stmt_inc = $pdo->prepare("SELECT SUM(jumlah) as total FROM transactions WHERE user_id = :user_id AND tipe = 'pemasukan'");
$stmt_inc->execute(['user_id' => $user_id]);
$total_income = $stmt_inc->fetch()['total'] ?? 0;

$stmt_exp = $pdo->prepare("SELECT SUM(jumlah) as total FROM transactions WHERE user_id = :user_id AND tipe = 'pengeluaran' AND DATE_FORMAT(tanggal, '%Y-%m') = :bulan_ini");
$stmt_exp->execute(['user_id' => $user_id, 'bulan_ini' => $current_month]);
$total_expense = $stmt_exp->fetch()['total'] ?? 0;

$financial_score = 100;
$status_health = "Sehat Walafiat 💰";
$tips_health = "Luar biasa! Manajemen keuanganmu sangat terkontrol dengan baik bulan ini. Pertahankan porsi ini dan teruskan menabung.";
$score_color = "text-emerald-600 bg-emerald-50 border border-emerald-100";

if ($total_income > 0) {
    $rasio_pengeluaran = ($total_expense / $total_income) * 100;
    if ($rasio_pengeluaran > 100) {
        $financial_score = max(0, round(100 - ($rasio_pengeluaran - 100)));
        $status_health = "Kritis / Overbudget 🚨";
        $tips_health = "Peringatan! Pengeluaranmu sudah melebihi pemasukan bulan ini. Segera batasi pengeluaran non-primer.";
        $score_color = "text-rose-600 bg-rose-50 border border-rose-100";
    } elseif ($rasio_pengeluaran >= 70) {
        $financial_score = round(100 - ($rasio_pengeluaran * 0.5));
        $status_health = "Siaga / Harap Berhemat ⚠️";
        $tips_health = "Dompetmu mulai menipis. Pengeluaran sudah memakan lebih dari 70% dana bulananmu. Coba batasi pengeluaran sisa bulan ini.";
        $score_color = "text-amber-600 bg-amber-50 border border-amber-100";
    } else {
        $financial_score = round(100 - ($rasio_pengeluaran * 0.3));
    }
} else if ($total_expense > 0 && $total_income == 0) {
    $financial_score = 30;
    $status_health = "Kritis / Belum Ada Pemasukan 🚨";
    $tips_health = "Kamu mencatat pengeluaran tanpa adanya saldo masuk. Pastikan menginput pemasukan terlebih dahulu agar perhitungan akurat.";
    $score_color = "text-rose-600 bg-rose-50 border border-rose-100";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitur Pro Ekstra - SenjaTrack</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style> .gradient-senja { background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #f97316 100%); } </style>
</head>
<body class="bg-[#fcfbf7] text-slate-800 font-sans min-h-screen flex flex-col justify-between">

    <nav class="bg-indigo-950 text-white px-8 py-4.5 shadow-xs sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-2.5"><span>🌅</span><span class="font-bold text-xl text-orange-400 tracking-wide">SenjaTrack</span></div>
            <a href="/logout" class="bg-rose-600 hover:bg-rose-700 text-xs px-4 py-2 rounded-xl font-bold transition-all shadow-xs">Keluar 🚪</a>
        </div>
    </nav>

    <main class="flex-grow max-w-7xl w-full mx-auto p-6 md:p-8 space-y-8">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
            <div>
                <h1 class="text-2xl font-bold text-indigo-950 tracking-tight">Fitur Akselerasi Finansial (Pro)</h1>
                <p class="text-xs text-slate-400 mt-1">Gunakan modul pelacak lanjutan untuk simulasi dan kearsipan digital.</p>
            </div>
            <div class="flex flex-wrap gap-2.5 w-full sm:w-auto">
                <a href="/dashboard" class="text-xs bg-slate-50 text-indigo-950 font-bold px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-indigo-50 transition-all shadow-xs">🏠 Dashboard Utama</a>
                <button onclick="window.print()" class="text-xs bg-orange-500 hover:bg-orange-600 text-white font-bold px-4 py-2.5 rounded-xl shadow-xs cursor-pointer transition-all">🖨️ Cetak Laporan</button>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="p-4 rounded-xl border text-xs font-semibold <?= $status === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <div class="space-y-8">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🎯</span>
                            <h3 class="font-bold text-sm text-indigo-950">Celengan Target Impian</h3>
                        </div>
                        <button onclick="bukaModalTarget()" class="text-[10px] bg-orange-500 hover:bg-orange-600 text-white font-bold px-3 py-1.5 rounded-lg cursor-pointer transition-all shadow-xs">+ Target</button>
                    </div>
                    <div class="space-y-4 text-xs">
                        <?php if (empty($saving_goals)): ?>
                            <p class="text-slate-400 italic text-center py-2">Belum ada target impian.</p>
                        <?php endif; ?>
                        <?php foreach ($saving_goals as $goal): 
                            $persen = $goal['nominal_target'] > 0 ? round(($goal['nominal_terkumpul'] / $goal['nominal_target']) * 100) : 0;
                        ?>
                            <div class="bg-slate-50/60 p-4 rounded-xl border border-slate-100 space-y-3">
                                <div class="flex justify-between font-bold">
                                    <span class="text-slate-700"><?= htmlspecialchars($goal['nama_target']) ?></span>
                                    <span class="text-orange-600"><?= $persen ?>% Terpenuhi</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden"><div class="gradient-senja h-2 rounded-full" style="width: <?= $persen ?>%"></div></div>
                                <p class="text-[10px] text-slate-400">Terkumpul: <strong class="text-indigo-950">Rp <?= number_format($goal['nominal_terkumpul'], 0, ',', '.') ?></strong> / Rp <?= number_format($goal['nominal_target'], 0, ',', '.') ?></p>
                                <form action="/proses_nabung" method="POST" class="flex gap-1.5 pt-1">
                                    <input type="hidden" name="goal_id" value="<?= $goal['id'] ?>">
                                    <input type="number" name="nominal_setor" required placeholder="Masukkan nominal (Rp)" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-[10px] focus:outline-none focus:border-orange-400">
                                    <button type="submit" class="bg-indigo-950 hover:bg-indigo-900 text-white font-bold px-3 rounded-lg text-[10px] transition-all">Setor</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🛒</span>
                            <div>
                                <h3 class="font-bold text-sm text-indigo-950">Rencana Belanja Bulanan</h3>
                                <p class="text-[9px] text-slate-400">Daftar kebutuhan masa depan.</p>
                            </div>
                        </div>
                        <button onclick="bukaModalBelanja()" class="text-[10px] bg-indigo-950 hover:bg-indigo-900 text-white font-bold px-3 py-1.5 rounded-lg shrink-0 transition-all shadow-xs">+ Daftar</button>
                    </div>
                    <div class="overflow-x-auto border border-slate-100 rounded-xl">
                        <table class="w-full text-left text-[11px] border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 border-b border-slate-100 font-bold">
                                    <th class="p-3">Nama Barang</th>
                                    <th class="p-3 text-right">Estimasi</th>
                                    <th class="p-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-600">
                                <?php if (empty($shopping_plans)): ?>
                                    <tr><td colspan="3" class="p-4 text-center text-slate-400 italic">Belum ada item rencana.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($shopping_plans as $plan): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="p-3 <?= $plan['status_beli'] === 'sudah' ? 'line-through text-slate-300' : 'font-medium text-slate-700' ?>">
                                                <?= htmlspecialchars($plan['nama_barang']) ?>
                                            </td>
                                            <td class="p-3 text-right <?= $plan['status_beli'] === 'sudah' ? 'text-slate-300' : 'font-bold text-slate-800' ?>">
                                                Rp <?= number_format($plan['estimasi_harga'], 0, ',', '.') ?>
                                            </td>
                                            <td class="p-3 text-center space-x-2 whitespace-nowrap">
                                                <?php if ($plan['status_beli'] === 'belum'): ?>
                                                    <a href="/fitur_plus?action_belanja=check&id=<?= $plan['id'] ?>" class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded text-[9px] font-bold hover:bg-emerald-100 transition-all">Beli</a>
                                                <?php else: ?>
                                                    <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded text-[9px] font-bold">Selesai</span>
                                                <?php endif; ?>
                                                <a href="/fitur_plus?action_belanja=hapus&id=<?= $plan['id'] ?>" onclick="return confirm('Hapus rencana ini?')" class="text-slate-400 hover:text-rose-600 transition-all">❌</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🔔</span>
                            <div>
                                <h3 class="font-bold text-sm text-indigo-950">Kalender Tagihan Kosan</h3>
                                <p class="text-[9px] text-slate-400">Alarm rutin pengeluaran wajib.</p>
                            </div>
                        </div>
                        <button onclick="bukaModalTagihan()" class="text-[10px] bg-indigo-950 hover:bg-indigo-900 text-white font-bold px-3 py-1.5 rounded-lg cursor-pointer transition-all shadow-xs">+ Pasang</button>
                    </div>

                    <div class="space-y-3.5 text-xs max-h-64 overflow-y-auto pr-1">
                        <?php if (empty($all_bills)): ?>
                            <p class="text-slate-400 italic text-center py-6">Belum ada daftar tagihan tercatat.</p>
                        <?php else: ?>
                            <?php foreach ($all_bills as $bill): 
                                $sisa_hari = (int)floor((strtotime($bill['jatuh_tempo']) - strtotime(date('Y-m-d'))) / 86400);
                            ?>
                                <div class="p-3.5 rounded-xl border <?= $bill['status_bayar'] === 'lunas' ? 'bg-emerald-50/40 border-emerald-100' : 'bg-rose-50/40 border-rose-100' ?> flex justify-between items-center">
                                    <div class="space-y-1">
                                        <p class="font-bold text-slate-800 text-[12px] truncate max-w-[140px]"><?= htmlspecialchars($bill['nama_tagihan']) ?></p>
                                        <p class="text-[11px] font-black text-indigo-950">Rp <?= number_format($bill['nominal'], 0, ',', '.') ?></p>
                                        <p class="text-[10px] <?= $sisa_hari <= 2 && $bill['status_bayar'] === 'belum' ? 'text-rose-600 font-bold animate-pulse' : 'text-slate-400' ?>">
                                            📅 <?= $bill['status_bayar'] === 'lunas' ? 'Lunas' : ($sisa_hari < 0 ? 'Terlewat' : $sisa_hari . ' hari lagi') ?>
                                        </p>
                                    </div>
                                    <div class="flex gap-1.5 shrink-0">
                                        <?php if ($bill['status_bayar'] === 'belum'): ?>
                                            <button onclick="bukaModalBayarStruk(<?= $bill['id'] ?>, '<?= htmlspecialchars($bill['nama_tagihan'], ENT_QUOTES) ?>')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold px-3 py-1.5 rounded shadow-xs transition-all cursor-pointer">Bayar</button>
                                        <?php endif; ?>
                                        <a href="/fitur_plus?action_tagihan=hapus&id=<?= $bill['id'] ?>" onclick="return confirm('Hapus tagihan ini?')" class="bg-slate-100 text-slate-500 hover:text-rose-600 text-[10px] p-1.5 rounded transition-all">❌</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="text-base">📊</span>
                        <div>
                            <h3 class="font-bold text-sm text-indigo-950">Kalkulator Alokasi Pintar (50/30/20)</h3>
                            <p class="text-[9px] text-slate-400">Bagi dana bulanan secara otomatis.</p>
                        </div>
                    </div>
                    <div class="space-y-4 text-xs">
                        <div>
                            <input type="number" id="input_saku" oninput="hitungAlokasi()" placeholder="Ketik Uang Saku Bulanan (Rp)" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/40 focus:border-orange-400">
                        </div>

                        <div class="grid grid-cols-3 gap-2.5 text-center pt-1">
                            <div class="bg-indigo-50/70 p-3 rounded-xl border border-indigo-100">
                                <p class="text-[9px] font-bold text-indigo-950 uppercase tracking-wider">🍔 Pokok</p>
                                <p id="pos_pokok" class="font-black text-indigo-900 text-[11px] mt-1.5">Rp 0</p>
                            </div>
                            <div class="bg-amber-50/70 p-3 rounded-xl border border-amber-100">
                                <p class="text-[9px] font-bold text-amber-950 uppercase tracking-wider">☕ Keinginan</p>
                                <p id="pos_keinginan" class="font-black text-amber-900 text-[11px] mt-1.5">Rp 0</p>
                            </div>
                            <div class="bg-emerald-50/70 p-3 rounded-xl border border-emerald-100">
                                <p class="text-[9px] font-bold text-emerald-950 uppercase tracking-wider">💰 Tabung</p>
                                <p id="pos_tabungan" class="font-black text-emerald-900 text-[11px] mt-1.5">Rp 0</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🔒</span>
                        <h3 class="font-bold text-sm text-indigo-950">Keamanan PIN (Simulation)</h3>
                    </div>
                    <button onclick="aktifkanSimulasiPin()" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 rounded-xl text-xs cursor-pointer transition-all shadow-xs">🛡️ Uji Simulasi Kunci Aplikasi</button>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="text-base">📸</span>
                        <h3 class="font-bold text-sm text-indigo-950">Scanner & Arsip Berkas Nota</h3>
                    </div>
                    <form action="/fitur_plus" method="POST" enctype="multipart/form-data" class="space-y-3 text-xs">
                        <input type="hidden" name="action" value="upload_struk">
                        <div class="border-2 border-dashed border-slate-200 hover:border-orange-400 rounded-xl p-5 text-center bg-slate-50/50 relative transition-all group">
                            <input type="file" name="struk_file" required accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                            <span class="text-2xl block group-hover:scale-110 transition-transform">📂</span>
                            <p class="font-bold text-slate-600 text-[11px] mt-1.5">Pilih File Gambar Struk / Nota</p>
                            <p class="text-[9px] text-slate-400 mt-0.5">Format: JPG, JPEG, PNG (Maks. 2MB)</p>
                        </div>
                        <button type="submit" class="w-full gradient-senja text-white text-[11px] font-bold py-2.5 rounded-xl shadow-xs cursor-pointer hover:opacity-95 transition-all">Unggah & Kunci Arsip 🚀</button>
                    </form>

                    <div class="border-t border-slate-100 pt-3 space-y-2.5">
                        <p class="font-bold text-[11px] text-indigo-950 flex items-center gap-1.5">🗂️ Lemari Riwayat Berkas Arsip</p>
                        <div class="overflow-x-auto max-h-44 overflow-y-auto border border-slate-100 rounded-xl">
                            <table class="w-full text-left text-[11px] border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-100 font-bold">
                                        <th class="p-2.5">Pratinjau</th>
                                        <th class="p-2.5">Berkas</th>
                                        <th class="p-2.5 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($uploaded_receipts)): ?>
                                        <tr><td colspan="3" class="p-3 text-center text-slate-400 italic">Belum ada nota.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($uploaded_receipts as $rcpt): ?>
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <td class="p-2.5">
                                                    <div class="w-9 h-9 bg-slate-100 flex items-center justify-center rounded border border-slate-200 text-base" title="<?= htmlspecialchars($rcpt['file_name']) ?>">📄</div>
                                                </td>
                                                <td class="p-2.5 text-slate-600 text-[10px]"><p class="font-semibold text-slate-700 truncate max-w-[100px]"><?= htmlspecialchars($rcpt['file_name']) ?></p></td>
                                                <td class="p-2.5 text-center"><span class="text-slate-400 text-[9px] italic">Terarsip (/tmp)</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">💡</span>
                            <h3 class="font-bold text-sm text-indigo-950">Skor Kesehatan Finansial</h3>
                        </div>
                        <p class="text-[9px] text-slate-400 mt-1">Analisis berdasarkan transaksi aktif bulan ini.</p>
                    </div>

                    <div class="border border-slate-100 rounded-xl overflow-hidden text-[11px]">
                        <div class="grid grid-cols-2 divide-x divide-slate-100 border-b border-slate-100 bg-slate-50/60 p-2.5 text-center font-bold text-slate-500">
                            <div>Pemasukan</div>
                            <div>Pengeluaran</div>
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-slate-100 text-center font-extrabold text-[12px] p-3 bg-white">
                            <div class="text-emerald-600">Rp <?= number_format($total_income, 0, ',', '.') ?></div>
                            <div class="text-rose-600">Rp <?= number_format($total_expense, 0, ',', '.') ?></div>
                        </div>
                    </div>

                    <div class="p-3.5 flex items-center gap-3 rounded-xl <?= $score_color ?>">
                        <div class="text-2xl font-black tracking-tight shrink-0"><?= $financial_score ?>%</div>
                        <div class="space-y-0.5">
                            <p class="text-[8px] uppercase font-bold tracking-wider opacity-70">Status Kondisi:</p>
                            <p class="font-bold text-[12px] leading-tight"><?= $status_health ?></p>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3.5 border border-slate-100 rounded-xl text-[11px] text-slate-600 leading-relaxed">
                        <strong class="text-indigo-950 block mb-0.5">💡 Rekomendasi Sistem:</strong>
                        <?= $tips_health ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <div id="bayarStrukModal" class="hidden fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-slate-100 space-y-4">
            <div class="flex justify-between items-center border-b pb-2">
                <h3 class="font-bold text-indigo-950 text-sm">💸 Konfirmasi Bayar Tagihan</h3>
                <button onclick="tutupModalBayarStruk()" class="text-slate-400 font-bold hover:text-slate-600">✕</button>
            </div>
            <p class="text-xs text-slate-500">Membayar tagihan: <strong id="nama_tagihan_display" class="text-indigo-950"></strong></p>
            
            <form action="/fitur_plus" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                <input type="hidden" name="action" value="bayar_tagihan_struk">
                <input type="hidden" name="id_tagihan" id="input_id_tagihan">
                
                <div class="space-y-1.5">
                    <label class="font-bold text-slate-600 block">Upload Bukti Transfer / Struk</label>
                    <div class="border border-slate-200 rounded-xl p-3 bg-slate-50 relative text-center">
                        <input type="file" name="struk_file" required accept="image/*" class="w-full text-slate-600 text-xs cursor-pointer">
                    </div>
                    <p class="text-[9px] text-slate-400">Format yang diterima: JPG, JPEG, PNG (Maks. 2MB)</p>
                </div>
                
                <button type="submit" class="w-full gradient-senja text-white font-bold py-2.5 rounded-xl transition-all shadow-xs">Konfirmasi Lunas & Upload Struk ✨</button>
            </form>
        </div>
    </div>

    <div id="belanjaModal" class="hidden fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-slate-100 space-y-4">
            <div class="flex justify-between items-center border-b pb-2"><h3 class="font-bold text-indigo-950 text-sm">🛒 Tambah Rencana Belanja</h3><button onclick="tutupModalBelanja()" class="text-slate-400 font-bold hover:text-slate-600">✕</button></div>
            <form action="/fitur_plus" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="action" value="tambah_belanja">
                <div><label class="font-bold text-slate-600 block mb-1">Nama Barang / Keperluan</label><input type="text" name="nama_barang" required placeholder="Contoh: Skincare Bulanan" class="w-full bg-slate-50 border rounded-xl p-2.5"></div>
                <div><label class="font-bold text-slate-600 block mb-1">Estimasi Harga (Rp)</label><input type="number" name="estimasi_harga" required placeholder="Contoh: 120000" class="w-full bg-slate-50 border rounded-xl p-2.5"></div>
                <button type="submit" class="w-full gradient-senja text-white font-bold py-2.5 rounded-xl transition-all shadow-xs">Masukkan Rencana ✨</button>
            </form>
        </div>
    </div>

    <div id="tagihanModal" class="hidden fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-slate-100 space-y-4">
            <div class="flex justify-between items-center border-b pb-2"><h3 class="font-bold text-indigo-950 text-sm">🔔 Pasang Pengingat Tagihan</h3><button onclick="tutupModalTagihan()" class="text-slate-400 font-bold hover:text-slate-600">✕</button></div>
            <form action="/fitur_plus" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="action" value="tambah_tagihan">
                <div><label class="font-bold text-slate-600 block mb-1">Nama Tagihan</label><input type="text" name="nama_tagihan" required placeholder="Contoh: Uang Kos Juni" class="w-full bg-slate-50 border rounded-xl p-2.5"></div>
                <div><label class="font-bold text-slate-600 block mb-1">Jumlah Nominal (Rp)</label><input type="number" name="nominal_tagihan" required placeholder="Contoh: 500000" class="w-full bg-slate-50 border rounded-xl p-2.5"></div>
                <div><label class="font-bold text-slate-600 block mb-1">Tanggal Jatuh Tempo</label><input type="date" name="jatuh_tempo" required class="w-full bg-slate-50 border rounded-xl p-2.5"></div>
                <button type="submit" class="w-full gradient-senja text-white font-bold py-2.5 rounded-xl transition-all shadow-xs">Simpan Alarm Tagihan ⏰</button>
            </form>
        </div>
    </div>

    <div id="targetModal" class="hidden fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 z-50"><div class="bg-white rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-slate-100 space-y-4"><div class="border-b pb-2 flex justify-between items-center"><h3 class="font-bold text-indigo-950 text-sm">🎯 Pasang Target Tabungan</h3><button onclick="tutupModalTarget()" class="text-slate-400 font-bold hover:text-slate-600">✕</button></div><form action="/fitur_plus" method="POST" class="space-y-4 text-xs"><input type="hidden" name="action" value="tambah_target"><div><label class="font-bold text-slate-600 block mb-1">Nama Target</label><input type="text" name="nama_target" required placeholder="Contoh: Beli HP" class="w-full bg-slate-50 border rounded-xl p-2.5"></div><div><label class="font-bold text-slate-600 block mb-1">Nominal (Rp)</label><input type="number" name="nominal_target" required placeholder="Contoh: 3000000" class="w-full bg-slate-50 border rounded-xl p-2.5"></div><button type="submit" class="w-full gradient-senja text-white font-bold py-2.5 rounded-xl transition-all shadow-xs">Simpan Rencana 🚀</button></form></div></div>

    <div id="pinModal" class="hidden fixed inset-0 bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4 z-50"><div class="w-full max-w-[260px] text-center space-y-6"><div class="space-y-2"><span>🌅</span><h3 class="font-bold text-white text-base">SenjaTrack App Lock</h3><p class="text-xs text-slate-400">Masukkan PIN Keamanan</p></div><div class="flex justify-center gap-4 py-2"><?php for($k=0;$k<6;$k++): ?><div class="pin-dot w-3 h-3 rounded-full bg-slate-700 transition-colors"></div><?php endfor; ?></div><div class="grid grid-cols-3 gap-4 max-w-[240px] mx-auto"><?php for($i=1; $i<=9; $i++): ?><button onclick="pressPin()" class="w-12 h-12 rounded-full border border-slate-700 text-white font-bold text-sm flex items-center justify-center transition-all cursor-pointer mx-auto hover:bg-slate-800"><?= $i ?></button><?php endfor; ?><button onclick="resetPin()" class="w-12 h-12 text-slate-500 font-semibold text-[10px] flex items-center justify-center mx-auto">Reset</button><button onclick="pressPin()" class="w-12 h-12 rounded-full border border-slate-700 text-white font-bold text-sm flex items-center justify-center mx-auto hover:bg-slate-800">0</button><button onclick="tutupPinModal()" class="w-12 h-12 text-rose-500 font-bold text-[10px] flex items-center justify-center mx-auto">Batal</button></div></div></div>

    <footer class="bg-indigo-950 text-slate-500 text-[10px] py-5 text-center border-t border-indigo-900/40"><p>&copy; 2026 SenjaTrack Advanced Core</p></footer>

    <script>
        function bukaModalTarget() { document.getElementById('targetModal').classList.remove('hidden'); }
        function tutupModalTarget() { document.getElementById('targetModal').classList.add('hidden'); }
        function bukaModalTagihan() { document.getElementById('tagihanModal').classList.remove('hidden'); }
        function tutupModalTagihan() { document.getElementById('tagihanModal').classList.add('hidden'); }
        function bukaModalBelanja() { document.getElementById('belanjaModal').classList.remove('hidden'); }
        function tutupModalBelanja() { document.getElementById('belanjaModal').classList.add('hidden'); }
        
        function bukaModalBayarStruk(id, namaTagihan) {
            document.getElementById('input_id_tagihan').value = id;
            document.getElementById('nama_tagihan_display').innerText = namaTagihan;
            document.getElementById('bayarStrukModal').classList.remove('hidden');
        }
        function tutupModalBayarStruk() {
            document.getElementById('bayarStrukModal').classList.add('hidden');
        }

        function hitungAlokasi() {
            const saku = parseFloat(document.getElementById('input_saku').value) || 0;
            const kebutuhan = saku * 0.5;
            const keinginan = saku * 0.3;
            const tabungan   = saku * 0.2;
            
            document.getElementById('pos_pokok').innerText = "Rp " + kebutuhan.toLocaleString('id-ID');
            document.getElementById('pos_keinginan').innerText = "Rp " + keinginan.toLocaleString('id-ID');
            document.getElementById('pos_tabungan').innerText = "Rp " + tabungan.toLocaleString('id-ID');
        }

        let pinCount = 0;
        function aktifkanSimulasiPin() { document.getElementById('pinModal').classList.remove('hidden'); resetPin(); }
        function tutupPinModal() { document.getElementById('pinModal').classList.add('hidden'); }
        function pressPin() { if (pinCount < 6) { const dots = document.querySelectorAll('.pin-dot'); dots[pinCount].classList.remove('bg-slate-700'); dots[pinCount].classList.add('bg-orange-500'); pinCount++; if (pinCount === 6) { setTimeout(() => { alert("🎉 PIN Benar! Simulasi Kunci Aplikasi Berhasil Terbuka."); tutupPinModal(); }, 250); } } }
        function resetPin() { pinCount = 0; const dots = document.querySelectorAll('.pin-dot'); dots.forEach(dot => { dot.classList.remove('bg-orange-500'); dot.classList.add('bg-slate-700'); }); }
    </script>
</body>
</html>