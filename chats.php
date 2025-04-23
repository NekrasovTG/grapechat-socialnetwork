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

// Получение списка чатов и имен участников, кроме текущего пользователя
$stmt = $pdo->prepare('SELECT chats.id, GROUP_CONCAT(users.username SEPARATOR ", ") AS participants
                       FROM chats
                       JOIN chat_users ON chats.id = chat_users.chat_id
                       JOIN users ON chat_users.user_id = users.id
                       WHERE chats.id IN (
                           SELECT chat_id FROM chat_users WHERE user_id = ?
                       ) AND users.id != ?
                       GROUP BY chats.id');
$stmt->execute([$user_id, $user_id]);
$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чаты - GrapesChat</title>
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

        .chats-container {
            max-width: 600px;
            width: 100%;
        }

        .chat-item {
            background-color: #2f2f2f;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .chat-item a {
            color: #32cd32;
            text-decoration: none;
        }

        .chat-item a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="chats-container">
        <h2>Ваши чаты</h2>
        <?php if (empty($chats)): ?>
            <p>У вас пока нет чатов.</p>
        <?php else: ?>
            <?php foreach ($chats as $chat): ?>
                <div class="chat-item">
                    <a href="/chat.php?id=<?php echo $chat['id']; ?>">
                        Чат с <?php echo htmlspecialchars($chat['participants']); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>