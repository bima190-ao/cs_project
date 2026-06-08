<?php
session_start();
include 'koneksi.php';

// ==========================================
// 1. LOGIKA KERANJANG BELANJA (ADD TO CART)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    $product_id = $_GET['id'];
    
    try {
        $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['qty']++;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name'  => $product['NAME'] ?? $product['name'],
                    'price' => $product['PRICE'] ?? $product['price'],
                    'image' => $product['IMAGE'] ?? $product['image'],
                    'qty'   => 1
                ];
            }
            $_SESSION['msg_success'] = "Produk " . ($product['NAME'] ?? $product['name']) . " berhasil dimasukkan ke keranjang!";
        }
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "Gagal menambahkan ke keranjang: " . $e->getMessage();
    }
    
    header("Location: index.php?page=katalog");
    exit();
}

// ==========================================
// 2. LOGIKA HAPUS ITEM / KOSONGKAN KERANJANG
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $product_id = $_GET['id'];
    unset($_SESSION['cart'][$product_id]);
    header("Location: index.php?page=cart");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'empty') {
    unset($_SESSION['cart']);
    header("Location: index.php?page=cart");
    exit();
}

// ==========================================
// 3. LOGIKA SIMPAN ULASAN BARU (BEBAS TEKS)
// ==========================================
if (isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['msg_error'] = "Anda harus login untuk memberikan ulasan.";
        header("Location: index.php?page=review");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment_text']; 

    try {
        $stmt_rev = $conn->prepare("INSERT INTO reviews (user_id, rating, comment_text) VALUES (:user_id, :rating, :comment_text)");
        
        $stmt_rev->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_rev->bindParam(':rating', $rating, PDO::PARAM_INT);
        $stmt_rev->bindParam(':comment_text', $comment, PDO::PARAM_STR);
        
        $stmt_rev->execute();
        $_SESSION['msg_success'] = "Ulasan Anda berhasil dikirim! Terima kasih.";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "Gagal mengirim ulasan: " . $e->getMessage();
    }
    
    header("Location: index.php?page=review");
    exit();
}

// ==========================================
// LOGIKA HAPUS ULASAN MANDIRI OLEH USER
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete_review') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['msg_error'] = "Anda harus login terlebih dahulu.";
        header("Location: index.php?page=review");
        exit();
    }

    $review_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    try {
        $stmt_del = $conn->prepare("DELETE FROM reviews WHERE id = :id AND user_id = :user_id");
        $stmt_del->execute([
            ':id' => $review_id,
            ':user_id' => $user_id
        ]);

        if ($stmt_del->rowCount() > 0) {
            $_SESSION['msg_success'] = "Ulasan Anda berhasil dihapus.";
        } else {
            $_SESSION['msg_error'] = "Anda tidak memiliki akses untuk menghapus ulasan ini.";
        }
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "Gagal menghapus ulasan: " . $e->getMessage();
    }

    header("Location: index.php?page=review");
    exit();
}

