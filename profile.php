<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=profile.php');
}

$error = '';
$success = '';
$userStatement = $pdo->prepare('SELECT name, email FROM users WHERE id = :id LIMIT 1');
$userStatement->execute(['id' => $_SESSION['user_id']]);
$user = $userStatement->fetch();

if (!$user) {
    redirect('logout.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $name = postString('name');
        $email = postString('email');
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('يرجى إدخال الاسم والبريد الإلكتروني بشكل صحيح.');
        }
        if ($password !== '' && (strlen($password) < 6 || $password !== $confirmation)) {
            throw new RuntimeException('كلمة المرور يجب أن تكون 6 أحرف على الأقل ومتطابقة مع التأكيد.');
        }

        $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $duplicate->execute(['email' => $email, 'id' => $_SESSION['user_id']]);
        if ($duplicate->fetch()) {
            throw new RuntimeException('هذا البريد الإلكتروني مستخدم بالفعل.');
        }

        if ($password !== '') {
            $update = $pdo->prepare('UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id');
            $update->execute(['name' => $name, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $_SESSION['user_id']]);
        } else {
            $update = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
            $update->execute(['name' => $name, 'email' => $email, 'id' => $_SESSION['user_id']]);
        }

        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $user['name'] = $name;
        $user['email'] = $email;
        $success = 'تم تحديث بيانات حسابك بنجاح.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$pageTitle = 'الملف الشخصي';
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><div class="container"><p class="eyebrow">حسابك</p><h1>الملف الشخصي</h1><p>حدّث بياناتك الشخصية أو غيّر كلمة المرور.</p></div></section>
<section class="section"><div class="container"><form class="form-card profile-form" method="post"><h2>بيانات الحساب</h2><?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?><?php if ($success): ?><div class="success-alert" role="status"><?= e($success) ?></div><?php endif; ?><div class="form-field"><label for="name">الاسم الكامل</label><input id="name" name="name" value="<?= e($user['name']) ?>" autocomplete="name" required></div><div class="form-field"><label for="email">البريد الإلكتروني</label><input id="email" name="email" type="email" value="<?= e($user['email']) ?>" autocomplete="email" required></div><div class="form-field"><label for="password">كلمة مرور جديدة <small>(اختياري)</small></label><input id="password" name="password" type="password" minlength="6" autocomplete="new-password"></div><div class="form-field"><label for="password_confirmation">تأكيد كلمة المرور</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="6" autocomplete="new-password"></div><button class="button button-primary" type="submit">حفظ التغييرات</button></form></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>