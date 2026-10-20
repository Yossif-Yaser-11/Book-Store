<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$commentError = '';
$commentBody = '';
$ratingError = '';
$selectedRating = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        if (!isLoggedIn()) {
            throw new RuntimeException('سجل الدخول أولًا لتنفيذ هذا الإجراء.');
        }
        $action = postString('action');
        if ($action === 'rating') {
            $selectedRating = (int) ($_POST['rating'] ?? 0);
            if ($bookId < 1 || $selectedRating < 1 || $selectedRating > 5) {
                throw new RuntimeException('اختر تقييمًا من نجمة إلى خمس نجوم.');
            }
            $ratingStatement = $pdo->prepare('INSERT INTO book_ratings (book_id, user_id, rating) VALUES (:book_id, :user_id, :rating) ON DUPLICATE KEY UPDATE rating = VALUES(rating), created_at = CURRENT_TIMESTAMP');
            $ratingStatement->execute(['book_id' => $bookId, 'user_id' => $_SESSION['user_id'], 'rating' => $selectedRating]);
            awardPoints($pdo, (int) $_SESSION['user_id'], 'rating:' . $bookId, 15, 'تقييم كتاب');
            redirect('book.php?id=' . $bookId . '#rating');
        }
        if ($action === 'comment') {
            $commentBody = trim((string) ($_POST['body'] ?? ''));
            if ($bookId < 1 || $commentBody === '' || mb_strlen($commentBody) > 2000) {
                throw new RuntimeException('اكتب تعليقًا صحيحًا لا يتجاوز 2000 حرف.');
            }
            $commentStatement = $pdo->prepare('INSERT INTO book_comments (book_id, user_id, body) VALUES (:book_id, :user_id, :body)');
            $commentStatement->execute(['book_id' => $bookId, 'user_id' => $_SESSION['user_id'], 'body' => $commentBody]);
            awardPoints($pdo, (int) $_SESSION['user_id'], 'comment:' . $pdo->lastInsertId(), 10, 'نشر تعليق');
            redirect('book.php?id=' . $bookId . '#comments');
        }
    } catch (Throwable $exception) {
        if (($action ?? '') === 'rating') {
            $ratingError = $exception->getMessage();
        } else {
            $commentError = $exception->getMessage();
        }
    }
}

$statement = $pdo->prepare('SELECT books.id, books.title, books.author, books.description, books.price, books.rating, books.image, books.stock, categories.name AS category_name, publishers.name AS publisher_name FROM books LEFT JOIN categories ON categories.id = books.category_id LEFT JOIN publishers ON publishers.id = books.publisher_id WHERE books.id = :id LIMIT 1');
$statement->execute(['id' => $bookId]);
$book = $statement->fetch();
$hasAccess = false;
$comments = [];
$averageRating = 0;
$userRating = 0;

if ($book) {
    $averageStatement = $pdo->prepare('SELECT AVG(rating) FROM book_ratings WHERE book_id = :book_id');
    $averageStatement->execute(['book_id' => $bookId]);
    $averageRating = (float) ($averageStatement->fetchColumn() ?: $book['rating'] ?: 0);
    if (isLoggedIn()) {
        $userRatingStatement = $pdo->prepare('SELECT rating FROM book_ratings WHERE book_id = :book_id AND user_id = :user_id LIMIT 1');
        $userRatingStatement->execute(['book_id' => $bookId, 'user_id' => $_SESSION['user_id']]);
        $userRating = (int) ($userRatingStatement->fetchColumn() ?: 0);
        $accessStatement = $pdo->prepare('SELECT id FROM book_access WHERE user_id = :user_id AND book_id = :book_id LIMIT 1');
        $accessStatement->execute(['user_id' => $_SESSION['user_id'], 'book_id' => $bookId]);
        $hasAccess = (bool) $accessStatement->fetch();
    }
    $commentsStatement = $pdo->prepare('SELECT book_comments.body, book_comments.created_at, users.name AS user_name FROM book_comments INNER JOIN users ON users.id = book_comments.user_id WHERE book_comments.book_id = :book_id ORDER BY book_comments.created_at DESC');
    $commentsStatement->execute(['book_id' => $bookId]);
    $comments = $commentsStatement->fetchAll();
} else {
    http_response_code(404);
}

$pageTitle = $book ? (string) $book['title'] : 'الكتاب غير موجود';
$activePage = 'books';
require __DIR__ . '/includes/header.php';
?>
<?php if (!$book): ?>
    <section class="section"><div class="container"><div class="empty-state"><h1>الكتاب غير موجود</h1><p>ربما تم حذف الكتاب أو أن الرابط غير صحيح.</p><a class="button button-primary" href="books.php">العودة إلى الكتب</a></div></div></section>
