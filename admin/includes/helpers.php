<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function takeFlash(string $type): string
{
    $message = (string) ($_SESSION['flash'][$type] ?? '');
    unset($_SESSION['flash'][$type]);
    return $message;
}

function postString(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function postId(string $key = 'id'): int
{
    return filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT) ?: 0;
}

function queryId(): int
{
    return filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: 0;
}

function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrfToken(), $token)) {
        throw new RuntimeException('انتهت صلاحية النموذج. حدّث الصفحة وحاول مرة أخرى.');
    }
}

function uploadBookImage(string $field, string $uploadDirectory, string $publicDirectory): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('تعذر رفع الصورة أو أن حجمها أكبر من 5 ميجابايت.');
    }

    $imageInfo = getimagesize($_FILES[$field]['tmp_name']);
    $allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if ($imageInfo === false || !isset($allowedMimeTypes[$imageInfo['mime']])) {
        throw new RuntimeException('يرجى رفع صورة JPG أو PNG أو WEBP صالحة.');
    }

    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('تعذر إنشاء مجلد الصور.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$imageInfo['mime']];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
        throw new RuntimeException('تعذر حفظ الصورة.');
    }

    return $publicDirectory . '/' . $filename;
}

function deleteBookImage(?string $imagePath, string $uploadDirectory): void
{
    if (!$imagePath || !str_starts_with($imagePath, 'uploads/books/')) {
        return;
    }

    $filename = basename($imagePath);
    $filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

    if (is_file($filePath)) {
        unlink($filePath);
    }
}
