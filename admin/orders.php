<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/app.php';

$allowedStatuses = ['pending' => 'قيد المراجعة', 'paid' => 'تم الدفع', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $orderId = postId();
        $status = postString('status');
        if ($orderId < 1 || !isset($allowedStatuses[$status])) {
            throw new RuntimeException('بيانات الطلب غير صحيحة.');
        }
        $statement = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => $orderId]);
        redirect('orders.php');
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

$orders = $pdo->query('SELECT orders.id, orders.customer_name, orders.customer_email, orders.whatsapp, orders.address, orders.total, orders.payment_reference, orders.status, orders.created_at, GROUP_CONCAT(CONCAT(order_items.title, " × ", order_items.quantity) SEPARATOR ", ") AS item_summary FROM orders LEFT JOIN order_items ON order_items.order_id = orders.id GROUP BY orders.id ORDER BY orders.id DESC')->fetchAll();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>المشتريات</title><link rel="stylesheet" href="css/admin.css"></head>
<body class="dashboard-page"><div class="dashboard-shell">
<header class="topbar"><a class="brand" href="dashboard.php"><span class="brand-mark">ب</span><span>متجر الكتب</span></a><nav class="admin-nav"><a href="dashboard.php">لوحة التحكم</a><a href="users.php">المستخدمون</a><a href="books.php">الكتب</a><a href="categories.php">التصنيفات</a><a class="active" href="orders.php">المشتريات</a><a href="access.php">فتح الكتب</a></nav><div class="user-header"><a class="button button-quiet" href="logout.php">تسجيل الخروج</a></div></header>
<main class="dashboard-content"><div class="page-heading"><div><p class="eyebrow">الطلبات والعملاء</p><h1>المشتريات</h1></div><a class="button button-primary" href="access.php">فتح كتاب لمستخدم</a></div>
<?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
<section class="content-panel orders-panel"><div class="panel-title"><div><h2>كل الطلبات</h2><small><?= number_format(count($orders)) ?> طلب</small></div><a class="button button-quiet" href="dashboard.php">العودة للوحة التحكم</a></div><div class="table-wrap"><table><thead><tr><th>رقم</th><th>العميل</th><th>واتساب</th><th>الكتب</th><th>الإجمالي</th><th>InstaPay</th><th>التاريخ</th><th>الحالة</th></tr></thead><tbody><?php if (!$orders): ?><tr><td class="empty-state" colspan="8">لا توجد مشتريات حتى الآن.</td></tr><?php endif; ?><?php foreach ($orders as $order): ?><tr><td>#<?= e($order['id']) ?></td><td><strong><?= e($order['customer_name']) ?></strong><br><small><?= e($order['customer_email']) ?></small></td><td><?= e($order['whatsapp']) ?></td><td class="order-items-cell"><?= e($order['item_summary'] ?? '') ?></td><td><strong><?= e(number_format((float) $order['total'], 2)) ?> ج</strong></td><td><?= e($order['payment_reference'] ?: 'لم يرسل') ?></td><td><?= e($order['created_at']) ?></td><td><form method="post"><input type="hidden" name="id" value="<?= e($order['id']) ?>"><select name="status" onchange="this.form.submit()" aria-label="حالة الطلب"><option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>قيد المراجعة</option><option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>تم الدفع</option><option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>مكتمل</option><option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>ملغي</option></select></form></td></tr><?php endforeach; ?></tbody></table></div></section></main></div></body></html>