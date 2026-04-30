<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}
require_once 'config.php';

try {
    $pdo = getDB();

    $tipoTopic = $_POST['type'];
    $nameTopic = trim($_POST['title']);
    $descTopic = trim($_POST['description']);

    $uploadDir = 'img/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imagemFile = $_FILES['image'];

    $extensao = pathinfo($imagemFile['name'], PATHINFO_EXTENSION);
    $nomeUnico = uniqid() . '_' . time() . '.' . $extensao;
    $caminhoCompleto = $uploadDir . $nomeUnico;

    if (!move_uploaded_file($imagemFile['tmp_name'], $caminhoCompleto)) {
        throw new Exception('Erro ao salvar imagem');
    }

    $sql = "INSERT INTO topicCards (tipoTopic, imgCard, nameTopic, descTopic) 
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tipoTopic, $caminhoCompleto, $nameTopic, $descTopic]);

    $_SESSION['sucesso'] = "✅ Card '{$nameTopic}' salvo!";
} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
}
