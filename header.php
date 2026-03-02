<?php
require_once 'config.php';

// Подсчитываем товары в корзине
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог дверей</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .header {
            background: #3D3D3D;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s;
            padding: 8px 12px;
            border-radius: 5px;
        }
        
        .nav a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .cart-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cart-count {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: <?php echo $cart_count > 0 ? 'inline-flex' : 'none'; ?>;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            position: absolute;
            top: -8px;
            right: -8px;
        }
        
        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .user-info:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background-color: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-radius: 8px;
            padding: 10px 0;
            display: none;
            z-index: 1001;
        }
        
        .user-menu:hover .user-dropdown {
            display: block;
        }
        
        .user-dropdown a {
            display: block;
            padding: 10px 20px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .user-dropdown a:hover {
            background-color: #f5f5f5;
        }
        
        .main-container {
            min-height: calc(100vh - 150px);
        }
        
        .footer {
            background: #3D3D3D;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="catalog.php" class="logo">
                <span style="font-size: 28px;"><svg width="35" height="30" viewBox="0 0 75 72" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M34.1263 33.5V42.7253M34.1368 12.0879H12C6.47715 12.0879 2 16.5651 2 22.0879V60C2 65.5228 6.47715 70 12 70H30.8632C36.386 70 40.8632 65.5228 40.8632 60V59.1648M34.1368 12.0879C37.8517 12.0879 40.8632 15.0994 40.8632 18.8142V59.1648M34.1368 12.0879C34.1368 6.56507 38.614 2 44.1368 2H63C68.5228 2 73 6.47715 73 12V49.1648C73 54.6877 68.5228 59.1648 63 59.1648H40.8632M59.5 31.5165H68.8895" stroke="white" stroke-width="4"/>
</svg></span>
                <span>Лига дверей</span>
            </a>
            
            <div class="nav">
                    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="admin.php" class="admin-link"> Администрирование</a>
                <?php endif; ?>
                <a href="catalog.php">Каталог</a>
                <a href="cart.php" class="cart-link">
                    🛒 Корзина
                    <span id="cart-count" class="cart-count"><?php echo $cart_count; ?></span>
                </a>
                
                <?php if ($is_logged_in): ?>
                    <div class="user-menu">


                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars($current_user['username']); ?></span>
                        </div>
                        <div class="user-dropdown">
                            <a href="profile.php">Профиль</a>
                            <a href="logout.php">Выйти</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display: flex; gap: 10px;">
                        <a href="login.php">Войти</a>
                        <a href="register.php" style="background-color: rgba(255,255,255,0.2);">Регистрация</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="main-container">