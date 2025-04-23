<?php
session_start();
require 'config.php';

// Включение отображения ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// Обработка создания канала
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_channel'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if ($name !== '' && $description !== '') {
        try {
            $stmt = $pdo->prepare('INSERT INTO channels (name, description, owner_id) VALUES (?, ?, ?)');
            $stmt->execute([$name, $description, $user_id]);
            header('Location: /my_channels.php');
            exit();
        } catch (PDOException $e) {
            $error_message = 'Ошибка создания канала: ' . $e->getMessage();
        }
    } else {
        $error_message = 'Название и описание канала не могут быть пустыми.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать канал - GrapesChat</title>
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

        .channel-form-container {
            max-width: 600px;
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

        .form-group input[type="text"],
        .form-group textarea {
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
    <div class="channel-form-container">
        <h2>Создать канал</h2>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <div class="form-group">
                <label for="name">Название канала:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Описание:</label>
                <textarea name="description" rows="4" required></textarea>
            </div>
            <button type="submit" name="create_channel" class="button">Создать</button>
        </form>
    </div>
</body>
</html>