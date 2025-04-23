<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; // Сохраняем ID пользователя в сессии
        header('Location: /index.php'); // Перенаправляем на главную страницу
        exit();
    } else {
        header('Location: /login.html?error=Неверные данные для входа.');
    }
}
?>