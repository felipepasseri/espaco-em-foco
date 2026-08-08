<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_GET['type'])) {
    echo json_encode([]);
    exit();
}

require_once __DIR__ . '/../config.php';
$email = $_SESSION['user'];
$type = $_GET['type'];
$targetUsername = $_GET['target'] ?? null;

try {
    $pdo = getDB();

    // Se foi passado um username de target, usa o email dele
    if ($targetUsername) {
        $stmtTarget = $pdo->prepare("SELECT email FROM user WHERE nomeDeUsuario = :username");
        $stmtTarget->execute(['username' => $targetUsername]);
        $targetEmail = $stmtTarget->fetchColumn();
        if (!$targetEmail) {
            echo json_encode([]);
            exit();
        }
        $queryEmail = $targetEmail;
    } else {
        $queryEmail = $email;
    }

    if ($type === 'followers') {
        // Busca quem segue o ALVO, e pega Level, XP, e as contagens do perfil deles
        $stmt = $pdo->prepare("
            SELECT u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
                   COALESCE(ul.userLevel, 1) as userLevel,
                   COALESCE(up.userPoints, 0) as userPoints,
                   (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
                   (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following,
                   (SELECT COUNT(*) FROM userFollowers uf2 WHERE uf2.emailFollower = :loggedUser AND uf2.emailFollowed = u.email) as segue_de_volta
            FROM userFollowers uf
            JOIN user u ON uf.emailFollower = u.email
            LEFT JOIN userLevel ul ON ul.emailLevel = u.email
            LEFT JOIN userPoints up ON up.emailPoints = u.email
            WHERE uf.emailFollowed = :queryEmail
        ");
        $stmt->execute(['loggedUser' => $email, 'queryEmail' => $queryEmail]);
    } else {
        // Busca quem o ALVO segue, e pega as informações deles
        $stmt = $pdo->prepare("
            SELECT u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
                   COALESCE(ul.userLevel, 1) as userLevel,
                   COALESCE(up.userPoints, 0) as userPoints,
                   (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
                   (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following
            FROM userFollowers uf
            JOIN user u ON uf.emailFollowed = u.email
            LEFT JOIN userLevel ul ON ul.emailLevel = u.email
            LEFT JOIN userPoints up ON up.emailPoints = u.email
            WHERE uf.emailFollower = :queryEmail
        ");
        $stmt->execute(['queryEmail' => $queryEmail]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode([]);
}
