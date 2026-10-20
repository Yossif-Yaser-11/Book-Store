<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
if (isLoggedIn()) { redirect('index.php'); }
$error = ''; $email = '';
if (($_GET['registered'] ?? '') === '1') { $message = 'تم إنشاء الحساب. يمكنك تسجيل الدخول الآن.'; } else { $message = ''; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = postString('email'); $password = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') { $error = 'يرجى إدخال بريد إلكتروني صحيح وكلمة المرور.'; }
    else { $statement = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1'); $statement->execute(['email' => $email]); $user = $statement->fetch(); if (!$user || !password_verify($password, $user['password'])) { $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'; } else { session_regenerate_id(true); $_SESSION['user_id'] = (int) $user['id']; $_SESSION['user_name'] = $user['name']; $_SESSION['user_email'] = $user['email']; $_SESSION['user_role'] = $user['role']; redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'); } }
}
$pageTitle = 'تسجيل الدخول'; require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout"><aside class="auth-aside"><span class="brand-mark">ب</span><p class="eyebrow">مرحبًا بعودتك</p><h1>كتابك القادم أقرب مما تظن.</h1><p>سجل الدخول للوصول إلى تجربة مكتبة بُعد ومتابعة أحدث الإضافات.</p></aside><section class="auth-form-wrap"><form class="form-card" method="post"><?= csrfField() ?><p class="eyebrow">العودة إلى القراءة</p><h1>تسجيل الدخول</h1><p class="form-note">استخدم بيانات حسابك للمتابعة.</p><?php if ($message): ?><div class="alert" style="color:#245b4d;background:#e4f1eb;border-color:#bddbce" role="status"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?><div class="form-field"><label for="email">البريد الإلكتروني</label><input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required></div><div class="form-field"><label for="password">كلمة المرور</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button class="button button-primary" type="submit">تسجيل الدخول</button><p class="form-footer">ليس لديك حساب؟ <a class="form-link" href="register.php">أنشئ حسابًا</a></p></form></section></div>
<?php require __DIR__ . '/includes/footer.php'; ?>