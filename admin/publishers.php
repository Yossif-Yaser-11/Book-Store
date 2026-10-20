<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/app.php';

$error = '';
$editingPublisher = null;
$search = trim((string) ($_GET['search'] ?? ''));

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $action = postString('action');
        $publisherId = postId();
        if ($action === 'delete') {
            $bookCount = $pdo->prepare('SELECT COUNT(*) FROM books WHERE publisher_id = :id');
            $bookCount->execute(['id' => $publisherId]);
            if ((int) $bookCount->fetchColumn() > 0) {
                throw new RuntimeException('لا يمكن حذف دار نشر مرتبطة بكتب. غيّر ربط الكتب أولًا.');
            }
            $statement = $pdo->prepare('DELETE FROM publishers WHERE id = :id');
            $statement->execute(['id' => $publisherId]);
            redirect('publishers.php');
        }
        $name = postString('name');
        if ($name === '') {
            throw new RuntimeException('يرجى إدخال اسم دار النشر.');
        }
        if ($action === 'create') {
            $statement = $pdo->prepare('INSERT INTO publishers (name) VALUES (:name)');
            $statement->execute(['name' => $name]);
            redirect('publishers.php');
        }
        if ($action === 'update' && $publisherId > 0) {
            $statement = $pdo->prepare('UPDATE publishers SET name = :name WHERE id = :id');
            $statement->execute(['name' => $name, 'id' => $publisherId]);
            redirect('publishers.php');
        }
    }
} catch (Throwable $exception) {
    $error = $exception->getCode() === '23000' ? 'اسم دار النشر مستخدم بالفعل.' : $exception->getMessage();
}

if (queryId() > 0) {
    $statement = $pdo->prepare('SELECT id, name FROM publishers WHERE id = :id');
    $statement->execute(['id' => queryId()]);
    $editingPublisher = $statement->fetch() ?: null;
}

if ($search !== '') {
    $statement = $pdo->prepare('SELECT id, name FROM publishers WHERE name LIKE :search ORDER BY name');
    $statement->execute(['search' => '%' . $search . '%']);
} else {
    $statement = $pdo->query('SELECT id, name FROM publishers ORDER BY name');
}
$publishers = $statement->fetchAll();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>دور النشر</title><link rel="stylesheet" href="css/admin.css"></head>
<body class="dashboard-page"><div class="dashboard-shell"><header class="topbar"><a class="brand" href="dashboard.php"><span class="brand-mark">ب</span><span>متجر الكتب</span></a><nav class="admin-nav"><a href="dashboard.php">لوحة التحكم</a><a href="users.php">المستخدمون</a><a href="books.php">الكتب</a><a href="categories.php">التصنيفات</a><a class="active" href="publishers.php">دور النشر</a><a href="orders.php">المشتريات</a><a href="access.php">فتح الكتب</a></nav><a class="button button-quiet" href="logout.php">تسجيل الخروج</a></header><main class="dashboard-content"><div class="page-heading"><div><p class="eyebrow">مصادر الكتب</p><h1>دور النشر</h1></div><a class="button button-primary" href="publishers.php#publisher-form">+ إضافة دار نشر</a></div><?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?><section class="content-panel" id="publisher-form"><div class="panel-title"><h2><?= $editingPublisher ? 'تعديل دار نشر' : 'إضافة دار نشر' ?></h2><?php if ($editingPublisher): ?><a class="button button-quiet" href="publishers.php">إلغاء</a><?php endif; ?></div><form class="entity-form compact-form" method="post"><input type="hidden" name="action" value="<?= $editingPublisher ? 'update' : 'create' ?>"><input type="hidden" name="id" value="<?= e($editingPublisher['id'] ?? '') ?>"><div><label for="name">اسم دار النشر</label><input id="name" name="name" required value="<?= e($editingPublisher['name'] ?? '') ?>"></div><button class="button button-primary" type="submit">حفظ</button></form></section><section class="content-panel"><form class="search-form" method="get"><input name="search" placeholder="ابحث عن دار نشر" value="<?= e($search) ?>"><button class="button button-quiet" type="submit">بحث</button></form><div class="table-wrap"><table><thead><tr><th>الاسم</th><th>الإجراءات</th></tr></thead><tbody><?php if (!$publishers): ?><tr><td class="empty-state" colspan="2">لا توجد دور نشر.</td></tr><?php endif; ?><?php foreach ($publishers as $publisher): ?><tr><td><?= e($publisher['name']) ?></td><td class="actions"><a class="button button-small button-quiet" href="publishers.php?edit=<?= e($publisher['id']) ?>#publisher-form">تعديل</a><form method="post" onsubmit="return confirm('هل أنت متأكد من حذف دار النشر؟');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($publisher['id']) ?>"><button class="button button-small button-danger" type="submit">حذف</button></form></td></tr><?php endforeach; ?></tbody></table></div></section></main></div></body></html>