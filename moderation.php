<?php
session_start();
require 'config.php';

// Получение информации о пользователе и проверка роли
if (!isset($_SESSION['user_id'])) {
    echo 'Вы не авторизованы.';
    exit();
}

$user_id = $_SESSION['user_id'];

// Получение роли пользователя из базы данных
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'moderator') {
    echo 'У вас нет доступа к этой странице.';
    exit();
}

// Получение всех постов для модерации
try {
    $stmt = $pdo->query('SELECT posts.id, posts.content, posts.created_at, posts.user_id, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC');
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
    exit();
}

// Обработка удаления поста
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post_id'])) {
    $delete_post_id = $_POST['delete_post_id'];
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->execute([$delete_post_id]);
    header('Location: /moderation.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Модерация - GrapesChat</title>
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
        }

        .moderation-container {
            max-width: 800px;
            width: 100%;
            padding: 20px;
            background-color: #2f2f2f;
            border-radius: 5px;
            margin-top: 20px;
        }

        .post {
            background-color: #444;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            position: relative;
        }

        .post-info {
            font-size: 12px;
            color: #bbb;
            margin-top: 5px;
        }

        .delete-button {
            background-color: #ff4c4c;
            color: #fff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 12px;
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .delete-button:hover {
            background-color: #e60000;
        }
    </style>
</head>
<body>
    <div class="moderation-container">
        <h2>Модерация постов</h2>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <?php echo htmlspecialchars($post['content']); ?>
                <div class="post-info">
                    — <?php echo htmlspecialchars($post['username']); ?>, <?php echo date('d M Y H:i', strtotime($post['created_at'])); ?>
                </div>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete_post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" class="delete-button">Удалить</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>