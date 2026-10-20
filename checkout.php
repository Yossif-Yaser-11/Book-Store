<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

if (!isLoggedIn()) { redirect('login.php?redirect=checkout.php'); }
$cart = array_filter(array_map('intval', $_SESSION['cart'] ?? []));
if (!$cart) { redirect('cart.php'); }
$error = '';
$items = [];
$ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$statement = $pdo->prepare("SELECT id, title, price, stock FROM books WHERE id IN ($placeholders)");
$statement->execute($ids);
foreach ($statement->fetchAll() as $book) { $book['quantity'] = $cart[(int) $book['id']]; $items[] = $book; }
$total = array_sum(array_map(static fn (array $item): float => (float) $item['price'] * $item['quantity'], $items));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $customerName = postString('customer_name');
    $address = postString('address');
    $whatsapp = '01121771193';
    if ($customerName === '' || $address === '') { $error = 'يرجى إدخال الاسم والعنوان.'; }
    elseif (!$items || count($items) !== count($cart)) { $error = 'بعض الكتب في العربة لم تعد متاحة.'; }
    else {
        try {
            $pdo->beginTransaction();
            foreach ($items as $item) {
                if ((int) $item['quantity'] > (int) $item['stock']) { throw new RuntimeException('الكمية المطلوبة من كتاب «' . $item['title'] . '» غير متاحة.'); }
            }
            $userStatement = $pdo->prepare('SELECT email FROM users WHERE id = :id LIMIT 1'); $userStatement->execute(['id' => $_SESSION['user_id']]); $user = $userStatement->fetch();
            if (!$user) { throw new RuntimeException('تعذر العثور على حساب المستخدم.'); }
            $orderStatement = $pdo->prepare('INSERT INTO orders (user_id, customer_name, customer_email, whatsapp, address, total, payment_method) VALUES (:user_id, :name, :email, :whatsapp, :address, :total, "instapay")');
            $orderStatement->execute(['user_id' => $_SESSION['user_id'], 'name' => $customerName, 'email' => $user['email'], 'whatsapp' => $whatsapp, 'address' => $address, 'total' => $total]);
            $orderId = (int) $pdo->lastInsertId();
            awardPoints($pdo, (int) $_SESSION['user_id'], 'order:' . $orderId, 50, 'إنشاء طلب شراء');
            $itemStatement = $pdo->prepare('INSERT INTO order_items (order_id, book_id, title, price, quantity) VALUES (:order_id, :book_id, :title, :price, :quantity)');
            $stockStatement = $pdo->prepare('UPDATE books SET stock = stock - :deduct_quantity WHERE id = :id AND stock >= :minimum_quantity');
            foreach ($items as $item) {
                $itemStatement->execute(['order_id' => $orderId, 'book_id' => $item['id'], 'title' => $item['title'], 'price' => $item['price'], 'quantity' => $item['quantity']]);
                $stockStatement->execute(['deduct_quantity' => $item['quantity'], 'minimum_quantity' => $item['quantity'], 'id' => $item['id']]);
                if ($stockStatement->rowCount() !== 1) {
                    throw new RuntimeException('تغير مخزون كتاب «' . $item['title'] . '». راجع العربة وحاول مرة أخرى.');
                }
            }
            $pdo->commit(); unset($_SESSION['cart']);
            $messageLines = ['طلب جديد رقم #' . $orderId, 'الاسم: ' . $customerName, 'العنوان: ' . $address, 'واتساب: ' . $whatsapp, '', 'تفاصيل الطلب:'];
            foreach ($items as $item) { $messageLines[] = $item['title'] . ' × ' . $item['quantity'] . ' = ' . number_format((float) $item['price'] * $item['quantity'], 2) . ' جنيه'; }
            $messageLines[] = ''; $messageLines[] = 'الإجمالي: ' . number_format($total, 2) . ' جنيه'; $messageLines[] = 'يرجى تحويل المبلغ عبر InstaPay إلى 01121771193 وإرسال Screenshot التحويل في هذه المحادثة.';
            header('Location: https://web.whatsapp.com/send?phone=201121771193&text=' . rawurlencode(implode("\n", $messageLines)));
            exit;
        } catch (Throwable $exception) { if ($pdo->inTransaction()) { $pdo->rollBack(); } $error = $exception->getMessage(); }
    }
}
$pageTitle = 'إتمام الطلب'; require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><div class="container"><p class="eyebrow">إتمام الطلب</p><h1>بيانات التوصيل والتواصل</h1><p>بعد التأكيد ستنتقل إلى WhatsApp Web لإرسال تفاصيل طلبك.</p></div></section>
<section class="section"><div class="container checkout-layout"><form class="form-card" method="post"><h2>بياناتك الأساسية</h2><p class="form-note">يرجى تحويل المبلغ إلى InstaPay على الرقم <strong>01121771193</strong> ثم إرسال Screenshot التحويل على واتساب.</p><?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?><div class="form-field"><label for="customer_name">الاسم الكامل</label><input id="customer_name" name="customer_name" autocomplete="name" required></div><div class="form-field"><label for="address">العنوان</label><textarea id="address" name="address" rows="3" autocomplete="street-address" required></textarea></div><button class="button whatsapp-button" type="submit">تأكيد الطلب والمتابعة إلى WhatsApp</button></form><aside class="cart-summary"><h2>تفاصيل الطلب</h2><?php foreach ($items as $item): ?><p><span><?= e($item['title']) ?> × <?= e($item['quantity']) ?></span><strong><?= e(number_format((float) $item['price'] * $item['quantity'], 2)) ?></strong></p><?php endforeach; ?><p class="cart-total"><span>الإجمالي</span><strong><?= e(number_format($total, 2)) ?> جنيه</strong></p></aside></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
