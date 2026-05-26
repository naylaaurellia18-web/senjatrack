<?php
// Konfigurasi koneksi ke TiDB Cloud (Versi PDO PHP 8.5+)
$host     = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com'; 
$port     = 4000; 
$user     = '3vTUmEehdVYc5pg.root';
$password = 'Pd8EOwUWoHfM5feG';
$database = 'senjatrack_db';

// FIX: Coba beberapa path SSL certificate yang umum di berbagai environment Linux
$ssl_ca_paths = [
    '/etc/pki/tls/certs/ca-bundle.crt',      // Amazon Linux / CentOS
    '/etc/ssl/certs/ca-certificates.crt',     // Debian / Ubuntu
    '/etc/ssl/cert.pem',                      // Alpine / macOS
];

$ssl_ca = null;
foreach ($ssl_ca_paths as $path) {
    if (file_exists($path)) {
        $ssl_ca = $path;
        break;
    }
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // FIX: Hanya tambahkan SSL_CA jika path ditemukan di sistem
    if ($ssl_ca !== null) {
        $options[\Pdo\Mysql::ATTR_SSL_CA] = $ssl_ca;
    }

    // Membuat koneksi PDO
    $pdo = new PDO($dsn, $user, $password, $options);

} catch (PDOException $e) {
    die("Koneksi ke database Senja Track gagal: " . $e->getMessage());
}
?>