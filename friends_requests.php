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

// Получение входящих запросов на дружбу
$stmt = $pdo->prepare('SELECT friend_requests.id, users.username, users.id AS sender_id FROM friend_requests
                       JOIN users ON friend_requests.sender_id = users.id
                       WHERE friend_requests.receiver_id = ? AND friend_requests.status = "pending"');
$stmt->execute([$user_id]);
$friend_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка принятия или отклонения запроса на дружбу
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $sender_id = $_POST['sender_id'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        try {
            // Принять запрос
            $stmt = $pdo->prepare('UPDATE friend_requests SET status = "accepted" WHERE id = ?');
            $stmt->execute([$request_id]);

            // Добавить в друзья
            $stmt = $pdo->prepare('INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)');
            $stmt->execute([$user_id, $sender_id, $sender_id, $user_id]);

            // Создать чат между пользователями
            $chat_name = "Чат между пользователями $user_id и $sender_id";
            $stmt = $pdo->prepare('INSERT INTO chats (name) VALUES (?)');
            $stmt->execute([$chat_name]);
            $chat_id = $pdo->lastInsertId();

            // Добавить пользователей в чат
            $stmt = $pdo->prepare('INSERT INTO chat_users (chat_id, user_id) VALUES (?, ?), (?, ?)');
            $stmt->execute([$chat_id, $user_id, $chat_id, $sender_id]);

            header('Location: /notifications.php');
            exit();
        } catch (PDOException $e) {
            echo 'Ошибка базы данных: ' . $e->getMessage();
            exit();
        }
    } elseif ($action === 'reject') {
        // Отклонить запрос
        $stmt = $pdo->prepare('UPDATE friend_requests SET status = "rejected" WHERE id = ?');
        $stmt->execute([$request_id]);
        header('Location: /notifications.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Уведомления - GrapesChat</title>
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

        .notifications-container {
            max-width: 600px;
            width: 100%;
        }

        .notification-item {
            background-color: #2f2f2f;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .notification-item p {
            margin: 0;
        }

        .button {
            background-color: #32cd32;
            color: #1c1c1c;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin-right: 5px;
        }

        .button:hover {
            background-color: #28a428;
        }
    </style>
</head>
<body>
    <div class="notifications-container">
        <h2>Запросы на дружбу</h2>
        <?php if (empty($friend_requests)): ?>
            <p>Запросов на дружбу пока нет.</p>
        <?php else: ?>
            <?php foreach ($friend_requests as $request): ?>
                <div class="notification-item">
                    <p>Запрос на дружбу от <?php echo htmlspecialchars($request['username']); ?></p>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="sender_id" value="<?php echo $request['sender_id']; ?>">
                        <button type="submit" name="action" value="accept" class="button">Принять</button>
                        <button type="submit" name="action" value="reject" class="button">Отклонить</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>