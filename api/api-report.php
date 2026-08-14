<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$me = $_SESSION['user'];

// Aceita tanto JSON quanto multipart/form-data
$motivo = $_POST['motivo'] ?? null;
$nomeUsuarioAlvo = $_POST['nome_usuario_alvo'] ?? null;
$tipoAlvo = $_POST['tipo_alvo'] ?? null;
$categoria = $_POST['categoria'];

if (!$motivo || !$nomeUsuarioAlvo || !$tipoAlvo) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit();
}

try {
    $pdo = getDB();

    // Pega o nomeDeUsuario do denunciante
    $stmtMe = $pdo->prepare("SELECT nomeDeUsuario FROM user WHERE email = :email");
    $stmtMe->execute(['email' => $me]);
    $meuUsername = $stmtMe->fetchColumn();

    if (!$meuUsername) {
        echo json_encode(['success' => false, 'error' => 'Usuário denunciante não encontrado']);
        exit();
    }

    // Insere a denúncia
    $stmt = $pdo->prepare("
        INSERT INTO denuncias (nome_usuario_denunciante, nome_usuario_alvo, tipo_alvo, categoria_denuncia, motivo, status)
        VALUES (:denunciante, :alvo, :tipo_alvo, :categoria_denuncia, :motivo, 'Em Análise')
    ");
    $stmt->execute([
        'denunciante' => $meuUsername,
        'alvo' => $nomeUsuarioAlvo,
        'tipo_alvo' => $tipoAlvo,
        'categoria_denuncia' => $categoria,
        'motivo' => $motivo
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}
