<?php
require_once 'config.php';

// Получаем категории из таблицы
try {
    $stmt = $pdo->query("SELECT DISTINCT categoryId, class1 FROM door_products1 ORDER BY class1");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка при получении категорий: " . $e->getMessage());
}

// Фильтрация по категории
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Сортировка
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = 'id';
$order_dir = 'DESC';

switch ($sort) {
    case 'price_asc':
        $order_by = 'price';
        $order_dir = 'ASC';
        break;
    case 'price_desc':
        $order_by = 'price';
        $order_dir = 'DESC';
        break;
    case 'name_asc':
        $order_by = 'name';
        $order_dir = 'ASC';
        break;
    case 'name_desc':
        $order_by = 'name';
        $order_dir = 'DESC';
        break;
    case 'newest':
    default:
        $order_by = 'id';
        $order_dir = 'DESC';
        break;
}

// Получаем текущую страницу
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Формируем запрос товаров с пагинацией
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM door_products1 WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM door_products1 WHERE 1=1";

$params = [];
$count_params = [];

if (!empty($category_filter)) {
    $sql .= " AND categoryId = ?";
    $count_sql .= " AND categoryId = ?";
    $params[] = $category_filter;
    $count_params[] = $category_filter;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR desctiption LIKE ? OR code LIKE ?)";
    $count_sql .= " AND (name LIKE ? OR desctiption LIKE ? OR code LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $count_params[] = $searchTerm;
    $count_params[] = $searchTerm;
    $count_params[] = $searchTerm;
}

$sql .= " ORDER BY $order_by $order_dir LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset";

try {
    // Получаем товары
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Получаем общее количество товаров для пагинации
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($count_params);
    $total_result = $stmt->fetch();
    $total_products = $total_result['total'];
    
    // Вычисляем общее количество страниц
    $total_pages = ceil($total_products / ITEMS_PER_PAGE);
    
} catch (PDOException $e) {
    die("Ошибка при получении товаров: " . $e->getMessage());
}
?>

<?php include 'header.php'; ?>

