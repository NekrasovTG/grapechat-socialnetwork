<?php
session_start();
require 'config.php';

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    echo 'Вы не авторизованы.';
    exit();
}

$current_user_id = $_SESSION['user_id'];
$profile_user_id = $_GET['id'] ?? null;

if (!$profile_user_id) {
    echo 'Пользователь не найден.';
    exit();
}

// Получение информации о просматриваемом пользователе
$stmt = $pdo->prepare('SELECT username, birthdate, avatar, description FROM users WHERE id = ?');
$stmt->execute([$profile_user_id]);
$user_profile = $stmt->fetch();

if (!$user_profile) {
    echo 'Пользователь не найден.';
    exit();
}

// Проверка, являются ли пользователи друзьями
$stmt = $pdo->prepare('SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)');
$stmt->execute([$current_user_id, $profile_user_id, $profile_user_id, $current_user_id]);
$are_friends = $stmt->fetchColumn() !== false;

// Проверка, был ли уже отправлен запрос на дружбу
$stmt = $pdo->prepare('SELECT 1 FROM friend_requests WHERE (sender_id = ? AND receiver_id = ? OR sender_id = ? AND receiver_id = ?) AND status = "pending"');
$stmt->execute([$current_user_id, $profile_user_id, $profile_user_id, $current_user_id]);
$request_exists = $stmt->fetchColumn() !== false;

// Обработка отправки запроса на дружбу
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_request']) && !$request_exists && !$are_friends) {
    $stmt = $pdo->prepare('INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)');
    $stmt->execute([$current_user_id, $profile_user_id]);

    // Создание уведомления для получателя запроса
    $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
    $stmt->execute([$current_user_id]);
    $sender_username = $stmt->fetchColumn();
    $message = "Вы получили новый запрос на дружбу от " . htmlspecialchars($sender_username) . ".";
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
    $stmt->execute([$profile_user_id, $message]);

    header('Location: /user_profile.php?id=' . $profile_user_id);
    exit();
}

// Получение постов пользователя
$stmt = $pdo->prepare('SELECT id, content, created_at, image_path FROM posts WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$profile_user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <style>
        body {
            background-color: #1c1c1c;
            color: #d3d3d3;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            flex-direction: column;
        }

        .header {
            width: 100%;
            display: flex;
            justify-content: flex-end;
            padding: 20px;
            box-sizing: border-box;
            background-color: #1c1c1c;
            z-index: 100;
        }

        .home-button {
            background-color: #32cd32;
            color: #1c1c1c;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .home-button:hover {
            background-color: #28a428;
        }

        .content-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #1c1c1c;
            padding: 20px;
            box-sizing: border-box;
            width: 250px;
        }

        .profile-container {
            background-color: #2f2f2f;
            padding: 20px;
            border-radius: 5px;
            width: 100%;
            margin-bottom: 20px;
            text-align: center;
        }

        .profile-container h2 {
            color: #32cd32;
            margin-bottom: 20px;
        }

        .profile-info {
            margin-bottom: 10px;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .post-list {
            max-width: 600px;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        .post {
            background-color: #444;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            position: relative;
            text-align: left;
        }

        .post-info {
            font-size: 12px;
            color: #bbb;
            margin-top: 5px;
        }

        .post-image {
            max-width: 100%;
            margin-top: 10px;
            border-radius: 5px;
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

        @media (max-width: 768px) {
            .content-container {
                flex-direction: column;
                align-items: center;
            }

            .sidebar {
                width: 100%;
                padding: 10px;
            }

            .post-list {
                padding: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="home-button" onclick="window.location.href='/'">Главная</button>
    </div>

    <div class="content-container">
        <div class="sidebar">
            <div class="profile-container">
                <h2>Профиль пользователя</h2>
                <?php if ($user_profile['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($user_profile['avatar']); ?>" alt="Аватарка" class="avatar">
                <?php else: ?>
                    <img src="default-avatar.png" alt="Аватарка" class="avatar">
                <?php endif; ?>
                <div class="profile-info">
                    <strong>Имя пользователя:</strong> <?php echo htmlspecialchars($user_profile['username']); ?>
                </div>
                <div class="profile-info">
                    <strong>Дата рождения:</strong> <?php echo htmlspecialchars($user_profile['birthdate']); ?>
                </div>
                <div class="profile-info">
                    <strong>Описание:</strong> <?php echo htmlspecialchars($user_profile['description']); ?>
                </div>
                <?php if (!$are_friends && !$request_exists): ?>
                    <form action="" method="post">
                        <button type="submit" name="send_request" class="button">Добавить в друзья</button>
                    </form>
                <?php elseif ($request_exists): ?>
                    <p>Запрос на дружбу отправлен</p>
                <?php else: ?>
                    <p>Вы уже друзья</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="post-list">
            <h2>Посты пользователя</h2>
            <?php if (empty($posts)): ?>
                <p>Постов пока нет.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <?php echo htmlspecialchars($post['content']); ?>
                        <?php if ($post['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Image" class="post-image">
                        <?php endif; ?>
                        <div class="post-info">
                            <?php echo date('d M Y H:i', strtotime($post['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>