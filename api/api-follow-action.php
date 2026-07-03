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
$targetEmail = $data['targetEmail'];

try {
    $pdo = getDB();

    if ($action === 'follow') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO userFollowers (emailFollower, emailFollowed) VALUES (:me, :target)");
        $stmt->execute(['me' => $me, 'target' => $targetEmail]);
    } elseif ($action === 'unfollow') {
        $stmt = $pdo->prepare("DELETE FROM userFollowers WHERE emailFollower = :me AND emailFollowed = :target");
        $stmt->execute(['me' => $me, 'target' => $targetEmail]);
    } elseif ($action === 'remove_follower') {
        $stmt = $pdo->prepare("DELETE FROM userFollowers WHERE emailFollower = :target AND emailFollowed = :me");
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
