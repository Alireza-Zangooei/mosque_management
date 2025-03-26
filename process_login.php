<?php

require_once 'db_config.php';
session_start(); // شروع سیشن

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, permission FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // بررسی رمز عبور
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['permission'] = $user['permission'];
                header('Location: index.php'); // انتقال به صفحه اصلی
                exit();
            } else {
                header('Location: login.php?error=رمز عبور اشتباه است');
                exit();
            }
        } else {
            header('Location: login.php?error=نام کاربری اشتباه است');
            exit();
        }
    } catch (PDOException $e) {
        die("خطا در پایگاه داده: " . $e->getMessage());
    }
} else {
    // اگر درخواست از طریق غیر POST باشد، به صفحه ورود هدایت شود
    header('Location: login.php');
    exit();
}

?>
