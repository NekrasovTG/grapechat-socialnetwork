<?php
session_start();
require 'config.php';

// Включение отображения ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверка авторизации пользователя
$user = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT username, role FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

try {
    // Получение постов
    $stmt = $pdo->query('SELECT posts.id, posts.content, posts.created_at, posts.user_id, posts.image_path, users.username,
                        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count
                        FROM posts 
                        JOIN users ON posts.user_id = users.id 
                        ORDER BY posts.created_at DESC');
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение новостей
    $stmt = $pdo->query('SELECT news.id, news.title, news.content, news.created_at, users.username FROM news JOIN users ON news.created_by = users.id ORDER BY news.created_at DESC');
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo 'Ошибка базы данных: ' . $e->getMessage();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like_post_id'])) {
    $like_post_id = $_POST['like_post_id'];

    if ($user_id) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM likes WHERE post_id = ? AND user_id = ?');
            $stmt->execute([$like_post_id, $user_id]);
            $like = $stmt->fetch();

            if ($like) {
                $stmt = $pdo->prepare('DELETE FROM likes WHERE post_id = ? AND user_id = ?');
                $stmt->execute([$like_post_id, $user_id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO likes (post_id, user_id) VALUES (?, ?)');
                $stmt->execute([$like_post_id, $user_id]);

                $stmt = $pdo->prepare('SELECT user_id FROM posts WHERE id = ?');
                $stmt->execute([$like_post_id]);
                $post_author_id = $stmt->fetchColumn();

                if ($post_author_id && $post_author_id != $user_id) {
                    $message = "Ваш пост получил новый лайк.";
                    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
                    $stmt->execute([$post_author_id, $message]);
                }
            }
        } catch (Exception $e) {
            echo 'Ошибка базы данных: ' . $e->getMessage();
            exit();
        }
    }
    header('Location: /index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Социальная сеть GrapesChat</title>
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

        .header {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
            background-color: #1c1c1c;
            z-index: 100;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .menu-button {
            font-weight: bold;
            font-size: 40px;
            color: #32cd32;
            background-color: transparent;
            border: none;
            cursor: pointer;
            transition: color 0.3s;
            position: absolute;
            right: 20px;
        }

        .menu-button:hover {
            color: #28a428;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 20px;
            top: 60px;
            background-color: #2f2f2f;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .dropdown-item {
            color: #d3d3d3;
            padding: 10px 20px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .dropdown-item:hover {
            background-color: #444;
        }

        .title {
            color: #32cd32;
            font-size: 32px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
            transition: color 0.3s;
        }

        .title:hover {
            color: #28a428;
        }

        .content {
            display: flex;
            justify-content: center;
            width: 100%;
            max-width: 1200px;
            padding: 20px;
            box-sizing: border-box;
        }

        .news-section {
            flex-basis: 250px;
            background-color: #2f2f2f;
            padding: 20px;
            margin-right: 20px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
        }

        .news-title {
            color: #32cd32;
            font-size: 24px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }

        .news-item {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #444;
            border-radius: 5px;
        }

        .main-content {
            flex-grow: 1;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .post-list {
            width: 100%;
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

        .post-image {
            max-width: 100%;
            margin-top: 10px;
            border-radius: 5px;
        }

        .like-button, .comment-button {
            background-color: #32cd32;
            color: #fff;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            border-radius: 25px;
            font-weight: bold;
            font-size: 14px;
            transition: background-color 0.3s, transform 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            margin-top: 10px;
            margin-right: 5px;
        }

        .like-button:hover, .comment-button:hover {
            background-color: #28a428;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .username-link {
            color: #32cd32;
            text-decoration: none;
        }

        .username-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .content {
                flex-direction: column;
                align-items: center;
            }

            .news-section {
                margin-right: 0;
                width: 100%;
                margin-bottom: 20px;
                max-height: 200px;
            }

            .news-items {
                display: none;
            }
        }
    </style>
    <script>
        function toggleMenu() {
            var menu = document.getElementById('dropdownMenu');
            if (menu.style.display === 'none' || menu.style.display === '') {
                menu.style.display = 'block';
            } else {
                menu.style.display = 'none';
            }
        }

        function toggleNews() {
            var newsItems = document.getElementById('newsItems');
            if (newsItems.style.display === 'none' || newsItems.style.display === '') {
                newsItems.style.display = 'block';
            } else {
                newsItems.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="header">
        <div class="title">GrapesChat</div>
        <button class="menu-button" onclick="toggleMenu()">≡</button>
        <div id="dropdownMenu" class="dropdown-menu">
            <a href="/" class="dropdown-item">Главная</a>
            <a href="/search.php" class="dropdown-item">Поиск</a>
            <?php if ($user): ?>
                <a href="/notifications.php" class="dropdown-item">Уведомления</a>
                <a href="/friends.php" class="dropdown-item">Мои друзья</a>
                <a href="/chats.php" class="dropdown-item">Чаты</a>
                <a href="/profile.php" class="dropdown-item">Мой профиль</a>
                <?php if ($user['role'] === 'moderator'): ?>
                    <a href="/moderation.php" class="dropdown-item">Модерация</a>
                <?php endif; ?>
                <a href="/logout.php" class="dropdown-item">Выйти</a>
            <?php else: ?>
                <a href="/login.html" class="dropdown-item">Войти</a>
                <a href="/register.html" class="dropdown-item">Зарегистрироваться</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="content">
        <div class="news-section">
            <div class="news-title" onclick="toggleNews()">
                Новости
                <span>▼</span>
            </div>
            <div id="newsItems" class="news-items">
                <?php if ($user && $user['role'] === 'moderator'): ?>
                    <button class="button" onclick="window.location.href='/create_news.php'">Создать новость</button>
                <?php endif; ?>
                <?php foreach ($news as $item): ?>
                    <div class="news-item">
                        <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
                        <?php echo htmlspecialchars($item['content']); ?><br>
                        <small><?php echo htmlspecialchars($item['username']); ?>, <?php echo date('d M Y H:i', strtotime($item['created_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="main-content">
            <h2>Посты</h2>
            <div class="post-list" id="postList">
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <?php echo htmlspecialchars($post['content']); ?>
                        <?php if ($post['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Image" class="post-image">
                        <?php endif; ?>
                        <div class="post-info">
                            — <a href="/user_profile.php?id=<?php echo $post['user_id']; ?>" class="username-link"><?php echo htmlspecialchars($post['username']); ?></a>, <?php echo date('d M Y H:i', strtotime($post['created_at'])); ?>
                        </div>
                        <?php if ($user_id): ?>
                            <div>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="like_post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="like-button">
                                        Лайк (<?php echo $post['like_count']; ?>)
                                    </button>
                                </form>
                                <button class="comment-button" onclick="window.location.href='/post.php?id=<?php echo $post['id']; ?>'">Комментировать</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>