<?php else: ?>
    <section class="book-detail-section"><div class="container"><a class="back-link" href="books.php">← العودة إلى كل الكتب</a><div class="book-detail"><div class="book-detail-cover"><img src="<?= e(bookImage($book['image'])) ?>" alt="غلاف كتاب <?= e($book['title']) ?>"></div><div class="book-detail-content"><p class="eyebrow"><?= e($book['category_name'] ?? 'بدون تصنيف') ?></p><h1><?= e($book['title']) ?></h1><p class="detail-author">بقلم <?= e($book['author']) ?></p><div class="detail-meta"><span>السعر: <strong><?= e(number_format((float) $book['price'], 2)) ?> جنيه</strong></span><span>التقييم: <strong><?= e(number_format($averageRating, 1)) ?>/5</strong></span><span>المتاح: <strong><?= e($book['stock']) ?></strong></span></div><?php if (!empty($book['publisher_name'])): ?><div class="purchase-location"><strong>دار النشر:</strong> <?= e($book['publisher_name']) ?></div><?php endif; ?><?php if (!empty($book['purchase_location'])): ?><div class="purchase-location"><strong>مكان الشراء:</strong> <?= e($book['purchase_location']) ?></div><?php endif; ?><div class="detail-description"><h2>عن الكتاب</h2><p><?= nl2br(e($book['description'] ?: 'لا يوجد وصف لهذا الكتاب حاليًا.')) ?></p></div><section class="rating-box" id="rating"><h2>قيّم هذا الكتاب</h2><?php if ($ratingError): ?><div class="alert" role="alert"><?= e($ratingError) ?></div><?php endif; ?><?php if (isLoggedIn()): ?><form method="post"><input type="hidden" name="action" value="rating"><div class="rating-options" role="radiogroup" aria-label="تقييم الكتاب"><?php for ($rating = 1; $rating <= 5; $rating++): ?><label><input type="radio" name="rating" value="<?= $rating ?>" <?= $userRating === $rating ? 'checked' : '' ?> required><span><?= $rating ?> ★</span></label><?php endfor; ?></div><button class="button button-quiet" type="submit"><?= $userRating ? 'تحديث التقييم' : 'إرسال التقييم' ?></button></form><?php else: ?><p class="form-note">سجل الدخول حتى تتمكن من تقييم الكتاب.</p><?php endif; ?></section><?php if ($hasAccess): ?><div class="access-note">هذا الكتاب مفتوح لك ويمكنك الوصول إليه.</div><a class="button button-primary" href="my-books.php">فتح كتبي</a><?php elseif ((int) $book['stock'] > 0): ?><form class="add-cart-form" method="post" action="cart-add.php"><input type="hidden" name="book_id" value="<?= e($book['id']) ?>"><label for="quantity">الكمية</label><input id="quantity" name="quantity" type="number" min="1" max="<?= e($book['stock']) ?>" value="1"><button class="button button-primary" type="submit">أضف إلى العربة</button></form><?php else: ?><p class="out-of-stock">هذا الكتاب غير متاح حاليًا.</p><?php endif; ?><a class="button button-outline" href="books.php">تصفح كتب أخرى</a></div></div></div></section>
    <section class="section comments-section" id="comments"><div class="container comments-layout"><div><div class="section-heading"><div><p class="eyebrow">آراء القراء</p><h2>تعليقات المستخدمين</h2></div><span class="comments-count"><?= number_format(count($comments)) ?> تعليق</span></div><?php if (!$comments): ?><div class="empty-state">لا توجد تعليقات بعد. كن أول من يشارك رأيه.</div><?php else: ?><div class="comments-list"><?php foreach ($comments as $comment): ?><article class="comment-card"><div class="comment-card-heading"><strong><?= e($comment['user_name']) ?></strong><time datetime="<?= e($comment['created_at']) ?>"><?= e($comment['created_at']) ?></time></div><p><?= nl2br(e($comment['body'])) ?></p></article><?php endforeach; ?></div><?php endif; ?></div><aside class="comment-form-card"><h2>شارك رأيك</h2><?php if ($commentError): ?><div class="alert" role="alert"><?= e($commentError) ?></div><?php endif; ?><?php if (isLoggedIn()): ?><form method="post"><input type="hidden" name="action" value="comment"><label for="comment-body">تعليقك</label><textarea id="comment-body" name="body" rows="6" maxlength="2000" required><?= e($commentBody) ?></textarea><button class="button button-primary" type="submit">نشر التعليق</button></form><?php else: ?><p class="form-note">سجل الدخول حتى تتمكن من إضافة تعليق.</p><a class="button button-primary" href="login.php?redirect=book.php%3Fid%3D<?= e($book['id']) ?>">تسجيل الدخول</a><?php endif; ?></aside></div></section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>