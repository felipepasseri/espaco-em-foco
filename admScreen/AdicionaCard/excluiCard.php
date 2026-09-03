<?php
    header('Content-Type: application/json');

    require_once '../../config.php';

    try {
        $pdo = getDB();

        $id = $_POST['id'] ?? null;

        if (!$id) {
            throw new Exception("ID não informado.");
        }

        $stmt = $pdo->prepare(
            "SELECT imgCard FROM topicCards WHERE id = ?"
        );

        $stmt->execute([$id]);

        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            throw new Exception("Card não encontrado.");
        }

        $caminhoImagem = $card['imgCard'];

        $caminhoFisico = __DIR__ . '/../../' . $caminhoImagem;

        
        if (file_exists($caminhoFisico)) {
            if (!unlink($caminhoFisico)) {
                throw new Exception("Não foi possível apagar a imagem.");
            }
        }

        $stmt = $pdo->prepare("DELETE FROM topicCards WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['sucesso'] = "✅ Card '{$nameTopic}' salvo!";

    } catch (Exception $e) {
        $_SESSION['erro'] = $e->getMessage();
    }
?>