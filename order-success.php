<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
if (!isLoggedIn()) { redirect('login.php'); }
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$statement = $pdo->prepare('SELECT id, total, whatsapp, payment_method, payment_reference, status, created_at FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1');
$statement->execute(['id' => $orderId, 'user_id' => $_SESSION['user_id']]);
$order = $statement->fetch();
if (!$order) { http_response_code(404); }
$whatsappMessage = $order ? 'فاتورة طلب رقم ' . $order['id'] . '%0Aالإجمالي: ' . number_format((float) $order['total'], 2) . ' جنيه%0Aطريقة الدفع: InstaPay%0Aرقم العملية: ' . ($order['payment_reference'] ?? 'سيتم إرساله لاحقًا') : '';
$pageTitle = 'تم استلام الطلب'; require __DIR__ . '/includes/header.php';
?>
<section class="section"><div class="container"><div class="success-card"><?php if ($order): ?><p class="eyebrow">تم استلام طلبك</p><h1>شكرًا لك، طلبك رقم #<?= e($order['id']) ?></h1><p>تم حفظ الطلب بحالة قيد المراجعة. أرسل تفاصيل الفاتورة على واتساب لتأكيد الدفع.</p><div class="payment-note"><strong>InstaPay</strong><span>01121771193</span><small>بعد التحويل، اضغط الزر لإرسال الفاتورة إلى واتساب.</small></div><a class="button whatsapp-button" href="https://wa.me/201121771193?text=<?= $whatsappMessage ?>" target="_blank" rel="noopener">إرسال الفاتورة على واتساب</a><a class="button button-outline" href="books.php">العودة إلى الكتب</a><?php else: ?><h1>الطلب غير موجود</h1><a class="button button-primary" href="index.php">العودة للرئيسية</a><?php endif; ?></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
