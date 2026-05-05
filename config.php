<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'dkj_apotek_sederhana';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Generate Vigenere Key from Server Info
function getVigenereKey() {
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
    $hostname = gethostname() ?: 'LOCALHOST';
    $raw_key = strtoupper($server_addr . $hostname);
    
    // Filter only A-Z
    $filtered_key = preg_replace("/[^A-Z]/", "", $raw_key);
    
    // Fallback if empty
    if (empty($filtered_key)) {
        $filtered_key = "APOTEK";
    }
    
    return $filtered_key;
}

$VIGENERE_KEY = getVigenereKey();
?>
