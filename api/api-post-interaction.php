<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$email = $_SESSION['user'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false]);
    exit();
}

$action = $data['action'] ?? null; // like, deslike, repost, like_comment
$postId = $data['post_id'] ?? null;
$commentId = $data['comment_id'] ?? null;

try {
    $pdo = getDB();

    // Pega o username do usuário logado
    $stmtMe = $pdo->prepare("SELECT nomeDeUsuario FROM user WHERE email = :email");
    $stmtMe->execute(['email' => $email]);
    $myUsername = $stmtMe->fetchColumn();

    if (!$myUsername) {
        echo json_encode(['success' => false, 'error' => 'Usuário não encontrado']);
        exit();
    }

    if ($action === 'like' || $action === 'deslike' || $action === 'repost') {
        if (!$postId) {
            echo json_encode(['success' => false]);
            exit();
        }

        $tipoMap = ['like' => 'Like', 'deslike' => 'Deslike', 'repost' => 'Repost'];
        $tipo = $tipoMap[$action];

        // Verifica se já existe essa interação
        $stmtCheck = $pdo->prepare("
            SELECT id, tipo FROM interacao_post 
            WHERE nome_usuario = :user AND id_post = :postId AND tipo = :tipo
        ");
        $stmtCheck->execute(['user' => $myUsername, 'postId' => $postId, 'tipo' => $tipo]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $pdo->beginTransaction();

        if ($existing) {
            // Já existe → remove (toggle off)
            $stmtDel = $pdo->prepare("DELETE FROM interacao_post WHERE id = :id");
            $stmtDel->execute(['id' => $existing['id']]);

            // Atualiza contagem no post
            $coluna = $tipo === 'Like' ? 'likes_post' : ($tipo === 'Deslike' ? 'deslikes_post' : 'reposts');
            $stmtUpdate = $pdo->prepare("UPDATE postagens_forum SET $coluna = GREATEST(0, $coluna - 1) WHERE id = :postId");
            $stmtUpdate->execute(['postId' => $postId]);

            $pdo->commit();

            // Retorna contagens atualizadas
            $stmtCounts = $pdo->prepare("SELECT likes_post, deslikes_post, reposts FROM postagens_forum WHERE id = :postId");
            $stmtCounts->execute(['postId' => $postId]);
            $counts = $stmtCounts->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'toggled' => 'off', 'tipo' => $action, 'counts' => $counts]);
            exit();
        }

        // Se é like, remove deslike existente (e vice-versa)
        if ($action === 'like') {
            $stmtRemoveOpposite = $pdo->prepare("
                DELETE FROM interacao_post WHERE nome_usuario = :user AND id_post = :postId AND tipo = 'Deslike'
            ");
            $stmtRemoveOpposite->execute(['user' => $myUsername, 'postId' => $postId]);
            if ($stmtRemoveOpposite->rowCount() > 0) {
                $pdo->prepare("UPDATE postagens_forum SET deslikes_post = GREATEST(0, deslikes_post - 1) WHERE id = :postId")
                    ->execute(['postId' => $postId]);
            }
        } elseif ($action === 'deslike') {
            $stmtRemoveOpposite = $pdo->prepare("
                DELETE FROM interacao_post WHERE nome_usuario = :user AND id_post = :postId AND tipo = 'Like'
            ");
            $stmtRemoveOpposite->execute(['user' => $myUsername, 'postId' => $postId]);
            if ($stmtRemoveOpposite->rowCount() > 0) {
                $pdo->prepare("UPDATE postagens_forum SET likes_post = GREATEST(0, likes_post - 1) WHERE id = :postId")
                    ->execute(['postId' => $postId]);
            }
        }

        // Insere nova interação
        $stmtInsert = $pdo->prepare("
            INSERT INTO interacao_post (nome_usuario, id_post, tipo) VALUES (:user, :postId, :tipo)
        ");
        $stmtInsert->execute(['user' => $myUsername, 'postId' => $postId, 'tipo' => $tipo]);

        // Atualiza contagem
        $coluna = $tipo === 'Like' ? 'likes_post' : ($tipo === 'Deslike' ? 'deslikes_post' : 'reposts');
        $stmtUpdate = $pdo->prepare("UPDATE postagens_forum SET $coluna = $coluna + 1 WHERE id = :postId");
        $stmtUpdate->execute(['postId' => $postId]);

        $pdo->commit();

        // Retorna contagens atualizadas
        $stmtCounts = $pdo->prepare("SELECT likes_post, deslikes_post, reposts FROM postagens_forum WHERE id = :postId");
        $stmtCounts->execute(['postId' => $postId]);
        $counts = $stmtCounts->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'toggled' => 'on', 'tipo' => $action, 'counts' => $counts]);

    } elseif ($action === 'like_comment') {
        if (!$commentId) {
            echo json_encode(['success' => false]);
            exit();
        }

        // Verifica se já curtiu esse comentário
        $stmtCheck = $pdo->prepare("
            SELECT id FROM interacao_post 
            WHERE nome_usuario = :user AND id_comentario = :commentId AND tipo = 'Like'
        ");
        $stmtCheck->execute(['user' => $myUsername, 'commentId' => $commentId]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $pdo->beginTransaction();
        $toggle = null;
        if ($existing) {
            // Toggle off
            $pdo->prepare("DELETE FROM interacao_post WHERE id = :id")->execute(['id' => $existing['id']]);
            $pdo->prepare("UPDATE comentarios_post SET likes = GREATEST(0, likes - 1) WHERE id = :commentId")
                ->execute(['commentId' => $commentId]);
            $pdo->commit();
            $toggle = "off";
            // echo json_encode(['success' => true, 'toggled' => 'off']);
        } else {
            // Toggle on
            $stmtInsert = $pdo->prepare("
                INSERT INTO interacao_post (nome_usuario, id_comentario, tipo) VALUES (:user, :commentId, 'Like')
            ");
            $stmtInsert->execute(['user' => $myUsername, 'commentId' => $commentId]);
            $pdo->prepare("UPDATE comentarios_post SET likes = likes + 1 WHERE id = :commentId")
                ->execute(['commentId' => $commentId]);
            $pdo->commit();
            $toggle = "on";
            // echo json_encode(['success' => true, 'toggled' => 'on']);
        }

        // Retorna likes atualizados
        $stmtLikes = $pdo->prepare("SELECT likes FROM comentarios_post WHERE id = :commentId");
        $stmtLikes->execute(['commentId' => $commentId]);
        $likes = $stmtLikes->fetchColumn();
        echo json_encode(['success' => true, 'likes' => $likes, $toggle]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}
