<?php
session_start();
// Proteksi halaman: Jika belum login atau bukan admin, tendang kembali ke login
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'koneksi.php';
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Oracle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { overflow-x: hidden; }
        .sidebar { min-height: 100vh; width: 250px; background-color: #212529; position: fixed; transition: all 0.3s; }
        .main-content { margin-left: 250px; padding: 20px; transition: all 0.3s; width: calc(100% - 250px); }
        .sidebar .nav-link { color: #adb5bd; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #343a40; color: #fff; }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3 text-white">
        <h4 class="text-center mb-4"><i class="bi bi-database-fill-gear me-2"></i>Admin</h4>
        <hr>
        <div class="mb-3 px-3 small text-muted">Selamat Datang, <br><strong class="text-white"><?= $_SESSION['user_name']; ?></strong></div>
        <hr>
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a href="dashboard.php?page=home" class="nav-link <?= $page=='home'?'active':''; ?>"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="dashboard.php?page=product" class="nav-link <?= $page=='product'?'active':''; ?>"><i class="bi bi-box-seam me-2"></i> Input Produk</a>
            </li>
            <li class="nav-item">
                <a href="dashboard.php?page=order" class="nav-link <?= $page=='order'?'active':''; ?>"><i class="bi bi-cart-check me-2"></i> Input Order</a>
            </li>
            <li class="nav-item">
                <a href="dashboard.php?page=review" class="nav-link <?= $page=='review'?'active':''; ?>"><i class="bi bi-chat-left-heart me-2"></i> Input Ulasan</a>
            </li>
        </ul>
        <div class="position-absolute bottom-0 start-0 w-100 p-3">
            <a href="auth.php?logout=true" class="btn btn-danger w-100"><i class="bi bi-box-arrow-left me-2"></i> Keluar / Logout</a>
        </div>
    </div>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4 rounded shadow-sm">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">Panel Kontrol Utama</span>
                <span class="badge bg-success p-2">Status: Connected to ORCLPDB</span>
            </div>
        </nav>

        <div class="container-fluid">
            <?php
            // Routing halaman form dinamis di dalam dashboard
            switch ($page) {
                case 'product':
                    include 'sub_form_product.php';
                    break;
                case 'order':
                    include 'sub_form_order.php';
                    break;
                case 'review':
                    include 'sub_form_review.php';
                    break;
                case 'home':
                default:
                    // Statistik / Ringkasan Data Utama Dashboard
                    try {
                        $u = $conn->query("SELECT count(*) as total FROM users")->fetch();
                        $p = $conn->query("SELECT count(*) as total FROM products")->fetch();
                        $o = $conn->query("SELECT count(*) as total FROM orders")->fetch();
                    } catch(PDOException $e) {}
                    ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white shadow-sm border-0">
                                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                    <div><h5>Total Users</h5><h3><?= $u['TOTAL'] ?? 0; ?></h3></div>
                                    <i class="bi bi-people h1 text-white-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white shadow-sm border-0">
                                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                    <div><h5>Total Produk</h5><h3><?= $p['TOTAL'] ?? 0; ?></h3></div>
                                    <i class="bi bi-box-seam h1 text-white-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark shadow-sm border-0">
                                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                    <div><h5>Transaksi Order</h5><h3><?= $o['TOTAL'] ?? 0; ?></h3></div>
                                    <i class="bi bi-cart h1 text-dark-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-4 border-0 shadow-sm p-4 text-center">
                        <h4>Sistem Manajemen Database Oracle</h4>
                        <p class="text-muted">Gunakan menu navigasi di bagian kiri untuk menyisipkan data langsung ke tabel relasional Anda.</p>
                    </div>
                    <?php
                    break;
            }
            ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>