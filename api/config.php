<?php
// ============================================================
// KONEKSI DATABASE - TiDB Cloud
// ============================================================
$host     = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com';
$port     = 4000;
$user     = '3vTUmEehdVYc5pg.root';
$password = 'Pd8EOwUWoHfM5feG';
$database = 'senjatrack_db';

$ssl_ca_paths = [
    '/etc/pki/tls/certs/ca-bundle.crt',
    '/etc/ssl/certs/ca-certificates.crt',
    '/etc/ssl/cert.pem',
];
$ssl_ca = null;
foreach ($ssl_ca_paths as $path) {
    if (file_exists($path)) { $ssl_ca = $path; break; }
}

try {
    $dsn     = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if ($ssl_ca !== null) {
        $options[\Pdo\Mysql::ATTR_SSL_CA] = $ssl_ca;
    }
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die("Koneksi ke database Senja Track gagal: " . $e->getMessage());
}

// ============================================================
// FIX #1: INISIALISASI & PERBAIKAN STRUKTUR TABEL
// ------------------------------------------------------------
// PENTING: Setiap tabel dibungkus try-catch SENDIRI.
// Dulu semua dalam 1 blok try-catch — kalau 1 gagal,
// semua tabel di bawahnya tidak pernah diproses!
// ============================================================

// Helper: paksa kolom id jadi AUTO_INCREMENT PRIMARY KEY di TiDB
// TiDB mensyaratkan kolom harus PRIMARY KEY sebelum bisa AUTO_INCREMENT
function fixAutoIncrement(PDO $pdo, string $table): void {
    // Langkah 1: Coba langsung MODIFY dengan PRIMARY KEY
    try {
        $pdo->exec("ALTER TABLE `$table` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
        return;
    } catch (PDOException $e) {}

    // Langkah 2: Kalau gagal (mungkin sudah ada PK), coba tanpa PRIMARY KEY
    try {
        $pdo->exec("ALTER TABLE `$table` MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
        return;
    } catch (PDOException $e) {}

    // Langkah 3: Kalau masih gagal (PK di kolom lain), drop PK dulu lalu re-add
    try {
        $pdo->exec("ALTER TABLE `$table` DROP PRIMARY KEY, MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
    } catch (PDOException $e) {}
}

// --- Tabel users ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        nama       VARCHAR(255) NOT NULL,
        email      VARCHAR(255) NOT NULL UNIQUE,
        password   VARCHAR(255) NOT NULL,
        created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}
fixAutoIncrement($pdo, 'users');

// --- Tabel transactions ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id       INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id  INT          NOT NULL,
        tipe     ENUM('pemasukan','pengeluaran') NOT NULL,
        jumlah   FLOAT        NOT NULL,
        kategori VARCHAR(255) NOT NULL,
        tanggal  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}
fixAutoIncrement($pdo, 'transactions');

// --- Tabel saving_goals ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS saving_goals (
        id                INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id           INT          NOT NULL,
        nama_target       VARCHAR(255) NOT NULL,
        nominal_target    FLOAT        NOT NULL,
        nominal_terkumpul FLOAT        NOT NULL DEFAULT 0
    )");
} catch (PDOException $e) {}
fixAutoIncrement($pdo, 'saving_goals');

// --- Tabel bills ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bills (
        id           INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id      INT          NOT NULL,
        nama_tagihan VARCHAR(255) NOT NULL,
        nominal      FLOAT        NOT NULL,
        jatuh_tempo  DATE         NOT NULL,
        status_bayar ENUM('belum','lunas') DEFAULT 'belum'
    )");
} catch (PDOException $e) {}
fixAutoIncrement($pdo, 'bills');

// --- Tabel shopping_plans ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS shopping_plans (
        id             INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id        INT          NOT NULL,
        nama_barang    VARCHAR(255) NOT NULL,
        estimasi_harga FLOAT        NOT NULL,
        status_beli    ENUM('belum','sudah') DEFAULT 'belum'
    )");
} catch (PDOException $e) {}
fixAutoIncrement($pdo, 'shopping_plans');

// --- Tabel receipts ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS receipts (
        id        INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id   INT          NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        tanggal   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}
fixAutoIncrement($pdo, 'receipts');

// --- Tabel php_sessions ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS php_sessions (
        session_id    VARCHAR(128) NOT NULL PRIMARY KEY,
        session_data  MEDIUMTEXT   NOT NULL,
        last_activity INT          NOT NULL
    )");
} catch (PDOException $e) {}

// ============================================================
// FIX #2: DATABASE-BACKED SESSION HANDLER UNTUK VERCEL
// ------------------------------------------------------------
// Vercel PHP = serverless, /tmp tidak di-share antar container.
// Session disimpan ke TiDB agar persist di semua container.
// ============================================================
class DbSessionHandler implements SessionHandlerInterface {
    private PDO $pdo;
    private int $lifetime;

    public function __construct(PDO $pdo, int $lifetime = 7200) {
        $this->pdo      = $pdo;
        $this->lifetime = $lifetime;
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false {
        $stmt = $this->pdo->prepare(
            "SELECT session_data FROM php_sessions
             WHERE session_id = :id AND last_activity > :expire"
        );
        $stmt->execute(['id' => $id, 'expire' => time() - $this->lifetime]);
        $row = $stmt->fetch();
        return $row ? $row['session_data'] : '';
    }

    public function write(string $id, string $data): bool {
        $stmt = $this->pdo->prepare(
            "REPLACE INTO php_sessions (session_id, session_data, last_activity)
             VALUES (:id, :data, :time)"
        );
        return $stmt->execute(['id' => $id, 'data' => $data, 'time' => time()]);
    }

    public function destroy(string $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE session_id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function gc(int $maxlifetime): int|false {
        $stmt = $this->pdo->prepare(
            "DELETE FROM php_sessions WHERE last_activity < :expire"
        );
        $stmt->execute(['expire' => time() - $maxlifetime]);
        return $stmt->rowCount();
    }
}

$sessionHandler = new DbSessionHandler($pdo);
session_set_save_handler($sessionHandler, true);