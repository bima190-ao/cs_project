<?php
// Sertakan file konfigurasi lokal
require_once 'config.php';

// Format TNS / Connection String untuk Oracle menggunakan konstanta dari config.php
$tns = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = " . DB_HOST . ")(PORT = " . DB_PORT . ")) (CONNECT_DATA = (SERVICE_NAME = " . DB_SERVICE_NAME . ")))";

try {
    // Membuat koneksi PDO Oracle
    $conn = new PDO("oci:dbname=" . $tns, DB_USERNAME, DB_PASSWORD);
    
    // Mengatur mode error PDO ke Exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mengatur fetch mode default menjadi associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // echo "Koneksi ke Oracle Berhasil!";
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>