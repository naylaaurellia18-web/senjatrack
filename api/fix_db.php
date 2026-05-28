<?php
require_once 'config.php';
session_start();

// Daftar semua ALTER yang perlu dijalankan
$fixes = [
    'users'          => "ALTER TABLE `users` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY",
    'transactions'   => "ALTER TABLE `transactions` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY",
    'saving_goals'   => "ALTER TABLE `saving_goals` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY",
    'bills'          => "ALTER TABLE `bills` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY",
    'shopping_plans' => "ALTER TABLE `shopping_plans` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY",
    'receipts'       => "ALTER TABLE `receipts` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY",
];

$results = [];
foreach ($fixes as $table => $sql) {
    try {
        $pdo->exec($sql);
        $results[$table] = ['ok' => true, 'msg' => 'AUTO_INCREMENT berhasil dipasang'];
    } catch (PDOException $e) {
        // Kode 1060/1061/1068 = sudah AUTO_INCREMENT → anggap OK
        $code = $e->getCode();
        $msg  = $e->getMessage();
        $alreadyOk = (str_contains($msg, 'AUTO_INCREMENT') || $code == '42000');
        $results[$table] = ['ok' => $alreadyOk, 'msg' => $msg];
    }
}

// Cek apakah INSERT ke transactions bisa berjalan (test sebenarnya)
$insert_ok  = false;
$insert_msg = '';
try {
    $pdo->exec("INSERT INTO transactions (user_id, tipe, jumlah, kategori, tanggal) VALUES (0, 'pemasukan', 1, 'TEST_FIX_DB', NOW())");
    // Hapus data test
    $pdo->exec("DELETE FROM transactions WHERE kategori = 'TEST_FIX_DB'");
    $insert_ok  = true;
    $insert_msg = 'INSERT berhasil! Tabel transactions sudah berfungsi normal.';
} catch (PDOException $e) {
    $insert_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Fixer - SenjaTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen p-6 font-mono text-sm">
<div class="max-w-2xl mx-auto space-y-4">
    <h1 class="text-xl font-bold text-indigo-900">🔧 SenjaTrack — Database Fixer</h1>

    <div class="bg-white rounded-2xl p-5 shadow space-y-3">
        <h2 class="font-bold text-slate-700">Hasil ALTER TABLE per tabel:</h2>
        <?php foreach ($results as $table => $r): ?>
            <div class="flex items-start gap-3 p-3 rounded-xl <?= $r['ok'] ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' ?>">
                <span class="text-lg"><?= $r['ok'] ? '✅' : '❌' ?></span>
                <div>
                    <p class="font-bold"><?= $table ?></p>
                    <p class="text-xs opacity-70"><?= htmlspecialchars($r['msg']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow">
        <h2 class="font-bold text-slate-700 mb-3">Test INSERT nyata ke tabel transactions:</h2>
        <div class="p-3 rounded-xl <?= $insert_ok ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' ?>">
            <span class="text-lg"><?= $insert_ok ? '✅' : '❌' ?></span>
            <p class="text-xs mt-1"><?= htmlspecialchars($insert_msg) ?></p>
        </div>
    </div>

    <?php if ($insert_ok): ?>
        <a href="/dashboard" class="block text-center bg-indigo-900 text-white font-bold py-3 px-6 rounded-2xl shadow hover:bg-indigo-800">
            → Ke Dashboard
        </a>
    <?php else: ?>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-amber-800 text-xs">
            <strong>INSERT masih gagal.</strong> Ini berarti ALTER TABLE juga gagal dieksekusi oleh TiDB lewat PHP.<br><br>
            Kamu <strong>harus jalankan SQL ini langsung di TiDB Chat2Query</strong>:<br><br>
            <pre class="bg-white p-3 rounded-xl mt-2 text-[11px] overflow-x-auto">ALTER TABLE `transactions` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE `users` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE `saving_goals` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE `bills` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE `shopping_plans` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE `receipts` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY;</pre>
        </div>
    <?php endif; ?>
</div>
</body>
</html>