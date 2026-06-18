<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_data'])) {
    $cart = json_decode($_POST['cart_data'], true);
    $nama_pelanggan = trim($_POST['nama_pelanggan']);
    $tanggal = date('Y-m-d H:i:s'); 

    if (empty($cart) || empty($nama_pelanggan)) {
        http_response_code(400);
        echo "Data transaksi tidak valid!";
        exit();
    }

    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += ($item['price'] * $item['qty']);
    }
    
    $ppn = round($subtotal * 0.10);
    $service = round($subtotal * 0.025);
    $total_final = $subtotal + $ppn + $service;

    $sql_transaksi = "INSERT INTO transaksi (nama_pelanggan, tanggal, subtotal, ppn, service, total) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_transaksi);
    $stmt->bind_param("ssiiii", $nama_pelanggan, $tanggal, $subtotal, $ppn, $service, $total_final);
    
    if ($stmt->execute()) {
        $id_transaksi = $conn->insert_id;

        $sql_detail = "INSERT INTO detail_transaksi (id_transaksi, id_menu, jumlah, subtotal) VALUES (?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_detail);
        
        foreach ($cart as $item) {
            $item_subtotal = $item['price'] * $item['qty'];
            $stmt_detail->bind_param("iiid", $id_transaksi, $item['id'], $item['qty'], $item_subtotal);
            $stmt_detail->execute();
        }

        $embed_mode = true; 
        include 'cetak_struk.php'; 
        
        exit();
    } else {
        http_response_code(500);
        echo "Error Database: " . $conn->error;
    }
} else {
    header("Location: dashboard.php");
}
?>