<?php
    header('Content-Type: application/json');

    require_once '../config.php';

    try {
        $pdo = getDB();

        $id = $_POST['id'] ?? null;

        if (!$id) {
            throw new Exception("ID não informado.");
        }

        $stmt = $pdo->prepare("DELETE FROM topicCards WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            "sucesso" => true
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "sucesso" => false,
            "erro" => $e->getMessage()
        ]);
    }
?>