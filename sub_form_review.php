<?php
// Pastikan koneksi Oracle disertakan
include 'koneksi.php';

// ==========================================
// PROSES HAPUS ULASAN OLEH ADMIN
// ==========================================
if (isset($_GET['delete_review'])) {
    $id_rev = $_GET['delete_review'];
    try {
        $stmt_del = $conn->prepare("DELETE FROM reviews WHERE id = :id");
        $stmt_del->execute([':id' => $id_rev]);
        
        $_SESSION['msg_success'] = "Ulasan pelanggan berhasil dihapus dari sistem!";
        // Mengarahkan kembali ke halaman review di dashboard admin
        echo "<script>window.location.href='dashboard.php?page=ulasan';</script>"; 
        exit();
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "Gagal menghapus ulasan: " . $e->getMessage();
        echo "<script>window.location.href='dashboard.php?page=ulasan';</script>";
        exit();
    }
}

// ==========================================
// AMBIL DATA ULASAN LENGKAP UNTUK ADMIN (PERBAIKAN QUERY TRIM)
// ==========================================
try {
    $query = $conn->query("SELECT r.id, r.rating, 
                                  TRIM(r.comment_text) AS COMMENT_TEXT, 
                                  r.created_at, u.name, u.email 
                           FROM reviews r 
                           JOIN users u ON r.user_id = u.id 
                           ORDER BY r.id DESC");
    $admin_reviews = $query->fetchAll();
} catch (PDOException $e) {
    $admin_reviews = [];
}
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-chat-right-text me-2 text-primary"></i>Daftar Ulasan & Penilaian Pelanggan</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="20%">Nama Pelanggan</th>
                        <th width="20%">Email</th>
                        <th width="15%">Penilaian</th>
                        <th width="30%">Komentar Ulasan</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($admin_reviews) > 0): ?>
                        <?php foreach ($admin_reviews as $r): ?>
                            <?php 
                                // Ambil index kapital hasil dari query TRIM() AS COMMENT_TEXT
                                $admin_komentar = $r['COMMENT_TEXT'];
                                if (is_resource($admin_komentar)) {
                                    $admin_komentar = stream_get_contents($admin_komentar);
                                }
                            ?>
                            <tr>
                                <td class="fw-bold text-secondary"><?= htmlspecialchars($r['NAME'] ?? $r['name'] ?? ''); ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($r['EMAIL'] ?? $r['email'] ?? ''); ?></td>
                                <td class="text-warning">
                                    <?= str_repeat('⭐', $r['RATING'] ?? $r['rating'] ?? 5); ?>
                                </td>
                                <td><small class="text-dark">"<?= htmlspecialchars($admin_komentar); ?>"</small></td>
                                <td class="text-center">
                                    <a href="dashboard.php?page=ulasan&delete_review=<?= $r['ID'] ?? $r['id']; ?>" 
                                       class="btn btn-danger btn-sm py-1 px-2" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan dari pelanggan ini?');">
                                        <i class="bi bi-trash me-1"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada ulasan yang masuk dari pelanggan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>