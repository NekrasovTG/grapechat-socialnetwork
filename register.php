<?php
session_start();
require 'config.php';

// Включение отображения ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $birthdate = $_POST['birthdate'];

    // Валидация ввода
    if (empty($username) || empty($password) || empty($birthdate)) {
        $error_message = 'Все поля обязательны для заполнения.';
    } else {
        try {
            // Проверка на уникальность имени пользователя
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error_message = 'Имя пользователя уже занято.';
            } else {
                // Хеширование пароля
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Вставка нового пользователя в базу данных
                $stmt = $pdo->prepare('INSERT INTO users (username, password, birthdate) VALUES (?, ?, ?)');
                $stmt->execute([$username, $hashed_password, $birthdate]);

                // Перенаправление на страницу входа после успешной регистрации
                header('Location: /login.html');
                exit();
            }
        } catch (PDOException $e) {
            $error_message = 'Ошибка регистрации: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - GrapesChat</title>
    <style>
        body {
            background-color: #1c1c1c;
            color: #d3d3d3;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .register-container {
            max-width: 400px;
            width: 100%;
            background-color: #2f2f2f;
            padding: 20px;
            border-radius: 5px;
        }

        h2 {
            color: #32cd32;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
            width: 100%;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: none;
            color: #333;
        }

        .button {
            background-color: #32cd32;
            color: #1c1c1c;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
            width: 100%;
        }

        .button:hover {
            background-color: #28a428;
        }

        .error-message {
            color: #ff6b6b;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Регистрация</h2>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="birthdate">Дата рождения:</label>
                <input type="date" name="birthdate" required>
            </div>
            <button type="submit" class="button">Зарегистрироваться</button>
        </form>
    </div>
</body>
</html>