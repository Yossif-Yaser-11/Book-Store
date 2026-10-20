<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$error = '';
$editingBook = null;
$search = trim((string) ($_GET['search'] ?? ''));
$uploadDirectory = __DIR__ . '/uploads/books';
$publicDirectory = 'uploads/books';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $action = postString('action');
        $bookId = postId();

        if ($action === 'delete') {
            if ($bookId < 1) {
                throw new RuntimeException('الكتاب المحدد غير صحيح.');
            }
            $imageStatement = $pdo->prepare('SELECT image FROM books WHERE id = :id LIMIT 1');
            $imageStatement->execute(['id' => $bookId]);
            $bookToDelete = $imageStatement->fetch();
            if (!$bookToDelete) {
                throw new RuntimeException('الكتاب غير موجود.');
            }
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM book_access WHERE book_id = :id')->execute(['id' => $bookId]);
            $pdo->prepare('DELETE FROM books WHERE id = :id')->execute(['id' => $bookId]);
            $pdo->commit();
            deleteBookImage($bookToDelete['image'] ?? null, $uploadDirectory);
            setFlash('success', 'تم حذف الكتاب بنجاح.');
            redirect('books.php');
        }

        $title = postString('title');
        $author = postString('author');
        $description = postString('description');
        $price = postString('price');
        $categoryId = postId('category_id');
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);

        if ($title === '' || $author === '' || $price === '' || !is_numeric($price) || (float) $price < 0 || $categoryId < 1 || $stock === false || $stock < 0) {
            throw new RuntimeException('يرجى إدخال بيانات الكتاب بشكل صحيح.');
        }

        $categoryStatement = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
        $categoryStatement->execute(['id' => $categoryId]);
        if (!$categoryStatement->fetch()) {
            throw new RuntimeException('التصنيف المحدد غير موجود.');
        }

        $newImage = uploadBookImage('image', $uploadDirectory, $publicDirectory);
        if ($action === 'create') {
            $statement = $pdo->prepare('INSERT INTO books (title, author, description, price, image, category_id, stock) VALUES (:title, :author, :description, :price, :image, :category_id, :stock)');
            $statement->execute(['title' => $title, 'author' => $author, 'description' => $description, 'price' => $price, 'image' => $newImage, 'category_id' => $categoryId, 'stock' => $stock]);
            redirect('books.php');
        }

        if ($action === 'update' && $bookId > 0) {
            $oldStatement = $pdo->prepare('SELECT image FROM books WHERE id = :id');
            $oldStatement->execute(['id' => $bookId]);
            $oldBook = $oldStatement->fetch();
            $image = $newImage ?: ($oldBook['image'] ?? null);
            $statement = $pdo->prepare('UPDATE books SET title = :title, author = :author, description = :description, price = :price, image = :image, category_id = :category_id, stock = :stock WHERE id = :id');
            $statement->execute(['title' => $title, 'author' => $author, 'description' => $description, 'price' => $price, 'image' => $image, 'category_id' => $categoryId, 'stock' => $stock, 'id' => $bookId]);
            if ($newImage) {
                deleteBookImage($oldBook['image'] ?? null, $uploadDirectory);
            }
            redirect('books.php');
        }
    }
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = $exception->getMessage();
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
if (queryId() > 0) {
    $statement = $pdo->prepare('SELECT id, title, author, description, price, image, category_id, stock FROM books WHERE id = :id');
    $statement->execute(['id' => queryId()]);
    $editingBook = $statement->fetch() ?: null;
}