<div style="max-width: 1400px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #2c3e50; margin-bottom: 30px; text-align: center;">Каталог дверей</h1>
    
    <!-- Уведомление о добавлении в корзину -->
    <div id="cart-notification" style="display: none; position: fixed; top: 100px; right: 20px; background-color: #3d3d3d; color: white; padding: 15px 20px; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; animation: slideIn 0.3s ease-out;">
        <span id="notification-message">Товар добавлен в корзину!</span>
        <span id="notification-close" style="margin-left: 15px; cursor: pointer; font-weight: bold;">×</span>
    </div>
    
    <!-- Поиск, фильтры и сортировка -->
    <div style="background: #fff; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
        <!-- Поиск и категории -->
        <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; margin-bottom: 20px;">
            <div style="flex: 2; min-width: 300px;">
                <input type="text" name="search" placeholder="Поиск по названию, описанию или артикулу..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                       onfocus="this.style.borderColor='#3498db'"
                       onblur="this.style.borderColor='#e0e0e0'">
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <select name="category" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; background-color: white;">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['categoryId']); ?>" 
                                <?php echo ($category_filter == $category['categoryId']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['class1']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" 
                    style="padding: 12px 25px; background-color: #3d3d3d; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background-color 0.3s;"
                    onmouseover="this.style.backgroundColor='#707070'"
                    onmouseout="this.style.backgroundColor='#3d3d3d'">
                🔍 Найти
            </button>
            
            <?php if (!empty($category_filter) || !empty($search)): ?>
                <a href="catalog.php" 
                   style="padding: 12px 25px; background-color: #3d3d3d; color: #fff; text-decoration: none; border-radius: 8px; font-size: 16px; transition: background-color 0.3s;"
                   onmouseover="this.style.backgroundColor='#707070'"
                   onmouseout="this.style.backgroundColor='#3d3d3d'">
                    ✕ Сбросить
                </a>
            <?php endif; ?>
        </form>
        
        <!-- Сортировка -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="color: #7f8c8d; font-size: 14px;">Сортировка:</span>
                <select name="sort" id="sort-select" onchange="updateSort(this.value)" 
                        style="padding: 8px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; background-color: white;">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Сначала новые</option>
                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Цена по возрастанию</option>
                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Цена по убыванию</option>
                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>По названию (А-Я)</option>
                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>По названию (Я-А)</option>
                </select>
            </div>
            
            <div style="color: #7f8c8d; font-size: 14px;">
                Найдено товаров: <strong><?php echo $total_products; ?></strong>
                <?php if ($total_pages > 1): ?>
                    | Страница <strong><?php echo $page; ?></strong> из <strong><?php echo $total_pages; ?></strong>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Товары -->
    <div style="margin-bottom: 40px;">
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 10px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
                <p style="color: #7f8c8d; font-size: 18px; margin-bottom: 20px;">😕 Товары не найдены</p>
                <a href="catalog.php" 
                   style="padding: 10px 25px; background-color: #3d3d3d; color: white; text-decoration: none; border-radius: 8px; font-size: 16px;">
                    Показать все товары
                </a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px;">
                <?php foreach ($products as $product): ?>
                    <div id="product-<?php echo $product['id']; ?>" 
                         class="product-card"
                         style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 3px 15px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;">
                        
                        <!-- Изображение товара -->
                        <div style="height: 220px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; padding: 20px; position: relative;">
                            <?php if (!empty($product['picture'])): ?>
                                <img src="<?php echo htmlspecialchars($product['picture']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <div style="color: #bdc3c7; font-size: 14px; text-align: center;">
                                    <span style="font-size: 48px;">🚪</span><br>
                                    Нет изображения
                                </div>
                            <?php endif; ?>
                            
                            <!-- Артикул -->
                            <div style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px;">
                                Арт: <?php echo htmlspecialchars($product['code']); ?>
                            </div>
                            
                            <!-- Убираем блок наличия на складе, так как в базе нет этих данных -->
                        </div>
                        
                        <!-- Информация о товаре -->
                        <div style="padding: 25px;">
                            <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; font-weight: 600; min-height: 54px; line-height: 1.4;">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h4>
                            
                            <!-- Категории -->
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;">
                                <?php if (!empty($product['class1'])): ?>
                                    <span style="background-color: #fff; color: #2980b9; padding: 4px 12px; border-radius: 15px; font-size: 12px;">
                                        <?php echo htmlspecialchars($product['class1']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($product['class2'])): ?>
                                    <span style="background-color: #fff; color: #27ae60; padding: 4px 12px; border-radius: 15px; font-size: 12px;">
                                        <?php echo htmlspecialchars($product['class2']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Описание -->
                            <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 20px; min-height: 60px; line-height: 1.5;">
                                <?php 
                                    $description = $product['desctiption'] ?? '';
                                    if (strlen($description) > 80) {
                                        echo htmlspecialchars(substr($description, 0, 80)) . '...';
                                    } else {
                                        echo htmlspecialchars($description);
                                    }
                                ?>
                            </p>
                            
                            <!-- Цена и кнопки -->
                            <div style="border-top: 1px solid #eee; padding-top: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <div>
                                        <div style="font-size: 24px; font-weight: bold; color: #e74c3c;">
                                            <?php echo number_format($product['price'], 0, ',', ' '); ?> ₽
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 12px;">
                                    <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')" 
                                            id="cart-btn-<?php echo $product['id']; ?>"
                                            style="flex: 1; padding: 12px; background-color: #3d3d3d; 
                                                   color: white; border: none; border-radius: 8px; cursor: pointer; 
                                                   font-size: 16px; font-weight: 600; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;"
                                            onmouseover="this.style.backgroundColor='#707070'"
                                            onmouseout="this.style.backgroundColor='#3d3d3d'">
                                        <span id="btn-text-<?php echo $product['id']; ?>">
                                            🛒 В корзину
                                        </span>
                                        <span id="btn-icon-<?php echo $product['id']; ?>" style="display: none; animation: checkmark 0.3s ease-out;">✓</span>
                                    </button>
                                    
                                    <a href="<?php echo htmlspecialchars($product['url']); ?>" 
                                       target="_blank"
                                       style="padding: 12px 20px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 8px; font-size: 16px; display: flex; align-items: center; justify-content: center; transition: background-color 0.3s;"
                                       onmouseover="this.style.backgroundColor='#3d3d3d'"
                                       onmouseout="this.style.backgroundColor='#f5f5f5'">
                                        ℹ️
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Улучшенная пагинация -->
    <?php if ($total_pages > 1): ?>
        <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-top: 30px;">
            <div style="display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;">
                <!-- Кнопка "В начало" -->
                <?php if ($page > 1): ?>
                    <a href="catalog.php?<?php 
                        $query = $_GET;
                        $query['page'] = 1;
                        echo http_build_query($query);
                    ?>" style="padding: 10px 15px; background-color: #3d3d3d; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                        « В начало
                    </a>
                <?php endif; ?>
                
                <!-- Кнопка "Назад" -->
                <?php if ($page > 1): ?>
                    <a href="catalog.php?<?php 
                        $query = $_GET;
                        $query['page'] = $page - 1;
                        echo http_build_query($query);
                    ?>" style="padding: 10px 15px; background-color: #3d3d3d; color: white; text-decoration: none; border-radius: 6px; font-size: 14px;">
                        ← Назад
                    </a>
                <?php endif; ?>
                
                <!-- Первая страница -->
                <?php if ($page > 3): ?>
                    <a href="catalog.php?<?php 
                        $query = $_GET;
                        $query['page'] = 1;
                        echo http_build_query($query);
                    ?>" style="padding: 10px 15px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 6px; font-size: 14px;">
                        1
                    </a>
                    <?php if ($page > 4): ?>
                        <span style="padding: 10px 5px; color: #7f8c8d;">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Номера страниц -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="catalog.php?<?php 
                        $query = $_GET;
                        $query['page'] = $i;
                        echo http_build_query($query);
                    ?>" 
                       style="padding: 10px 15px; text-decoration: none; border-radius: 6px; font-size: 14px; min-width: 40px; text-align: center;
                              background-color: <?php echo ($i == $page) ? '#3d3d3d' : '#f5f5f5'; ?>;
                              color: <?php echo ($i == $page) ? 'white' : '#666'; ?>;
                              font-weight: <?php echo ($i == $page) ? 'bold' : 'normal'; ?>;">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <!-- Последняя страница -->
                <?php if ($page < $total_pages - 2): ?>
                    <?php if ($page < $total_pages - 3): ?>
                        <span style="padding: 10px 5px; color: #7f8c8d;">...</span>
                    <?php endif; ?>
                    <a href="catalog.php?<?php 
                        $query = $_GET;
                        $query['page'] = $total_pages;
                        echo http_build_query($query);
                    ?>" style="padding: 10px 15px; background-color: #f5f5f5; color: #666; text-decoration: none; border-radius: 6px; font-size: 14px;">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>
                
                <!-- Кнопка "Вперед" -->
                <?php if ($page < $total_pages): ?>
                    <a href="catalog.php?<?php 
                        $query = $_GET;
                        $query['page'] = $page + 1;
                        echo http_build_query($query);
                    ?>" style="padding: 10px 15px; background-color: #3d3d3d; color: white; text-decoration: none; border-radius: 6px; font-size: 14px;">
                        Вперед →
                    </a>
                <?php endif; ?>
                
                <!-- Кнопка "В конец" -->
                <?php if ($page < $total_pages): ?>
                    <a href="catalog.php?<?php 
                        $query = $_GET;
                        $query['page'] = $total_pages;
                        echo http_build_query($query);
                    ?>" style="padding: 10px 15px; background-color: #3d3d3d; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                        В конец »
                    </a>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 15px; color: #7f8c8d; font-size: 14px;">
                <form method="GET" action="" style="display: inline-block;">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    <?php if (!empty($category_filter)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                    <?php endif; ?>
                    <?php if (!empty($sort) && $sort != 'newest'): ?>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                    <?php endif; ?>
                    Перейти на страницу: 
                    <input type="number" name="page" min="1" max="<?php echo $total_pages; ?>" value="<?php echo $page; ?>" 
                           style="width: 60px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; text-align: center; margin: 0 5px;">
                    <button type="submit" style="padding: 5px 15px; background-color: #3d3d3d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Перейти
                    </button>
                </form>
            </div>
            
            <div style="text-align: center; margin-top: 10px; color: #7f8c8d; font-size: 14px;">
                Страница <?php echo $page; ?> из <?php echo $total_pages; ?> 
                | Показано <?php echo count($products); ?> из <?php echo $total_products; ?> товаров
                | Товаров на странице: 
                <select onchange="changeItemsPerPage(this.value)" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="12" <?php echo ITEMS_PER_PAGE == 12 ? 'selected' : ''; ?>>12</option>
                    <option value="24" <?php echo ITEMS_PER_PAGE == 24 ? 'selected' : ''; ?>>24</option>
                    <option value="48" <?php echo ITEMS_PER_PAGE == 48 ? 'selected' : ''; ?>>48</option>
                </select>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 0 20px rgba(52, 152, 219, 0.5);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
    }
}

@keyframes checkmark {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

.product-added {
    animation: pulse 0.5s ease;
}

.btn-success {
    background-color: #3d3d3d !important;
}

.btn-success:hover {
    background-color: #707070 !important;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
</style>

<script>
let notificationTimeout;
let currentProductName = '';

function updateSort(sortValue) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    url.searchParams.delete('page'); // Сбрасываем на первую страницу при смене сортировки
    window.location.href = url.toString();
}

function changeItemsPerPage(count) {
    // Сохраняем в cookie для запоминания выбора
    document.cookie = `items_per_page=${count}; path=/; max-age=${30*24*60*60}`;
    // Перезагружаем страницу, значение будет прочитано в config.php
    window.location.reload();
}

function showNotification(message) {
    const notification = document.getElementById('cart-notification');
    const messageElement = document.getElementById('notification-message');
    
    messageElement.textContent = message;
    notification.style.display = 'block';
    notification.style.animation = 'slideIn 0.3s ease-out';
    
    clearTimeout(notificationTimeout);
    notificationTimeout = setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 300);
    }, 4000);
}

