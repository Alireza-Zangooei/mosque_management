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

    // دریافت اطلاعات دارایی برای ویرایش
    try {
        $stmt = $pdo->prepare("SELECT * FROM assets WHERE id = :id");
        $stmt->bindParam(':id', $assetId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "دارایی مورد نظر یافت نشد.";
        }
    } catch (PDOException $e) {
        die("خطا در دریافت اطلاعات دارایی: " . $e->getMessage());
    }

    // دریافت لیست دسته بندی ها برای نمایش در فرم
    try {
        $stmtCategories = $pdo->query("SELECT id, name FROM categories");
        $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("خطا در دریافت دسته بندی ها: " . $e->getMessage());
    }

    // پردازش فرم در صورت ارسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $code = $_POST['code'];
        $categoryId = $_POST['category_id'];
        $description = $_POST['description'];
        $status = $_POST['status'];

        // مدیریت آپلود تصویر (اگر تصویر جدید انتخاب شده باشد)
        $imagePath = $asset['image']; // پیش فرض تصویر قبلی
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $imageTmpName = $_FILES['image']['tmp_name'];
            $imageName = basename($_FILES['image']['name']);
            $imagePath = $uploadDir . $imageName;
            move_uploaded_file($imageTmpName, $imagePath);

            // حذف تصویر قبلی اگر تغییر کرده باشد (اختیاری)
            if (!empty($asset['image']) && $asset['image'] !== $imagePath && file_exists($asset['image'])) {
                unlink($asset['image']);
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE assets SET name = :name, code = :code, category_id = :category_id, image = :image, description = :description, status = :status WHERE id = :id");
            $stmt->bindParam(':id', $assetId, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':image', $imagePath);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':status', $status);
            $stmt->execute();

            header('Location: index.php?message=دارایی با موفقیت ویرایش شد');
            exit();
        } catch (PDOException $e) {
            die("خطا در ویرایش دارایی: " . $e->getMessage());
        }
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
    <title>ویرایش دارایی</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #222;
            color: #eee;
            margin: 0;
            padding: 20px;
            direction: rtl;
            box-sizing: border-box;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            box-sizing: border-box;
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
        .current-image {
            display: block;
            max-width: 150px;
            height: auto;
            margin-bottom: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ویرایش دارایی</h1>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php elseif (isset($asset)): ?>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">نام دارایی:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($asset['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="code">کد دارایی:</label>
                    <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($asset['code']); ?>">
                </div>
                <div class="form-group">
                    <label for="category_id">دسته بندی:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">انتخاب کنید</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php if ($category['id'] == $asset['category_id']) echo 'selected'; ?>><?php echo $category['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="image">تصویر دارایی:</label>
                    <?php if (!empty($asset['image'])): ?>
                        <img src="<?php echo htmlspecialchars($asset['image']); ?>" alt="تصویر فعلی" class="current-image">
                        <p>برای تغییر تصویر، یک تصویر جدید انتخاب کنید:</p>
                    <?php endif; ?>
                    <input type="file" id="image" name="image">
                </div>
                <div class="form-group">
                    <label for="description">توضیحات:</label>
                    <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($asset['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="status">وضعیت:</label>
                    <input type="text" id="status" name="status" value="<?php echo htmlspecialchars($asset['status']); ?>">
                </div>
                <div class="form-group">
                    <button type="submit">ذخیره تغییرات</button>
                </div>
            </form>
        <?php endif; ?>
        <a href="index.php" class="back-link">بازگشت به لیست دارایی ها</a>
    </div>
</body>
</html>
