<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não autenticado.']);
    exit;
}

require_once '../config.php';
require_once '../login/verify-user.php';

try {
    $pdo = getDB();

    $userroles = verificarUsuario($_SESSION['user']);
    if (!$userroles || $userroles['codTypeRoles'] != 1) {
        throw new Exception('Acesso restrito a administradores.');
    }

    $id = $_POST['id'] ?? null;
    if (!$id) {
        throw new Exception("ID do tópico não informado.");
    }

    $stmt = $pdo->prepare("SELECT imgCard, nameTopic FROM topiccards WHERE id = ?");
    $stmt->execute([$id]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        throw new Exception("Tópico não encontrado.");
    }

    $caminhoImagem = $card['imgCard'];
    $caminhoFisico = __DIR__ . '/../' . $caminhoImagem;

    if (file_exists($caminhoFisico) && is_file($caminhoFisico)) {
        @unlink($caminhoFisico);
    }

    $stmt = $pdo->prepare("DELETE FROM topiccards WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode([
        'sucesso' => true,
        'mensagem' => "Tópico '{$card['nameTopic']}' excluído com sucesso!"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}
