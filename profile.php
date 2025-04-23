<?php
session_start();
require 'config.php';

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    echo 'Вы не авторизованы.';
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Получение информации о текущем пользователе
$stmt = $pdo->prepare('SELECT username, birthdate, avatar, description FROM users WHERE id = ?');
$stmt->execute([$current_user_id]);
$user_profile = $stmt->fetch();

if (!$user_profile) {
    echo 'Пользователь не найден.';
    exit();
}

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $description = $_POST['description'];
    $avatar_path = $user_profile['avatar'];

    // Обработка загрузки аватарки
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['avatar']['type'], $allowed_types)) {
            $upload_dir = 'uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $avatar_path = $upload_dir . basename($_FILES['avatar']['name']);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path);
        }
    }

    $stmt = $pdo->prepare('UPDATE users SET avatar = ?, description = ? WHERE id = ?');
    $stmt->execute([$avatar_path, $description, $current_user_id]);
    header('Location: /profile.php');
    exit();
}

// Обработка создания поста
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post'])) {
    $content = $_POST['postContent'];
    $image_path = null;

    if (isset($_FILES['postImage']) && $_FILES['postImage']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['postImage']['type'], $allowed_types)) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $image_path = $upload_dir . basename($_FILES['postImage']['name']);
            move_uploaded_file($_FILES['postImage']['tmp_name'], $image_path);
        }
    }

    $stmt = $pdo->prepare('INSERT INTO posts (content, user_id, image_path) VALUES (?, ?, ?)');
    $stmt->execute([$content, $current_user_id, $image_path]);
    header('Location: /profile.php');
    exit();
}

// Получение постов текущего пользователя
$stmt = $pdo->prepare('SELECT id, content, created_at, image_path FROM posts WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$current_user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль</title>
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
            justify-content: flex-end;
        }

        .menu-button {
            font-weight: bold;
            font-size: 40px;
            color: #32cd32;
            background-color: transparent;
            border: none;
            cursor: pointer;
            transition: color 0.3s;
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

        .main-content {
            max-width: 600px;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .post-form {
            background-color: #2f2f2f;
            padding: 20px;
            border-radius: 5px;
            width: 100%;
            margin-bottom: 20px;
            text-align: center;
        }

        .post-form textarea, .post-form input[type="file"] {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: none;
            margin-bottom: 10px;
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
            padding: 12px 24px;
            cursor: pointer;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            transition: background-color 0.3s, transform 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .button:hover {
            background-color: #28a428;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: none;
            color: #333;
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

            .main-content {
                padding: 10px;
                width: 100%;
            }
        }
    </style>
    <script>
        function toggleDropdown() {
            var dropdown = document.getElementById('dropdownMenu');
            if (dropdown.style.display === 'none' || dropdown.style.display === '') {
                dropdown.style.display = 'block';
            } else {
                dropdown.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="header">
        <button class="menu-button" onclick="toggleDropdown()">≡</button>
        <div id="dropdownMenu" class="dropdown-menu">
            <a href="/" class="dropdown-item">Главная</a>
            <a href="/create_channel.php" class="dropdown-item">Создать канал</a>
            <a href="/create_group.php" class="dropdown-item">Создать группу</a>
            <a href="/my_channels.php" class="dropdown-item">Мои каналы</a>
            <a href="/my_groups.php" class="dropdown-item">Мои группы</a>
            <a href="/friends_requests.php" class="dropdown-item">Запросы на дружбу</a>
        </div>
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
            </div>

            <div class="profile-container">
                <h2>Обновить профиль</h2>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="avatar">Аватарка:</label>
                        <input type="file" name="avatar" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="description">Описание:</label>
                        <textarea name="description" rows="4"><?php echo htmlspecialchars($user_profile['description']); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="button">Обновить</button>
                </form>
            </div>
        </div>

        <div class="main-content">
            <div class="post-form">
                <h2>Создать пост</h2>
                <form action="" method="post" enctype="multipart/form-data">
                    <textarea name="postContent" placeholder="Что у вас нового?" required></textarea>
                    <input type="file" name="postImage" accept="image/*">
                    <button type="submit" name="create_post" class="button">Опубликовать</button>
                </form>
            </div>

            <div class="post-list">
                <h2>Мои посты</h2>
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
            </div>
        </div>
    </div>
</body>
</html>