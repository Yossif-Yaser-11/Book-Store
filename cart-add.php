<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('books.php'); }
verifyCsrf();
$bookId = postId('book_id');
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));
$statement = $pdo->prepare('SELECT stock FROM books WHERE id = :id LIMIT 1');
$statement->execute(['id' => $bookId]);
$book = $statement->fetch();
if (!$book || (int) $book['stock'] < 1) { redirect('books.php'); }
$current = (int) ($_SESSION['cart'][$bookId] ?? 0);
$_SESSION['cart'][$bookId] = min((int) $book['stock'], $current + $quantity);
redirect('cart.php');