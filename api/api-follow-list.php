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
                   COALESCE(ul.userlevel, 1) as userlevel,
                   COALESCE(up.userpoints, 0) as userpoints,
                   (SELECT COUNT(*) FROM userfollowers WHERE emailFollowed = u.email) AS total_followers,
                   (SELECT COUNT(*) FROM userfollowers WHERE emailFollower = u.email) AS total_following,
                   (SELECT COUNT(*) FROM userfollowers uf2 WHERE uf2.emailFollower = :loggedUser AND uf2.emailFollowed = u.email) as segue_de_volta
            FROM userfollowers uf
            JOIN user u ON uf.emailFollower = u.email
            LEFT JOIN userlevel ul ON ul.emailLevel = u.email
            LEFT JOIN userpoints up ON up.emailPoints = u.email
            WHERE uf.emailFollowed = :queryEmail
        ");
        $stmt->execute(['loggedUser' => $email, 'queryEmail' => $queryEmail]);
    } else {
        // Busca quem o ALVO segue, e pega as informações deles
        $stmt = $pdo->prepare("
            SELECT u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
                   COALESCE(ul.userlevel, 1) as userlevel,
                   COALESCE(up.userpoints, 0) as userpoints,
                   (SELECT COUNT(*) FROM userfollowers WHERE emailFollowed = u.email) AS total_followers,
                   (SELECT COUNT(*) FROM userfollowers WHERE emailFollower = u.email) AS total_following
            FROM userfollowers uf
            JOIN user u ON uf.emailFollowed = u.email
            LEFT JOIN userlevel ul ON ul.emailLevel = u.email
            LEFT JOIN userpoints up ON up.emailPoints = u.email
            WHERE uf.emailFollower = :queryEmail
        ");
        $stmt->execute(['queryEmail' => $queryEmail]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode([]);
}
