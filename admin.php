<?php
require_once 'config.php';

// Проверка администратора
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

// Проверяем права на редактирование цветов
$can_edit_colors = false;
try {
    $stmt = $pdo->prepare("SELECT can_edit_colors FROM door_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $can_edit_colors = $user['can_edit_colors'] ?? false;
} catch (PDOException $e) {
    // Ошибка при проверке прав
}

// Обработка редактирования товара
$editing_product = null;
if (isset($_GET['edit_product'])) {
    $product_id = intval($_GET['edit_product']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM door_products1 WHERE id = ?");
        $stmt->execute([$product_id]);
        $editing_product = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = "Ошибка при загрузке товара: " . $e->getMessage();
    }
}

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Добавление товара
    if (isset($_POST['add_product'])) {
        $code = trim($_POST['code']);
        $url = trim($_POST['url']);
        $categoryId = trim($_POST['categoryId']);
        $picture = trim($_POST['picture']);
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $description = trim($_POST['description']);
        $class1 = trim($_POST['class1']);
        $class2 = trim($_POST['class2']);
        $class3 = trim($_POST['class3']);
        $class4 = trim($_POST['class4']);
        $class5 = trim($_POST['class5']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO door_products1 (code, url, categoryId, picture, name, price, description, class1, class2, class3, class4, class5) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $url, $categoryId, $picture, $name, $price, $description, $class1, $class2, $class3, $class4, $class5]);
            $success_message = "Товар успешно добавлен!";
        } catch (PDOException $e) {
            $error_message = "Ошибка при добавлении товара: " . $e->getMessage();
        }
    }
    
    // 2. Обновление товара
    if (isset($_POST['update_product'])) {
        $product_id = intval($_POST['product_id']);
        $code = trim($_POST['code']);
        $url = trim($_POST['url']);
        $categoryId = trim($_POST['categoryId']);
        $picture = trim($_POST['picture']);
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $description = trim($_POST['description']);
        $class1 = trim($_POST['class1']);
        $class2 = trim($_POST['class2']);
        $class3 = trim($_POST['class3']);
        $class4 = trim($_POST['class4']);
        $class5 = trim($_POST['class5']);
        
        try {
            $stmt = $pdo->prepare("UPDATE door_products1 SET 
                                  code = ?, 
                                  url = ?, 
                                  categoryId = ?, 
                                  picture = ?, 
                                  name = ?, 
                                  price = ?, 
                                  description = ?, 
                                  class1 = ?, 
                                  class2 = ?, 
                                  class3 = ?, 
                                  class4 = ?, 
                                  class5 = ? 
                                  WHERE id = ?");
            $stmt->execute([$code, $url, $categoryId, $picture, $name, $price, $description, $class1, $class2, $class3, $class4, $class5, $product_id]);
            $success_message = "Товар успешно обновлен!";
            $editing_product = null;
        } catch (PDOException $e) {
            $error_message = "Ошибка при обновлении товара: " . $e->getMessage();
        }
    }
    
    // 3. Удаление товара
    if (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM door_products1 WHERE id = ?");
            $stmt->execute([$product_id]);
            $success_message = "Товар успешно удален!";
        } catch (PDOException $e) {
            $error_message = "Ошибка при удалении товара: " . $e->getMessage();
        }
    }
    
    // 4. Обновление цветов оформления
    if ($can_edit_colors && isset($_POST['update_colors'])) {
        try {
            foreach ($_POST['colors'] as $key => $value) {
                $value = trim($value);
                if (!empty($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                    $stmt = $pdo->prepare("INSERT INTO door_site_settings (setting_key, setting_value) 
                                          VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                }
            }
            $success_message = "Цвета оформления успешно обновлены!";
        } catch (PDOException $e) {
            $error_message = "Ошибка при обновлении цветов: " . $e->getMessage();
        }
    }
}

// Получаем данные для админки
try {
    // Пользователи (только количество для статистики)
    $stmt = $pdo->query("SELECT COUNT(*) as total_users, SUM(is_admin) as admin_users FROM door_users");
    $user_stats = $stmt->fetch();
    $total_users = $user_stats['total_users'] ?? 0;
    $admin_users = $user_stats['admin_users'] ?? 0;
    
    // Заказы (если таблица существует, только количество и сумму)
    $total_orders = 0;
    $total_revenue = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total_orders, SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as total_revenue FROM door_orders");
        $order_stats = $stmt->fetch();
        $total_orders = $order_stats['total_orders'] ?? 0;
        $total_revenue = $order_stats['total_revenue'] ?? 0;
    } catch (Exception $e) {
        // Таблица заказов может не существовать
    }
    
    // Настройки цветов
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM door_site_settings");
    $settings_data = $stmt->fetchAll();
    $color_settings = [];
    foreach ($settings_data as $setting) {
        $color_settings[$setting['setting_key']] = $setting['setting_value'];
    }
    
} catch (PDOException $e) {
    die("Ошибка при получении данных: " . $e->getMessage());
}

