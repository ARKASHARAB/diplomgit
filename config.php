<?php


// Настройки подключения к базе данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'n9998335_magaz');
define('DB_USER', 'n9998335_magaz');
define('DB_PASS', 'Qwerty123');

// Настройки пагинации
define('ITEMS_PER_PAGE', 12);

// Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключение к базе данных
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Инициализация корзины
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Проверка авторизации
$is_logged_in = isset($_SESSION['user_id']);
$current_user = $is_logged_in ? $_SESSION['user'] : null;

// Подсчитываем товары в корзине
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key_dachnyinventary, setting_value_dachnyinventary FROM site_settings_dachnyinventary");
    $settings_data = $stmt->fetchAll();
    
    // Преобразуем в ассоциативный массив
    foreach ($settings_data as $setting) {
        $settings[$setting['setting_key_dachnyinventary']] = $setting['setting_value_dachnyinventary'];
    }
} catch (PDOException $e) {
    // Если таблица не существует, используем цвета по умолчанию
    $settings = [
        'primary_color' => '#3d3d3d',
        'secondary_color' => '#707070',
        'accent_color' => '#ff9800',
        'text_color' => '#333333',
        'background_color' => '#f5f5f5',
        'card_color' => '#ffffff',
        'button_color' => '#4caf50',
        'error_color' => '#f44336',
        'success_color' => '#4caf50'
    ];
}

// Функция для получения значения настройки
function getSetting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? $settings[$key] : $default;
}
?>