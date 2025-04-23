<?php
require 'config.php';

$stmt = $pdo->query('SELECT content FROM posts ORDER BY created_at DESC');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($posts);
?>