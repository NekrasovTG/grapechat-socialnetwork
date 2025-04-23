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
$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    echo 'Пост не найден.';
    exit();
}

// Получение информации о посте
$stmt = $pdo->prepare('SELECT posts.content, posts.created_at, posts.image_path, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?');
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    echo 'Пост не найден.';
    exit();
}

// Обработка добавления комментария
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_content'])) {
    $comment_content = trim($_POST['comment_content']);

    if ($comment_content !== '') {
        $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
        $stmt->execute([$post_id, $user_id, $comment_content]);

        // Уведомление автора поста
        $stmt = $pdo->prepare('SELECT user_id FROM posts WHERE id = ?');
        $stmt->execute([$post_id]);
        $post_author_id = $stmt->fetchColumn();

        if ($post_author_id && $post_author_id != $user_id) {
            $message = "Ваш пост был прокомментирован.";
            $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
            $stmt->execute([$post_author_id, $message]);
        }

        // Перенаправление на главную страницу после добавления комментария
        header('Location: /index.php');
        exit();
    } else {
        echo 'Комментарий не может быть пустым.';
    }
}

// Получение комментариев к посту
$stmt = $pdo->prepare('SELECT comments.content, comments.created_at, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY comments.created_at ASC');
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пост - GrapesChat</title>
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

        .post-container {
            max-width: 600px;
            width: 100%;
            background-color: #2f2f2f;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .post-content {
            margin-bottom: 20px;
        }

        .post-image {
            max-width: 100%;
            margin-top: 10px;
            border-radius: 5px;
        }

        .comment-form {
            margin-bottom: 20px;
            width: 100%;
        }

        .comment-form textarea {
            width: 100%;
            height: 70px;
            padding: 8px;
            border-radius: 5px;
            border: none;
            margin-bottom: 10px;
            resize: none;
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

        .comments-container {
            max-width: 600px;
            width: 100%;
        }

        .comment-item {
            background-color: #444;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .comment-info {
            font-size: 12px;
            color: #bbb;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="post-container">
        <div class="post-content">
            <p><?php echo htmlspecialchars($post['content']); ?></p>
            <?php if ($post['image_path']): ?>
                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Image" class="post-image">
            <?php endif; ?>
            <div class="comment-info">
                — <?php echo htmlspecialchars($post['username']); ?>, <?php echo date('d M Y H:i', strtotime($post['created_at'])); ?>
            </div>
        </div>

        <form action="" method="post" class="comment-form">
            <textarea name="comment_content" placeholder="Напишите комментарий..." required></textarea>
            <button type="submit" class="button">Отправить</button>
        </form>
    </div>

    <div class="comments-container">
        <h2>Комментарии</h2>
        <?php foreach ($comments as $comment): ?>
            <div class="comment-item">
                <p><?php echo htmlspecialchars($comment['content']); ?></p>
                <div class="comment-info">
                    — <?php echo htmlspecialchars($comment['username']); ?>, <?php echo date('d M Y H:i', strtotime($comment['created_at'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>