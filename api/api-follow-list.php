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

try {
    $pdo = getDB();

    if ($type === 'followers') {
        // Busca quem TE segue, e pega Level, XP, e as contagens do perfil deles
        $stmt = $pdo->prepare("
            SELECT u.email, u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
                   COALESCE(ul.userLevel, 1) as userLevel,
                   COALESCE(up.userPoints, 0) as userPoints,
                   (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
                   (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following,
                   (SELECT COUNT(*) FROM userFollowers uf2 WHERE uf2.emailFollower = :me AND uf2.emailFollowed = u.email) as segue_de_volta
            FROM userFollowers uf
            JOIN user u ON uf.emailFollower = u.email
            LEFT JOIN userLevel ul ON ul.emailLevel = u.email
            LEFT JOIN userPoints up ON up.emailPoints = u.email
            WHERE uf.emailFollowed = :me
        ");
    } else {
        // Busca quem VOCÊ segue, e pega as informações deles
        $stmt = $pdo->prepare("
            SELECT u.email, u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
                   COALESCE(ul.userLevel, 1) as userLevel,
                   COALESCE(up.userPoints, 0) as userPoints,
                   (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
                   (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following
            FROM userFollowers uf
            JOIN user u ON uf.emailFollowed = u.email
            LEFT JOIN userLevel ul ON ul.emailLevel = u.email
            LEFT JOIN userPoints up ON up.emailPoints = u.email
            WHERE uf.emailFollower = :me
        ");
    }

    $stmt->execute(['me' => $email]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode([]);
}
