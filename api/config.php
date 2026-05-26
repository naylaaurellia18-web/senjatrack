<?php
// Konfigurasi koneksi ke TiDB Cloud
$host     = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com'; 
$port     = 4000; // TiDB menggunakan port 4000, bukan 3306
$user     = '3vTUmEehdVYc5pg.root';
$password = 'Pd8EOwUWoHfM5feG';
$database = 'senjatrack-db';

// Membuat koneksi ke server database
$koneksi = mysqli_connect($host, $user, $password, $database, $port);

// Cek apakah koneksi berhasil atau gagal
if (!$koneksi) {
    die("Koneksi ke database Senja Track gagal: " . mysqli_connect_error());
}
?>