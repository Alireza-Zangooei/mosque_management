<?php
require_once 'db_config.php';
session_start();

// بررسی اینکه آیا کاربر وارد سیستم شده است یا خیر و سطح دسترسی کافی دارد
if (!isset($_SESSION['user_id']) || $_SESSION['permission'] < 2) {
    header('Location: index.php');
    exit();
}

// بررسی وجود پارامتر id در URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $assetId = $_GET['id'];

    // دریافت مسیر تصویر دارایی برای حذف (اختیاری)
    try {
        $stmt = $pdo->prepare("SELECT image FROM assets WHERE id = :id");
        $stmt->bindParam(':id', $assetId, PDO::PARAM_INT);
        $stmt->execute();
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        $imagePath = $asset['image'];
    } catch (PDOException $e) {
        // اگر در دریافت مسیر تصویر مشکلی بود، مهم نیست، به حذف ادامه می دهیم
        $imagePath = '';
    }

    // حذف دارایی از پایگاه داده
    try {
        $stmt = $pdo->prepare("DELETE FROM assets WHERE id = :id");
        $stmt->bindParam(':id', $assetId, PDO::PARAM_INT);
        $stmt->execute();

        // حذف تصویر مربوطه از پوشه uploads (اختیاری)
        if (!empty($imagePath) && file_exists($imagePath)) {
            unlink($imagePath);
        }

        header('Location: index.php?message=دارایی با موفقیت حذف شد');
        exit();

    } catch (PDOException $e) {
        die("خطا در حذف دارایی: " . $e->getMessage());
    }

} else {
    // اگر پارامتر id در URL وجود نداشت یا معتبر نبود، به صفحه اصلی هدایت شود
    header('Location: index.php');
    exit();
}

?>
