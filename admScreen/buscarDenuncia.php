<?php

header('Content-Type: application/json');

require_once '../config.php';

try {
    $pdo = getDB();
    $id = $_GET['id'] ?? null;
    if (!$id) {
        throw new Exception('ID da denúncia não informado.');
    }

    $sql = "SELECT * FROM denuncias WHERE id = ?";
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