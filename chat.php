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
$chat_id = $_GET['id'] ?? null;

if (!$chat_id) {
    echo 'Чат не найден.';
    exit();
}

// Проверка, что пользователь является участником чата
$stmt = $pdo->prepare('SELECT 1 FROM chat_users WHERE chat_id = ? AND user_id = ?');
$stmt->execute([$chat_id, $user_id]);
$is_member = $stmt->fetchColumn();

if (!$is_member) {
    echo 'У вас нет доступа к этому чату.';
    exit();
}

// Получение сообщений из чата
$stmt = $pdo->prepare('SELECT messages.content, messages.created_at, users.username FROM messages
                       JOIN users ON messages.user_id = users.id
                       WHERE messages.chat_id = ?
                       ORDER BY messages.created_at ASC');
$stmt->execute([$chat_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка отправки сообщения
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message_content = trim($_POST['message']);

    if ($message_content !== '') {
        try {
            $stmt = $pdo->prepare('INSERT INTO messages (chat_id, user_id, content) VALUES (?, ?, ?)');
            $stmt->execute([$chat_id, $user_id, $message_content]);
            header('Location: /chat.php?id=' . $chat_id);
            exit();
        } catch (PDOException $e) {
            echo 'Ошибка базы данных: ' . $e->getMessage();
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат - GrapesChat</title>
    <style>
        body {
            background-color: #1c1c1c;
            color: #d3d3d3;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
        }

        .chat-container {
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .message-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            background-color: #2f2f2f;
            border-radius: 5px;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column-reverse; /* Изменяет направление потока сообщений */
        }

        .message {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #444;
            border-radius: 5px;
            position: relative;
        }

        .message-info {
            font-size: 12px;
            color: #bbb;
            margin-top: 5px;
        }

        .message-form {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #2f2f2f;
            border-radius: 5px;
        }

        .message-form textarea {
            flex-grow: 1;
            height: 50px;
            padding: 8px;
            border-radius: 5px;
            border: none;
            resize: none;
            margin-right: 10px;
        }

        .button {
            background-color: #32cd32;
            color: #1c1c1c;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #28a428;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="message-list">
            <?php foreach ($messages as $message): ?>
                <div class="message">
                    <?php echo htmlspecialchars($message['content']); ?>
                    <div class="message-info">
                        — <?php echo htmlspecialchars($message['username']); ?>, <?php echo date('d M Y H:i', strtotime($message['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <form action="" method="post" class="message-form">
            <textarea name="message" placeholder="Введите сообщение..." required></textarea>
            <button type="submit" class="button">Отправить</button>
        </form>
    </div>
</body>
</html>