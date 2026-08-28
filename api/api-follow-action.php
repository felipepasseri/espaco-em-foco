<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false]);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once '../userScreen/user-functions.php';

$me = $_SESSION['user'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false]);
    exit();
}

$action = $data['action'];
$targetUsername = $data['targetUsername'] ?? null;

if (!$targetUsername || !$action) {
    echo json_encode(['success' => false]);
    exit();
}

try {
    $pdo = getDB();

    // Resolve o nomeDeUsuario para email no servidor (nunca expõe o email ao cliente)
    $stmtUser = $pdo->prepare("SELECT email FROM user WHERE nomeDeUsuario = :username");
    $stmtUser->execute(['username' => $targetUsername]);
    $targetEmail = $stmtUser->fetchColumn();

    if (!$targetEmail) {
        echo json_encode(['success' => false, 'error' => 'Usuário não encontrado']);
        exit();
    }

    if ($action === 'follow') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO userfollowers (emailFollower, emailFollowed) VALUES (:me, :target)");
        $stmt->execute(['me' => $me, 'target' => $targetEmail]);
    } elseif ($action === 'unfollow') {
        $stmt = $pdo->prepare("DELETE FROM userfollowers WHERE emailFollower = :me AND emailFollowed = :target");
        $stmt->execute(['me' => $me, 'target' => $targetEmail]);
    } elseif ($action === 'remove_follower') {
        $stmt = $pdo->prepare("DELETE FROM userfollowers WHERE emailFollower = :target AND emailFollowed = :me");
        $stmt->execute(['me' => $me, 'target' => $targetEmail]);
    }

    $newFollowersCount = getFollowersCount($pdo, $me);
    $newFollowingCount = getFollowingCount($pdo, $me);

    echo json_encode([
        'success' => true,
        'newFollowersCount' => $newFollowersCount,
        'newFollowingCount' => $newFollowingCount
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
