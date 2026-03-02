<?php
require_once 'config.php';

// ПРОСТОЙ ПАРОЛЬ: 123456
$simple_password = '123456';
$hashed_password = password_hash($simple_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO door_users 
        (username, email, password_hash, is_admin) 
        VALUES (?, ?, ?, ?)");
    
    $stmt->execute([
        'admin',
        'admin@liga.ru',
        $hashed_password,
        1
    ]);
    
    echo "НОВЫЙ АДМИН СОЗДАН!<br>";
    echo "Логин: admin<br>";
    echo "Пароль: 123456<br>";
    echo "УДАЛИТЕ ЭТОТ ФАЙЛ ПОСЛЕ СОЗДАНИЯ!";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>