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
    $acao = $_POST['acao'] ?? '';

    if (!$id) {
        throw new Exception('ID do artigo não informado.');
    }

    if (!in_array($acao, ['aprovar', 'reprovar'])) {
        throw new Exception('Ação inválida.');
    }

    $novoStatus = ($acao === 'aprovar') ? 'Aprovado' : 'Reprovado';

    $stmt = $pdo->prepare("UPDATE artigo SET avaliacao_adm = ? WHERE id = ?");
    $stmt->execute([$novoStatus, $id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Artigo não encontrado ou já com este status.');
    }

    $msg = ($novoStatus === 'Aprovado') ? 'Artigo aprovado com sucesso!' : 'Artigo reprovado com sucesso.';
    echo json_encode([
        'sucesso' => true,
        'mensagem' => $msg,
        'novoStatus' => $novoStatus
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}

