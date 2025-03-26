<?php

$host = 'localhost'; // نام هاست (معمولاً localhost)
$dbname = 'mosque_assets'; // نام پایگاه داده شما
$username = 'root'; // نام کاربری پایگاه داده (پیش‌فرض در XAMPP)
$password = ''; // رمز عبور پایگاه داده (پیش‌فرض در XAMPP معمولاً خالی است)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // تنظیم حالت خطا برای نمایش خطاها در حالت توسعه
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("خطا در اتصال به پایگاه داده: " . $e->getMessage());
}

?>
