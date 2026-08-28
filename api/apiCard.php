<?php
// api/apiCard.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config.php';


try {
    $pdo = getDB();

    $stmt = $pdo->query("SELECT id, tipoTopic, nameTopic, descTopic, imgCard FROM topiccards ORDER BY id");
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cards);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
