<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado.']);
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

    $id = $_GET['id'] ?? null;
    if (!$id) {
        throw new Exception('ID da denúncia não informado.');
    }

    $sql = "
        SELECT d.*,
               u_alvo.email as email_alvo,
               u_alvo.nome as nome_alvo,
               u_alvo.fotoPerfil as foto_alvo,
               u_den.email as email_denunciante,
               u_den.nome as nome_denunciante,
               u_den.fotoPerfil as foto_denunciante
        FROM denuncias d
        LEFT JOIN user u_alvo ON u_alvo.nomeDeUsuario = d.nome_usuario_alvo
        LEFT JOIN user u_den ON u_den.nomeDeUsuario = d.nome_usuario_denunciante
        WHERE d.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    $denuncia = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$denuncia) {
        throw new Exception('Denúncia não encontrada.');
    }

    echo json_encode([
        'sucesso' => true,
        'denuncia' => $denuncia
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}
