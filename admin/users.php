<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$error = '';
$editingUser = null;
$search = trim((string) ($_GET['search'] ?? ''));

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $action = postString('action');
        $userId = postId();

        if ($action === 'delete') {
            $targetStatement = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
            $targetStatement->execute(['id' => $userId]);
            $targetUser = $targetStatement->fetch();
            if (!$targetUser || $targetUser['role'] === 'admin') {
                throw new RuntimeException('لا يمكن حذف حساب مدير.');
            }
            $statement = $pdo->prepare('DELETE FROM users WHERE id = :id AND role = "user"');
            $statement->execute(['id' => $userId]);
            redirect('users.php');
        }

        $name = postString('name');
        $email = postString('email');
        $password = (string) ($_POST['password'] ?? '');
        $role = postString('role');

        if (!in_array($role, ['admin', 'user'], true)) {
            throw new RuntimeException('الدور المحدد غير صحيح.');
        }

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('يرجى إدخال الاسم وبريد إلكتروني صحيح.');
        }

        $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $duplicate->execute(['email' => $email, 'id' => $userId]);
        if ($duplicate->fetch()) {
            throw new RuntimeException('هذا البريد الإلكتروني مسجل بالفعل.');
        }

        if ($action === 'create') {
            if ($password === '') {
                throw new RuntimeException('كلمة المرور مطلوبة عند إضافة مستخدم.');
            }
            $statement = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
            $statement->execute(['name' => $name, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
            redirect('users.php');
        }

        if ($action === 'update' && $userId > 0) {
            if ($password !== '') {
                $statement = $pdo->prepare('UPDATE users SET name = :name, email = :email, password = :password, role = :role WHERE id = :id');
                $statement->execute(['name' => $name, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role, 'id' => $userId]);
            } else {
                $statement = $pdo->prepare('UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id');
                $statement->execute(['name' => $name, 'email' => $email, 'role' => $role, 'id' => $userId]);
            }
            if ($userId === (int) $_SESSION['user_id']) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
            }
            redirect('users.php');
        }
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

if (queryId() > 0) {
    $statement = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = :id');
    $statement->execute(['id' => queryId()]);
    $editingUser = $statement->fetch() ?: null;
}

if ($search !== '') {
    $statement = $pdo->prepare('SELECT id, name, email, role FROM users WHERE name LIKE :search_name OR email LIKE :search_email ORDER BY id DESC');
    $statement->execute(['search_name' => '%' . $search . '%', 'search_email' => '%' . $search . '%']);
} else {
    $statement = $pdo->query('SELECT id, name, email, role FROM users ORDER BY id DESC');
}
$users = $statement->fetchAll();
$formAction = $editingUser ? 'update' : 'create';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>المستخدمون</title><link rel="stylesheet" href="css/admin.css"></head>
<body class="dashboard-page"><div class="dashboard-shell">
<header class="topbar"><a class="brand" href="dashboard.php"><span class="brand-mark">ب</span><span>متجر الكتب</span></a><nav class="admin-nav"><a href="dashboard.php">لوحة التحكم</a><a class="active" href="users.php">المستخدمون</a><a href="books.php">الكتب</a><a href="categories.php">التصنيفات</a><a href="publishers.php">دور النشر</a><a href="orders.php">المشتريات</a><a href="access.php">فتح الكتب</a></nav><a class="button button-quiet" href="logout.php">تسجيل الخروج</a></header>
<main class="dashboard-content"><div class="page-heading"><div><p class="eyebrow">إدارة الحسابات</p><h1>المستخدمون</h1></div><a class="button button-primary" href="users.php#user-form">+ إضافة مستخدم</a></div>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
<section class="content-panel" id="user-form"><div class="panel-title"><h2><?= $editingUser ? 'تعديل مستخدم' : 'إضافة مستخدم' ?></h2><?php if ($editingUser): ?><a class="button button-quiet" href="users.php">إلغاء</a><?php endif; ?></div>
<form class="entity-form" method="post"><input type="hidden" name="action" value="<?= $formAction ?>"><input type="hidden" name="id" value="<?= e($editingUser['id'] ?? '') ?>"><div><label for="name">الاسم</label><input id="name" name="name" required value="<?= e($editingUser['name'] ?? '') ?>"></div><div><label for="email">البريد الإلكتروني</label><input id="email" name="email" type="email" required value="<?= e($editingUser['email'] ?? '') ?>"></div><div><label for="role">نوع الحساب</label><select id="role" name="role" required><option value="user" <?= ($editingUser['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>مستخدم</option><option value="admin" <?= ($editingUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>مدير</option></select></div><div><label for="password">كلمة المرور <?= $editingUser ? '(اختياري)' : '' ?></label><input id="password" name="password" type="password" <?= $editingUser ? '' : 'required' ?> autocomplete="new-password"></div><button class="button button-primary" type="submit">حفظ</button></form></section>
<section class="content-panel"><form class="search-form" method="get"><input name="search" placeholder="ابحث بالاسم أو البريد الإلكتروني" value="<?= e($search) ?>"><button class="button button-quiet" type="submit">بحث</button></form><div class="table-wrap"><table><thead><tr><th>ID</th><th>الاسم</th><th>البريد الإلكتروني</th><th>نوع الحساب</th><th>الإجراءات</th></tr></thead><tbody><?php if (!$users): ?><tr><td class="empty-state" colspan="5">لا توجد نتائج.</td></tr><?php endif; ?><?php foreach ($users as $user): ?><tr><td><?= e($user['id']) ?></td><td><?= e($user['name']) ?></td><td><?= e($user['email']) ?></td><td><span class="role-badge <?= e($user['role']) ?>"><?= $user['role'] === 'admin' ? 'مدير' : 'مستخدم' ?></span></td><td class="actions"><a class="button button-small button-quiet" href="users.php?edit=<?= e($user['id']) ?>#user-form">تعديل</a><form method="post" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($user['id']) ?>"><button class="button button-small button-danger" type="submit">حذف</button></form></td></tr><?php endforeach; ?></tbody></table></div></section></main></div></body></html>
