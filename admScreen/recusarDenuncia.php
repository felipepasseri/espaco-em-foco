<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
    exit;
}

require_once '../config.php';
try {
    $pdo = getDB();
    $id = $_POST['id'] ?? null;
    if (!$id) {
        throw new Exception('ID da denúncia não informado.');
    }

    $sql = "UPDATE denuncias 
            SET status = 'Reprovado'
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

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