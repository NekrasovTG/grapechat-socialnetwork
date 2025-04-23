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
$channel_id = $_GET['id'] ?? null;

if (!$channel_id) {
    echo 'Канал не найден.';
    exit();
}

// Получение информации о канале
$stmt = $pdo->prepare('SELECT id, name, description, owner_id FROM channels WHERE id = ?');
$stmt->execute([$channel_id]);
$channel = $stmt->fetch();

if (!$channel) {
    echo 'Канал не найден.';
    exit();
}

// Проверка, является ли пользователь владельцем канала
$is_owner = ($channel['owner_id'] == $user_id);

// Получение количества подписчиков
$stmt = $pdo->prepare('SELECT COUNT(*) FROM channel_subscriptions WHERE channel_id = ?');
$stmt->execute([$channel_id]);
$subscriber_count = $stmt->fetchColumn();

// Получение постов канала
$stmt = $pdo->prepare('SELECT channel_posts.content, channel_posts.created_at, users.username FROM channel_posts
                       JOIN users ON channel_posts.user_id = users.id
                       WHERE channel_posts.channel_id = ?
                       ORDER BY channel_posts.created_at ASC');
$stmt->execute([$channel_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Проверка подписки на канал
$stmt = $pdo->prepare('SELECT 1 FROM channel_subscriptions WHERE channel_id = ? AND user_id = ?');
$stmt->execute([$channel_id, $user_id]);
$is_subscribed = $stmt->fetchColumn();

// Обработка подписки/отписки на канал
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['subscribe']) && !$is_subscribed) {
        $stmt = $pdo->prepare('INSERT INTO channel_subscriptions (user_id, channel_id) VALUES (?, ?)');
        $stmt->execute([$user_id, $channel_id]);
    } elseif (isset($_POST['unsubscribe']) && $is_subscribed) {
        $stmt = $pdo->prepare('DELETE FROM channel_subscriptions WHERE user_id = ? AND channel_id = ?');
        $stmt->execute([$user_id, $channel_id]);
    } elseif (isset($_POST['create_post']) && $is_owner) {
        $post_content = trim($_POST['post_content']);
        if ($post_content !== '') {
            $stmt = $pdo->prepare('INSERT INTO channel_posts (channel_id, user_id, content) VALUES (?, ?, ?)');
            $stmt->execute([$channel_id, $user_id, $post_content]);
            header('Location: /channel.php?id=' . $channel_id);
            exit();
        } else {
            $error_message = 'Содержание поста не может быть пустым.';
        }
    }
    header('Location: /channel.php?id=' . $channel_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($channel['name']); ?> - GrapesChat</title>
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

        .channel-container {
            max-width: 600px;
            width: 100%;
            background-color: #2f2f2f;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .post-container {
            max-width: 600px;
            width: 100%;
            background-color: #2f2f2f;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 10px;
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
            margin-top: 20px;
        }

        .button:hover {
            background-color: #28a428;
        }

        .post-info {
            font-size: 12px;
            color: #bbb;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 15px;
            width: 100%;
        }

        .form-group textarea {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: none;
            margin-bottom: 10px;
            resize: none;
        }

        .error-message {
            color: #ff6b6b;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="channel-container">
        <h2><?php echo htmlspecialchars($channel['name']); ?></h2>
        <p><?php echo htmlspecialchars($channel['description']); ?> | Подписчиков: <?php echo $subscriber_count; ?></p>

        <form action="" method="post">
            <?php if (!$is_subscribed): ?>
                <button type="submit" name="subscribe" class="button">Подписаться на канал</button>
            <?php else: ?>
                <button type="submit" name="unsubscribe" class="button">Отписаться от канала</button>
            <?php endif; ?>
        </form>

        <?php if ($is_owner): ?>
            <h3>Создать пост</h3>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <form action="" method="post">
                <div class="form-group">
                    <textarea name="post_content" rows="4" placeholder="Напишите что-нибудь..."></textarea>
                </div>
                <button type="submit" name="create_post" class="button">Опубликовать</button>
            </form>
        <?php endif; ?>
    </div>

    <h3>Посты канала</h3>
    <?php foreach ($posts as $post): ?>
        <div class="post-container">
            <?php echo htmlspecialchars($post['content']); ?>
            <div class="post-info">
                — <?php echo htmlspecialchars($post['username']); ?>, <?php echo date('d M Y H:i', strtotime($post['created_at'])); ?>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>