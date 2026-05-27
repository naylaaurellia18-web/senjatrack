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
// Pastikan semua tabel ada dengan skema yang benar.
// ALTER TABLE dijalankan untuk memastikan kolom id
// selalu AUTO_INCREMENT meski tabel sudah terlanjur dibuat
// tanpa AUTO_INCREMENT (penyebab error 1364).
// ============================================================
try {
    // Tabel users — pastikan id AUTO_INCREMENT
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id       INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        nama     VARCHAR(255) NOT NULL,
        email    VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP  DEFAULT CURRENT_TIMESTAMP
    )");

    // FIX UTAMA: Paksa id menjadi AUTO_INCREMENT jika tabel
    // sudah terlanjur dibuat tanpa AUTO_INCREMENT
    $pdo->exec("ALTER TABLE users MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");

    // Tabel transactions
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id       INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id  INT          NOT NULL,
        tipe     ENUM('pemasukan','pengeluaran') NOT NULL,
        jumlah   FLOAT        NOT NULL,
        kategori VARCHAR(255) NOT NULL,
        tanggal  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("ALTER TABLE transactions MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");

    // Tabel saving_goals
    $pdo->exec("CREATE TABLE IF NOT EXISTS saving_goals (
        id                INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id           INT          NOT NULL,
        nama_target       VARCHAR(255) NOT NULL,
        nominal_target    FLOAT        NOT NULL,
        nominal_terkumpul FLOAT        NOT NULL DEFAULT 0
    )");
    $pdo->exec("ALTER TABLE saving_goals MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");

    // Tabel bills
    $pdo->exec("CREATE TABLE IF NOT EXISTS bills (
        id            INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id       INT          NOT NULL,
        nama_tagihan  VARCHAR(255) NOT NULL,
        nominal       FLOAT        NOT NULL,
        jatuh_tempo   DATE         NOT NULL,
        status_bayar  ENUM('belum','lunas') DEFAULT 'belum'
    )");
    $pdo->exec("ALTER TABLE bills MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");

    // Tabel shopping_plans
    $pdo->exec("CREATE TABLE IF NOT EXISTS shopping_plans (
        id             INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id        INT          NOT NULL,
        nama_barang    VARCHAR(255) NOT NULL,
        estimasi_harga FLOAT        NOT NULL,
        status_beli    ENUM('belum','sudah') DEFAULT 'belum'
    )");
    $pdo->exec("ALTER TABLE shopping_plans MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");

    // Tabel receipts
    $pdo->exec("CREATE TABLE IF NOT EXISTS receipts (
        id        INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id   INT          NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        tanggal   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("ALTER TABLE receipts MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");

} catch (PDOException $e) {
    // Jika ALTER gagal (misalnya sudah benar), abaikan dan lanjutkan
}

// ============================================================
// FIX #2: DATABASE-BACKED SESSION HANDLER UNTUK VERCEL
// ------------------------------------------------------------
// Vercel menjalankan PHP di container serverless berbeda tiap
// request. File session /tmp tidak di-share antar container,
// sehingga session hilang → redirect loop (ERR_TOO_MANY_REDIRECTS).
// Solusi: simpan session di TiDB agar bisa diakses dari mana saja.
// ============================================================
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS php_sessions (
        session_id    VARCHAR(128) NOT NULL PRIMARY KEY,
        session_data  MEDIUMTEXT   NOT NULL,
        last_activity INT          NOT NULL
    )");
} catch (PDOException $e) {}

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