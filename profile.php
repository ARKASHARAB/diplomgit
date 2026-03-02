<?php
require_once 'config.php';

if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    try {
        // Обновление основной информации
        $stmt = $pdo->prepare("UPDATE door_users SET full_name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$full_name, $phone, $address, $_SESSION['user_id']]);
        
        // Обновление пароля, если указан
        if (!empty($current_password)) {
            // Проверяем текущий пароль
            $stmt = $pdo->prepare("SELECT password_hash FROM door_users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (password_verify($current_password, $user['password_hash'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 6) {
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE door_users SET password_hash = ? WHERE id = ?");
                        $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
                        $success = true;
                    } else {
                        $errors['new_password'] = 'Новый пароль должен быть не менее 6 символов';
                    }
                } else {
                    $errors['confirm_password'] = 'Новые пароли не совпадают';
                }
            } else {
                $errors['current_password'] = 'Неверный текущий пароль';
            }
        } else {
            $success = true;
        }
        
        // Обновляем информацию в сессии
        $_SESSION['user']['full_name'] = $full_name;
        
    } catch (PDOException $e) {
        $errors['general'] = 'Ошибка при обновлении профиля: ' . $e->getMessage();
    }
}

// Получаем текущую информацию о пользователе
try {
    $stmt = $pdo->prepare("SELECT * FROM door_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Ошибка при получении данных пользователя: " . $e->getMessage());
}
?>

<?php include 'header.php'; ?>

<div style="max-width: 800px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 10px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
    <h2 style="color: #2c3e50; margin-bottom: 30px; text-align: center;">Профиль пользователя</h2>
    
    <?php if ($success): ?>
        <div style="background-color: #e8f6ef; color: #27ae60; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
            ✅ Профиль успешно обновлен
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors['general'])): ?>
        <div style="background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($errors['general']); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div>
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Имя пользователя</label>
                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" 
                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; background-color: #f5f5f5;"
                       disabled>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Email</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; background-color: #f5f5f5;"
                       disabled>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div>
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Полное имя</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Телефон</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;">
            </div>
        </div>
        
        <div style="margin-bottom: 30px;">
            <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Адрес</label>
            <textarea name="address" rows="3" 
                      style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; resize: vertical;"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>
        
        <h3 style="color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #eee;">Смена пароля</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div>
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Текущий пароль</label>
                <input type="password" name="current_password" 
                       style="width: 100%; padding: 12px; border: 2px solid <?php echo !empty($errors['current_password']) ? '#e74c3c' : '#e0e0e0'; ?>; border-radius: 8px; font-size: 16px;">
                <?php if (!empty($errors['current_password'])): ?>
                    <div style="color: #e74c3c; font-size: 14px; margin-top: 5px;"><?php echo htmlspecialchars($errors['current_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Новый пароль</label>
                <input type="password" name="new_password" 
                       style="width: 100%; padding: 12px; border: 2px solid <?php echo !empty($errors['new_password']) ? '#e74c3c' : '#e0e0e0'; ?>; border-radius: 8px; font-size: 16px;">
                <?php if (!empty($errors['new_password'])): ?>
                    <div style="color: #e74c3c; font-size: 14px; margin-top: 5px;"><?php echo htmlspecialchars($errors['new_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Подтвердите новый пароль</label>
                <input type="password" name="confirm_password" 
                       style="width: 100%; padding: 12px; border: 2px solid <?php echo !empty($errors['confirm_password']) ? '#e74c3c' : '#e0e0e0'; ?>; border-radius: 8px; font-size: 16px;">
                <?php if (!empty($errors['confirm_password'])): ?>
                    <div style="color: #e74c3c; font-size: 14px; margin-top: 5px;"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="text-align: center;">
            <button type="submit" 
                    style="padding: 15px 40px; background-color: #3498db; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background-color 0.3s;"
                    onmouseover="this.style.backgroundColor='#2980b9'"
                    onmouseout="this.style.backgroundColor='#3498db'">
                Сохранить изменения
            </button>
        </div>
    </form>
    
    <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #eee;">
        <h3 style="color: #2c3e50; margin-bottom: 20px;">Статистика</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #3498db;">
                    <?php 
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM door_orders WHERE user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $result = $stmt->fetch();
                            echo $result['count'];
                        } catch (PDOException $e) {
                            echo "0";
                        }
                    ?>
                </div>
                <div style="color: #7f8c8d; font-size: 14px;">Всего заказов</div>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #2ecc71;">
                    <?php 
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM door_cart WHERE user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $result = $stmt->fetch();
                            echo $result['count'];
                        } catch (PDOException $e) {
                            echo "0";
                        }
                    ?>
                </div>
                <div style="color: #7f8c8d; font-size: 14px;">Товаров в корзине</div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>