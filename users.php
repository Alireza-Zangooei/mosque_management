<?php
require_once 'db_config.php';
session_start();

// بررسی اینکه آیا کاربر وارد سیستم شده است یا خیر و سطح دسترسی کافی دارد (سطح دسترسی 4 برای مدیریت کاربران)
if (!isset($_SESSION['user_id']) || $_SESSION['permission'] < 4) {
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

// پردازش افزودن کاربر جدید
if (isset($_POST['add_user'])) {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $permission = $_POST['permission'];

    if (!empty($username) && !empty($password) && is_numeric($permission)) {
        try {
            // هش کردن رمز عبور قبل از ذخیره
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, password, permission) VALUES (:first_name, :last_name, :username, :password, :permission)");
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':permission', $permission, PDO::PARAM_INT);
            $stmt->execute();
            header('Location: users.php?message=کاربر با موفقیت اضافه شد');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // کد خطای Unique Key Violation (username تکراری)
                $error = "نام کاربری قبلاً ثبت شده است.";
            } else {
                $error = "خطا در افزودن کاربر: " . $e->getMessage();
            }
        }
    } else {
        $error = "لطفاً تمام فیلدها را به درستی وارد کنید.";
    }
}

// پردازش حذف کاربر
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];

    // جلوگیری از حذف کاربری که با آن وارد سیستم شده ایم
    if ($deleteId == $_SESSION['user_id']) {
        $error = "شما نمی توانید حساب کاربری خود را حذف کنید.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $deleteId, PDO::PARAM_INT);
            $stmt->execute();
            header('Location: users.php?message=کاربر با موفقیت حذف شد');
            exit();
        } catch (PDOException $e) {
            $error = "خطا در حذف کاربر: " . $e->getMessage();
        }
    }
}

// دریافت لیست کاربران
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, username, permission FROM users ORDER BY username ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطا در دریافت لیست کاربران: " . $e->getMessage());
}

// پردازش ویرایش کاربر
if (isset($_POST['edit_user'])) {
    $editUserId = $_POST['edit_user_id'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $permission = $_POST['permission'];

    if (is_numeric($editUserId) && is_numeric($permission)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, permission = :permission WHERE id = :id");
            $stmt->bindParam(':id', $editUserId, PDO::PARAM_INT);
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':permission', $permission, PDO::PARAM_INT);
            $stmt->execute();
            header('Location: users.php?message=اطلاعات کاربر با موفقیت ویرایش شد');
            exit();
        } catch (PDOException $e) {
            $error = "خطا در ویرایش اطلاعات کاربر: " . $e->getMessage();
        }
    } else {
        $error = "اطلاعات ورودی معتبر نیست.";
    }
}

