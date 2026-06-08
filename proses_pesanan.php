<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        $_SESSION['msg_error'] = "Akses ditolak atau keranjang kosong.";
        header("Location: index.php?page=cart");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $total_price = $_POST['total_bayar'];
    $payment_method = $_POST['payment_method'];
    
    // Sesuaikan status awal
    $status = ($payment_method == 'COD') ? 'Success' : 'Pending'; 

    try {
        $conn->beginTransaction();

        // 1. Menggunakan HURUF BESAR untuk kolom Oracle
        $sql_order = "INSERT INTO orders (USER_ID, TOTAL_PRICE, STATUS, PAYMENT_METHOD, CREATED_AT) 
                      VALUES (:user_id, :total_price, :status, :payment_method, CURRENT_TIMESTAMP)
                      RETURNING ID INTO :inserted_id";
        
        $stmt = $conn->prepare($sql_order);
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':total_price', $total_price);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':payment_method', $payment_method);
        
        // Menampung ID baru dari Oracle
        $order_id = 0;
        $stmt->bindParam(':inserted_id', $order_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 10);
        
        $stmt->execute();
        
        // Validasi pengaman: pastikan Oracle melempar ID transaksi yang valid
        if (!$order_id || $order_id <= 0) {
            throw new PDOException("Oracle tidak mengembalikan ID Transaksi yang valid.");
        }

        // 2. Insert ke detail menggunakan HURUF BESAR untuk kolom
        $sql_item = "INSERT INTO order_details (ORDER_ID, PRODUCT_ID, QTY, PRICE) 
                      VALUES (:order_id, :product_id, :qty, :price)";
        $stmt_item = $conn->prepare($sql_item);

        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmt_item->execute([
                ':order_id'   => $order_id,
                ':product_id' => $product_id,
                ':qty'        => $item['qty'],
                ':price'      => $item['price']
            ]);
        }

        // Komit Transaksi ke Oracle
        $conn->commit();

        // Kosongkan keranjang jika sukses sebelum dilempar ke halaman nota
        unset($_SESSION['cart']);

        // 3. PERBAIKAN UTAMA: Alihkan halaman lewat index.php agar template navbar tetap utuh
        if ($payment_method == 'QRIS') {
            header("Location: index.php?page=bayar_qris&order_id=" . $order_id);
        } else {
            header("Location: index.php?page=bayar_cod&order_id=" . $order_id);
        }
        exit();

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['msg_error'] = "Gagal memproses pesanan: " . $e->getMessage();
        header("Location: index.php?page=cart");
        exit();
    }
} else {
    header("Location: index.php?page=cart");
    exit();
}