if ($search !== '') {
    $statement = $pdo->prepare('SELECT books.id, books.title, books.author, books.price, books.image, books.stock, categories.name AS category_name FROM books LEFT JOIN categories ON categories.id = books.category_id WHERE books.title LIKE :search_title OR books.author LIKE :search_author ORDER BY books.id DESC');
    $statement->execute(['search_title' => '%' . $search . '%', 'search_author' => '%' . $search . '%']);
} else {
    $statement = $pdo->query('SELECT books.id, books.title, books.author, books.price, books.image, books.stock, categories.name AS category_name FROM books LEFT JOIN categories ON categories.id = books.category_id ORDER BY books.id DESC');
}
$books = $statement->fetchAll();
$success = takeFlash('success');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>الكتب</title><link rel="stylesheet" href="css/admin.css?v=<?= (int) filemtime(__DIR__ . '/css/admin.css') ?>"></head>
<body class="dashboard-page"><div class="dashboard-shell">
<header class="topbar"><a class="brand" href="dashboard.php"><span class="brand-mark">ب</span><span>متجر الكتب</span></a><nav class="admin-nav"><a href="dashboard.php">لوحة التحكم</a><a href="users.php">المستخدمون</a><a class="active" href="books.php">الكتب</a><a href="categories.php">التصنيفات</a><a href="publishers.php">دور النشر</a><a href="orders.php">المشتريات</a><a href="access.php">فتح الكتب</a></nav><a class="button button-quiet" href="logout.php">تسجيل الخروج</a></header>
<main class="dashboard-content"><div class="page-heading"><div><p class="eyebrow">مخزون المتجر</p><h1>الكتب</h1></div><a class="button button-primary" href="books.php#book-form">+ إضافة كتاب</a></div>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($success !== ''): ?><div class="success-alert" role="status"><?= e($success) ?></div><?php endif; ?>
<section class="content-panel" id="book-form"><div class="panel-title"><h2><?= $editingBook ? 'تعديل كتاب' : 'إضافة كتاب' ?></h2><?php if ($editingBook): ?><a class="button button-quiet" href="books.php">إلغاء</a><?php endif; ?></div><?php if (!$categories): ?><div class="alert">أضف تصنيفًا أولًا قبل إضافة كتاب.</div><?php endif; ?><form class="entity-form book-form" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="<?= $editingBook ? 'update' : 'create' ?>"><input type="hidden" name="id" value="<?= e($editingBook['id'] ?? '') ?>"><div><label for="title">العنوان</label><input id="title" name="title" required value="<?= e($editingBook['title'] ?? '') ?>"></div><div><label for="author">المؤلف</label><input id="author" name="author" required value="<?= e($editingBook['author'] ?? '') ?>"></div><div><label for="price">السعر</label><input id="price" name="price" type="number" min="0" step="0.01" required value="<?= e($editingBook['price'] ?? '') ?>"></div><div><label for="stock">المخزون</label><input id="stock" name="stock" type="number" min="0" step="1" required value="<?= e($editingBook['stock'] ?? 0) ?>"></div><div><label for="category_id">التصنيف</label><select id="category_id" name="category_id" required><option value="">اختر التصنيف</option><?php foreach ($categories as $category): ?><option value="<?= e($category['id']) ?>" <?= (int) ($editingBook['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div><div><label for="image">صورة الغلاف <?= $editingBook ? '(اختياري)' : '' ?></label><input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><?php if (!empty($editingBook['image'])): ?><small>الصورة الحالية محفوظة، ارفع صورة جديدة لاستبدالها.</small><?php endif; ?></div><div class="field-wide"><label for="description">الوصف</label><textarea id="description" name="description" rows="4"><?= e($editingBook['description'] ?? '') ?></textarea></div><button class="button button-primary" type="submit" <?= !$categories ? 'disabled' : '' ?>>حفظ</button></form></section>
<section class="content-panel"><form class="search-form" method="get"><input name="search" placeholder="ابحث بعنوان الكتاب أو المؤلف" value="<?= e($search) ?>"><button class="button button-quiet" type="submit">بحث</button></form><div class="table-wrap"><table><thead><tr><th>ID</th><th>الصورة</th><th>العنوان</th><th>المؤلف</th><th>التصنيف</th><th>السعر</th><th>المخزون</th><th>الإجراءات</th></tr></thead><tbody><?php if (!$books): ?><tr><td class="empty-state" colspan="8">لا توجد كتب.</td></tr><?php endif; ?><?php foreach ($books as $book): ?><tr><td><?= e($book['id']) ?></td><td><?php if ($book['image']): ?><img class="book-thumb" src="<?= e($book['image']) ?>" alt="<?= e($book['title']) ?>"><?php else: ?><span class="no-image">لا توجد</span><?php endif; ?></td><td><?= e($book['title']) ?></td><td><?= e($book['author']) ?></td><td><?= e($book['category_name'] ?? 'بدون تصنيف') ?></td><td><?= e(number_format((float) $book['price'], 2)) ?></td><td><?= e($book['stock']) ?></td><td class="actions"><a class="button button-small button-quiet" href="books.php?edit=<?= e($book['id']) ?>#book-form">تعديل</a><form class="delete-form" method="post" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكتاب؟');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($book['id']) ?>"><button class="button button-small button-danger" type="submit">حذف الكتاب</button></form></td></tr><?php endforeach; ?></tbody></table></div></section></main></div></body></html>
