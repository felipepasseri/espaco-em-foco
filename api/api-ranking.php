<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$email = $_SESSION['user'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit > 50) $limit = 50;

try {
    $pdo = getDB();

    // Busca o Ranking e descobre se VOCÊ já segue cada um deles
    $stmtTop = $pdo->prepare("
        SELECT u.email, u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
               COALESCE(ul.userLevel, 1) as userLevel,
               COALESCE(up.userPoints, 0) as userPoints,
               (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
               (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following,
               (SELECT COUNT(*) FROM userFollowers uf2 WHERE uf2.emailFollower = :me AND uf2.emailFollowed = u.email) as estou_seguindo
        FROM user u
        LEFT JOIN userLevel ul ON u.email = ul.emailLevel
        LEFT JOIN userPoints up ON u.email = up.emailPoints
        ORDER BY ul.userLevel DESC, up.userPoints DESC, u.nomeDeUsuario ASC
        LIMIT :limit
    ");
    $stmtTop->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtTop->bindValue(':me', $email, PDO::PARAM_STR);
    $stmtTop->execute();
    $topUsers = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    $rankPosition = 1;
    foreach ($topUsers as &$user) {
        $user['rank'] = $rankPosition++;
        // Mantivemos o e-mail aqui para o botão de "Seguir" funcionar!
    }

    // Busca os SEUS dados exatos (Para o rodapé e para o destaque de "Você")
    $stmtMe = $pdo->prepare("
        SELECT u.email, u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
               COALESCE(ul.userLevel, 1) as userLevel,
               COALESCE(up.userPoints, 0) as userPoints
        FROM user u
        LEFT JOIN userLevel ul ON u.email = ul.emailLevel
        LEFT JOIN userPoints up ON u.email = up.emailPoints
        WHERE u.email = :me
    ");
    $stmtMe->execute(['me' => $email]);
    $myData = $stmtMe->fetch(PDO::FETCH_ASSOC);

    $myRank = 0;
    if ($myData) {
        $stmtMyRank = $pdo->prepare("
            SELECT COUNT(*) + 1 
            FROM userLevel ul
            JOIN userPoints up ON ul.emailLevel = up.emailPoints
            WHERE ul.userLevel > :myLevel
               OR (ul.userLevel = :myLevel AND up.userPoints > :myPoints)
        ");
        $stmtMyRank->execute([
            'myLevel' => $myData['userLevel'],
            'myPoints' => $myData['userPoints']
        ]);
        $myRank = $stmtMyRank->fetchColumn();
        $myData['rank'] = $myRank;
    }

    echo json_encode(['topUsers' => $topUsers, 'me' => $myData]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro interno']);
}
