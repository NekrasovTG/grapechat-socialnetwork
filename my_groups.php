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
    // Получение всех групп, которыми владеет пользователь
    $stmt = $pdo->prepare('SELECT id, name, description FROM `groups` WHERE owner_id = ?');
    $stmt->execute([$user_id]);
    $owned_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение всех групп, на которые подписан пользователь
    $stmt = $pdo->prepare('SELECT `groups`.id, `groups`.name, `groups`.description FROM `groups`
                           JOIN group_members ON `groups`.id = group_members.group_id
                           WHERE group_members.user_id = ?');
    $stmt->execute([$user_id]);
    $subscribed_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Мои группы - GrapesChat</title>
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

        .groups-container {
            max-width: 600px;
            width: 100%;
        }

        .group-item {
            background-color: #2f2f2f;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .group-item a {
            color: #32cd32;
            text-decoration: none;
        }

        .group-item a:hover {
            text-decoration: underline;
        }

        .group-description {
            color: #bbb;
        }
    </style>
</head>
<body>
    <div class="groups-container">
        <h2>Мои группы</h2>
        <?php if (empty($owned_groups) && empty($subscribed_groups)): ?>
            <p>Вы пока не создали и не подписаны ни на одну группу.</p>
        <?php else: ?>
            <?php if (!empty($owned_groups)): ?>
                <h3>Группы, которыми вы владеете</h3>
                <?php foreach ($owned_groups as $group): ?>
                    <div class="group-item">
                        <a href="/group.php?id=<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></a>
                        <p class="group-description"><?php echo htmlspecialchars($group['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($subscribed_groups)): ?>
                <h3>Группы, на которые вы подписаны</h3>
                <?php foreach ($subscribed_groups as $group): ?>
                    <div class="group-item">
                        <a href="/group.php?id=<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></a>
                        <p class="group-description"><?php echo htmlspecialchars($group['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>