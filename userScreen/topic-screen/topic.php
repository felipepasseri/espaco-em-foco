<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

require_once __DIR__ . '/../../config.php';

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$email = $_SESSION['user'];

// Pega o ID do tópico pela URL
$id_topico = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_topico === 0) {
    header("Location: ../home-user.php");
    exit();
}

try {
    // 1. Busca os dados do Tópico selecionado
    $stmtTopico = $pdo->prepare("SELECT id, tipoTopic, imgCard, nameTopic, descTopic FROM topicCards WHERE id = :id");
    $stmtTopico->execute(['id' => $id_topico]);
    $topico = $stmtTopico->fetch(PDO::FETCH_ASSOC);

    if (!$topico) {
        die("Tópico não encontrado.");
    }

    // 2. Busca todos os artigos deste tópico E verifica se o usuário já concluiu
    // O LEFT JOIN verifica se existe um registro na tabela usuario_progresso para aquele artigo e usuário
    $sqlArtigos = "
        SELECT a.id, a.titulo, a.xp_recompensa, 
               IF(up.id_artigo IS NOT NULL, 1, 0) AS concluido
        FROM artigo a
        LEFT JOIN usuario_progresso up 
               ON a.id = up.id_artigo AND up.email_usuario = :email
        WHERE a.id_topic = :id_topico
        ORDER BY a.id ASC
    ";

    $stmtArtigos = $pdo->prepare($sqlArtigos);
    $stmtArtigos->execute(['email' => $email, 'id_topico' => $id_topico]);
    $artigos = $stmtArtigos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erro ao carregar o tópico: ' . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($topico['nameTopic']) ?> - Espaço em Foco</title>
    <link rel="stylesheet" href="../../global.css" />
    <link rel="stylesheet" href="../../style.css" />
    <link rel="stylesheet" href="topic.css" />
    <script src="../../scripts/index.js" type="module" defer></script>
</head>

<body>
    <header id="main-header">
        <?php include __DIR__ . "/../../navBar.php"; ?>
    </header>

    <main class="topic-page fade-in animate">
        <div class="topic-container">

            <a href="../home-user.php" class="back-link">← Voltar para o Início</a>

            <header class="topic-banner glass-card" style="background-image: linear-gradient(to right, rgba(11, 7, 34, 0.9), rgba(11, 7, 34, 0.4)), url('/<?= htmlspecialchars($topico['imgCard']) ?>');">
                <div class="topic-banner-content">
                    <h1><?= htmlspecialchars($topico['nameTopic']) ?></h1>
                    <p><?= htmlspecialchars($topico['descTopic']) ?></p>
                </div>
            </header>

            <section class="topic-articles-section">
                <h2 class="section-title">Artigos Disponíveis</h2>

                <div class="articles-grid">
                    <?php if (!empty($artigos)): ?>
                        <?php foreach ($artigos as $artigo): ?>
                            <a href="../article-screen/artigo.php?id=<?= $artigo['id'] ?>" class="article-mission-card <?= $artigo['concluido'] ? 'mission-completed' : '' ?>">
                                <div class="mission-info">
                                    <h3 class="mission-title"><?= htmlspecialchars($artigo['titulo']) ?></h3>
                                    <span class="mission-xp">+<?= htmlspecialchars($artigo['xp_recompensa']) ?> XP</span>
                                </div>
                                <div class="mission-status">
                                    <?php if ($artigo['concluido']): ?>
                                        <span class="badge-done">✔ Concluído</span>
                                    <?php else: ?>
                                        <span class="badge-pending">Iniciar</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Nenhum artigo encontrado para este tópico no momento. Volte em breve!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

    <?php include_once "../../footer.php" ?>
</body>

</html>