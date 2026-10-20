<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? 'user') === 'admin') {
    header('Location: dashboard.php');
    exit;
}

if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'user') {
    header('Location: user-dashboard.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    $_SESSION = [];
    session_destroy();
}

$error = ($_GET['error'] ?? '') === 'admin-only' ? 'هذا الحساب ليس لديه صلاحية دخول لوحة الإدارة.' : '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'يرجى إدخال بريد إلكتروني صحيح.';
    } else {
        $statement = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'dashboard.php' : 'user-dashboard.php'));
            exit;
        }

        $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل دخول الإدارة</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="auth-page">
    <main class="login-shell">
        <section class="login-intro" aria-label="معلومات الإدارة">
            <span class="brand-mark">ب</span>
            <p class="eyebrow">إدارة متجر الكتب</p>
            <h1>مرحبًا بك مجددًا</h1>
            <p>سجّل دخولك للوصول إلى لوحة التحكم ومتابعة بيانات المتجر.</p>
        </section>

        <section class="login-panel">
            <div class="panel-heading">
                <p class="eyebrow">منطقة آمنة</p>
                <h2>تسجيل الدخول</h2>
                <p>استخدم بيانات حسابك للمتابعة.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="login.php" class="login-form">
                <?= csrfField() ?>
                <label for="email">البريد الإلكتروني</label>
                <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>

                <label for="password">كلمة المرور</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>

                <button class="button button-primary" type="submit">دخول إلى لوحة التحكم <span aria-hidden="true">←</span></button>
            </form>
        </section>
    </main>
</body>
</html>
