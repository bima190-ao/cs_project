<?php
// Hubungkan file koneksi agar variabel $conn terdefinisi
include 'koneksi.php';

// ==========================================
// LOGIKA HAPUS TRANSAKSI ORDER OLEH ADMIN
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete_order') {
    $delete_id = $_GET['id'];
    try {
        $conn->beginTransaction();

        // 1. Hapus rincian item terlebih dahulu (Foreign Key)
        $stmt_del_detail = $conn->prepare("DELETE FROM ORDER_DETAILS WHERE ORDER_ID = :order_id");
        $stmt_del_detail->execute([':order_id' => $delete_id]);

        // 2. Hapus data utama order
        $stmt_del_main = $conn->prepare("DELETE FROM ORDERS WHERE ID = :id");
        $stmt_del_main->execute([':id' => $delete_id]);

        $conn->commit();
        echo "<script>alert('Transaksi ID #$delete_id berhasil dihapus dari sistem!'); window.location='dashboard.php?page=order';</script>";
        exit();
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo "<div class='alert alert-danger m-3'>Gagal menghapus transaksi: " . $e->getMessage() . "</div>";
    }
}

// Ambil ID Order dari URL jika admin memilih salah satu nota
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
$order_items = [];
$all_orders = [];

// Ambil daftar seluruh pesanan untuk ditampilkan di tabel utama admin
try {
    $stmt_all = $conn->query("SELECT ID, USER_ID, TOTAL_PRICE, STATUS, PAYMENT_METHOD, CREATED_AT FROM ORDERS ORDER BY ID DESC");
    $all_orders = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-3'>Gagal memuat daftar transaksi: " . $e->getMessage() . "</div>";
}

// Jika admin memilih detail transaksi tertentu
if ($order_id) {
    try {
        // PERBAIKAN: Mengubah P.PRODUCT_NAME menjadi P.NAME sesuai struktur tabel Oracle kamu
        $stmt = $conn->prepare("
            SELECT 
                P.NAME AS PRODUCT_NAME, 
                OD.PRICE, 
                OD.QTY, 
                O.PAYMENT_METHOD, 
                O.TOTAL_PRICE 
            FROM ORDER_DETAILS OD
            JOIN ORDERS O ON OD.ORDER_ID = O.ID
            JOIN PRODUCTS P ON OD.PRODUCT_ID = P.ID
            WHERE OD.ORDER_ID = :order_id
        ");
        $stmt->execute([':order_id' => $order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger m-3'>Gagal mengambil data rincian: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold py-3">
                <i class="bi bi-list-ul me-2"></i>Daftar Semua Transaksi Masuk
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center small">
                        <thead class="table-secondary">
                            <tr>
                                <th>ID Nota</th>
                                <th>ID User</th>
                                <th>Waktu Transaksi</th>
                                <th>Metode</th>
                                <th>Total Bayar</th>
                                <th>Status</th>
                                <th>Aksi Kontrol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_orders)): ?>
                                <?php foreach ($all_orders as $ord): 
                                    $o_id = $ord['ID'] ?? $ord['id'];
                                    $o_user = $ord['USER_ID'] ?? $ord['user_id'];
                                    $o_date = $ord['CREATED_AT'] ?? $ord['created_at'] ?? 'now';
                                    $o_method = $ord['PAYMENT_METHOD'] ?? $ord['payment_method'] ?? 'COD';
                                    $o_total = $ord['TOTAL_PRICE'] ?? $ord['total_price'] ?? 0;
                                    $o_status = $ord['STATUS'] ?? $ord['status'] ?? 'Pending';
                                ?>
                                <tr class="<?= $order_id == $o_id ? 'table-warning' : ''; ?>">
                                    <td class="fw-bold">#<?= $o_id; ?></td>
                                    <td>User ID: <?= $o_user; ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($o_date)); ?></td>
                                    <td><span class="badge bg-secondary"><?= $o_method; ?></span></td>
                                    <td class="text-success fw-bold">Rp <?= number_format($o_total, 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?= $o_status == 'Success' ? 'success' : 'warning'; ?>">
                                            <?= $o_status; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="dashboard.php?page=order&order_id=<?= $o_id; ?>" class="btn btn-primary btn-sm px-2 py-1" style="font-size: 11px;">
                                                <i class="bi bi-eye-fill"></i> Rincian Item
                                            </a>
                                            <a href="dashboard.php?page=order&action=delete_order&id=<?= $o_id; ?>" 
                                               class="btn btn-danger btn-sm px-2 py-1" 
                                               style="font-size: 11px;"
                                               onclick="return confirm('PENTING: Menghapus order ini juga akan menghapus seluruh data rincian item di dalamnya. Lanjutkan?');">
                                                <i class="bi bi-trash3-fill"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada transaksi pembelian masuk ke database.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($order_id): ?>
    <div class="col-12">
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning text-dark fw-bold py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart-check-fill me-2"></i>Rincian Belanjaan untuk ID Transaksi: #<?= htmlspecialchars($order_id); ?></span>
                <a href="dashboard.php?page=order" class="btn btn-sm btn-outline-dark fw-semibold py-0">Tutup Rincian</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Nama Produk</th>
                                <th style="width: 170px;">Harga Satuan</th>
                                <th style="width: 100px;">Qty</th>
                                <th style="width: 180px;">Subtotal Item</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($order_items)): ?>
                                <?php foreach ($order_items as $item): 
                                    $p_name  = $item['PRODUCT_NAME'] ?? '-';
                                    $p_price = $item['PRICE'] ?? 0;
                                    $p_qty   = $item['QTY'] ?? 0;
                                    $sub_total = $p_price * $p_qty;
                                ?>
                                <tr>
                                    <td class="fw-semibold text-dark text-start ps-3"><?= htmlspecialchars($p_name); ?></td>
                                    <td class="text-end pe-3">Rp <?= number_format($p_price, 0, ',', '.'); ?></td>
                                    <td><?= $p_qty; ?>x</td>
                                    <td class="text-end pe-3 fw-bold text-dark">Rp <?= number_format($sub_total, 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Data rincian item tidak ditemukan untuk transaksi ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>