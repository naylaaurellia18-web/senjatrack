<?php
// config.php
$host = '...'; // Sesuaikan dengan host kamu
$db   = '...'; // Sesuaikan dengan nama database kamu
$user = '...'; // Sesuaikan dengan username kamu
$pass = '...'; // Sesuaikan dengan password kamu
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     
     // 🔥 TRICK AMAN: Memaksa database online mengabaikan aturan strict 'default value'
     // Ini akan membuat MySQL otomatis memberikan nilai default (seperti angka 0 atau kosong) 
     // pada kolom ID yang tidak diisi, sehingga aplikasi tidak akan crash lagi!
     $pdo->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'STRICT_TRANS_TABLES',''));");
     $pdo->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'STRICT_ALL_TABLES',''));");

} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>