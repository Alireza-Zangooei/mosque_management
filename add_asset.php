<?php
require_once 'db_config.php';
session_start();

// بررسی اینکه آیا کاربر وارد سیستم شده است یا خیر و سطح دسترسی کافی دارد
if (!isset($_SESSION['user_id']) || !isset($_SESSION['permission']) || $_SESSION['permission'] < 2) {
    header('Location: index.php'); // اگر دسترسی ندارد به صفحه اصلی هدایت شود
    exit();
}

// دریافت لیست دسته بندی ها برای نمایش در فرم
try {
    $stmtCategories = $pdo->query("SELECT id, name FROM categories");
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطا در دریافت دسته بندی ها از پایگاه داده: " . $e->getMessage());
}

// پردازش فرم در صورت ارسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $code = $_POST['code'];
    $categoryId = $_POST['category_id'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    // مدیریت آپلود تصویر
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/'; // پوشه ای برای ذخیره تصاویر (باید ایجاد شود)
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageName = basename($_FILES['image']['name']);
        $imagePath = $uploadDir . $imageName;
        move_uploaded_file($imageTmpName, $imagePath);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO assets (name, code, category_id, image, description, status) VALUES (:name, :code, :category_id, :image, :description, :status)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':image', $imagePath);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->execute();

        header('Location: index.php?message=دارایی جدید با موفقیت اضافه شد');
        exit();
    } catch (PDOException $e) {
        die("خطا در افزودن دارایی به پایگاه داده: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>افزودن دارایی جدید</title>
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
            max-width: 600px;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        .form-group input[type="text"],
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #444;
            color: #eee;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>افزودن دارایی جدید</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">نام دارایی:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="code">کد دارایی:</label>
                <input type="text" id="code" name="code">
            </div>
            <div class="form-group">
                <label for="category_id">دسته بندی:</label>
                <select id="category_id" name="category_id" required>
                    <option value="">انتخاب کنید</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="image">تصویر دارایی:</label>
                <input type="file" id="image" name="image">
            </div>
            <div class="form-group">
                <label for="description">توضیحات:</label>
                <textarea id="description" name="description" rows="5"></textarea>
            </div>
            <div class="form-group">
                <label for="status">وضعیت:</label>
                <input type="text" id="status" name="status">
            </div>
            <div class="form-group">
                <button type="submit">افزودن</button>
            </div>
        </form>
        <a href="index.php" class="back-link">بازگشت به لیست دارایی ها</a>
    </div>
</body>
</html>
