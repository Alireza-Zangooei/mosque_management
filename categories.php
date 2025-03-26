<?php
require_once 'db_config.php';
session_start();

// بررسی اینکه آیا کاربر وارد سیستم شده است یا خیر و سطح دسترسی کافی دارد (سطح دسترسی 3 برای مدیریت دسته‌بندی‌ها)
if (!isset($_SESSION['user_id']) || $_SESSION['permission'] < 3) {
    header('Location: index.php');
    exit();
}

// پیام های وضعیت
$message = '';
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
$error = '';
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// پردازش افزودن دسته‌بندی جدید
if (isset($_POST['add_category'])) {
    $categoryName = $_POST['category_name'];
    if (!empty($categoryName)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
            $stmt->bindParam(':name', $categoryName);
            $stmt->execute();
            header('Location: categories.php?message=دسته‌بندی با موفقیت اضافه شد');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // کد خطای Unique Key Violation
                $error = "دسته‌بندی با این نام قبلاً ثبت شده است.";
            } else {
                $error = "خطا در افزودن دسته‌بندی: " . $e->getMessage();
            }
        }
    } else {
        $error = "لطفاً نام دسته‌بندی را وارد کنید.";
    }
}

// پردازش ویرایش دسته‌بندی
if (isset($_POST['edit_category'])) {
    $categoryId = $_POST['category_id'];
    $categoryName = $_POST['category_name'];
    if (!empty($categoryName) && is_numeric($categoryId)) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = :name WHERE id = :id");
            $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':name', $categoryName);
            $stmt->execute();
            header('Location: categories.php?message=دسته‌بندی با موفقیت ویرایش شد');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // کد خطای Unique Key Violation
                $error = "دسته‌بندی با این نام قبلاً ثبت شده است.";
            } else {
                $error = "خطا در ویرایش دسته‌بندی: " . $e->getMessage();
            }
        }
    } else {
        $error = "نام دسته‌بندی نمی‌تواند خالی باشد.";
    }
}

// پردازش حذف دسته‌بندی
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];

    // بررسی اینکه آیا هیچ دارایی به این دسته‌بندی تعلق دارد یا خیر
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE category_id = :id");
        $stmt->bindParam(':id', $deleteId, PDO::PARAM_INT);
        $stmt->execute();
        $assetCount = $stmt->fetchColumn();

        if ($assetCount > 0) {
            $error = "این دسته‌بندی دارای " . $assetCount . " دارایی است و قابل حذف نمی‌باشد. لطفاً ابتدا دارایی‌های مربوطه را ویرایش یا حذف کنید.";
        } else {
            // اگر هیچ دارایی به این دسته‌بندی تعلق ندارد، آن را حذف کنید
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->bindParam(':id', $deleteId, PDO::PARAM_INT);
            $stmt->execute();
            header('Location: categories.php?message=دسته‌بندی با موفقیت حذف شد');
            exit();
        }
    } catch (PDOException $e) {
        $error = "خطا در بررسی یا حذف دسته‌بندی: " . $e->getMessage();
    }
}

// دریافت لیست دسته‌بندی‌ها
try {
    $stmt = $pdo->query("
       SELECT
           c.id,
           c.name,
           COUNT(a.id) AS asset_count
       FROM
           categories c
       LEFT JOIN
           assets a ON c.id = a.category_id
       GROUP BY
           c.id, c.name
       ORDER BY
           c.name ASC
   ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطا در دریافت لیست دسته‌بندی‌ها: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت دسته‌بندی‌ها</title>
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
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .category-list {
            margin-bottom: 20px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #444;
            padding: 15px;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #666;
        }
        .category-item:last-child {
            border-bottom: none;
        }
        .category-name {
            flex-grow: 1;
            color: #ccc;
        }
        .category-actions a {
            color: #007bff;
            text-decoration: none;
            margin-left: 10px;
            font-size: 0.9em;
        }
        .category-actions a:hover {
            text-decoration: underline;
        }
        h2 {
            color: #ddd;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        .form-group input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #444;
            color: #eee;
            box-sizing: border-box;
        }
        .form-group button {
            padding: 10px 15px;
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
        .edit-form {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #666;
            border-radius: 4px;
            background-color: #555;
        }
        .edit-form label {
            display: block;
            margin-bottom: 5px;
            color: #ddd;
        }
        .edit-form input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #777;
            border-radius: 4px;
            background-color: #666;
            color: #eee;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        .edit-form button {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .edit-form button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>مدیریت دسته‌بندی‌ها</h1>

        <?php if ($message): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="category-list">
            <h2>لیست دسته‌بندی‌ها</h2>
            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $category): ?>
                    <div class="category-item">
                        <span class="category-name"><?php echo htmlspecialchars($category['name']); ?> (<?php echo htmlspecialchars($category['asset_count']); ?>)</span>
                        <div class="category-actions">
                            <a href="?edit_id=<?php echo $category['id']; ?>">ویرایش</a>
                            <a href="?delete_id=<?php echo $category['id']; ?>" onclick="return confirm('آیا مطمئن هستید که می خواهید این دسته‌بندی را حذف کنید؟');">حذف</a>
                        </div>
                        <?php if (isset($_GET['edit_id']) && $_GET['edit_id'] == $category['id']): ?>
                            <div class="edit-form">
                                <form method="post">
                                    <label for="edit_category_name_<?php echo $category['id']; ?>">نام جدید:</label>
                                    <input type="text" id="edit_category_name_<?php echo $category['id']; ?>" name="category_name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="edit_category">ذخیره</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>هیچ دسته‌بندی ثبت نشده است.</p>
            <?php endif; ?>
        </div>

        <h2>افزودن دسته‌بندی جدید</h2>
        <form method="post">
            <div class="form-group">
                <label for="category_name">نام دسته‌بندی:</label>
                <input type="text" id="category_name" name="category_name" required>
            </div>
            <button type="submit" name="add_category">افزودن</button>
        </form>

        <a href="index.php" class="back-link">بازگشت به لیست دارایی ها</a>
    </div>
</body>
</html>
