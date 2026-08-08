<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit;
}

require_once __DIR__ . '/../../login/verify-user.php';
$userRoles = verificarUsuario($_SESSION['user']);
if ($userRoles['codTypeRoles'] == 0) {
    header("Location: ../../userScreen/home-user.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: adicionaCard.php');
    exit;
}
require_once '../../config.php';


try {
    $pdo = getDB();

    $id = $_POST['idhidden'];
    $tipoTopic = $_POST['type'];
    $nameTopic = trim($_POST['title']);
    $descTopic = trim($_POST['description']);

    $uploadDir = '../../img/';
    $uploadDirBD = 'img/';

    $imagemFile = $_FILES['image'] ?? null;

    if ($imagemFile && $imagemFile['error'] !== UPLOAD_ERR_NO_FILE) {
        $sqlBusca = "SELECT imgCard FROM topicCards WHERE id = ?";
        $stmtBusca = $pdo->prepare($sqlBusca);
        $stmtBusca->execute([$id]);

        $card = $stmtBusca->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            throw new Exception('Card não encontrado.');
        }

        $imagemAntiga = $card['imgCard'];

        if (!empty($imagemAntiga)) {

            $caminhoImagemAntiga = '../../' . $imagemAntiga;

            if (file_exists($caminhoImagemAntiga)) {
                unlink($caminhoImagemAntiga);
            }
        }

        // Nova imagem foi enviada

        $extensao = pathinfo($imagemFile['name'], PATHINFO_EXTENSION);
        $nomeUnico = uniqid() . '_' . time() . '.' . $extensao;
        $caminhoCompleto = $uploadDir . $nomeUnico;

        if (!move_uploaded_file($imagemFile['tmp_name'], $caminhoCompleto)) {
            throw new Exception('Erro ao salvar imagem');
        }
        $caminhoCompleto = $uploadDirBD . $nomeUnico;

        $sql = "
            UPDATE topicCards
            SET tipoTopic=?, imgCard=?, nameTopic=?, descTopic=?
            WHERE id=?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $tipoTopic,
            $caminhoCompleto,
            $nameTopic,
            $descTopic,
            $id
        ]);

    } else {

        // Nenhuma imagem nova foi enviada.
        // Mantém a imagem que já está no banco.

        $sql = "
            UPDATE topicCards
            SET tipoTopic=?, nameTopic=?, descTopic=?
            WHERE id=?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $tipoTopic,
            $nameTopic,
            $descTopic,
            $id
        ]);
    }

    $_SESSION['sucesso'] = "✅ Card '{$nameTopic}' salvo!";
} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
}

header("Location: adicionaCard.php");
exit;
