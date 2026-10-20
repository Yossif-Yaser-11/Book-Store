<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$statement = $pdo->query('SELECT users.id, users.name, COALESCE(SUM(points_ledger.points), 0) AS points, COUNT(points_ledger.id) AS activities FROM users LEFT JOIN points_ledger ON points_ledger.user_id = users.id WHERE users.role = "user" GROUP BY users.id, users.name ORDER BY points DESC, users.id ASC LIMIT 50');
$leaders = $statement->fetchAll();
$currentUserId = isLoggedIn() ? (int) $_SESSION['user_id'] : 0;
$currentRank = null;
$currentPoints = 0;

foreach ($leaders as $index => $leader) {
    if ((int) $leader['id'] === $currentUserId) {
        $currentRank = $index + 1;
        $currentPoints = (int) $leader['points'];
        break;
    }
}

$pageTitle = 'لوحة المتصدرين';
$activePage = 'leaderboard';
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro leaderboard-intro"><div class="container"><p class="eyebrow">مجتمع القراء</p><h1>لوحة المتصدرين</h1><p>اجمع النقاط من خلال القراءة والتفاعل، وتابع تقدمك بين القراء.</p></div></section>
<section class="section leaderboard-section"><div class="container leaderboard-layout">
    <div class="leaderboard-main">
        <div class="section-heading"><div><p class="eyebrow">أفضل القراء</p><h2>الترتيب الحالي</h2></div><span class="leaderboard-rule">النقاط الأعلى أولًا</span></div>
        <?php if (!$leaders): ?>
            <div class="empty-state"><h2>ابدأ المنافسة</h2><p>لا توجد نقاط بعد. أنشئ حسابًا وابدأ التفاعل مع الكتب.</p></div>
        <?php else: ?>
            <div class="leaderboard-list">
                <?php foreach ($leaders as $index => $leader): ?>
                    <?php $rank = $index + 1; ?>
                    <article class="leaderboard-row <?= $currentUserId === (int) $leader['id'] ? 'is-current-user' : '' ?>">
                        <span class="leaderboard-rank rank-<?= $rank <= 3 ? $rank : 'default' ?>"><?= $rank ?></span>
                        <span class="leaderboard-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $leader['name'], 0, 1))) ?></span>
                        <span class="leaderboard-person"><strong><?= e($leader['name']) ?></strong><small><?= e($leader['activities']) ?> نشاطًا</small></span>
                        <strong class="leaderboard-points"><?= e(number_format((int) $leader['points'])) ?> <small>نقطة</small></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <aside class="points-card">
        <span class="points-card-icon" aria-hidden="true">✦</span>
        <p class="eyebrow">كيف تجمع النقاط؟</p>
        <h2>كل تفاعل يقرّبك من القمة.</h2>
        <ul class="points-rules"><li><span>إنشاء حساب</span><b>+100</b></li><li><span>إنشاء طلب</span><b>+50</b></li><li><span>تقييم كتاب</span><b>+15</b></li><li><span>نشر تعليق</span><b>+10</b></li></ul>
        <?php if (!isLoggedIn()): ?><a class="button button-primary" href="register.php">ابدأ الآن</a><?php elseif ($currentRank !== null): ?><div class="current-score"><span>ترتيبك الحالي</span><strong>#<?= e($currentRank) ?></strong><small><?= e(number_format($currentPoints)) ?> نقطة</small></div><?php else: ?><p class="points-note">لم تسجل نقاطًا بعد. قيّم كتابًا أو شارك رأيك لتظهر هنا.</p><?php endif; ?>
    </aside>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