function addToCart(productId, productName) {
    if (currentProductName === productName) return;
    
    currentProductName = productName;
    const button = document.getElementById(`cart-btn-${productId}`);
    const btnText = document.getElementById(`btn-text-${productId}`);
    const btnIcon = document.getElementById(`btn-icon-${productId}`);
    const productCard = document.getElementById(`product-${productId}`);
    
    // Визуальная обратная связь
    button.classList.add('btn-success');
    btnText.style.display = 'none';
    btnIcon.style.display = 'inline-block';
    btnIcon.style.animation = 'checkmark 0.3s ease-out';
    button.style.backgroundColor = '#3d3d3d';
    
    // Анимация карточки
    productCard.classList.add('product-added');
    
    // Отправляем запрос на сервер
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&action=add'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`${productName} добавлен в корзину!`);
            updateCartCount(data.cart_count);
            
            // Восстанавливаем кнопку через 2 секунды
            setTimeout(() => {
                button.classList.remove('btn-success');
                btnText.style.display = 'inline-block';
                btnIcon.style.display = 'none';
                button.style.backgroundColor = '';
                productCard.classList.remove('product-added');
                currentProductName = '';
            }, 2000);
        } else {
            btnText.textContent = 'Ошибка';
            btnText.style.color = '#e74c3c';
            setTimeout(() => {
                btnText.textContent = '🛒 В корзину';
                btnText.style.color = 'white';
                btnText.style.display = 'inline-block';
                btnIcon.style.display = 'none';
                productCard.classList.remove('product-added');
                currentProductName = '';
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btnText.textContent = 'Ошибка';
        btnText.style.color = '#e74c3c';
        setTimeout(() => {
            btnText.textContent = '🛒 В корзину';
            btnText.style.color = 'white';
            btnText.style.display = 'inline-block';
            btnIcon.style.display = 'none';
            productCard.classList.remove('product-added');
            currentProductName = '';
        }, 2000);
    });
}

