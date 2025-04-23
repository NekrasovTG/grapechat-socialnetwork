<?php
session_start();
require 'config.php';

// Проверка авторизации пользователя и роли модератора
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

// Обработка создания новости
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title']) && isset($_POST['content'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $stmt = $pdo->prepare('INSERT INTO news (title, content, created_by) VALUES (?, ?, ?)');
    $stmt->execute([$title, $content, $user_id]);
    header('Location: /index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать новость - GrapesChat</title>
    <style>
        body {
            background-color: #1c1c1c;
            color: #d3d3d3;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .news-form-container {
            background-color: #2f2f2f;
            padding: 20px;
            border-radius: 5px;
            width: 400px;
            text-align: center;
        }

        .news-form-container h2 {
            color: #32cd32;
            margin-bottom: 20px;
        }

        .news-form input[type="text"],
        .news-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: none;
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
    </style>
</head>
<body>
    <div class="news-form-container">
        <h2>Создать новость</h2>
        <form action="" method="post" class="news-form">
            <input type="text" name="title" placeholder="Заголовок" required>
            <textarea name="content" placeholder="Текст новости" rows="5" required></textarea>
            <button type="submit" class="button">Создать</button>
        </form>
    </div>
</body>
</html>