<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'books';
const DB_USER = 'root';
const DB_PASSWORD = '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit('تعذر الاتصال بقاعدة البيانات. راجع إعدادات الاتصال في admin/includes/db.php.');
}
