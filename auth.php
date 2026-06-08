<?php
session_start();
include 'koneksi.php';

// PROSES REGISTER
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role']; // default 'user' atau 'admin'

    try {
        // UBAH BARIS KODE INI DI auth.php
$sql = "INSERT INTO users (name, email, password, user_role) VALUES (:name, :email, :password, :role)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $password,
            ':role' => $role
        ]);
        $_SESSION['success'] = "Registrasi berhasil! Silahkan login.";
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal Registrasi: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }
}

// PROSES LOGIN
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Ingat: Oracle case-sensitive untuk query jika data tersimpan berbeda, 
        // namun nama kolom yang dikembalikan PDO default-nya KAPITAL
        $sql = "SELECT id, name, password, user_role FROM users WHERE email = :email";
$stmt = $conn->prepare($sql);
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

        if ($user && password_verify($password, $user['PASSWORD'])) {
    $_SESSION['user_id'] = $user['ID'];
    $_SESSION['user_name'] = $user['NAME'];
    
    // Ingat: Oracle mengembalikan key array dalam HURUF KAPITAL
    $_SESSION['user_role'] = $user['USER_ROLE']; 

    if ($user['USER_ROLE'] == 'admin') {
        header("Location: dashboard.php");
    } else {
        $_SESSION['USER_ROLE'] = "user";
        header("Location: index.php");
    }
    exit();
        } else {
            $_SESSION['error'] = "Email atau Password salah!";
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
}

// PROSES LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>