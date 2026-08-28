<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../login/verify-user.php';
$userroles = verificarUsuario($_SESSION['user']);
if ($userroles['codTypeRoles'] == 0) {
    header("Location: ../userScreen/home-user.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: adicionaCard.php');
    exit;
}
require_once '../config.php';


try {
    $pdo = getDB();

    $tipoTopic = $_POST['type'];
    $nameTopic = trim($_POST['title']);
    $descTopic = trim($_POST['description']);

    $uploadDir = '../img/';
    $uploadDirBD = 'img/';

    $imagemFile = $_FILES['image'];

    $extensao = pathinfo($imagemFile['name'], PATHINFO_EXTENSION);
    $nomeUnico = uniqid() . '_' . time() . '.' . $extensao;
    $caminhoCompleto = $uploadDir . $nomeUnico;

    if (!move_uploaded_file($imagemFile['tmp_name'], $caminhoCompleto)) {
        throw new Exception('Erro ao salvar imagem');
    }
    $caminhoCompleto = $uploadDirBD . $nomeUnico;

    $sql = "INSERT INTO topiccards (tipoTopic, imgCard, nameTopic, descTopic) 
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tipoTopic, $caminhoCompleto, $nameTopic, $descTopic]);

    $_SESSION['sucesso'] = "✅ Card '{$nameTopic}' salvo!";
} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
}

header("Location: adicionaCard.php");
exit;
