<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

function tableCount(PDO $pdo, string $table): int
{
    $allowedTables = ['users', 'books', 'categories'];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('اسم جدول غير مسموح.');
    }
    return (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
}

$statistics = [
    ['label' => 'المستخدمون', 'value' => tableCount($pdo, 'users'), 'class' => 'users', 'icon' => '◉'],
    ['label' => 'الكتب', 'value' => tableCount($pdo, 'books'), 'class' => 'books', 'icon' => '▤'],
    ['label' => 'التصنيفات', 'value' => tableCount($pdo, 'categories'), 'class' => 'categories', 'icon' => '◈'],
    ['label' => 'دور النشر', 'value' => (int) $pdo->query('SELECT COUNT(*) FROM publishers')->fetchColumn(), 'class' => 'publishers', 'icon' => '▥'],
    ['label' => 'المشتريات', 'value' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(), 'class' => 'orders', 'icon' => '◫'],
    ['label' => 'الكتب المفتوحة', 'value' => (int) $pdo->query('SELECT COUNT(*) FROM book_access')->fetchColumn(), 'class' => 'access', 'icon' => '✓'],
];

$recentBooks = $pdo->query('SELECT id, title, author, image, stock FROM books ORDER BY id DESC LIMIT 5')->fetchAll();
$userName = (string) ($_SESSION['user_name'] ?? 'المشرف');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="css/admin.css?v=<?= (int) filemtime(__DIR__ . '/css/admin.css') ?>">
</head>
<body class="dashboard-page dashboard-home">
<div class="dashboard-shell">
    <header class="topbar">
        <a class="brand" href="dashboard.php"><span class="brand-mark">ب</span><span>متجر الكتب</span></a>
        <nav class="admin-nav" aria-label="التنقل الإداري">
            <a class="active" href="dashboard.php">لوحة التحكم</a>
            <a href="users.php">المستخدمون</a>
            <a href="books.php">الكتب</a>
            <a href="categories.php">التصنيفات</a>
            <a href="publishers.php">دور النشر</a>
            <a href="orders.php">المشتريات</a>
            <a href="access.php">فتح الكتب</a>
        </nav>
        <div class="user-header">
            <a class="front-link" href="../index.php" target="_blank" rel="noopener">فتح الموقع</a>
            <a class="button button-quiet" href="logout.php">تسجيل الخروج</a>
        </div>
    </header>

    <main class="dashboard-content">
        <section class="dashboard-welcome">
            <div>
                <p class="eyebrow">نظرة عامة</p>
                <h1>أهلًا، <?= e($userName) ?></h1>
                <p>إدارة الكتب والتصنيفات والمستخدمين والمشتريات من مكان واحد.</p>
            </div>
            <div class="dashboard-actions">
                <a class="button button-primary" href="books.php#book-form">إضافة كتاب</a>
                <a class="button button-quiet" href="categories.php#category-form">إضافة تصنيف</a>
            </div>
        </section>

        <section class="stats-grid" aria-label="إحصائيات المتجر">
            <?php foreach ($statistics as $stat): ?>
                <article class="stat-card <?= e($stat['class']) ?>">
                    <div class="stat-card-top"><span class="stat-icon" aria-hidden="true"><?= e($stat['icon']) ?></span><span class="stat-label">إحصائية</span></div>
                    <span><?= e($stat['label']) ?></span>
                    <strong><?= number_format($stat['value']) ?></strong>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="admin-feature-links" aria-label="إدارة خدمات المتجر">
            <a href="publishers.php"><strong>دور النشر</strong><span>إضافة وتعديل دور النشر وربطها بالكتب</span><b>إدارة الدور ←</b></a>
            <a href="orders.php"><strong>المشتريات</strong><span>مراجعة الطلبات وتحديث حالتها</span><b>مراجعة الطلبات ←</b></a>
            <a href="access.php"><strong>فتح الكتب</strong><span>إتاحة الكتب للمستخدمين بعد تأكيد الدفع</span><b>فتح كتاب ←</b></a>
        </section>

        <section class="dashboard-grid">
            <article class="dashboard-panel">
                <div class="panel-heading-row"><div><p class="eyebrow">المكتبة</p><h2>أحدث الكتب</h2></div><a href="books.php">إدارة الكتب</a></div>
                <?php if (!$recentBooks): ?>
                    <p class="dashboard-empty">لا توجد كتب حتى الآن.</p>
                <?php else: ?>
                    <div class="book-list">
                        <?php foreach ($recentBooks as $book): ?>
                            <a class="book-row" href="books.php?edit=<?= e($book['id']) ?>#book-form"><span class="mini-cover"><?php if ($book['image']): ?><img src="<?= e($book['image']) ?>" alt=""><?php else: ?><span>ب</span><?php endif; ?></span><span class="book-row-info"><strong><?= e($book['title']) ?></strong><small><?= e($book['author']) ?></small></span><span class="stock-value <?= (int) $book['stock'] < 5 ? 'low-stock' : '' ?>"><?= e($book['stock']) ?> متاح</span></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    </main>
</div>
</body>
</html>