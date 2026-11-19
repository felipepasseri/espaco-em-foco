<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
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
    $motivoRecusa = trim($_POST['motivo_recusa'] ?? 'Denúncia improcedente ou sem infração identificada.');

    if (!$id) {
        throw new Exception('ID da denúncia não informado.');
    }

    $sql = "UPDATE denuncias 
            SET status = 'Reprovado',
                resolucao_acao = 'Denúncia recusada',
                resolucao_obs = ?,
                data_resolucao = NOW(),
                admin_responsavel = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$motivoRecusa, $_SESSION['user'], $id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Denúncia não encontrada.');
    }

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Denúncia recusada com sucesso.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}
