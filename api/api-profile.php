<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../userScreen/calcularXp.php';

$email = $_SESSION['user'];
$targetUsername = $_GET['username'] ?? null;

if (!$targetUsername) {
    echo json_encode(['error' => 'Username não informado']);
    exit();
}

try {
    $pdo = getDB();

    // Busca dados do usuário alvo pelo nomeDeUsuario (nunca expõe email)
    $stmtUser = $pdo->prepare("
        SELECT u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil, u.bannerPerfil,
               COALESCE(ul.userLevel, 1) as userLevel,
               COALESCE(up.userPoints, 0) as userPoints,
               (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
               (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following,
               (SELECT COUNT(*) FROM userFollowers uf2 WHERE uf2.emailFollower = :me AND uf2.emailFollowed = u.email) as estou_seguindo
        FROM user u
        LEFT JOIN userLevel ul ON u.email = ul.emailLevel
        LEFT JOIN userPoints up ON u.email = up.emailPoints
        WHERE u.nomeDeUsuario = :username
    ");
    $stmtUser->execute(['me' => $email, 'username' => $targetUsername]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit();
    }

    // Pega o email do alvo internamente (nunca retorna ao client)
    $stmtEmail = $pdo->prepare("SELECT email FROM user WHERE nomeDeUsuario = :username");
    $stmtEmail->execute(['username' => $targetUsername]);
    $targetEmail = $stmtEmail->fetchColumn();

    // Posição no ranking (baseado em XP)
    $stmtRank = $pdo->prepare("
        SELECT COUNT(*) + 1 
        FROM userPoints up
        WHERE up.userPoints > :myPoints
    ");
    $stmtRank->execute(['myPoints' => $userData['userPoints']]);
    $userData['rank'] = $stmtRank->fetchColumn();

    // XP formatado
    $userData['xpFormatado'] = formatarXP($userData['userPoints']);
    $xpNivelAtual = xpNecessario($userData['userLevel']);
    $xpProximoNivel = xpNecessario($userData['userLevel'] + 1);
    $xpDelta = $xpProximoNivel - $xpNivelAtual;
    $xpProgresso = $userData['userPoints'] - $xpNivelAtual;
    $userData['xpPorcentagem'] = $xpDelta > 0 ? round(max(0, min(100, ($xpProgresso / $xpDelta) * 100)), 1) : 100;
    $userData['xpProximoNivel'] = $xpProximoNivel;
    $userData['xpProximoNivelFormatado'] = formatarXP($xpProximoNivel);

    // Verifica se é o próprio usuário
    $userData['isMe'] = ($targetEmail === $email);

    // Artigos completados recentemente
    $stmtArtigos = $pdo->prepare("
        SELECT a.id, a.titulo, a.xp_recompensa, up2.status, up2.data_tentativa
        FROM usuario_progresso up2
        JOIN artigo a ON a.id = up2.id_artigo
        WHERE up2.email_usuario = :targetEmail AND up2.status = 'aprovado'
        ORDER BY up2.data_tentativa DESC
        LIMIT 6
    ");
    $stmtArtigos->execute(['targetEmail' => $targetEmail]);
    $userData['artigosCompletados'] = $stmtArtigos->fetchAll(PDO::FETCH_ASSOC);

    // Tópicos mais feitos (agrupando pelo tópico do artigo via topiccards)
    $stmtTopicos = $pdo->prepare("
        SELECT tc.nameTopic as topico, COUNT(*) as total
        FROM usuario_progresso up2
        JOIN artigo a ON a.id = up2.id_artigo
        JOIN topiccards tc ON tc.id = a.id_topic
        WHERE up2.email_usuario = :targetEmail AND up2.status = 'aprovado'
        GROUP BY tc.id, tc.nameTopic
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmtTopicos->execute(['targetEmail' => $targetEmail]);
    $userData['topicosFrequentes'] = $stmtTopicos->fetchAll(PDO::FETCH_ASSOC);

    // Publicações no fórum (aprovadas)
    $order = ($_GET['order'] ?? 'recentes') === 'relevantes' 
        ? 'ORDER BY (pf.likes_post - pf.deslikes_post) DESC, pf.created_at DESC' 
        : 'ORDER BY pf.created_at DESC';

    $stmtPosts = $pdo->prepare("
        SELECT pf.id, pf.topic_post, pf.titulo_post, pf.desc_post, pf.likes_post, pf.deslikes_post, pf.reposts, pf.created_at,
               (SELECT img_caminho FROM imgs_post WHERE id_post = pf.id LIMIT 1) as primeira_img
        FROM postagens_forum pf
        WHERE pf.nome_usuario_post = :username AND pf.avaliacao_adm = 'Aprovado'
        $order
        LIMIT 10
    ");
    $stmtPosts->execute(['username' => $targetUsername]);
    $userData['publicacoes'] = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

    // Reposts do usuário
    $stmtReposts = $pdo->prepare("
        SELECT pf.id, pf.nome_usuario_post, pf.topic_post, pf.titulo_post, pf.desc_post, pf.likes_post, pf.deslikes_post, pf.reposts, pf.created_at,
               (SELECT img_caminho FROM imgs_post WHERE id_post = pf.id LIMIT 1) as primeira_img
        FROM interacao_post ip
        JOIN postagens_forum pf ON ip.id_post = pf.id
        WHERE ip.nome_usuario = :username AND ip.tipo = 'Repost' AND pf.avaliacao_adm = 'Aprovado'
        ORDER BY pf.created_at DESC
        LIMIT 10
    ");
    $stmtReposts->execute(['username' => $targetUsername]);
    $userData['reposts'] = $stmtReposts->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($userData);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro interno']);
}
