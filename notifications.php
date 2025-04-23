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

// Получение уведомлений пользователя
$stmt = $pdo->prepare('SELECT message, created_at, read_status FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обновление статуса уведомлений на "прочитано"
$stmt = $pdo->prepare('UPDATE notifications SET read_status = "read" WHERE user_id = ? AND read_status = "unread"');
$stmt->execute([$user_id]);
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

        .notification-item.unread {
            background-color: #444;
        }

        .notification-item p {
            margin: 0;
        }

        .notification-item small {
            color: #bbb;
        }
    </style>
</head>
<body>
    <div class="notifications-container">
        <h2>Уведомления</h2>
        <?php if (empty($notifications)): ?>
            <p>Уведомлений пока нет.</p>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['read_status'] == 'unread' ? 'unread' : ''; ?>">
                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                    <small><?php echo date('d M Y H:i', strtotime($notification['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>