<?php
session_start();
require 'config.php';

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['chat_name']) && !empty($_POST['username'])) {
    $chat_name = $_POST['chat_name'];
    $username = $_POST['username'];

    // Поиск пользователя по нику
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $other_user = $stmt->fetch();

    if ($other_user) {
        // Создание нового чата
        $stmt = $pdo->prepare('INSERT INTO chats (name) VALUES (?)');
        $stmt->execute([$chat_name]);
        $chat_id = $pdo->lastInsertId();

        // Добавление обоих пользователей в чат (например, в таблицу chat_users, если потребуется)
        // Здесь предполагается, что у вас может быть такая таблица для хранения участников чата
        // Пример: $stmt = $pdo->prepare('INSERT INTO chat_users (chat_id, user_id) VALUES (?, ?), (?, ?)');
        // $stmt->execute([$chat_id, $user_id, $chat_id, $other_user['id']]);

        header('Location: /chat.php?id=' . $chat_id);
        exit();
    } else {
        $error = 'Пользователь не найден.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать новый чат - GrapesChat</title>
    <style>
        body {
            background-color: #1c1c1c;
            color: #d3d3d3;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background-color: #2f2f2f;
            padding: 20px;
            border-radius: 5px;
            width: 300px;
            text-align: center;
        }

        .form-container h2 {
            color: #32cd32;
            margin-bottom: 20px;
        }

        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            border-radius: 5px;
        }

        .form-container button {
            background-color: #32cd32;
            color: #1c1c1c;
            border: none;
            padding: 10px;
            width: 100%;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .form-container button:hover {
            background-color: #28a428;
        }

        .error-message {
            color: #ff4c4c;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Создать новый чат</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="create_chat.php" method="post">
            <input type="text" name="chat_name" placeholder="Название чата" required>
            <input type="text" name="username" placeholder="Имя пользователя для добавления" required>
            <button type="submit">Создать чат</button>
        </form>
    </div>
</body>
</html>