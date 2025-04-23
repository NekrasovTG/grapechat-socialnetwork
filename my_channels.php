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

try {
    // Получение всех каналов, которыми владеет пользователь
    $stmt = $pdo->prepare('SELECT id, name, description FROM channels WHERE owner_id = ?');
    $stmt->execute([$user_id]);
    $owned_channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение всех каналов, на которые подписан пользователь
    $stmt = $pdo->prepare('SELECT channels.id, channels.name, channels.description FROM channels
                           JOIN channel_subscriptions ON channels.id = channel_subscriptions.channel_id
                           WHERE channel_subscriptions.user_id = ?');
    $stmt->execute([$user_id]);
    $subscribed_channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Ошибка базы данных: ' . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои каналы - GrapesChat</title>
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

        .channels-container {
            max-width: 600px;
            width: 100%;
        }

        .channel-item {
            background-color: #2f2f2f;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .channel-item a {
            color: #32cd32;
            text-decoration: none;
        }

        .channel-item a:hover {
            text-decoration: underline;
        }

        .channel-description {
            color: #bbb;
        }
    </style>
</head>
<body>
    <div class="channels-container">
        <h2>Мои каналы</h2>
        <?php if (empty($owned_channels) && empty($subscribed_channels)): ?>
            <p>Вы пока не создали и не подписаны ни на один канал.</p>
        <?php else: ?>
            <?php if (!empty($owned_channels)): ?>
                <h3>Каналы, которыми вы владеете</h3>
                <?php foreach ($owned_channels as $channel): ?>
                    <div class="channel-item">
                        <a href="/channel.php?id=<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['name']); ?></a>
                        <p class="channel-description"><?php echo htmlspecialchars($channel['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($subscribed_channels)): ?>
                <h3>Каналы, на которые вы подписаны</h3>
                <?php foreach ($subscribed_channels as $channel): ?>
                    <div class="channel-item">
                        <a href="/channel.php?id=<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['name']); ?></a>
                        <p class="channel-description"><?php echo htmlspecialchars($channel['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>