<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$error = '';
$editingCategory = null;
$search = trim((string) ($_GET['search'] ?? ''));

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $action = postString('action');
        $categoryId = postId();

        if ($action === 'delete') {
            if ($categoryId < 1) {
                throw new RuntimeException('التصنيف المحدد غير صحيح.');
            }
            $categoryStatement = $pdo->prepare('SELECT id FROM categories WHERE id = :id FOR UPDATE');
            $pdo->beginTransaction();
            $categoryStatement->execute(['id' => $categoryId]);
            if (!$categoryStatement->fetch()) {
                throw new RuntimeException('التصنيف غير موجود.');
            }
            $bookCount = $pdo->prepare('SELECT COUNT(*) FROM books WHERE category_id = :id');
            $bookCount->execute(['id' => $categoryId]);
            if ((int) $bookCount->fetchColumn() > 0) {
                throw new RuntimeException('لا يمكن حذف تصنيف يحتوي على كتب. انقل الكتب إلى تصنيف آخر أولًا.');
            }
            $statement = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $statement->execute(['id' => $categoryId]);
            $pdo->commit();
            setFlash('success', 'تم حذف التصنيف بنجاح.');
            redirect('categories.php');
        }

        $name = postString('name');
        if ($name === '') {
            throw new RuntimeException('يرجى إدخال اسم التصنيف.');
        }

        if ($action === 'create') {
            $statement = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
            $statement->execute(['name' => $name]);
            redirect('categories.php');
        }

        if ($action === 'update' && $categoryId > 0) {
            $statement = $pdo->prepare('UPDATE categories SET name = :name WHERE id = :id');
            $statement->execute(['name' => $name, 'id' => $categoryId]);
            redirect('categories.php');
        }
    }
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = $exception->getCode() === '23000' ? 'لا يمكن حذف هذا التصنيف.' : $exception->getMessage();
}

if (queryId() > 0) {
    $statement = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id');
    $statement->execute(['id' => queryId()]);
    $editingCategory = $statement->fetch() ?: null;
}

if ($search !== '') {
    $statement = $pdo->prepare('SELECT id, name FROM categories WHERE name LIKE :search ORDER BY id DESC');
    $statement->execute(['search' => '%' . $search . '%']);
} else {
    $statement = $pdo->query('SELECT id, name FROM categories ORDER BY id DESC');
}
$categories = $statement->fetchAll();
$success = takeFlash('success');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>التصنيفات</title><link rel="stylesheet" href="css/admin.css?v=<?= (int) filemtime(__DIR__ . '/css/admin.css') ?>"></head>
<body class="dashboard-page"><div class="dashboard-shell">
<header class="topbar"><a class="brand" href="dashboard.php"><span class="brand-mark">ب</span><span>متجر الكتب</span></a><nav class="admin-nav"><a href="dashboard.php">لوحة التحكم</a><a href="users.php">المستخدمون</a><a href="books.php">الكتب</a><a class="active" href="categories.php">التصنيفات</a><a href="publishers.php">دور النشر</a><a href="orders.php">المشتريات</a><a href="access.php">فتح الكتب</a></nav><a class="button button-quiet" href="logout.php">تسجيل الخروج</a></header>
<main class="dashboard-content"><div class="page-heading"><div><p class="eyebrow">تنظيم الكتب</p><h1>التصنيفات</h1></div><a class="button button-primary" href="categories.php#category-form">+ إضافة تصنيف</a></div>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($success !== ''): ?><div class="success-alert" role="status"><?= e($success) ?></div><?php endif; ?>
<section class="content-panel" id="category-form"><div class="panel-title"><h2><?= $editingCategory ? 'تعديل تصنيف' : 'إضافة تصنيف' ?></h2><?php if ($editingCategory): ?><a class="button button-quiet" href="categories.php">إلغاء</a><?php endif; ?></div><form class="entity-form compact-form" method="post"><input type="hidden" name="action" value="<?= $editingCategory ? 'update' : 'create' ?>"><input type="hidden" name="id" value="<?= e($editingCategory['id'] ?? '') ?>"><div><label for="name">اسم التصنيف</label><input id="name" name="name" required value="<?= e($editingCategory['name'] ?? '') ?>"></div><button class="button button-primary" type="submit">حفظ</button></form></section>
<section class="content-panel"><form class="search-form" method="get"><input name="search" placeholder="ابحث باسم التصنيف" value="<?= e($search) ?>"><button class="button button-quiet" type="submit">بحث</button></form><div class="table-wrap"><table><thead><tr><th>ID</th><th>اسم التصنيف</th><th>الإجراءات</th></tr></thead><tbody><?php if (!$categories): ?><tr><td class="empty-state" colspan="3">لا توجد تصنيفات.</td></tr><?php endif; ?><?php foreach ($categories as $category): ?><tr><td><?= e($category['id']) ?></td><td><?= e($category['name']) ?></td><td class="actions"><a class="button button-small button-quiet" href="categories.php?edit=<?= e($category['id']) ?>#category-form">تعديل</a><form class="delete-form" method="post" onsubmit="return confirm('هل أنت متأكد من حذف هذا التصنيف؟');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($category['id']) ?>"><button class="button button-small button-danger" type="submit">حذف التصنيف</button></form></td></tr><?php endforeach; ?></tbody></table></div></section></main></div></body></html>
