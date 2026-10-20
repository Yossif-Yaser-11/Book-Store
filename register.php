<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

if (isLoggedIn()) { redirect('index.php'); }
$error = '';
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = postString('name'); $email = postString('email');
    $password = (string) ($_POST['password'] ?? ''); $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) { $error = 'يرجى إدخال الاسم وبريد صحيح وكلمة مرور من 6 أحرف على الأقل.'; }
    elseif ($password !== $confirmation) { $error = 'تأكيد كلمة المرور غير مطابق.'; }
    else {
        $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1'); $duplicate->execute(['email' => $email]);
        if ($duplicate->fetch()) { $error = 'هذا البريد الإلكتروني مسجل بالفعل.'; }
        else { $statement = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)'); $statement->execute(['name' => $name, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'role' => 'user']); awardPoints($pdo, (int) $pdo->lastInsertId(), 'registration', 100, 'إنشاء حساب جديد'); redirect('login.php?registered=1'); }
    }
}
$pageTitle = 'إنشاء حساب'; require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout"><aside class="auth-aside"><span class="brand-mark">ب</span><p class="eyebrow">انضم إلى مجتمع القراء</p><h1>اجعل القراءة عادة يومية.</h1><p>أنشئ حسابك وتابع مجموعة الكتب المتاحة في مكتبتنا الإلكترونية.</p></aside><section class="auth-form-wrap"><form class="form-card" method="post"><p class="eyebrow">حساب جديد</p><h1>إنشاء حساب</h1><p class="form-note">أدخل بياناتك للبدء في استكشاف المكتبة.</p><?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?><div class="form-field"><label for="name">الاسم الكامل</label><input id="name" name="name" value="<?= e($name) ?>" autocomplete="name" required></div><div class="form-field"><label for="email">البريد الإلكتروني</label><input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required></div><div class="form-field"><label for="password">كلمة المرور</label><input id="password" name="password" type="password" minlength="6" autocomplete="new-password" required></div><div class="form-field"><label for="password_confirmation">تأكيد كلمة المرور</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="6" autocomplete="new-password" required></div><button class="button button-primary" type="submit">إنشاء الحساب</button><p class="form-footer">لديك حساب؟ <a class="form-link" href="login.php">سجل الدخول</a></p></form></section></div>
<?php require __DIR__ . '/includes/footer.php'; ?>