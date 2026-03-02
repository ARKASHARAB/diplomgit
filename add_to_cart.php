<?php
require_once 'config.php';

header('Content-Type: application/json');

// Разрешаем CORS для AJAX запросов
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : 'add';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID', 'product_id' => $product_id]);
    exit;
}

// Проверяем наличие товара
try {
    $stmt = $pdo->prepare("SELECT * FROM door_products1 WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Проверяем наличие на складе только при добавлении
    if ($action == 'add' && isset($product['stock']) && $product['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Product out of stock']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Обработка действий с корзиной в сессии
switch ($action) {
    case 'add':
        // Проверяем, есть ли уже товар в корзине
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product_id,
                'quantity' => $quantity,
                'name' => $product['name'],
                'price' => $product['price'],
                'picture' => $product['picture'] ?? '',
                'code' => $product['code'] ?? ''
            ];
        }
        break;
        
    case 'remove':
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
        break;
        
    case 'update':
        if ($quantity > 0) {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            }
        } else {
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        break;
        
    case 'clear':
        $_SESSION['cart'] = [];
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

// Если пользователь авторизован, синхронизируем с БД
if ($is_logged_in) {
    try {
        // Удаляем все товары из корзины пользователя в БД
        $stmt = $pdo->prepare("DELETE FROM door_cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Добавляем все товары из сессии в БД
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $pdo->prepare("INSERT INTO door_cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $item['id'], $item['quantity']]);
        }
        
    } catch (PDOException $e) {
        // Если ошибка БД, продолжаем с сессией
        error_log("Cart sync error: " . $e->getMessage());
    }
}

// Подсчитываем общее количество товаров в корзине
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
}

// Возвращаем подробную информацию о корзине
// Возвращаем подробную информацию о корзине
$cart_items = [];
$cart_total = 0;

foreach ($_SESSION['cart'] as $product_id => $item) {
    $cart_items[] = [
        'id' => $product_id,
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'total' => $item['price'] * $item['quantity']
    ];
    $cart_total += $item['price'] * $item['quantity'];
}

echo json_encode([
    'success' => true,
    'cart_count' => $cart_count,
    'cart_total' => $cart_total,
    'cart_items' => $cart_items,
    'message' => $action == 'add' ? 'Товар добавлен в корзину' : 'Корзина обновлена'
]);
?>