<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/app.php';

$error = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $userId = postId('user_id'); $bookId = postId('book_id');
        if ($userId < 1 || $bookId < 1) { throw new RuntimeException('اختر المستخدم والكتاب.'); }
        $userCheck = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = "user" LIMIT 1'); $userCheck->execute(['id' => $userId]);
        $bookCheck = $pdo->prepare('SELECT id FROM books WHERE id = :id LIMIT 1'); $bookCheck->execute(['id' => $bookId]);
        if (!$userCheck->fetch() || !$bookCheck->fetch()) { throw new RuntimeException('المستخدم أو الكتاب غير موجود.'); }
        $grant = $pdo->prepare('INSERT IGNORE INTO book_access (user_id, book_id, granted_by) VALUES (:user_id, :book_id, :admin_id)'); $grant->execute(['user_id' => $userId, 'book_id' => $bookId, 'admin_id' => $_SESSION['user_id']]);
        redirect('access.php?done=1');
    }
} catch (Throwable $exception) { $error = $exception->getMessage(); }
$users = $pdo->query('SELECT users.id, users.name, users.email, COALESCE(MAX(orders.status), "no_order") AS order_status FROM users LEFT JOIN orders ON orders.user_id = users.id WHERE users.role = "user" GROUP BY users.id, users.name, users.email ORDER BY users.name')->fetchAll();
$books = $pdo->query('SELECT id, title, author FROM books ORDER BY title')->fetchAll();
$grants = $pdo->query('SELECT book_access.id, users.name AS user_name, users.email, books.title, book_access.created_at FROM book_access INNER JOIN users ON users.id = book_access.user_id INNER JOIN books ON books.id = book_access.book_id ORDER BY book_access.id DESC')->fetchAll();
?>
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>فتح الكتب</title><link rel="stylesheet" href="css/admin.css"></head><body class="dashboard-page"><div class="dashboard-shell"><header class="topbar"><a class="brand" href="dashboard.php"><span class="brand-mark">ب</span><span>متجر الكتب</span></a><nav class="admin-nav"><a href="dashboard.php">لوحة التحكم</a><a href="users.php">المستخدمون</a><a href="books.php">الكتب</a><a href="categories.php">التصنيفات</a><a href="orders.php">المشتريات</a><a class="active" href="access.php">فتح الكتب</a></nav><a class="button button-quiet" href="logout.php">تسجيل الخروج</a></header><main class="dashboard-content"><div class="page-heading"><div><p class="eyebrow">صلاحيات القراءة</p><h1>فتح كتب للمشترين</h1></div></div><?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?><?php if (($_GET['done'] ?? '') === '1'): ?><div class="success-alert" role="status">تم فتح الكتاب للمستخدم.</div><?php endif; ?><section class="content-panel"><form class="entity-form compact-form" method="post"><div><label for="user_id">المستخدم صاحب الدفع</label><select id="user_id" name="user_id" required><option value="">اختر المستخدم</option><?php foreach ($users as $user): ?><option value="<?= e($user['id']) ?>"><?= e($user['name']) ?> - <?= e($user['email']) ?></option><?php endforeach; ?></select></div><div><label for="book_id">الكتاب</label><select id="book_id" name="book_id" required><option value="">اختر الكتاب</option><?php foreach ($books as $book): ?><option value="<?= e($book['id']) ?>"><?= e($book['title']) ?> - <?= e($book['author']) ?></option><?php endforeach; ?></select></div><button class="button button-primary" type="submit">فتح الكتاب</button></form></section><section class="content-panel"><h2>الكتب المفتوحة حاليًا</h2><div class="table-wrap"><table><thead><tr><th>المستخدم</th><th>البريد</th><th>الكتاب</th><th>التاريخ</th></tr></thead><tbody><?php if (!$grants): ?><tr><td class="empty-state" colspan="4">لم يتم فتح كتب بعد.</td></tr><?php endif; ?><?php foreach ($grants as $grant): ?><tr><td><?= e($grant['user_name']) ?></td><td><?= e($grant['email']) ?></td><td><?= e($grant['title']) ?></td><td><?= e($grant['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></section></main></div></body></html>