// Определяем активную вкладку
$active_tab = 'products';
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
} elseif ($editing_product) {
    $active_tab = 'edit-product';
}

// Параметры сортировки и пагинации
$sort_by = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'desc';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 50; // Увеличиваем количество товаров на странице
$offset = ($page - 1) * $per_page;

// Допустимые поля для сортировки
$allowed_sort_fields = ['id', 'name', 'price', 'categoryId', 'code'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'id';
}

// Допустимые направления сортировки
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_order, $allowed_orders)) {
    $sort_order = 'desc';
}

// Получаем товары с сортировкой и пагинацией
try {
    // Сначала получаем общее количество товаров
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM door_products1");
    $total_result = $stmt->fetch();
    $total_products = $total_result['total'] ?? 0;
    
    // Рассчитываем общее количество страниц
    $total_pages = ceil($total_products / $per_page);
    
    // Получаем товары для текущей страницы
    $sql = "SELECT * FROM door_products1 ORDER BY $sort_by $sort_order LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Ошибка при получении товаров: " . $e->getMessage());
}
?>

<?php include 'header.php'; ?>

<div style="max-width: 1400px; margin: 40px auto; padding: 0 20px;">
    <h1 style="color: <?php echo getSetting('primary_color', '#2e7d32'); ?>; margin-bottom: 30px;">Панель администратора</h1>
    
    <!-- Уведомления -->
    <?php if(isset($success_message)): ?>
        <div style="background-color: <?php echo getSetting('success_color', '#d4edda'); ?>; 
                   border: 1px solid <?php echo getSetting('success_color', '#c3e6cb'); ?>; 
                   color: <?php echo getSetting('primary_color', '#155724'); ?>; 
                   padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 500;">
            ✅ <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div style="background-color: <?php echo getSetting('error_color', '#fff'); ?>; 
                   border: 1px solid <?php echo getSetting('error_color', '#f5c6cb'); ?>; 
                   color: <?php echo getSetting('error_color', '#fff'); ?>; 
                   padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 500;">
            ❌ <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Статистика -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 50px; height: 50px; background-color: <?php echo getSetting('success_color', '#4caf50'); ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="color: #333; margin: 0 0 5px 0; font-size: 16px;">Товары</h3>
                    <div style="font-size: 24px; font-weight: bold; color: <?php echo getSetting('success_color', '#4caf50'); ?>;"><?php echo $total_products; ?></div>
                    <div style="font-size: 12px; color: #666;">Страница <?php echo $page; ?> из <?php echo $total_pages; ?></div>
                </div>
            </div>
        </div>
        
        <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 50px; height: 50px; background-color: <?php echo getSetting('accent_color', '#ff9800'); ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <h3 style="color: #333; margin: 0 0 5px 0; font-size: 16px;">Пользователи</h3>
                    <div style="font-size: 24px; font-weight: bold; color: <?php echo getSetting('accent_color', '#ff9800'); ?>;"><?php echo $total_users; ?></div>
                    <div style="font-size: 12px; color: #666;">Админов: <?php echo $admin_users; ?></div>
                </div>
            </div>
        </div>
        
        <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 50px; height: 50px; background-color: #2196f3; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div>
                    <h3 style="color: #333; margin: 0 0 5px 0; font-size: 16px;">Заказы</h3>
                    <div style="font-size: 24px; font-weight: bold; color: #2196f3;"><?php echo $total_orders; ?></div>
                    <div style="font-size: 12px; color: #666;">Всего заказов</div>
                </div>
            </div>
        </div>
        
        <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 50px; height: 50px; background-color: #9c27b0; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="color: #333; margin: 0 0 5px 0; font-size: 16px;">Выручка</h3>
                    <div style="font-size: 24px; font-weight: bold; color: #9c27b0;"><?php echo number_format($total_revenue, 0, ',', ' '); ?> ₽</div>
                    <div style="font-size: 12px; color: #666;">Общая сумма</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Навигация -->
    <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 10px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
            <a href="?tab=products" 
               style="padding: 10px 20px; background-color: <?php echo $active_tab == 'products' ? getSetting('button_color', '#4caf50') : '#f5f5f5'; ?>; 
                      color: <?php echo $active_tab == 'products' ? 'white' : '#666'; ?>; 
                      text-decoration: none; border-radius: 5px; font-weight: <?php echo $active_tab == 'products' ? 'bold' : 'normal'; ?>; white-space: nowrap;">
                Товары
            </a>
            <a href="?tab=add-product" 
               style="padding: 10px 20px; background-color: <?php echo $active_tab == 'add-product' || $active_tab == 'edit-product' ? getSetting('button_color', '#4caf50') : '#f5f5f5'; ?>; 
                      color: <?php echo $active_tab == 'add-product' || $active_tab == 'edit-product' ? 'white' : '#666'; ?>; 
                      text-decoration: none; border-radius: 5px; font-weight: <?php echo $active_tab == 'add-product' || $active_tab == 'edit-product' ? 'bold' : 'normal'; ?>; white-space: nowrap;">
                <?php echo $editing_product ? 'Редактировать товар' : 'Добавить товар'; ?>
            </a>
            <a href="?tab=users" 
               style="padding: 10px 20px; background-color: <?php echo $active_tab == 'users' ? getSetting('button_color', '#4caf50') : '#f5f5f5'; ?>; 
                      color: <?php echo $active_tab == 'users' ? 'white' : '#666'; ?>; 
                      text-decoration: none; border-radius: 5px; font-weight: <?php echo $active_tab == 'users' ? 'bold' : 'normal'; ?>; white-space: nowrap;">
                Пользователи
            </a>
            <a href="?tab=orders" 
               style="padding: 10px 20px; background-color: <?php echo $active_tab == 'orders' ? getSetting('button_color', '#4caf50') : '#f5f5f5'; ?>; 
                      color: <?php echo $active_tab == 'orders' ? 'white' : '#666'; ?>; 
                      text-decoration: none; border-radius: 5px; font-weight: <?php echo $active_tab == 'orders' ? 'bold' : 'normal'; ?>; white-space: nowrap;">
                Заказы
            </a>
            <?php if($can_edit_colors): ?>
            <a href="?tab=colors" 
               style="padding: 10px 20px; background-color: <?php echo $active_tab == 'colors' ? getSetting('button_color', '#4caf50') : '#f5f5f5'; ?>; 
                      color: <?php echo $active_tab == 'colors' ? 'white' : '#666'; ?>; 
                      text-decoration: none; border-radius: 5px; font-weight: <?php echo $active_tab == 'colors' ? 'bold' : 'normal'; ?>; white-space: nowrap;">
                Оформление
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Контент -->
    <div id="tab-content">
        
        <!-- Вкладка Товары -->
        <?php if($active_tab == 'products'): ?>
            <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #333; margin: 0;">Управление товарами</h2>
                    <a href="?tab=add-product" 
                       style="padding: 8px 15px; background-color: <?php echo getSetting('button_color', '#4caf50'); ?>; color: white; text-decoration: none; border-radius: 5px;">
                        + Добавить товар
                    </a>
                </div>
                
                <!-- Панель сортировки и пагинации -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span style="color: #666; font-size: 14px;">Сортировать:</span>
                        <select onchange="changeSort(this)" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="id_desc" <?php echo $sort_by == 'id' && $sort_order == 'desc' ? 'selected' : ''; ?>>ID (по убыванию)</option>
                            <option value="id_asc" <?php echo $sort_by == 'id' && $sort_order == 'asc' ? 'selected' : ''; ?>>ID (по возрастанию)</option>
                            <option value="name_asc" <?php echo $sort_by == 'name' && $sort_order == 'asc' ? 'selected' : ''; ?>>Название (А-Я)</option>
                            <option value="name_desc" <?php echo $sort_by == 'name' && $sort_order == 'desc' ? 'selected' : ''; ?>>Название (Я-А)</option>
                            <option value="price_asc" <?php echo $sort_by == 'price' && $sort_order == 'asc' ? 'selected' : ''; ?>>Цена (по возрастанию)</option>
                            <option value="price_desc" <?php echo $sort_by == 'price' && $sort_order == 'desc' ? 'selected' : ''; ?>>Цена (по убыванию)</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="color: #666; font-size: 14px;">
                            Показано <?php echo count($products); ?> из <?php echo $total_products; ?> товаров
                        </span>
                        
                        <!-- Пагинация -->
                        <div style="display: flex; gap: 5px;">
                            <?php if($page > 1): ?>
                                <a href="?tab=products&page=<?php echo $page-1; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>"
                                   style="padding: 8px 12px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 5px;">←</a>
                            <?php endif; ?>
                            
                            <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?tab=products&page=<?php echo $i; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>"
                                   style="padding: 8px 12px; background-color: <?php echo $i == $page ? getSetting('button_color', '#4caf50') : '#f5f5f5'; ?>; 
                                          color: <?php echo $i == $page ? 'white' : '#666'; ?>; 
                                          text-decoration: none; border-radius: 5px;"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?tab=products&page=<?php echo $page+1; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>"
                                   style="padding: 8px 12px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 5px;">→</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background-color: #f5f5f5;">
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">ID</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Название</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Картинка</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Цена</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Категория</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?php echo $product['id']; ?></td>
                                <td style="padding: 10px;">
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                    <small style="color: #666;">Код: <?php echo htmlspecialchars(substr($product['code'], 0, 10)); ?>...</small>
                                </td>
                                <td style="padding: 10px;">
                                    <?php if($product['picture']): ?>
                                        <img src="<?php echo htmlspecialchars($product['picture']); ?>" 
                                             alt="Изображение" 
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: #eee; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">
                                            Нет
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px; font-weight: bold;">
                                    <?php echo number_format($product['price'], 0, ',', ' '); ?> ₽
                                </td>
                                <td style="padding: 10px;">
                                    <?php echo htmlspecialchars($product['categoryId']); ?>
                                </td>
                                <td style="padding: 10px;">
                                    <div style="display: flex; gap: 5px;">
                                        <a href="?edit_product=<?php echo $product['id']; ?>" 
                                           style="padding: 4px 8px; background-color: #2196f3; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 11px; text-decoration: none;">
                                            Редакт.
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Удалить товар?')" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="delete_product" 
                                                    style="padding: 4px 8px; background-color: <?php echo getSetting('error_color', '#f44336'); ?>; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 11px;">
                                                Удалить
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Пагинация снизу -->
                <?php if($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    <div style="display: flex; gap: 5px;">
                        <?php if($page > 1): ?>
                            <a href="?tab=products&page=1&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>"
                               style="padding: 8px 12px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 5px;">« Первая</a>
                            <a href="?tab=products&page=<?php echo $page-1; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>"
                               style="padding: 8px 12px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 5px;">← Назад</a>
                        <?php endif; ?>
                        
                        <span style="padding: 8px 12px; background-color: #f5f5f5; color: #666; border-radius: 5px;">
                            Страница <?php echo $page; ?> из <?php echo $total_pages; ?>
                        </span>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="?tab=products&page=<?php echo $page+1; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>"
                               style="padding: 8px 12px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 5px;">Вперед →</a>
                            <a href="?tab=products&page=<?php echo $total_pages; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>"
                               style="padding: 8px 12px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 5px;">Последняя »</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <script>
            function changeSort(select) {
                const value = select.value;
                const [field, order] = value.split('_');
                window.location.href = `?tab=products&sort=${field}&order=${order}&page=1`;
            }
            
            // Предзагрузка следующей страницы для быстрого перехода
            document.addEventListener('DOMContentLoaded', function() {
                const links = document.querySelectorAll('a[href*="page="]');
                links.forEach(link => {
                    link.addEventListener('mouseenter', function() {
                        const nextPage = this.getAttribute('href');
                        // Предварительная загрузка следующей страницы
                        const preloadLink = document.createElement('link');
                        preloadLink.rel = 'prefetch';
                        preloadLink.href = nextPage;
                        document.head.appendChild(preloadLink);
                    });
                });
            });
            </script>
        <?php endif; ?>
        
        <!-- Вкладка Добавить/Редактировать товар -->
        <?php if($active_tab == 'add-product' || $active_tab == 'edit-product'): ?>
            <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #333; margin: 0;">
                        <?php echo $editing_product ? 'Редактирование товара' : 'Добавление нового товара'; ?>
                    </h2>
                    <a href="?tab=products" 
                       style="padding: 8px 15px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 5px;">
                        ← Назад к товарам
                    </a>
                </div>
                
                <form method="POST" action="">
                    <?php if($editing_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $editing_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">Код товара *</label>
                            <input type="text" name="code" required 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['code']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">Название товара *</label>
                            <input type="text" name="name" required 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['name']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">URL страницы</label>
                            <input type="text" name="url" 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['url']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">ID категории</label>
                            <input type="text" name="categoryId" 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['categoryId']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">URL изображения</label>
                        <input type="text" name="picture" 
                               value="<?php echo $editing_product ? htmlspecialchars($editing_product['picture']) : ''; ?>"
                               placeholder="https://example.com/image.jpg"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">Цена *</label>
                            <input type="number" name="price" required step="0.01" min="0"
                                   value="<?php echo $editing_product ? $editing_product['price'] : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">Описание</label>
                            <input type="text" name="description" 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['description'] ?? '') : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">Class 1</label>
                            <input type="text" name="class1" 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['class1'] ?? '') : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">Class 2</label>
                            <input type="text" name="class2" 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['class2'] ?? '') : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">Class 3</label>
                            <input type="text" name="class3" 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['class3'] ?? '') : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">Class 4</label>
                            <input type="text" name="class4" 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['class4'] ?? '') : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">Class 5</label>
                            <input type="text" name="class5" 
                                   value="<?php echo $editing_product ? htmlspecialchars($editing_product['class5'] ?? '') : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <?php if($editing_product): ?>
                            <button type="submit" name="update_product" 
                                    style="padding: 12px 30px; background-color: #2196f3; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                                Обновить товар
                            </button>
                        <?php else: ?>
                            <button type="submit" name="add_product" 
                                    style="padding: 12px 30px; background-color: <?php echo getSetting('button_color', '#4caf50'); ?>; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                                Добавить товар
                            </button>
                        <?php endif; ?>
                        
                        <a href="?tab=products" 
                           style="padding: 12px 30px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 5px; font-size: 16px; display: inline-block;">
                            Отмена
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Вкладка Пользователи -->
        <?php if($active_tab == 'users'): ?>
            <?php
            // Пагинация для пользователей
            $user_page = isset($_GET['user_page']) ? intval($_GET['user_page']) : 1;
            $user_per_page = 50;
            $user_offset = ($user_page - 1) * $user_per_page;
            
            // Получаем пользователей с пагинацией
            try {
                $stmt = $pdo->query("SELECT * FROM door_users ORDER BY registration_date DESC LIMIT $user_per_page OFFSET $user_offset");
                $users = $stmt->fetchAll();
            } catch (PDOException $e) {
                die("Ошибка при получении пользователей: " . $e->getMessage());
            }
            ?>
            <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <h2 style="color: #333; margin-bottom: 20px;">Пользователи системы</h2>
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background-color: #f5f5f5;">
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">ID</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Имя</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Email</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Телефон</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Дата регистрации</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?php echo $user['id']; ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($user['phone'] ?: 'Не указан'); ?></td>
                                <td style="padding: 10px;"><?php echo date('d.m.Y H:i', strtotime($user['registration_date'])); ?></td>
                                <td style="padding: 10px;">
                                    <?php if($user['is_admin']): ?>
                                        <span style="background-color: <?php echo getSetting('accent_color', '#ff9800'); ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">Админ</span>
                                        <?php if($user['can_edit_colors']): ?>
                                            <span style="background-color: #9c27b0; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; margin-left: 3px;">Дизайн</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="background-color: <?php echo getSetting('success_color', '#4caf50'); ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">Пользователь</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Вкладка Заказы -->
        <?php if($active_tab == 'orders'): ?>
            <?php
            // Пагинация для заказов
            $order_page = isset($_GET['order_page']) ? intval($_GET['order_page']) : 1;
            $order_per_page = 50;
            $order_offset = ($order_page - 1) * $order_per_page;
            
            // Получаем заказы с пагинацией
            try {
                $stmt = $pdo->query("SELECT * FROM door_orders ORDER BY created_at DESC LIMIT $order_per_page OFFSET $order_offset");
                $orders = $stmt->fetchAll();
            } catch (Exception $e) {
                $orders = [];
            }
            ?>
            <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #333; margin: 0;">Управление заказами</h2>
                    <div style="color: #666; font-size: 14px;">
                        Всего: <?php echo $total_orders; ?> заказов
                    </div>
                </div>
                
                <?php if($total_orders > 0): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead>
                                <tr style="background-color: #f5f5f5;">
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">ID</th>
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Клиент</th>
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Email</th>
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Дата</th>
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Сумма</th>
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;"><?php echo $order['id']; ?></td>
                                    <td style="padding: 10px;">
                                        <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                    </td>
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                    <td style="padding: 10px;">
                                        <?php echo date('d.m.Y', strtotime($order['created_at'])); ?>
                                    </td>
                                    <td style="padding: 10px; font-weight: bold;">
                                        <?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽
                                    </td>
                                    <td style="padding: 10px;">
                                        <span style="background-color: 
                                            <?php echo $order['status'] == 'completed' ? getSetting('success_color', '#4caf50') : 
                                                   ($order['status'] == 'pending' ? getSetting('accent_color', '#ff9800') : 
                                                   getSetting('error_color', '#f44336')); ?>; 
                                            color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">
                                            <?php echo $order['status'] == 'completed' ? 'Завершен' : 
                                                   ($order['status'] == 'pending' ? 'В ожидании' : 'Отменен'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <p>Заказов пока нет</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Вкладка Оформление -->
        <?php if($can_edit_colors && $active_tab == 'colors'): ?>
            <div style="background: <?php echo getSetting('card_color', 'white'); ?>; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <h2 style="color: #333; margin-bottom: 20px;">Настройка оформления сайта</h2>
                
                <form method="POST" action="">
                    <div style="margin-bottom: 30px;">
                        <h3 style="color: #555; margin-bottom: 15px; font-size: 18px;">Основные цвета</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                            <?php 
                            $color_groups = [
                                'Основные' => [
                                    'primary_color' => 'Основной цвет',
                                    'secondary_color' => 'Вторичный цвет',
                                    'accent_color' => 'Акцентный цвет',
                                    'button_color' => 'Цвет кнопок'
                                ],
                                'Фон и текст' => [
                                    'background_color' => 'Цвет фона',
                                    'card_color' => 'Цвет карточек',
                                    'text_color' => 'Цвет текста'
                                ],
                                'Состояния' => [
                                    'success_color' => 'Цвет успеха',
                                    'error_color' => 'Цвет ошибок'
                                ]
                            ];
                            
                            foreach ($color_groups as $group_name => $colors): 
                            ?>
                            <div style="margin-bottom: 20px;">
                                <h4 style="color: #555; margin-bottom: 10px; font-size: 16px;"><?php echo $group_name; ?></h4>
                                <div style="display: grid; gap: 10px;">
                                    <?php foreach ($colors as $key => $label): ?>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ddd; background-color: <?php echo $color_settings[$key] ?? '#ffffff'; ?>;"></div>
                                        <div style="flex: 1;">
                                            <label style="display: block; margin-bottom: 3px; color: #555; font-size: 14px;"><?php echo $label; ?></label>
                                            <input type="color" name="colors[<?php echo $key; ?>]" value="<?php echo $color_settings[$key] ?? '#ffffff'; ?>"
                                                   style="width: 100%; height: 40px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div style="background-color: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                        <h3 style="color: #555; margin-bottom: 10px;">Предварительный просмотр</h3>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <div style="padding: 15px; border-radius: 5px; background: linear-gradient(135deg, <?php echo $color_settings['primary_color'] ?? '#2e7d32'; ?> 0%, <?php echo $color_settings['secondary_color'] ?? '#388e3c'; ?> 100%); color: white;">
                                Шапка сайта
                            </div>
                            <div style="padding: 15px; border-radius: 5px; background-color: <?php echo $color_settings['accent_color'] ?? '#ff9800'; ?>; color: white;">
                                Акцентный элемент
                            </div>
                            <div style="padding: 15px; border-radius: 5px; background-color: <?php echo $color_settings['button_color'] ?? '#4caf50'; ?>; color: white;">
                                Кнопка
                            </div>
                            <div style="padding: 15px; border-radius: 5px; background-color: <?php echo $color_settings['error_color'] ?? '#f44336'; ?>; color: white;">
                                Ошибка
                            </div>
                            <div style="padding: 15px; border-radius: 5px; background-color: <?php echo $color_settings['success_color'] ?? '#4caf50'; ?>; color: white;">
                                Успех
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="update_colors" 
                                style="padding: 12px 30px; background-color: <?php echo getSetting('button_color', '#4caf50'); ?>; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                            Сохранить цвета
                        </button>
                        
                        <button type="button" onclick="resetColors()"
                                style="padding: 12px 30px; background-color: #f5f5f5; color: #666; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                            Сбросить к стандартным
                        </button>
                    </div>
                </form>
            </div>
            
            <script>
            function resetColors() {
                if (confirm('Сбросить все цвета к стандартным значениям?')) {
                    const defaultColors = {
                        'primary_color': '#2e7d32',
                        'secondary_color': '#388e3c',
                        'accent_color': '#ff9800',
                        'text_color': '#333333',
                        'background_color': '#f5f5f5',
                        'card_color': '#ffffff',
                        'button_color': '#4caf50',
                        'error_color': '#f44336',
                        'success_color': '#4caf50'
                    };
                    
                    for (const [key, value] of Object.entries(defaultColors)) {
                        const input = document.querySelector(`input[name="colors[${key}]"]`);
                        if (input) {
                            input.value = value;
                        }
                    }
                    
                    alert('Цвета сброшены к стандартным значениям. Нажмите "Сохранить цвета" для применения.');
                }
            }
            </script>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>