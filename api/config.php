<?php
// Konfigurasi koneksi ke TiDB Cloud (Versi PDO PHP 8.5+)
$host     = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com'; 
$port     = 4000; 
$user     = '3vTUmEehdVYc5pg.root';
$password = 'Pd8EOwUWoHfM5feG';
$database = 'senjatrack_db';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    
    // Konfigurasi SSL menggunakan standar baru PHP 8.5 (Pdo\Mysql)
    $options = [
        \Pdo\Mysql::ATTR_SSL_CA => '/etc/pki/tls/certs/ca-bundle.crt', // Sertifikat bawaan Linux OS Vercel
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Membuat koneksi PDO
    $pdo = new PDO($dsn, $user, $password, $options);

} catch (PDOException $e) {
    die("Koneksi ke database Senja Track gagal: " . $e->getMessage());
}
?>