<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = postString('action');
    $bookId = postId('book_id');
    if ($action === 'remove') {
        unset($_SESSION['cart'][$bookId]);
    } elseif ($action === 'update' && $bookId > 0) {
        $quantity = max(0, (int) ($_POST['quantity'] ?? 0));
        $stockStatement = $pdo->prepare('SELECT stock FROM books WHERE id = :id LIMIT 1');
        $stockStatement->execute(['id' => $bookId]);
        $stock = (int) ($stockStatement->fetchColumn() ?: 0);
        if ($quantity === 0 || $stock < 1) {
            unset($_SESSION['cart'][$bookId]);
        } else {
            $_SESSION['cart'][$bookId] = min($quantity, $stock);
        }
    }
    redirect('cart.php');
}

$cart = array_filter(array_map('intval', $_SESSION['cart'] ?? []));
$items = [];
if ($cart) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare("SELECT id, title, author, image, price, stock FROM books WHERE id IN ($placeholders) ORDER BY id DESC");
    $statement->execute($ids);
    foreach ($statement->fetchAll() as $book) { $book['quantity'] = $cart[(int) $book['id']]; $items[] = $book; }
}
$total = array_sum(array_map(static fn (array $item): float => (float) $item['price'] * $item['quantity'], $items));
$pageTitle = 'عربة المشتريات'; require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><div class="container"><p class="eyebrow">خطوتك التالية</p><h1>عربة المشتريات</h1><p>راجع الكتب قبل إتمام الطلب والدفع عبر InstaPay.</p></div></section>
<section class="section"><div class="container cart-layout"><div><?php if (!$items): ?><div class="empty-state"><h2>العربة فارغة</h2><p>أضف كتابًا من صفحة الكتب للبدء.</p><a class="button button-primary" href="books.php">تصفح الكتب</a></div><?php else: ?><?php foreach ($items as $item): ?><article class="cart-item"><img src="<?= e(bookImage($item['image'])) ?>" alt="<?= e($item['title']) ?>"><div class="cart-item-info"><h2><?= e($item['title']) ?></h2><p class="book-author"><?= e($item['author']) ?></p><p><?= e(number_format((float) $item['price'], 2)) ?> جنيه</p></div><form method="post" class="quantity-form"><input type="hidden" name="action" value="update"><input type="hidden" name="book_id" value="<?= e($item['id']) ?>"><label>الكمية<input type="number" name="quantity" min="1" max="<?= e($item['stock']) ?>" value="<?= e($item['quantity']) ?>"></label><button class="button button-quiet" type="submit">تحديث</button></form><form method="post"><input type="hidden" name="action" value="remove"><input type="hidden" name="book_id" value="<?= e($item['id']) ?>"><button class="remove-link" type="submit">حذف</button></form></article><?php endforeach; ?><?php endif; ?></div><?php if ($items): ?><aside class="cart-summary"><h2>ملخص الطلب</h2><p><span>عدد الكتب</span><strong><?= cartCount() ?></strong></p><p class="cart-total"><span>الإجمالي</span><strong><?= e(number_format($total, 2)) ?> جنيه</strong></p><a class="button button-primary" href="checkout.php">متابعة الدفع</a></aside><?php endif; ?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
