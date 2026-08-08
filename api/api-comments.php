<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$email = $_SESSION['user'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    // Pega o username
    $stmtMe = $pdo->prepare("SELECT nomeDeUsuario FROM user WHERE email = :email");
    $stmtMe->execute(['email' => $email]);
    $myUsername = $stmtMe->fetchColumn();

    if ($method === 'GET') {
        // Buscar comentários de um post
        $postId = $_GET['post_id'] ?? null;
        $parentId = $_GET['parent_id'] ?? null;
        $offset = (int)($_GET['offset'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 5);

        if (!$postId) {
            echo json_encode(['error' => 'post_id obrigatório']);
            exit();
        }

        if ($parentId !== null) {
            // Busca respostas de um comentário específico
            $stmt = $pdo->prepare("
                SELECT cp.id, cp.nome_usuario, cp.comentario, cp.likes, cp.created_at,
                       u.fotoPerfil, u.nome, u.sobrenome,
                       COALESCE(ul.userLevel, 1) as userLevel,
                       COALESCE(up2.userPoints, 0) as userPoints,
                       (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
                       (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following,
                       (SELECT COUNT(*) FROM comentarios_post WHERE parent_id = cp.id) as total_respostas,
                       (SELECT COUNT(*) FROM interacao_post WHERE nome_usuario = :myUser AND id_comentario = cp.id AND tipo = 'Like') as eu_curti
                FROM comentarios_post cp
                JOIN user u ON u.nomeDeUsuario = cp.nome_usuario
                LEFT JOIN userLevel ul ON ul.emailLevel = u.email
                LEFT JOIN userPoints up2 ON up2.emailPoints = u.email
                WHERE cp.id_post = :postId AND cp.parent_id = :parentId
                ORDER BY cp.created_at ASC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
            $stmt->bindValue(':parentId', $parentId, PDO::PARAM_INT);
            $stmt->bindValue(':myUser', $myUsername, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        } else {
            // Busca comentários raíz (sem parent)
            $stmt = $pdo->prepare("
                SELECT cp.id, cp.nome_usuario, cp.comentario, cp.likes, cp.created_at,
                       u.fotoPerfil, u.nome, u.sobrenome,
                       COALESCE(ul.userLevel, 1) as userLevel,
                       COALESCE(up2.userPoints, 0) as userPoints,
                       (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
                       (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following,
                       (SELECT COUNT(*) FROM comentarios_post WHERE parent_id = cp.id) as total_respostas,
                       (SELECT COUNT(*) FROM interacao_post WHERE nome_usuario = :myUser AND id_comentario = cp.id AND tipo = 'Like') as eu_curti
                FROM comentarios_post cp
                JOIN user u ON u.nomeDeUsuario = cp.nome_usuario
                LEFT JOIN userLevel ul ON ul.emailLevel = u.email
                LEFT JOIN userPoints up2 ON up2.emailPoints = u.email
                WHERE cp.id_post = :postId AND cp.parent_id IS NULL
                ORDER BY cp.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
            $stmt->bindValue(':myUser', $myUsername, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comments as &$comment) {
            $comment['tempo_relativo'] = tempoRelativo($comment['created_at']);
            if (empty($comment['fotoPerfil'])) {
                $comment['fotoPerfil'] = 'img/user-profile-default.jpg';
            }
        }

        echo json_encode(['comments' => $comments]);

    } elseif ($method === 'POST') {
        // Criar comentário
        $data = json_decode(file_get_contents('php://input'), true);
        $postId = $data['post_id'] ?? null;
        $comentario = trim($data['comentario'] ?? '');
        $parentId = $data['parent_id'] ?? null;

        if (!$postId || !$comentario) {
            echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
            exit();
        }

        $stmt = $pdo->prepare("
            INSERT INTO comentarios_post (id_post, parent_id, nome_usuario, comentario)
            VALUES (:postId, :parentId, :username, :comentario)
        ");
        $stmt->execute([
            'postId' => $postId,
            'parentId' => $parentId ?: null,
            'username' => $myUsername,
            'comentario' => $comentario
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    }

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro interno']);
}

function tempoRelativo($datetime) {
    $agora = time();
    $tempo = strtotime($datetime);
    $diff = $agora - $tempo;
    if ($diff < 60) return 'agora';
    if ($diff < 3600) return floor($diff / 60) . 'min';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    if ($diff < 2592000) return floor($diff / 604800) . 'sem';
    return date('d/m/Y', $tempo);
}
