<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'مكتبة بُعد';
$activePage = $activePage ?? '';
require_once __DIR__ . '/app.php';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="مكتبة بُعد الإلكترونية لتصفح الكتب والتصنيفات بسهولة.">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e($pageTitle) ?> | مكتبة بُعد</title>
    <link rel="stylesheet" href="assets/site.css?v=<?= (int) filemtime(__DIR__ . '/../assets/site.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="site-brand" href="index.php"><span class="brand-mark">ب</span><span>مكتبة بُعد</span></a>
        <button class="menu-toggle" type="button" aria-label="فتح القائمة" aria-expanded="false">☰</button>
        <nav class="site-nav" aria-label="التنقل الرئيسي">
            <div class="nav-primary">
                <a class="<?= $activePage === 'home' ? 'active' : '' ?>" href="index.php">الرئيسية</a>
                <a class="<?= $activePage === 'books' ? 'active' : '' ?>" href="books.php">الكتب</a>
                <a class="<?= $activePage === 'publishers' ? 'active' : '' ?>" href="publishers.php">دور النشر</a>
                <a class="<?= $activePage === 'stats' ? 'active' : '' ?>" href="statistics.php">الإحصائيات</a>
                <a class="<?= $activePage === 'leaderboard' ? 'active' : '' ?>" href="leaderboard.php">المتصدرون</a>
                <a class="cart-link" href="cart.php"><span>العربة</span><b class="cart-count"><?= cartCount() ?></b></a>
            </div>
            <div class="nav-account">
            <?php if (isLoggedIn()): ?>
                <a href="my-books.php">كتبي المفتوحة</a>
                <a class="<?= $activePage === 'orders' ? 'active' : '' ?>" href="my-orders.php">مشترياتي</a>
                <a href="profile.php">الملف الشخصي</a>
                <a class="logout-link" href="logout.php">تسجيل الخروج</a>
            <?php else: ?>
                <a href="login.php">تسجيل الدخول</a>
                <a class="nav-cta" href="register.php">إنشاء حساب</a>
            <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
<main>