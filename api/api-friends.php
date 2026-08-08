<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$email = $_SESSION['user'];
$showAll = isset($_GET['all']); // Se true, mostra todos. Senão mostra apenas alguns.

try {
    $pdo = getDB();

    // Amigos = seguidores mútuos (eu sigo eles E eles me seguem)
    $limit = $showAll ? 100 : 8;

    $stmt = $pdo->prepare("
        SELECT u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
               COALESCE(ul.userLevel, 1) as userLevel,
               COALESCE(up.userPoints, 0) as userPoints,
               (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
               (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following
        FROM userFollowers uf1
        JOIN userFollowers uf2 ON uf1.emailFollower = uf2.emailFollowed AND uf1.emailFollowed = uf2.emailFollower
        JOIN user u ON u.email = uf1.emailFollowed
        LEFT JOIN userLevel ul ON ul.emailLevel = u.email
        LEFT JOIN userPoints up ON up.emailPoints = u.email
        WHERE uf1.emailFollower = :me
        LIMIT :limit
    ");
    $stmt->bindValue(':me', $email, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total de amigos para saber se precisa do "Ver todos"
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*)
        FROM userFollowers uf1
        JOIN userFollowers uf2 ON uf1.emailFollower = uf2.emailFollowed AND uf1.emailFollowed = uf2.emailFollower
        WHERE uf1.emailFollower = :me
    ");
    $stmtCount->execute(['me' => $email]);
    $totalFriends = $stmtCount->fetchColumn();

    echo json_encode(['friends' => $friends, 'total' => (int)$totalFriends]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro interno']);
}
