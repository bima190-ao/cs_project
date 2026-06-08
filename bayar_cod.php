<?php
// Pastikan file koneksi database terhubung
include 'koneksi.php';

$order_id = $_GET['order_id'] ?? null;

// Mengamankan output jika terjadi error agar layout navbar tetap muncul
function tampilkan_error($pesan) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pembayaran COD - Kebab & Burger</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
            .navbar-custom { background-color: #212529; color: #fff; padding: 15px; }
            .brand-title { color: #ffc107; font-weight: 700; text-decoration: none; }
        </style>
    </head>
    <body>
        <nav class="navbar-custom shadow-sm mb-4">
            <div class="container d-flex justify-content-between align-items-center">
                <a href="index.php" class="brand-title fs-4">🍔 KEBAB & BURGER</a>
            </div>
        </nav>

        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow border-0 text-center" style="border-radius: 15px;">
                        <div class="card-body p-5">
                            <div class="text-danger mb-3" style="font-size: 3rem;">⚠️</div>
                            <h4 class="fw-bold text-dark mb-3">Terjadi Kesalahan</h4>
                            <p class="text-muted"><?php echo htmlspecialchars($pesan); ?></p>
                            <a href="index.php" class="btn btn-warning px-4 fw-bold mt-2" style="border-radius: 8px;">Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

if (!$order_id) {
    tampilkan_error("Akses Ditolak! Nomor transaksi (Order ID) tidak ditemukan atau tidak valid.");
}

try {
    // SANGAT PENTING: Gunakan TO_CHAR agar format tanggal Oracle tidak merusak runtime PHP Anda!
    $sql = "SELECT total_price, TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') as tanggal_transaksi 
            FROM orders WHERE id = :order_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':order_id' => $order_id]);
    
    // Ambil data (Oracle secara default mengembalikan nama kolom dalam HURUF BESAR)
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        tampilkan_error("Data pesanan #$order_id tidak ditemukan di dalam sistem database kami.");
    }
} catch (PDOException $e) {
    tampilkan_error("Kesalahan Sistem Database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran COD #<?php echo htmlspecialchars($order_id); ?> - Kebab & Burger</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .card-cod {
            max-width: 450px; 
            width: 100%; 
            border-radius: 20px; 
            border: none;
            border-top: 6px solid #ffc107; 
            background-color: #ffffff;
        }
        .icon-box-cod {
            border: 3px dashed #ffc107;
            background-color: #fff;
            transition: transform 0.2s;
            padding: 30px;
            font-size: 4.5rem;
        }
        .icon-box-cod:hover {
            transform: scale(1.02);
        }
        .btn-custom-pay {
            border-radius: 10px;
            background-color: #ffc107;
            color: #212529;
            font-weight: 700;
            padding: 12px;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-custom-pay:hover {
            background-color: #e0a800;
            color: #000;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }
        .text-success-custom {
            color: #198754;
        }
    </style>
</head>
<body>

<div class="container my-5 d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card card-cod shadow-lg p-4 text-center">
        
        <div class="alert alert-success py-3 mb-4 small d-flex align-items-center justify-content-center fw-bold shadow-sm" style="border-radius: 10px;">
            <span class="fs-5 me-2">✔</span> Ulasan & Pesanan Anda Berhasil Dibuat!
        </div>

        <h5 class="fw-bold text-dark mb-1">Metode Pembayaran COD</h5>
        <p class="text-muted small mb-3">ID Pesanan: <span class="badge bg-dark text-warning fs-6">#<?php echo htmlspecialchars($order_id); ?></span></p>
        <hr class="my-2 text-muted opacity-25">
        
        <div class="bg-light rounded-3 py-3 my-3 border shadow-sm">
            <span class="small text-muted d-block text-uppercase tracking-wider" style="font-size: 0.75rem; font-weight: 600;">Total Tagihan Kuliner:</span>
            <h2 class="fw-bold text-success-custom m-0 mt-1">Rp <?php echo number_format($order['TOTAL_PRICE'], 0, ',', '.'); ?></h2>
        </div>

        <div class="my-3 d-inline-block rounded-3 shadow-sm icon-box-cod mx-auto">
            🚚💰
        </div>

        <div class="alert alert-warning py-3 mb-4 border-0 text-start shadow-sm" style="background-color: #fff3cd; border-radius: 12px;">
            <p class="text-dark small m-0" style="line-height: 1.5;">
                💡 <strong>Cara Membayar:</strong> Silakan siapkan uang tunai yang pas sesuai dengan total tagihan di atas. Pembayaran diberikan langsung ke kurir saat hidangan kuliner kamu sampai di rumah.
            </p>
        </div>
        
        <div>
            <a href="index.php?page=katalog" class="btn btn-custom-pay w-100 py-2.5 shadow-sm text-uppercase">
                Selesai / Kembali ke Katalog
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>