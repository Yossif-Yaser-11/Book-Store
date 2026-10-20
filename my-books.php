<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
if (!isLoggedIn()) { redirect('login.php'); }
$statement = $pdo->prepare('SELECT books.id, books.title, books.author, books.image, books.description, book_access.created_at FROM book_access INNER JOIN books ON books.id = book_access.book_id WHERE book_access.user_id = :user_id ORDER BY book_access.created_at DESC');
$statement->execute(['user_id' => $_SESSION['user_id']]);
$books = $statement->fetchAll();
$pageTitle = 'كتبي المفتوحة'; require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><div class="container"><p class="eyebrow">مكتبتك الخاصة</p><h1>كتبي المفتوحة</h1><p>الكتب التي أتاحها لك فريق المكتبة بعد مراجعة الدفع.</p></div></section><section class="section"><div class="container"><div class="book-grid"><?php if (!$books): ?><div class="empty-state" style="grid-column:1/-1"><h2>لا توجد كتب مفتوحة بعد</h2><p>بعد تأكيد الدفع سيظهر الكتاب هنا.</p><a class="button button-primary" href="books.php">تصفح الكتب</a></div><?php endif; ?><?php foreach ($books as $book): ?><article class="book-card"><img class="book-cover" src="<?= e(bookImage($book['image'])) ?>" alt="<?= e($book['title']) ?>"><div class="book-info"><p class="book-category">مفتوح لك</p><h3><?= e($book['title']) ?></h3><p class="book-author">بقلم <?= e($book['author']) ?></p><a class="form-link" href="book.php?id=<?= e($book['id']) ?>">فتح الكتاب ←</a></div></article><?php endforeach; ?></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
