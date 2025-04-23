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
$group_id = $_GET['id'] ?? null;

if (!$group_id) {
    echo 'Группа не найдена.';
    exit();
}

// Получение информации о группе
$stmt = $pdo->prepare('SELECT id, name, description, owner_id FROM `groups` WHERE id = ?');
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    echo 'Группа не найдена.';
    exit();
}

// Проверка, является ли пользователь участником группы
$stmt = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
$stmt->execute([$group_id, $user_id]);
$is_member = $stmt->fetchColumn();

if (!$is_member) {
    echo 'Вы не являетесь участником этой группы.';
    exit();
}

// Получение участников группы
$stmt = $pdo->prepare('SELECT users.username FROM group_members
                       JOIN users ON group_members.user_id = users.id
                       WHERE group_members.group_id = ?');
$stmt->execute([$group_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение сообщений группы
$stmt = $pdo->prepare('SELECT group_messages.content, group_messages.created_at, users.username FROM group_messages
                       JOIN users ON group_messages.user_id = users.id
                       WHERE group_messages.group_id = ?
                       ORDER BY group_messages.created_at ASC');
$stmt->execute([$group_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка отправки сообщения
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && $is_member) {
    $message_content = trim($_POST['message']);
    if ($message_content !== '') {
        $stmt = $pdo->prepare('INSERT INTO group_messages (group_id, user_id, content) VALUES (?, ?, ?)');
        $stmt->execute([$group_id, $user_id, $message_content]);
        header('Location: /group.php?id=' . $group_id);
        exit();
    } else {
        $error_message = 'Сообщение не может быть пустым.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['name']); ?> - GrapesChat</title>
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
            height: 100vh;
        }

        .group-container {
            max-width: 600px;
            width: 100%;
            background-color: #2f2f2f;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            background-color: #2f2f2f;
            border-radius: 5px;
            margin-bottom: 10px;
            width: 100%;
            max-width: 600px;
        }

        .message {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #444;
            border-radius: 5px;
            position: relative;
        }

        .message-info {
            font-size: 12px;
            color: #bbb;
            margin-top: 5px;
        }

        .message-form {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #2f2f2f;
            border-radius: 5px;
            width: 100%;
            max-width: 600px;
        }

        .message-form textarea {
            flex-grow: 1;
            height: 50px;
            padding: 8px;
            border-radius: 5px;
            border: none;
            resize: none;
            margin-right: 10px;
        }

        .button {
            background-color: #32cd32;
            color: #1c1c1c;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #28a428;
        }

        .error-message {
            color: #ff6b6b;
            margin-bottom: 15px;
        }

        .back-button {
            align-self: flex-start;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <button class="button back-button" onclick="window.location.href='/profile.php'">Назад</button>

    <div class="group-container">
        <h2><?php echo htmlspecialchars($group['name']); ?></h2>
        <p><?php echo htmlspecialchars($group['description']); ?></p>

        <h3>Участники</h3>
        <ul>
            <?php foreach ($members as $member): ?>
                <li><?php echo htmlspecialchars($member['username']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="message-list">
        <?php foreach ($messages as $message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message['content']); ?>
                <div class="message-info">
                    — <?php echo htmlspecialchars($message['username']); ?>, <?php echo date('d M Y H:i', strtotime($message['created_at'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($is_member): ?>
        <form action="" method="post" class="message-form">
            <textarea name="message" placeholder="Введите сообщение..." required></textarea>
            <button type="submit" class="button">Отправить</button>
        </form>
    <?php else: ?>
        <p>Вступите в группу, чтобы просматривать и отправлять сообщения.</p>
    <?php endif; ?>
</body>
</html>