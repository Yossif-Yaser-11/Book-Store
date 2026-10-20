<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

$search = trim((string) ($_GET['search'] ?? ''));
$publisherId = filter_input(INPUT_GET, 'publisher', FILTER_VALIDATE_INT) ?: 0;
$publishers = fetchPublishers($pdo);

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(publishers.name LIKE :search_name OR books.title LIKE :search_title)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_title'] = '%' . $search . '%';
}
if ($publisherId > 0) {
    $where[] = 'publishers.id = :publisher_id';
    $params['publisher_id'] = $publisherId;
}

$sql = 'SELECT books.id, books.title, books.author, books.image, books.price, publishers.name AS publisher_name FROM books INNER JOIN publishers ON publishers.id = books.publisher_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY publishers.name, books.title';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$books = $statement->fetchAll();

$pageTitle = 'دور النشر';
$activePage = 'publishers';
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><div class="container"><p class="eyebrow">اكتشف مصادر الكتب</p><h1>دور النشر</h1><p>تصفح الكتب حسب دار النشر وتعرّف على الإصدارات المتاحة.</p></div></section>
<section class="section"><div class="container">
    <form class="filter-bar" method="get">
        <input name="search" value="<?= e($search) ?>" placeholder="ابحث باسم دار النشر أو الكتاب" aria-label="بحث في دور النشر">
        <select name="publisher" aria-label="فلترة حسب دار النشر"><option value="0">كل دور النشر</option><?php foreach ($publishers as $publisher): ?><option value="<?= e($publisher['id']) ?>" <?= $publisherId === (int) $publisher['id'] ? 'selected' : '' ?>><?= e($publisher['name']) ?></option><?php endforeach; ?></select>
        <button class="button button-primary" type="submit">بحث</button>
    </form>
    <?php if (!$publishers): ?>
        <div class="empty-state"><h2>لا توجد دور نشر بعد</h2><p>ستظهر دور النشر هنا عند إضافتها من الإدارة.</p></div>
    <?php elseif (!$books): ?>
        <div class="empty-state"><h2>لا توجد كتب مطابقة</h2><p>جرّب بحثًا مختلفًا أو اختر دار نشر أخرى.</p></div>
    <?php else: ?>
        <div class="book-grid"><?php foreach ($books as $book): ?><article class="book-card"><img class="book-cover" src="<?= e(bookImage($book['image'])) ?>" alt="<?= e($book['title']) ?>"><div class="book-info"><p class="book-category"><?= e($book['publisher_name']) ?></p><h3><?= e($book['title']) ?></h3><p class="book-author">بقلم <?= e($book['author']) ?></p><p class="book-author"><?= e(number_format((float) $book['price'], 2)) ?> جنيه</p><a class="form-link" href="book.php?id=<?= e($book['id']) ?>">عرض الكتاب ←</a></div></article><?php endforeach; ?></div>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
