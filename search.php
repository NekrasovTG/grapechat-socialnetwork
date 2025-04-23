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

// Получение поискового запроса
$search_query = $_GET['query'] ?? '';

if ($search_query !== '') {
    // Поиск пользователей
    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE username LIKE ?');
    $stmt->execute(['%' . $search_query . '%']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Поиск каналов
    $stmt = $pdo->prepare('SELECT id, name, description FROM channels WHERE name LIKE ?');
    $stmt->execute(['%' . $search_query . '%']);
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Поиск групп
    $stmt = $pdo->prepare('SELECT id, name, description FROM `groups` WHERE name LIKE ?');
    $stmt->execute(['%' . $search_query . '%']);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $users = [];
    $channels = [];
    $groups = [];
}

// Обработка запроса на вступление в группу
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join_group_id'])) {
    $join_group_id = $_POST['join_group_id'];

    // Проверка, является ли пользователь уже участником группы
    $stmt = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
    $stmt->execute([$join_group_id, $user_id]);
    $is_member = $stmt->fetchColumn();

    if (!$is_member) {
        $stmt = $pdo->prepare('INSERT INTO group_members (group_id, user_id) VALUES (?, ?)');
        $stmt->execute([$join_group_id, $user_id]);
        header('Location: /group.php?id=' . $join_group_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск - GrapesChat</title>
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

        .search-container {
            max-width: 600px;
            width: 100%;
            margin-bottom: 20px;
        }

        .item {
            background-color: #2f2f2f;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .item a {
            color: #32cd32;
            text-decoration: none;
        }

        .item a:hover {
            text-decoration: underline;
        }

        .description {
            color: #bbb;
        }

        .search-form {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-form input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border-radius: 5px 0 0 5px;
            border: none;
            outline: none;
            font-size: 16px;
            color: #333;
        }

        .search-form .button {
            background-color: #32cd32;
            color: #1c1c1c;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 0 5px 5px 0;
            transition: background-color 0.3s;
            font-size: 16px;
            font-weight: bold;
        }

        .search-form .button:hover {
            background-color: #28a428;
        }
    </style>
</head>
<body>
    <h2>Поиск</h2>
    <form method="get" action="" class="search-form">
        <input type="text" name="query" placeholder="Поиск пользователей, каналов и групп..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit" class="button">Поиск</button>
    </form>

    <div class="search-container">
        <?php if ($search_query !== ''): ?>
            <h3>Пользователи</h3>
            <?php if (empty($users)): ?>
                <p>Пользователи не найдены.</p>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="item">
                        <a href="/user_profile.php?id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <h3>Каналы</h3>
            <?php if (empty($channels)): ?>
                <p>Каналы не найдены.</p>
            <?php else: ?>
                <?php foreach ($channels as $channel): ?>
                    <div class="item">
                        <a href="/channel.php?id=<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['name']); ?></a>
                        <p class="description"><?php echo htmlspecialchars($channel['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <h3>Группы</h3>
            <?php if (empty($groups)): ?>
                <p>Группы не найдены.</p>
            <?php else: ?>
                <?php foreach ($groups as $group): ?>
                    <div class="item">
                        <a href="/group.php?id=<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></a>
                        <p class="description"><?php echo htmlspecialchars($group['description']); ?></p>
                        <form method="post" action="">
                            <input type="hidden" name="join_group_id" value="<?php echo $group['id']; ?>">
                            <button type="submit" class="button">Вступить</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>