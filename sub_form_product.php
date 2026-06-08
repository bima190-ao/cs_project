<?php
// Pastikan koneksi sudah disertakan
include 'koneksi.php';

// ==========================================
// PROSES HAPUS PRODUK
// ==========================================
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];

    try {
        // 1. Ambil nama file gambar terlebih dahulu untuk dihapus dari folder server
        $stmt_img = $conn->prepare("SELECT image FROM products WHERE id = :id");
        $stmt_img->execute([':id' => $id_to_delete]);
        $prod_image = $stmt_img->fetch();

        if ($prod_image && !empty($prod_image['IMAGE'])) {
            $file_path = "uploads/" . $prod_image['IMAGE'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // 2. Hapus data produk dari tabel Oracle
        $stmt_del = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt_del->execute([':id' => $id_to_delete]);

        $_SESSION['msg_success'] = "Produk berhasil dihapus!";
              
        echo "<script>window.location.href='dashboard.php?page=product';</script>";
        exit();
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "Gagal menghapus produk: ".$e->getMessage();
        echo "<script>window.location.href='dashboard.php?page=product';</script>";
        exit();
    }
}

// ==========================================
// PROSES SIMPAN PRODUK BARU (NAMA GAMBAR UNIK)
// ==========================================
if (isset($_POST['submit_product'])) {
    $name = $_POST['name']; 
    $price = $_POST['price'];
    
    $image_name = $_FILES['image']['name'];
    $image_tmp  = $_FILES['image']['tmp_name'];
    $target_dir = "uploads/";
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $image_db = null;

    if (!empty($image_name)) {
        // SOLUSI: Gabungkan fungsi time() + nama file agar namanya selalu unik di server
        // Contoh: "kebab.jpg" menjadi "1715942100_kebab.jpg"
        $unique_image_name = time() . "_" . basename($image_name);
        $target_file = $target_dir . $unique_image_name;

        if (move_uploaded_file($image_tmp, $target_file)) {
            $image_db = $unique_image_name; // Nama unik ini yang masuk ke Oracle
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO products (name, price, image) VALUES (:name, :price, :image)");
        $stmt->execute([
            ':name' => $name, 
            ':price' => $price, 
            ':image' => $image_db
        ]);
        
        $_SESSION['msg_success'] = "Produk baru berhasil ditambahkan!";
        
        echo "<script>window.location.href='dashboard.php?page=product';</script>";
        exit();
    } catch (PDOException $e) { 
        $_SESSION['msg_error'] = "Error: ".$e->getMessage();
        echo "<script>window.location.href='dashboard.php?page=product';</script>";
        exit();
    }
}

// ==========================================
// AMBIL DATA PRODUK UNTUK TABEL
// ==========================================
try {
    $query = $conn->query("SELECT id, name, price, image FROM products ORDER BY id DESC");
    $products = $query->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>

<?php if (isset($_SESSION['msg_success'])): ?>
    <div class='alert alert-success alert-dismissible fade show' role='alert'>
        <?= $_SESSION['msg_success']; ?>
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>
    <?php unset($_SESSION['msg_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['msg_error'])): ?>
    <div class='alert alert-danger alert-dismissible fade show' role='alert'>
        <?= $_SESSION['msg_error']; ?>
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>
    <?php unset($_SESSION['msg_error']); ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-box-seam me-2"></i>Daftar Produk</h5>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahProduk">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="25%">Gambar</th>
                        <th width="45%">Nama Produk</th>
                        <th width="20%">Harga</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($p['IMAGE'])): ?>
                                        <img src="uploads/<?= $p['IMAGE']; ?>" alt="Produk" class="img-thumbnail" style="max-width: 60px; max-height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-muted small">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-secondary"><?= $p['NAME']; ?></td>
                                <td class="text-success fw-semibold">Rp <?= number_format($p['PRICE'], 2, ',', '.'); ?></td>
                                <td class="text-center">
                                    <a href="dashboard.php?page=product&delete=<?= $p['ID']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                        <i class="bi bi-trash3-fill"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada data produk. Klik "+ Tambah Produk" untuk mengisi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahProduk" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Form Tambah Produk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Produk</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Kebab Biasa" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga (Rupiah)</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="Contoh: 10000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unggah Gambar Produk</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_product" class="btn btn-success">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>