<?php
require_once 'config.php';

// Проверка администратора
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin_dachnyinventary']) {
    header('Location: login.php');
    exit();
}

$message = '';

try {
    // Проверяем существование таблицы настроек
    $stmt = $pdo->query("SHOW TABLES LIKE 'door_site_settings'");
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        $message = "Таблица настроек не существует. Создайте её через SQL: <br><br>
        CREATE TABLE door_site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value VARCHAR(20),
            description VARCHAR(255)
        );";
    } else {
        // Проверяем наличие настроек
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM door_site_settings");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            // Добавляем настройки по умолчанию
            $default_settings = [
                ['primary_color', '#3d3d3d', 'Основной цвет (header, кнопки)'],
                ['secondary_color', '#707070', 'Вторичный цвет (акценты)'],
                ['accent_color', '#ff9800', 'Акцентный цвет (корзина, акции)'],
                ['text_color', '#333333', 'Цвет текста'],
                ['background_color', '#f5f5f5', 'Цвет фона'],
                ['card_color', '#ffffff', 'Цвет карточек товаров'],
                ['button_color', '#3d3d3d', 'Цвет кнопок'],
                ['error_color', '#f44336', 'Цвет ошибок'],
                ['success_color', '#4caf50', 'Цвет успеха']
            ];
            
            foreach ($default_settings as $setting) {
                $stmt = $pdo->prepare("INSERT INTO door_site_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
                $stmt->execute($setting);
            }
            
            $message = "Настройки оформления успешно установлены!";
        } else {
            $message = "Настройки оформления уже существуют.";
        }
        
        // Проверяем наличие поля can_edit_colors в таблице пользователей
        $stmt = $pdo->query("SHOW COLUMNS FROM door_users LIKE 'can_edit_colors'");
        $column_exists = $stmt->fetch();
        
        if (!$column_exists) {
            $message .= "<br><br>Необходимо добавить поле can_edit_colors в таблицу door_users:<br>
            ALTER TABLE door_users ADD COLUMN can_edit_colors BOOLEAN DEFAULT FALSE;<br><br>
            И дать права администратору:<br>
            UPDATE door_users SET can_edit_colors = TRUE WHERE username = 'admin_dacha';";
        } else {
            // Даем права администратору
            $stmt = $pdo->prepare("UPDATE door_users SET can_edit_colors = TRUE WHERE username = 'admin_dacha'");
            $stmt->execute();
            $message .= "<br><br>Права на редактирование цветов обновлены для администратора.";
        }
    }
} catch (PDOException $e) {
    $message = "Ошибка: " . $e->getMessage();
}
?>

<?php include 'header.php'; ?>

<div style="max-width: 800px; margin: 40px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <h1 style="color: #2e7d32; margin-bottom: 30px;">Установка настроек оформления</h1>
    
    <div style="padding: 20px; background-color: #f9f9f9; border-radius: 5px; margin-bottom: 20px;">
        <?php echo $message; ?>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="admin.php" style="padding: 10px 20px; background-color: #4caf50; color: white; text-decoration: none; border-radius: 5px;">Вернуться в админку</a>
    </div>
</div>

<?php include 'footer.php'; ?>