function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        cartCountElement.style.display = count > 0 ? 'inline-flex' : 'none';
        
        // Анимация счетчика
        cartCountElement.style.transform = 'scale(1.3)';
        setTimeout(() => {
            cartCountElement.style.transform = 'scale(1)';
        }, 300);
    }
}

// Закрытие уведомления
document.getElementById('notification-close')?.addEventListener('click', function() {
    const notification = document.getElementById('cart-notification');
    notification.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => {
        notification.style.display = 'none';
    }, 300);
    clearTimeout(notificationTimeout);
});

// Закрытие уведомления при клике вне его
document.addEventListener('click', function(event) {
    const notification = document.getElementById('cart-notification');
    if (notification.style.display === 'block' && 
        !notification.contains(event.target) && 
        !event.target.closest('button[onclick^="addToCart"]')) {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 300);
        clearTimeout(notificationTimeout);
    }
});

// Сохранение позиции скролла при обновлении страницы
window.addEventListener('beforeunload', function() {
    sessionStorage.setItem('scrollPosition', window.scrollY);
});

window.addEventListener('load', function() {
    const scrollPosition = sessionStorage.getItem('scrollPosition');
    if (scrollPosition) {
        window.scrollTo(0, parseInt(scrollPosition));
        sessionStorage.removeItem('scrollPosition');
    }
});
</script>

<?php include 'footer.php'; ?>