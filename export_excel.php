<?php

require_once 'db_config.php';
session_start();

// بررسی سطح دسترسی
if (!isset($_SESSION['user_id']) || $_SESSION['permission'] < 2) {
    header('Location: index.php');
    exit();
}

// تنظیم هدرهای اکسل
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="asset_list_' . date('YmdHis') . '.xls"');
header('Cache-Control: max-age=0');

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
echo '<body>';
echo '<table>';

try {
    $stmt = $pdo->prepare("DESCRIBE assets");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // فیلدهایی که نمی‌خواهیم در اکسل باشند
    $excludedFields = ['image', 'category_id']; // category_id را حذف کردیم چون نام را جداگانه می‌گیریم

    // فیلتر کردن ستون‌های غیرضروری
    $exportColumns = array_diff($columns, $excludedFields);

    // دریافت داده‌های دارایی‌ها به همراه نام دسته‌بندی
    $selectClause = '';
    if (!empty($exportColumns)) {
        $selectClause = 'a.`' . implode('`, a.`', $exportColumns) . '`';
    }

    $stmt = $pdo->query("
       SELECT
           {$selectClause},
           c.name AS category_name
       FROM
           assets a
       LEFT JOIN
           categories c ON a.category_id = c.id
       ORDER BY
           a.name ASC
   ");
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ایجاد سربرگ‌ها
    echo '<thead><tr>';
    foreach ($exportColumns as $column) {
        $columnTitle = $column; // پیش‌فرض عنوان همان نام ستون است
        switch ($column) {
            case 'id':
                $columnTitle = 'شناسه';
                break;
            case 'name':
                $columnTitle = 'نام';
                break;
            case 'code':
                $columnTitle = 'کد';
                break;
            case 'quantity':
                $columnTitle = 'تعداد';
                break;
            case 'purchase_date':
                $columnTitle = 'تاریخ خرید';
                break;
            case 'purchase_price':
                $columnTitle = 'قیمت خرید';
                break;
            case 'status':
                $columnTitle = 'وضعیت';
                break;
            case 'description':
                $columnTitle = 'توضیحات';
                break;
            // می‌توانید موارد بیشتری را در صورت نیاز اضافه کنید
        }
        echo '<th>' . htmlspecialchars($columnTitle) . '</th>';
    }
    echo '<th>دسته‌بندی</th>'; // افزودن سربرگ دسته‌بندی
    echo '</tr></thead><tbody>';

    // ایجاد ردیف‌های داده
    foreach ($assets as $asset) {
        echo '<tr>';
        foreach ($exportColumns as $column) {
            echo '<td>' . htmlspecialchars($asset[$column]) . '</td>';
        }
        echo '<td>' . htmlspecialchars($asset['category_name']) . '</td>'; // نمایش نام دسته‌بندی
        echo '</tr>';
    }

    echo '</tbody></table></body></html>';

} catch (PDOException $e) {
    die("خطا در دریافت اطلاعات پایگاه داده: " . $e->getMessage());
}

?>
