<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=my-orders.php');
}

$statement = $pdo->prepare('SELECT orders.id, orders.total, orders.status, orders.created_at, GROUP_CONCAT(CONCAT(order_items.title, " × ", order_items.quantity) SEPARATOR "، ") AS items FROM orders LEFT JOIN order_items ON order_items.order_id = orders.id WHERE orders.user_id = :user_id GROUP BY orders.id, orders.total, orders.status, orders.created_at ORDER BY orders.id DESC');
$statement->execute(['user_id' => $_SESSION['user_id']]);
$orders = $statement->fetchAll();
$statusLabels = ['pending' => 'قيد المراجعة', 'paid' => 'تم الدفع', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
$statusClasses = ['pending' => 'status-pending', 'paid' => 'status-paid', 'completed' => 'status-completed', 'cancelled' => 'status-cancelled'];
$pageTitle = 'مشترياتي';
$activePage = 'orders';
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><div class="container"><p class="eyebrow">سجل الطلبات</p><h1>مشترياتي</h1><p>تابع طلباتك وحالة كل عملية شراء.</p></div></section>
<section class="section"><div class="container"><div class="orders-list">
    <?php if (!$orders): ?><div class="empty-state"><h2>لا توجد مشتريات بعد</h2><p>تصفح الكتب وأضف ما يعجبك إلى العربة.</p><a class="button button-primary" href="books.php">تصفح الكتب</a></div><?php endif; ?>
    <?php foreach ($orders as $order): ?><article class="order-card"><div class="order-card-header"><div><p class="eyebrow">طلب رقم #<?= e($order['id']) ?></p><h2><?= e(date('Y/m/d', strtotime($order['created_at']))) ?></h2></div><span class="status-pill <?= e($statusClasses[$order['status']] ?? '') ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></div><p class="order-items"><?= e($order['items'] ?? 'لا توجد تفاصيل') ?></p><strong class="order-total"><?= e(number_format((float) $order['total'], 2)) ?> جنيه</strong></article><?php endforeach; ?>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
