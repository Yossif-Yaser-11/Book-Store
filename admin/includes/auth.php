<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php?error=admin-only');
    exit;
}

require_once __DIR__ . '/helpers.php';

ob_start(static function (string $buffer): string {
    $csrfToken = e(csrfToken());
    $script = <<<'HTML'
<script>
(function () {
    document.querySelectorAll('form[method="post"], form:not([method])').forEach(function (form) {
        if (!form.querySelector('input[name="csrf_token"]')) {
            var token = document.createElement('input');
            token.type = 'hidden';
            token.name = 'csrf_token';
            token.value = '__CSRF_TOKEN__';
            form.appendChild(token);
        }
    });

    var bookForm = document.querySelector('.book-form');
    if (bookForm && Array.isArray(window.bookPublishers)) {
        if (!bookForm.querySelector('[name="publisher_id"]')) {
            var wrapper = document.createElement('div');
            var label = document.createElement('label');
            var select = document.createElement('select');
            label.htmlFor = 'publisher_id';
            label.textContent = 'دار النشر';
            select.id = 'publisher_id';
            select.name = 'publisher_id';
            select.required = false;
            select.innerHTML = '<option value="">اختر دار النشر</option>' + window.bookPublishers.map(function (publisher) { return '<option value="' + publisher.id + '">' + publisher.name.replace(/[&<>"']/g, function (char) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]; }) + '</option>'; }).join('');
            if (window.selectedPublisherId) select.value = String(window.selectedPublisherId);
            wrapper.append(label, select);
            bookForm.insertBefore(wrapper, bookForm.querySelector('button[type="submit"]'));
        }
    }

}());
</script>
HTML;
    $script = str_replace('__CSRF_TOKEN__', $csrfToken, $script);
    return $buffer . $script;
});
