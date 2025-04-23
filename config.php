<?php
     $host = 'localhost'; // или ваш хост
     $db = 'skinsbase'; // имя вашей базы данных
     $user = 'skinsbase'; // имя пользователя базы данных
     $pass = 'skins1256'; // пароль пользователя базы данных
     $charset = 'utf8mb4';

     $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
     $options = [
         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES   => false,
     ];

     try {
         $pdo = new PDO($dsn, $user, $pass, $options);
     } catch (\PDOException $e) {
         throw new \PDOException($e->getMessage(), (int)$e->getCode());
     }
     ?>