// ==========================================
// 4. HITUNG TOTAL ITEM DI KERANJANG (BADGE)
// ==========================================
$total_cart_items = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_cart_items += $item['qty'];
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'katalog';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UMKM Kebab & Burger - Pemesanan Online</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; display: flex; flex-direction: column; min-height: 100vh; }
        .navbar-brand { font-weight: 800; letter-spacing: 1px; }
        .product-card { transition: transform 0.2s; border: none; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        main { flex: 1; }
        
        .style-payment-option { transition: all 0.2s ease-in-out; cursor: pointer; }
        .style-payment-option:hover { background-color: #fffdf5; border-color: #ffc107 !important; }
        .form-check-input:checked ~ .form-check-label { color: #ff9800; font-weight: bold; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand text-warning" href="index.php?page=katalog">
                <i class="bi bi-shop me-2"></i>RAJA KEBAB & BURGER
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $page == 'katalog' ? 'active text-warning fw-bold' : ''; ?>" href="index.php?page=katalog">
                            <i class="bi bi-menu-button-wide me-1"></i> Menu Kuliner
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $page == 'review' ? 'active text-warning fw-bold' : ''; ?>" href="index.php?page=review">
                            <i class="bi bi-chat-left-heart me-1"></i> Ulasan
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php?page=cart" class="btn btn-outline-warning position-relative btn-sm px-3">
                        <i class="bi bi-cart3 me-1"></i> Keranjang
                        <?php if ($total_cart_items > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $total_cart_items; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <?php if (isset($_SESSION['user_name'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-warning btn-sm dropdown-toggle px-3" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i> Hai, <?= htmlspecialchars($_SESSION['user_name']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                                    <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Panel Admin</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="login.php"><i class="bi bi-box-arrow-right me-2"></i> Keluar / Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk / Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        
        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['msg_success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['msg_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['msg_error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['msg_error']); ?>
        <?php endif; ?>

        <?php if ($page == 'katalog'): ?>
            <?php
            try {
                $query = $conn->query("SELECT id, name, price, image FROM products ORDER BY id DESC");
                $products = $query->fetchAll();
            } catch (PDOException $e) {
                $products = [];
            }
            ?>
            
            <div class="row mb-4">
                <div class="col">
                    <h3 class="fw-bold text-dark">Selamat Datang di Kebab & Burger</h3>
                    <p class="text-muted">Silakan pilih menu favorit Anda di bawah ini dan lakukan pemesanan.</p>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $p): ?>
                        <div class="col">
                            <div class="card h-100 product-card shadow-sm">
                                <?php if (!empty($p['IMAGE'] ?? $p['image'])): ?>
                                    <img src="uploads/<?= $p['IMAGE'] ?? $p['image']; ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?= htmlspecialchars($p['NAME'] ?? $p['name']); ?>">
                                <?php else: ?>
                                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-image h1"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($p['NAME'] ?? $p['name']); ?></h5>
                                        <p class="text-success fw-bold fs-5 mb-3">Rp <?= number_format($p['PRICE'] ?? $p['price'], 0, ',', '.'); ?></p>
                                    </div>
                                    <a href="index.php?action=add&id=<?= $p['ID'] ?? $p['id']; ?>" class="btn btn-warning w-100 fw-semibold text-dark btn-sm">
                                        <i class="bi bi-bag-plus me-1"></i> Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-egg-fried text-muted display-1"></i>
                        <p class="text-muted mt-3">Maaf, menu masakan belum tersedia untuk saat ini.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($page == 'cart'): ?>
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-cart-check me-2 text-warning"></i>Isi Keranjang Anda</h5>
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <a href="index.php?action=empty" class="btn btn-outline-danger btn-sm" onclick="return confirm('Kosongkan keranjang belanja?');">
                                    <i class="bi bi-trash"></i> Kosongkan
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr>
                                                <th>Menu</th>
                                                <th>Harga</th>
                                                <th class="text-center">Jumlah</th>
                                                <th>Subtotal</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_belanja = 0;
                                            foreach ($_SESSION['cart'] as $id => $item): 
                                                $subtotal = $item['price'] * $item['qty'];
                                                $total_belanja += $subtotal;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <?php if (!empty($item['image'])): ?>
                                                                <img src="uploads/<?= $item['image']; ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="bg-secondary rounded text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                    <i class="bi bi-image"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <span class="fw-bold text-secondary"><?= htmlspecialchars($item['name']); ?></span>
                                                        </div>
                                                    </td>
                                                    <td>Rp <?= number_format($item['price'], 0, ',', '.'); ?></td>
                                                    <td class="text-center"><?= $item['qty']; ?>x</td>
                                                    <td class="text-success fw-bold">Rp <?= number_format($subtotal, 0, ',', '.'); ?></td>
                                                    <td class="text-center">
                                                        <a href="index.php?action=remove&id=<?= $id; ?>" class="text-danger"><i class="bi bi-x-circle-fill h5"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-cart-x display-3 text-body-tertiary"></i>
                                    <p class="mt-3">Keranjang belanja Anda masih kosong.</p>
                                    <a href="index.php?page=katalog" class="btn btn-warning btn-sm mt-2 px-4 fw-semibold text-dark">Lihat Daftar Menu</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark py-3 fw-bold">Ringkasan Pesanan</div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3 fs-5">
                                <span>Total Bayar:</span>
                                <span class="fw-bold text-success">Rp <?= isset($total_belanja) ? number_format($total_belanja, 0, ',', '.') : '0'; ?></span>
                            </div>
                            <hr>
                            
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <?php if (isset($_SESSION['user_name'])): ?>
                                    <form action="proses_pesanan.php" method="POST" id="formCheckout">
                                        <input type="hidden" name="total_bayar" value="<?= $total_belanja; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="fw-semibold small mb-2 d-block">Pilih Metode Pembayaran :</label>
                                            
                                            <div class="form-check p-2 border rounded mb-2 style-payment-option bg-light">
                                                <input class="form-check-input ms-1" type="radio" name="payment_method" value="QRIS" id="payQRIS" checked>
                                                <label class="form-check-label ms-2 small fw-bold text-dark" for="payQRIS">
                                                    QRIS (Automated) <span class="badge bg-danger ms-1">⚡ Instan</span>
                                                </label>
                                            </div>
                                            
                                            <div class="form-check p-2 border rounded mb-2 style-payment-option bg-light">
                                                <input class="form-check-input ms-1" type="radio" name="payment_method" value="COD" id="payCOD">
                                                <label class="form-check-label ms-2 small fw-bold text-dark" for="payCOD">
                                                    Cash on Delivery (COD)
                                                </label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                                            <i class="bi bi-wallet2 me-1"></i> Konfirmasi & Bayar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-warning small py-2 text-center">Anda harus login terlebih dahulu sebelum memesan kuliner.</div>
                                    <a href="login.php" class="btn btn-primary w-100 py-2 fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i> Login untuk Memesan</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 py-2 fw-bold" disabled>Keranjang Kosong</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($page == 'review'): ?>
            <?php
            try {
                $query_rev = $conn->query("SELECT r.id, r.user_id, r.rating, 
                                                  TRIM(r.comment_text) AS COMMENT_TEXT, 
                                                  r.created_at, u.name 
                                           FROM reviews r 
                                           JOIN users u ON r.user_id = u.id 
                                           ORDER BY r.id DESC");
                                           
                $all_reviews = $query_rev->fetchAll();
            } catch (PDOException $e) {
                $all_reviews = [];
            }
            ?>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-dark text-white fw-bold">Beri Ulasan Kami</div>
                        <div class="card-body">
                            <?php if (isset($_SESSION['user_name'])): ?>
                                <form action="index.php?page=review" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Rating Bintang</label>
                                        <select name="rating" class="form-select text-warning fw-bold" required>
                                            <option value="5">⭐⭐⭐⭐⭐ (5 - Sangat Enak)</option>
                                            <option value="4">⭐⭐⭐⭐ (4 - Enak)</option>
                                            <option value="3">⭐⭐⭐ (3 - Biasa Saja)</option>
                                            <option value="2">⭐⭐ (2 - Kurang Puas)</option>
                                            <option value="1">⭐ (1 - Kecewa)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Komentar / Ulasan</label>
                                        <textarea name="comment_text" class="form-control" rows="4" placeholder="Tulis pendapat Anda tentang makanan kami..." required></textarea>
                                    </div>
                                    <button type="submit" name="submit_review" class="btn btn-warning w-100 fw-bold text-dark">Kirim Ulasan</button>
                                </form>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <p class="text-muted small">Anda harus masuk akun terlebih dahulu sebelum dapat mengirim ulasan.</p>
                                    <a href="login.php" class="btn btn-primary btn-sm px-4 fw-semibold">Login Sekarang</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-chat-square-heart-fill text-danger me-2"></i>Apa Kata Mereka?</div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <?php if (count($all_reviews) > 0): ?>
                                <?php foreach ($all_reviews as $rev): ?>
                                    <?php 
                                        $komentar_db = $rev['COMMENT_TEXT'];
                                        if (is_resource($komentar_db)) {
                                            $komentar_teks = stream_get_contents($komentar_db);
                                        } else {
                                            $komentar_teks = $komentar_db;
                                        }

                                        $tanggal_raw = isset($rev['CREATED_AT']) ? $rev['CREATED_AT'] : (isset($rev['created_at']) ? $rev['created_at'] : 'now');
                                    ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="fw-bold mb-0 text-secondary"><?= htmlspecialchars($rev['NAME'] ?? $rev['name'] ?? ''); ?></h6>
                                            
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-muted" style="font-size: 11px;"><?= date('d M Y, H:i', strtotime($tanggal_raw)); ?></small>
                                                
                                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $rev['USER_ID']): ?>
                                                    <a href="index.php?action=delete_review&id=<?= $rev['ID'] ?? $rev['id']; ?>" 
                                                       class="text-danger small text-decoration-none fw-bold ms-2 border border-danger rounded px-1"
                                                       style="font-size: 10px;"
                                                       onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan ini?');">
                                                        <i class="bi bi-trash3"></i> Hapus
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-warning mb-2">
                                            <?= str_repeat('⭐', $rev['RATING'] ?? $rev['rating'] ?? 5); ?>
                                        </div>
                                        
                                        <p class="text-dark mb-0 bg-light p-2 rounded small">" <?= htmlspecialchars($komentar_teks); ?> "</p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center text-muted py-4 mb-0">Belum ada ulasan dari pelanggan untuk saat ini.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($page == 'bayar_qris'): ?>
            <div class="card shadow-sm border-0 p-4 bg-white">
                <?php 
                $_GET['order_id'] = $_GET['order_id'] ?? null;
                include 'bayar_qris.php'; 
                ?>
            </div>

        <?php elseif ($page == 'bayar_cod'): ?>
            <div class="card shadow-sm border-0 p-4 bg-white text-center">
                <?php 
                $_GET['order_id'] = $_GET['order_id'] ?? null;
                include 'bayar_cod.php'; 
                ?>
            </div>
        <?php endif; ?>

    </main>

    <footer class="bg-dark text-white text-center py-4 mt-auto">
        <div class="container">
            <p class="mb-1 small">&copy; 2026 UMKM Kebab & Burger. All Rights Reserved.</p>
            <small class="text-warning">Sistem Database Terintegrasi Oracle.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>