<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$email = $_SESSION['user'];
$type = $_GET['type'] ?? 'all'; // all, liked, repost
$topic = $_GET['topic'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $pdo = getDB();

    // Pega o username do usuário logado
    $stmtMe = $pdo->prepare("SELECT nomeDeUsuario FROM user WHERE email = :email");
    $stmtMe->execute(['email' => $email]);
    $myUsername = $stmtMe->fetchColumn();

    $params = [];
    $where = "pf.avaliacao_adm = 'Aprovado'";

    if ($type === 'liked') {
        // Posts que o usuário curtiu
        $joinClause = "JOIN interacao_post ip ON ip.id_post = pf.id AND ip.nome_usuario = :myUser AND ip.tipo = 'Like'";
        $params['myUser'] = $myUsername;
    } elseif ($type === 'repost') {
        // Posts que o usuário repostou
        $joinClause = "JOIN interacao_post ip ON ip.id_post = pf.id AND ip.nome_usuario = :myUser AND ip.tipo = 'Repost'";
        $params['myUser'] = $myUsername;
    } else {
        $joinClause = "";
    }

    // Filtro por tópico
    if ($topic && $topic !== 'all') {
        $where .= " AND pf.topic_post = :topic";
        $params['topic'] = $topic;
    }

    // Filtro por pesquisa
    if ($search) {
        $where .= " AND (pf.titulo_post LIKE :search OR pf.desc_post LIKE :search2)";
        $params['search'] = "%$search%";
        $params['search2'] = "%$search%";
    }

    $sql = "
        SELECT pf.id, pf.nome_usuario_post, pf.topic_post, pf.titulo_post, pf.desc_post, 
               pf.likes_post, pf.deslikes_post, pf.reposts, pf.created_at,
               u.fotoPerfil, u.nome, u.sobrenome,
               (SELECT img_caminho FROM imgs_post WHERE id_post = pf.id LIMIT 1) as primeira_img
        FROM postagens_forum pf
        $joinClause
        JOIN user u ON u.nomeDeUsuario = pf.nome_usuario_post
        WHERE $where
        ORDER BY pf.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue(":$key", $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcula "tempo relativo" para cada post
    foreach ($posts as &$post) {
        $post['tempo_relativo'] = tempoRelativo($post['created_at']);
        if (empty($post['fotoPerfil'])) {
            $post['fotoPerfil'] = 'img/user-profile-default.jpg';
        }
    }

    echo json_encode(['posts' => $posts, 'page' => $page]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro interno' . $e->getMessage()]);
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
