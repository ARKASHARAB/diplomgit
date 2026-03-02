<?php
require_once 'config.php';

$cart = $_SESSION['cart'] ?? [];
$total_price = 0;
$total_items = 0;

foreach ($cart as $item) {
    $total_price += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}
?>

<?php include 'header.php'; ?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #2c3e50; margin-bottom: 30px; text-align: center;">🛒 Корзина покупок</h1>
    
    <?php if (empty($cart)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 10px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
            <p style="color: #7f8c8d; font-size: 20px; margin-bottom: 20px;">😕 Ваша корзина пуста</p>
            <a href="catalog.php" 
               style="padding: 12px 30px; background-color: #3d3d3d; color: white; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600;">
                Перейти в каталог
            </a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Список товаров -->
            <div>
                <div style="background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
                    <h3 style="color: #2c3e50; margin-bottom: 25px; font-size: 18px;">Товары в корзине (<?php echo $total_items; ?>)</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php foreach ($cart as $product_id => $item): ?>
                            <div style="display: flex; gap: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; align-items: center;">
                                <!-- Изображение -->
                                <div style="width: 120px; height: 120px; background-color: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <?php if (!empty($item['picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['picture']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                    <?php else: ?>
                                        <span style="color: #bdc3c7; font-size: 14px;">Нет фото</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Информация -->
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 16px;">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </h4>
                                    <div style="color: #7f8c8d; font-size: 14px; margin-bottom: 10px;">
                                        Артикул: <?php echo htmlspecialchars($item['code']); ?>
                                    </div>
                                    <div style="font-size: 18px; font-weight: bold; color: #e74c3c;">
                                        <?php echo number_format($item['price'], 0, ',', ' '); ?> ₽
                                    </div>
                                </div>
                                
                                <!-- Управление количеством -->
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <button onclick="updateQuantity(<?php echo $product_id; ?>, -1)" 
                                                style="width: 30px; height: 30px; background-color: #3d3d3d; color: #fff ; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
                                            -
                                        </button>
                                        <span id="quantity-<?php echo $product_id; ?>" 
                                              style="min-width: 30px; text-align: center; font-weight: bold;">
                                            <?php echo $item['quantity']; ?>
                                        </span>
                                        <button onclick="updateQuantity(<?php echo $product_id; ?>, 1)" 
                                                style="width: 30px; height: 30px; background-color: #3d3d3d; border: none; color: #fff ; border-radius: 4px; cursor: pointer; font-size: 16px;">
                                            +
                                        </button>
                                    </div>
                                    
                                    <div style="font-size: 18px; font-weight: bold; color: #2c3e50; min-width: 120px; text-align: right;">
                                        <?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> ₽
                                    </div>
                                    
                                    <button onclick="removeFromCart(<?php echo $product_id; ?>)" 
                                            style="color: #e74c3c; background: none; border: none; cursor: pointer; font-size: 18px; padding: 5px 10px;">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 20px; border-top: 2px solid #eee;">
                        <a href="catalog.php" 
                           style="padding: 10px 20px; background-color: #3d3d3d; color: #fff; text-decoration: none; border-radius: 8px;">
                            ← Продолжить покупки
                        </a>
                        
                        <button onclick="clearCart()" 
                                style="padding: 10px 20px; background-color: #3d3d3d; color: #fff; border: none; border-radius: 8px; cursor: pointer;">
                            Очистить корзину
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Итоги -->
            <div>
                <div style="background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); position: sticky; top: 20px;">
                    <h3 style="color: #2c3e50; margin-bottom: 25px; font-size: 18px;">Итого</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; color: #7f8c8d;">
                            <span>Товары (<?php echo $total_items; ?> шт.)</span>
                            <span><?php echo number_format($total_price, 0, ',', ' '); ?> ₽</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; color: #3d3d3d;">
                            <span>Доставка</span>
                            <span>Расчитывается при оформлении</span>
                        </div>
                        
                        <div style="height: 1px; background-color: #eee; margin: 10px 0;"></div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: bold; color: #3d3d3d;">
                            <span>К оплате</span>
                            <span><?php echo number_format($total_price, 0, ',', ' '); ?> ₽</span>
                        </div>
                    </div>
                    
                    <button onclick="checkout()" 
                            style="width: 100%; padding: 15px; background-color: #3d3d3d; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background-color 0.3s;"
                            onmouseover="this.style.backgroundColor='#707070'"
                            onmouseout="this.style.backgroundColor='#3d3d3d'">
                        Перейти к оформлению
                    </button>
                    
                    <p style="color: #7f8c8d; font-size: 12px; text-align: center; margin-top: 15px;">
                        Нажимая кнопку, вы соглашаетесь с условиями обработки персональных данных
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(productId, change) {
    const quantityElement = document.getElementById(`quantity-${productId}`);
    let currentQuantity = parseInt(quantityElement.textContent);
    let newQuantity = currentQuantity + change;
    
    if (newQuantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&action=update&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            quantityElement.textContent = newQuantity;
            updateCartCount(data.cart_count);
            location.reload(); // Перезагружаем для обновления суммы
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function removeFromCart(productId) {
    if (!confirm('Удалить товар из корзины?')) {
        return;
    }
    
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&action=remove`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function clearCart() {
    if (!confirm('Вы уверены, что хотите очистить всю корзину?')) {
        return;
    }
    
    // Показываем индикатор загрузки
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = 'Очищаем...';
    button.disabled = true;
    
    fetch('clear_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Обновляем счетчик корзины
            updateCartCount(data.cart_count);
            
            // Показываем сообщение
            alert('Корзина успешно очищена!');
            
            // Перезагружаем страницу
            location.reload();
        } else {
            alert('Ошибка при очистке корзины: ' + data.message);
            button.textContent = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка сети при очистке корзины');
        button.textContent = originalText;
        button.disabled = false;
    });
}

function checkout() {
    alert('Функция оформления заказа в разработке');
    // window.location.href = 'checkout.php';
}

function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        cartCountElement.style.display = count > 0 ? 'inline-flex' : 'none';
    }
}
</script>

<?php include 'footer.php'; ?>