?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کاربران</title>
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
        .user-list {
            margin-bottom: 20px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #444;
            padding: 15px;
        }
        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #666;
        }
        .user-item:last-child {
            border-bottom: none;
        }
        .user-info {
            flex-grow: 1;
            color: #ccc;
        }
        .user-actions a {
            color: #d9534f; /* قرمز برای حذف */
            text-decoration: none;
            margin-left: 10px;
            font-size: 0.9em;
        }
        .user-actions a:hover {
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
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group select {
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
        .permission-level {
            font-size: 0.8em;
            color: #999;


        }
                .edit-form {
            margin-top: 10px;
            padding: 15px; /* افزایش padding برای فضای بیشتر */
            border: 1px solid #666;
            border-radius: 4px;
            background-color: #555;
            display: grid; /* استفاده از Grid Layout برای تنظیم بهتر عناصر */
            grid-template-columns: auto 1fr; /* دو ستون: label و input/select */
            gap: 10px; /* فاصله بین ردیف‌ها و ستون‌ها */
            align-items: center; /* تراز عمودی عناصر */
        }

        .edit-form label {
            display: block;
            color: #ddd;
            font-size: 0.9em; /* کاهش اندازه فونت label */
        }

        .edit-form input[type="text"],
        .edit-form select {
            width: 100%;
            padding: 8px;
            border: 1px solid #777;
            border-radius: 4px;
            background-color: #666;
            color: #eee;
            box-sizing: border-box;
            font-size: 0.9em; /* کاهش اندازه فونت input/select */
        }

        .edit-form button {
            grid-column: 1 / span 2; /* دکمه در تمام عرض قرار بگیرد */
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 0.9em; /* کاهش اندازه فونت دکمه */
        }

        .edit-form button:hover {
            background-color: #0056b3;
        }

        /* استایل برای بهبود نمایش نام و نام خانوادگی در لیست هنگام باز بودن فرم ویرایش */
        .user-item.editing .user-info {
            display: block; /* اطمینان از اینکه در یک خط قرار نگیرند */
            white-space: normal; /* اجازه شکستن خط در صورت نیاز */
        }

        @media (max-width: 600px) {
            .edit-form {
                grid-template-columns: 1fr; /* در موبایل، label و input در دو ردیف جداگانه */
            }
            .edit-form button {
                grid-column: 1; /* دکمه در تمام عرض */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>مدیریت کاربران</h1>

        <?php if ($message): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="user-list">
            <h2>لیست کاربران</h2>
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $user): ?>
                    <div class="user-item">
                        <span class="user-info"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> (<?php echo htmlspecialchars($user['username']); ?>) <span class="permission-level">(سطح دسترسی: <?php echo $user['permission']; ?>)</span></span>
                        <div class="user-actions">
                            <?php if ($_SESSION['user_id'] != $user['id']): ?>
                                <a href="?edit_id=<?php echo $user['id']; ?>">ویرایش</a>
                                <a href="?delete_id=<?php echo $user['id']; ?>" onclick="return confirm('آیا مطمئن هستید که می خواهید این کاربر را حذف کنید؟');">حذف</a>
                            <?php endif; ?>
                        </div>
                                                <?php if (isset($_GET['edit_id']) && $_GET['edit_id'] == $user['id']): ?>
                            <div class="edit-form">
                                <form method="post">
                                    <label for="edit_first_name_<?php echo $user['id']; ?>">نام:</label>
                                    <input type="text" id="edit_first_name_<?php echo $user['id']; ?>" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                    <label for="edit_last_name_<?php echo $user['id']; ?>">نام خانوادگی:</label>
                                    <input type="text" id="edit_last_name_<?php echo $user['id']; ?>" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                    <label for="edit_permission_<?php echo $user['id']; ?>">سطح دسترسی:</label>
                                    <select id="edit_permission_<?php echo $user['id']; ?>" name="permission" required>
                                        <option value="1" <?php if ($user['permission'] == 1) echo 'selected'; ?>>1 (مشاهده لیست)</option>
                                        <option value="2" <?php if ($user['permission'] == 2) echo 'selected'; ?>>2 (افزودن، ویرایش، حذف دارایی)</option>
                                        <option value="3" <?php if ($user['permission'] == 3) echo 'selected'; ?>>3 (مدیریت دسته‌بندی)</option>
                                        <option value="4" <?php if ($user['permission'] == 4) echo 'selected'; ?>>4 (مدیریت کاربران)</option>
                                    </select>
                                    <input type="hidden" name="edit_user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="edit_user">ذخیره</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>هیچ کاربری ثبت نشده است.</p>
            <?php endif; ?>
        </div>

        <h2>افزودن کاربر جدید</h2>
        <form method="post">
            <div class="form-group">
                <label for="first_name">نام:</label>
                <input type="text" id="first_name" name="first_name">
            </div>
            <div class="form-group">
                <label for="last_name">نام خانوادگی:</label>
                <input type="text" id="last_name" name="last_name">
            </div>
            <div class="form-group">
                <label for="username">نام کاربری:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">رمز عبور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="permission">سطح دسترسی:</label>
                <select id="permission" name="permission" required>
                    <option value="1">1 (مشاهده لیست)</option>
                    <option value="2">2 (افزودن، ویرایش، حذف دارایی)</option>
                    <option value="3">3 (مدیریت دسته‌بندی)</option>
                    <option value="4">4 (مدیریت کاربران)</option>
                </select>
            </div>
            <button type="submit" name="add_user">افزودن</button>
        </form>

        <a href="index.php" class="back-link">بازگشت به لیست دارایی ها</a>
    </div>
</body>
</html>
