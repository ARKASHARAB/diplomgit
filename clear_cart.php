<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Очищаем корзину в сессии
$_SESSION['cart'] = [];

// Если пользователь авторизован, очищаем корзину в БД
if ($is_logged_in) {
    try {
        $stmt = $pdo->prepare("DELETE FROM door_cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Логируем ошибку, но продолжаем
        error_log("Clear cart DB error: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'cart_count' => 0,
    'message' => 'Корзина очищена'
]);
?>