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
        SELECT u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
               COALESCE(ul.userlevel, 1) as userlevel,
               COALESCE(up.userpoints, 0) as userpoints,
               (SELECT COUNT(*) FROM userfollowers WHERE emailFollowed = u.email) AS total_followers,
               (SELECT COUNT(*) FROM userfollowers WHERE emailFollower = u.email) AS total_following,
               (SELECT COUNT(*) FROM userfollowers uf2 WHERE uf2.emailFollower = :me AND uf2.emailFollowed = u.email) as estou_seguindo
        FROM user u
        LEFT JOIN userlevel ul ON u.email = ul.emailLevel
        LEFT JOIN userpoints up ON u.email = up.emailPoints
        ORDER BY up.userpoints DESC, u.nomeDeUsuario ASC
        LIMIT :limit
    ");
    $stmtTop->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtTop->bindValue(':me', $email, PDO::PARAM_STR);
    $stmtTop->execute();
    $topUsers = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    $rankPosition = 1;
    foreach ($topUsers as &$user) {
        $user['rank'] = $rankPosition++;
    }

    // Busca os SEUS dados exatos (Para o rodapé e para o destaque de "Você")
    $stmtMe = $pdo->prepare("
        SELECT u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
               COALESCE(ul.userlevel, 1) as userlevel,
               COALESCE(up.userpoints, 0) as userpoints
        FROM user u
        LEFT JOIN userlevel ul ON u.email = ul.emailLevel
        LEFT JOIN userpoints up ON u.email = up.emailPoints
        WHERE u.email = :me
    ");
    $stmtMe->execute(['me' => $email]);
    $myData = $stmtMe->fetch(PDO::FETCH_ASSOC);

    $myRank = 0;
    if ($myData) {
        $stmtMyRank = $pdo->prepare("
            SELECT COUNT(*) + 1 
            FROM userpoints up
            WHERE up.userpoints > :myPoints
        ");
        $stmtMyRank->execute([
            'myPoints' => $myData['userpoints']
        ]);
        $myRank = $stmtMyRank->fetchColumn();
        $myData['rank'] = $myRank;
    }

    echo json_encode(['topUsers' => $topUsers, 'me' => $myData]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro interno']);
}
