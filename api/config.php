<?php
// Konfigurasi koneksi ke TiDB Cloud
$host     = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com'; 
$port     = 4000; 
$user     = '3vTUmEehdVYc5pg.root';
$password = 'Pd8EOwUWoHfM5feG';
$database = 'senjatrack_db'; // <--- DIUBAH MENJADI UNDERSCORE COCOK DENGAN DI TIDB

// Inisialisasi MySQLi
$koneksi = mysqli_init();

if (!$koneksi) {
    die("Inisialisasi MySQLi gagal");
}

// Mengaktifkan SSL
mysqli_options($koneksi, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);

// Lakukan koneksi menggunakan flag SSL
$real_connect = mysqli_real_connect($koneksi, $host, $user, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL);

// Cek apakah koneksi berhasil atau gagal
if (!$real_connect) {
    die("Koneksi ke database Senja Track gagal: " . mysqli_connect_error());
}
?>