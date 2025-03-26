<?php
require_once 'db_config.php';
session_start();

// بررسی اینکه آیا کاربر وارد سیستم شده است یا خیر
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// بررسی وجود پارامتر id در URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $assetId = $_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT assets.name, assets.code, assets.image, assets.description, assets.status, categories.name AS category_name FROM assets INNER JOIN categories ON assets.category_id = categories.id WHERE assets.id = :id");
        $stmt->bindParam(':id', $assetId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // اگر دارایی با این شناسه پیدا نشد، یک پیغام نمایش داده و یا به صفحه اصلی هدایت شود
            $error = "دارایی مورد نظر یافت نشد.";
        }
    } catch (PDOException $e) {
        die("خطا در دریافت اطلاعات از پایگاه داده: " . $e->getMessage());
    }
} else {
    // اگر پارامتر id در URL وجود نداشت یا معتبر نبود، به صفحه اصلی هدایت شود
    header('Location: index.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات دارایی</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #222;
            color: #eee;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #ddd;
        }
        .asset-details {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #444;
        }
        .asset-details h3 {
            margin-top: 0;
            color: #ddd;
        }
        .asset-details p {
            margin-bottom: 10px;
            color: #ccc;
        }
        .asset-image-large {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 20px auto;
            border-radius: 4px;
        }
        .back-link {
            display: inline-block;
            padding: 10px 15px;
            border: 1px solid #555;
            border-radius: 4px;
            color: #eee;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .back-link:hover {
            background-color: #555;
        }
        .error-message {
            color: #d9534f;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>جزئیات دارایی</h1>

        <?php if (isset($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php elseif (isset($asset)): ?>
            <div class="asset-details">
                <h3>نام:</h3>
                <p><?php echo $asset['name']; ?></p>
            </div>
            <div class="asset-details">
                <h3>کد:</h3>
                <p><?php echo $asset['code']; ?></p>
            </div>
            <div class="asset-details">
                <h3>دسته بندی:</h3>
                <p><?php echo $asset['category_name']; ?></p>
            </div>
            <div class="asset-details">
                <h3>وضعیت:</h3>
                <p><?php echo $asset['status']; ?></p>
            </div>
            <div class="asset-details">
                <h3>توضیحات:</h3>
                <p><?php echo nl2br($asset['description']); ?></p>
            </div>
            <?php if (!empty($asset['image'])): ?>
                <img src="<?php echo $asset['image']; ?>" alt="<?php echo $asset['name']; ?>" class="asset-image-large">
            <?php endif; ?>
        <?php endif; ?>

        <a href="index.php" class="back-link">بازگشت به لیست دارایی ها</a>
    </div>
</body>
</html>
