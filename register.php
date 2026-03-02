<?php
require_once 'config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Валидация
    if (empty($username)) {
        $errors['username'] = 'Имя пользователя обязательно';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Имя пользователя должно быть не менее 3 символов';
    } elseif (strlen($username) > 50) {
        $errors['username'] = 'Имя пользователя должно быть не более 50 символов';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email обязателен';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email адрес';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Пароль обязателен';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Пароли не совпадают';
    }
    
    // Проверка уникальности
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM door_users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $errors['general'] = 'Пользователь с таким именем или email уже существует';
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка при проверке данных: ' . $e->getMessage();
        }
    }
    
    // Регистрация
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO door_users (username, email, password_hash, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash, $full_name, $phone]);
            
            // Автоматический вход после регистрации
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM door_users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name']
            ];
            
            $success = true;
            
            // Переносим корзину из сессии в БД
            if (!empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $product_id => $item) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO door_cart (user_id, product_id, quantity) VALUES (?, ?, ?) 
                                              ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
                        $stmt->execute([$user_id, $product_id, $item['quantity']]);
                    } catch (PDOException $e) {
                        // Пропускаем ошибки при переносе корзины
                    }
                }
            }
            
            // Перенаправляем в каталог через 3 секунды
            header('Refresh: 3; URL=catalog.php');
            
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка при регистрации: ' . $e->getMessage();
        }
    }
}
?>

<?php include 'header.php'; ?>

<div style="max-width: 500px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 10px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
    
    <?php if ($success): ?>
        <div style="text-align: center; padding: 20px;">
            <div style="font-size: 48px; color: #2ecc71;">✓</div>
            <h2 style="color: #2c3e50; margin-bottom: 20px;">Регистрация успешна!</h2>
            <p style="color: #7f8c8d; margin-bottom: 30px;">Вы будете перенаправлены в каталог через 3 секунды...</p>
            <a href="catalog.php" style="color: #3498db; text-decoration: none;">Перейти сразу</a>
        </div>
    <?php else: ?>
        <h2 style="color: #2c3e50; margin-bottom: 30px; text-align: center;">Регистрация</h2>
        
        <?php if (!empty($errors['general'])): ?>
            <div style="background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Имя пользователя *</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                       style="width: 90%; padding: 12px; border: 2px solid <?php echo !empty($errors['username']) ? '#e74c3c' : '#e0e0e0'; ?>; border-radius: 8px; font-size: 16px;"
                       required>
                <?php if (!empty($errors['username'])): ?>
                    <div style="color: #e74c3c; font-size: 14px; margin-top: 5px;"><?php echo htmlspecialchars($errors['username']); ?></div>
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Email *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       style="width: 90%; padding: 12px; border: 2px solid <?php echo !empty($errors['email']) ? '#e74c3c' : '#e0e0e0'; ?>; border-radius: 8px; font-size: 16px;"
                       required>
                <?php if (!empty($errors['email'])): ?>
                    <div style="color: #e74c3c; font-size: 14px; margin-top: 5px;"><?php echo htmlspecialchars($errors['email']); ?></div>
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Полное имя</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                       style="width: 90%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Телефон</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                       style="width: 90%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Пароль *</label>
                <input type="password" name="password" 
                       style="width: 90%; padding: 12px; border: 2px solid <?php echo !empty($errors['password']) ? '#e74c3c' : '#e0e0e0'; ?>; border-radius: 8px; font-size: 16px;"
                       required>
                <?php if (!empty($errors['password'])): ?>
                    <div style="color: #e74c3c; font-size: 14px; margin-top: 5px;"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500;">Подтверждение пароля *</label>
                <input type="password" name="confirm_password" 
                       style="width: 90%; padding: 12px; border: 2px solid <?php echo !empty($errors['confirm_password']) ? '#e74c3c' : '#e0e0e0'; ?>; border-radius: 8px; font-size: 16px;"
                       required>
                <?php if (!empty($errors['confirm_password'])): ?>
                    <div style="color: #e74c3c; font-size: 14px; margin-top: 5px;"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <button type="submit" 
                    style="width: 90%; padding: 15px; background-color: #3d3d3d; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background-color 0.3s;"
                    onmouseover="this.style.backgroundColor='#707070'"
                    onmouseout="this.style.backgroundColor='#3d3d3d'">
                Зарегистрироваться
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 25px; padding-top: 25px; border-top: 1px solid #eee;">
            <p style="color: #7f8c8d;">Уже есть аккаунт? <a href="login.php" style="color: #3498db; text-decoration: none;">Войти</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>