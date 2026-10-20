<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../admin/includes/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function ensureStoreTables(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        customer_name VARCHAR(160) NOT NULL,
        customer_email VARCHAR(190) NOT NULL,
        whatsapp VARCHAR(30) NOT NULL,
        address TEXT NOT NULL,
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        payment_method VARCHAR(40) NOT NULL DEFAULT "instapay",
        payment_reference VARCHAR(120) NULL,
        status VARCHAR(30) NOT NULL DEFAULT "pending",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    try { $pdo->exec('ALTER TABLE orders ADD COLUMN address TEXT NOT NULL AFTER whatsapp'); } catch (PDOException) { }
    $pdo->exec('CREATE TABLE IF NOT EXISTS order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        book_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT UNSIGNED NOT NULL,
        INDEX (order_id),
        INDEX (book_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS book_access (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        book_id INT UNSIGNED NOT NULL,
        granted_by INT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_book (user_id, book_id),
        INDEX (user_id),
        INDEX (book_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS book_comments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        book_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (book_id),
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS publishers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(190) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS book_ratings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        book_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        rating TINYINT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_book_rating (book_id, user_id),
        INDEX (book_id),
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS points_ledger (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        event_key VARCHAR(120) NOT NULL,
        points INT NOT NULL,
        label VARCHAR(160) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_event (user_id, event_key),
        INDEX (user_id),
        INDEX (points)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    foreach ([
        'rating' => 'ALTER TABLE books ADD COLUMN rating DECIMAL(2,1) NOT NULL DEFAULT 0 AFTER price',
        'purchase_location' => 'ALTER TABLE books ADD COLUMN purchase_location VARCHAR(255) NULL AFTER rating',
        'publisher_id' => 'ALTER TABLE books ADD COLUMN publisher_id INT UNSIGNED NULL AFTER category_id',
    ] as $column => $statement) {
        try {
            $pdo->query('SELECT ' . $column . ' FROM books LIMIT 1');
        } catch (PDOException) {
            $pdo->exec($statement);
        }
    }
}

ensureStoreTables($pdo);

function bookImage(?string $image): string
{
    return $image ? 'admin/' . ltrim($image, '/') : 'assets/book-placeholder.svg';
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function siteUserName(): string
{
    return (string) ($_SESSION['user_name'] ?? '');
}

function fetchCategories(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
}

function cartCount(): int
{
    return array_sum(array_map('intval', $_SESSION['cart'] ?? []));
}

function fetchPublishers(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM publishers ORDER BY name')->fetchAll();
}

function awardPoints(PDO $pdo, int $userId, string $eventKey, int $points, string $label): void
{
    if ($userId < 1 || $points < 1) {
        return;
    }

    $statement = $pdo->prepare('INSERT IGNORE INTO points_ledger (user_id, event_key, points, label) VALUES (:user_id, :event_key, :points, :label)');
    $statement->execute([
        'user_id' => $userId,
        'event_key' => $eventKey,
        'points' => $points,
        'label' => $label,
    ]);
}

function userPoints(PDO $pdo, int $userId): int
{
    $statement = $pdo->prepare('SELECT COALESCE(SUM(points), 0) FROM points_ledger WHERE user_id = :user_id');
    $statement->execute(['user_id' => $userId]);
    return (int) $statement->fetchColumn();
}