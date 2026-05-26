<?php
// Konfigurasi koneksi ke TiDB Cloud (Versi PDO)
$host     = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com'; 
$port     = 4000; 
$user     = '3vTUmEehdVYc5pg.root';
$password = 'Pd8EOwUWoHfM5feG';
$database = 'senjatrack_db';

try {
    // Menyusun dsn dengan opsi SSL aktif
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true, // Mengaktifkan SSL untuk TiDB
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Membuat koneksi PDO menggunakan nama variabel $pdo
    $pdo = new PDO($dsn, $user, $password, $options);

} catch (PDOException $e) {
    die("Koneksi ke database Senja Track gagal: " . $e->getMessage());
}
?>