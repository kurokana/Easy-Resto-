<?php
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$username = "root";
$password = "";
$database = "easyresto";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->query("SET time_zone = '+07:00'");

$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>