<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../login/login.php");
    exit();
}

require_once __DIR__ . '/../../config.php';
$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pega o ID do artigo pela URL
$id_artigo = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_artigo === 0) {
    header("Location: ../userScreen/home-user.php");
    exit();
}

try {
    // 1. Busca o Artigo
    $stmt = $pdo->prepare("SELECT titulo, conteudo, COALESCE((SELECT SUM(xp_recompensa) FROM quiz_pergunta WHERE id_artigo = a.id), 0) AS xp_recompensa FROM artigo a WHERE id = :id AND avaliacao_adm = 'Aprovado'");
    $stmt->execute(['id' => $id_artigo]);
    $artigo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artigo) {
        die("Artigo não encontrado.");
    }

    // Não buscamos mais as perguntas aqui. O botão redirecionará para perguntas.php.

} catch (PDOException $e) {
    die('Erro ao carregar o artigo.');
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($artigo['titulo']) ?> - Espaço em Foco</title>
    <link rel="stylesheet" href="../../global.css" />
    <link rel="stylesheet" href="../../style.css" />
    <link rel="stylesheet" href="artigo.css" />
    <script src="../scripts/index.js" type="module" defer></script>
    <script src="artigo.js" defer></script>
</head>

<body>
    <header id="main-header">
        <?php include __DIR__ . "/../../navBar.php"; ?>
    </header>

    <main class="article-page fade-in animate">
        <div class="article-container">

            <a href="../home-user.php" class="back-link">← Voltar para o Painel</a>

            <header class="article-header">
                <h1><?= htmlspecialchars($artigo['titulo']) ?></h1>
                <span class="xp-badge">+<?= htmlspecialchars($artigo['xp_recompensa']) ?> XP</span>
            </header>

            <div class="article-content">
                <?= $artigo['conteudo'] ?> </div>

            <div class="quiz-section glass-card" style="margin-top: 40px;">
                <h2>Teste seus conhecimentos</h2>
                <p class="question-text">Após ler o artigo, teste o que você aprendeu e ganhe XP!</p>
                <a href="perguntas.php?id_artigo=<?= $id_artigo ?>" class="button btn-submit-quiz" style="display: inline-block; text-decoration: none;">Responder Perguntas</a>
            </div>

        </div>
    </main>

    <?php include_once __DIR__ . "/../../footer.php" ?>
</body>

</html>