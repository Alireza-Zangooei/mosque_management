<?php
require_once 'db_config.php';
session_start();

// بررسی اینکه آیا کاربر وارد سیستم شده است یا خیر
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// دریافت سطح دسترسی کاربر
$permission = $_SESSION['permission'];

// مقادیر جستجو و فیلتر
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category_filter']) ? $_GET['category_filter'] : '';

// ساخت کوئری SQL بر اساس جستجو و فیلتر
$sql = "SELECT assets.id, assets.name, assets.code, assets.image, assets.status, categories.name AS category_name FROM assets INNER JOIN categories ON assets.category_id = categories.id WHERE 1=1";

if (!empty($searchKeyword)) {
    $sql .= " AND (assets.name LIKE :search OR assets.code LIKE :search OR assets.description LIKE :search)";
}

if (!empty($categoryFilter)) {
    $sql .= " AND assets.category_id = :category_id";
}

$sql .= " ORDER BY assets.name ASC"; // مرتب سازی پیش فرض

try {
    $stmt = $pdo->prepare($sql);

    // اتصال مقادیر جستجو و فیلتر به کوئری
    if (!empty($searchKeyword)) {
        $stmt->bindValue(':search', '%' . $searchKeyword . '%', PDO::PARAM_STR);
    }
    if (!empty($categoryFilter)) {
        $stmt->bindValue(':category_id', $categoryFilter, PDO::PARAM_INT);
    }

    $stmt->execute();
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // دریافت لیست دسته بندی ها برای فیلتر (تکراری، می‌توانید حذف کنید اگر قبلاً دارید)
    $stmtCategories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطا در دریافت اطلاعات از پایگاه داده: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیست دارایی ها</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #222;
            color: #eee;
            margin: 0;
            padding: 10px;
            direction: rtl;
            box-sizing: border-box;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            background-color: #333;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            box-sizing: border-box;
            overflow-x: auto; /* اضافه شد: فعال کردن اسکرول افقی برای کانتینر در صورت نیاز */
        }
        h1 {
            text-align: center;
            margin-bottom: 15px;
            color: #ddd;
            font-size: 1.3em;
        }
        .search-bar {
            margin-bottom: 15px;
            box-sizing: border-box;
        }
        .search-bar input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #444;
            color: #eee;
            box-sizing: border-box;
            font-size: 0.9em;
        }
        .add-button {
            display: block;
            width: fit-content;
            padding: 8px 10px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            margin-bottom: 15px;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
            font-size: 0.9em;
        }
        .add-button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            box-sizing: border-box;
            min-width: 600px; /* حداقل عرض جدول را تعیین کنید */
        }
        th, td {
            padding: 6px;
            border-bottom: 1px solid #555;
            text-align: right;
            box-sizing: border-box;
            font-size: 0.85em;
        }
        th {
            background-color: #444;
            color: #ccc;
            cursor: pointer;
        }
        tr:nth-child(even) {
            background-color: #444;
        }
        tr:hover {
            background-color: #555;
        }
        .asset-image {
            max-width: 30px;
            max-height: 30px;
            vertical-align: middle;
            box-sizing: border-box;
        }
        .actions a {
            color: #5cb85c;
            text-decoration: none;
            margin-left: 3px;
            font-size: 0.8em;
            box-sizing: border-box;
        }
        .actions a:hover {
            text-decoration: underline;
        }

        /* Media Query برای دستگاه های با عرض حداکثر 600 پیکسل */
        @media (max-width: 600px) {
            .container {
                padding: 10px;
                overflow-x: auto; /* فعال کردن اسکرول افقی برای کانتینر در موبایل */
            }
            h1 {
                font-size: 1.7em;
                margin-bottom: 10px;
            }
            .search-bar input[type="text"] {
                font-size: 0.85em;
                padding: 6px;
            }
            .add-button {
                font-size: 0.85em;
                padding: 6px 8px;
                margin-bottom: 10px;
            }
            .table-responsive {
                overflow-x: auto; /* فعال کردن اسکرول افقی برای جدول */
            }
            table {
                min-width: 700px; /* افزایش حداقل عرض برای جلوگیری از شکستگی نامناسب */
            }
            th, td {
                padding: 4px;
                font-size: 1em;
                white-space: nowrap; /* جلوگیری از شکستن خط در سلول ها */
            }
            .asset-image {
                max-width: 25px;
                max-height: 25px;
            }
            .actions a {
                margin-left: 2px;
                font-size: 0.7em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>لیست دارایی ها</h1>

        <div style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
            <?php if ($permission >= 2): ?>
                <a href="export_excel.php" class="add-button" style="background-color: #5cb85c;">دانلود اکسل</a>
            <?php endif; ?>
            <?php if ($permission >= 3): ?>
                <a href="categories.php" class="add-button">مدیریت دسته‌بندی‌ها</a>
            <?php endif; ?>
            <?php if ($permission >= 4): ?>
                <a href="users.php" class="add-button">مدیریت کاربران</a>
            <?php endif; ?>
        </div>

        <form method="GET" action="">
            <div class="search-bar" style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
                <input type="text" name="search" placeholder="جستجو در نام، کد، توضیحات" style="flex-grow: 1; padding: 8px; border: 1px solid #555; border-radius: 4px; background-color: #444; color: #eee; box-sizing: border-box; font-size: 0.9em;">
                <select name="category_filter" id="category_filter" style="padding: 8px; border: 1px solid #555; border-radius: 4px; background-color: #444; color: #eee; box-sizing: border-box; font-size: 0.9em;">
                    <option value="">همه</option>
                    <?php
                    try {
                        $stmtCategories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
                        $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($categories as $cat) {
                            $selected = (isset($_GET['category_filter']) && $_GET['category_filter'] == $cat['id']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($cat['id']) . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                        }
                    } catch (PDOException $e) {
                        echo '<option value="">خطا در دریافت دسته‌بندی‌ها</option>';
                    }
                    ?>
                </select>
                <button type="submit" style="padding: 8px 15px; border: none; border-radius: 4px; background-color: #007bff; color: white; cursor: pointer; font-size: 0.9em;">جستجو</button>
                <?php if (isset($_GET['search']) || isset($_GET['category_filter'])): ?>
                    <a href="index.php" style="padding: 8px 15px; border: none; border-radius: 4px; background-color: #6c757d; color: white; cursor: pointer; text-decoration: none; font-size: 0.9em;">بازنشانی</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($permission >= 2): ?>
            <a href="add_asset.php" class="add-button">افزودن دارایی جدید</a>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ردیف</th>
                        <th>تصویر</th>
                        <th onclick="sortTable('name')">نام</th>
                        <th>کد</th>
                        <th onclick="sortTable('category')">دسته بندی</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($assets) > 0): ?>
                        <?php $rowNumber = 1; ?>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td><?php echo $rowNumber++; ?></td>
                                <td>
                                    <?php if (!empty($asset['image'])): ?>
                                        <img src="<?php echo $asset['image']; ?>" alt="<?php echo $asset['name']; ?>" class="asset-image">
                                    <?php else: ?>
                                        بدون تصویر
                                    <?php endif; ?>
                                </td>
                                <td><a href="asset_details.php?id=<?php echo $asset['id']; ?>"><?php echo $asset['name']; ?></a></td>
                                <td><?php echo $asset['code']; ?></td>
                                <td><?php echo $asset['category_name']; ?></td>
                                <td><?php echo $asset['status']; ?></td>
                                <td class="actions">
                                    <a href="edit_asset.php?id=<?php echo $asset['id']; ?>">ویرایش</a>
                                    <?php if ($permission >= 2): ?>
                                        <a href="delete_asset.php?id=<?php echo $asset['id']; ?>" onclick="return confirm('آیا مطمئن هستید که می خواهید این دارایی را حذف کنید؟');">حذف</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">هیچ دارایی ثبت نشده است.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function sortTable(column) {
            // فعلا پیاده سازی نشده، در مراحل بعدی با جاوا اسکریپت انجام می شود
            console.log('مرتب سازی بر اساس ' + column);
        }
    </script>
</body>
</html>
