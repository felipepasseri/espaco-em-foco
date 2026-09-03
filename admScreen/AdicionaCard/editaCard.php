<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../../config.php';

$pdo = getDB();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID do card não informado.");
}

$stmt = $pdo->prepare("
    SELECT tipoTopic, nameTopic, descTopic
    FROM topicCards
    WHERE id = ?
");

$stmt->execute([$id]);

$card = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$card) {
    die("Card não encontrado.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../style.css" />
    <script src="../../scripts/index.js" type="module" defer></script>
    <title>Editar Card</title>
</head>
<body>
    <header id="main-header">
        <?php include __DIR__ . "/../../navBar.php"; ?>
    </header>
    <form id="cardForm" method="POST" action="editaCard2.php" enctype="multipart/form-data">
        <label for="image">Imagem:</label>
        <input type="file" id="image" name="image">
        <input type="hidden" id="idhidden" name="idhidden" value="<?= htmlspecialchars($id) ?>">

        <label for="title">Título:</label>
        <input
            type="text"
            id="title"
            name="title"
            value="<?= htmlspecialchars($card['nameTopic']) ?>"
            required
        >

        <label for="description">Descrição:</label>
        <textarea
            id="description"
            name="description"
            required
        ><?= htmlspecialchars($card['descTopic']) ?></textarea>

        <label for="type">Tipo:</label>
        <select id="type" name="type" required>
            <option value="planets" <?= $card['tipoTopic'] === 'planets' ? 'selected' : '' ?>>
                Planets
            </option>

            <option value="stars" <?= $card['tipoTopic'] === 'stars' ? 'selected' : '' ?>>
                Stars
            </option>

            <option value="galaxies" <?= $card['tipoTopic'] === 'galaxies' ? 'selected' : '' ?>>
                Galáxias
            </option>

            <option value="cosmology" <?= $card['tipoTopic'] === 'cosmology' ? 'selected' : '' ?>>
                Cosmologia
            </option>

            <option value="other" <?= $card['tipoTopic'] === 'other' ? 'selected' : '' ?>>
                Other
            </option>
        </select>

        <input type="submit" value="Submit">
    </form>
</body>
</html>