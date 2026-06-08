<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - Admin System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center" style="height: 100vh;">
<div class="container">
    <div class="card mx-auto shadow" style="max-width: 450px;">
        <div class="card-body p-4">
            <h3 class="text-center mb-4">Buat Akun</h3>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form action="auth.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Daftar Sebagai (Role)</label>
                    <select name="role" class="form-select">
                        <option value="admin">Admin</option>
                        <option value="user">User Biasa</option>
                    </select>
                </div>
                <button type="submit" name="register" class="btn btn-success w-100 mb-3">Daftar Akun</button>
                <p class="text-center small mb-0">Sudah punya akun? <a href="login.php">Login</a></p>
            </form>
        </div>
    </div>
</div>
</body>
</html>