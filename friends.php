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

// Получение списка друзей пользователя
$stmt = $pdo->prepare('SELECT users.id, users.username, users.avatar FROM friends
                       JOIN users ON friends.friend_id = users.id
                       WHERE friends.user_id = ?');
$stmt->execute([$user_id]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои друзья - GrapesChat</title>
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

        .friends-container {
            max-width: 600px;
            width: 100%;
        }

        .friend-item {
            background-color: #2f2f2f;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .friend-item a {
            color: #32cd32;
            text-decoration: none;
        }

        .friend-item a:hover {
            text-decoration: underline;
        }

        .friend-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }

        .friend-info {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="friends-container">
        <h2>Мои друзья</h2>
        <?php if (empty($friends)): ?>
            <p>У вас пока нет друзей.</p>
        <?php else: ?>
            <?php foreach ($friends as $friend): ?>
                <div class="friend-item">
                    <div class="friend-info">
                        <img src="<?php echo htmlspecialchars($friend['avatar']); ?>" alt="Аватарка" class="friend-avatar">
                        <a href="/user_profile.php?id=<?php echo $friend['id']; ?>"><?php echo htmlspecialchars($friend['username']); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>