<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
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
    $stmt = $pdo->prepare("SELECT titulo, conteudo, xp_recompensa FROM artigo WHERE id = :id");
    $stmt->execute(['id' => $id_artigo]);
    $artigo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artigo) {
        die("Artigo não encontrado.");
    }

    // 2. Busca o Quiz vinculado a este artigo (se existir)
    $stmtQuiz = $pdo->prepare("SELECT id, texto_pergunta, tipo FROM quiz_pergunta WHERE id_artigo = :id_artigo LIMIT 1");
    $stmtQuiz->execute(['id_artigo' => $id_artigo]);
    $pergunta = $stmtQuiz->fetch(PDO::FETCH_ASSOC);

    $alternativas = [];
    // 3. Se for múltipla escolha, busca as alternativas (NUNCA traga a coluna 'is_correta' pro front-end)
    if ($pergunta && $pergunta['tipo'] === 'multipla_escolha') {
        $stmtAlt = $pdo->prepare("SELECT id, texto_alternativa FROM quiz_alternativa WHERE id_pergunta = :id_pergunta ORDER BY RAND()");
        $stmtAlt->execute(['id_pergunta' => $pergunta['id']]);
        $alternativas = $stmtAlt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Verifica o progresso e as respostas dadas
    $stmtProg = $pdo->prepare("SELECT status, data_tentativa, resposta_dada FROM usuario_progresso WHERE email_usuario = :email AND id_artigo = :id");
    $stmtProg->execute(['email' => $_SESSION['user'], 'id' => $id_artigo]);
    $progresso = $stmtProg->fetch(PDO::FETCH_ASSOC);

    $emCooldown = false;
    $tempoRestante = 0; // Nova variável
    if ($progresso && $progresso['status'] === 'reprovado') {
        $diff = time() - strtotime($progresso['data_tentativa']);
        if ($diff < 300) {
            $emCooldown = true;
            $tempoRestante = 300 - $diff; // Descobre quantos segundos faltam
        }
    }
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

            <?php if ($pergunta): ?>
                <div class="quiz-section glass-card">
                    <h2>Teste seus conhecimentos</h2>

                    <form id="quiz-form">
                        <input type="hidden" id="pergunta_id" value="<?= $pergunta['id'] ?>">
                        <input type="hidden" id="artigo_id" value="<?= $id_artigo ?>">
                        <input type="hidden" id="tipo_pergunta" value="<?= $pergunta['tipo'] ?>">

                        <p class="question-text">
                            <?php
                            $valorLacuna = ($progresso && $progresso['resposta_dada']) ? htmlspecialchars($progresso['resposta_dada']) : '';
                            $classeInput = 'input-lacuna';
                            $disabled = ($progresso && $progresso['status'] === 'aprovado') || $emCooldown ? 'disabled' : '';

                            if ($progresso && $progresso['status'] === 'aprovado') $classeInput .= ' lacuna-aprovada';
                            if ($emCooldown) $classeInput .= ' lacuna-reprovada';

                            if ($pergunta['tipo'] === 'lacuna') {
                                echo str_replace(
                                    '[lacuna]',
                                    '<input type="text" id="lacuna-input" class="' . $classeInput . '" placeholder="___" value="' . $valorLacuna . '" required autocomplete="off" ' . $disabled . '>',
                                    htmlspecialchars($pergunta['texto_pergunta'])
                                );
                            } else {
                                echo htmlspecialchars($pergunta['texto_pergunta']);
                            }
                            ?>
                        </p>

                        <?php if ($pergunta['tipo'] === 'multipla_escolha'): ?>
                            <div class="alternativas-grid">
                                <?php foreach ($alternativas as $alt): ?>
                                    <?php
                                    $isChecked = ($progresso && $progresso['resposta_dada'] == $alt['id']) ? 'checked' : '';
                                    $classeAlt = '';
                                    if ($isChecked && $progresso['status'] === 'aprovado') $classeAlt = 'alt-aprovada';
                                    if ($isChecked && $emCooldown) $classeAlt = 'alt-reprovada';
                                    ?>
                                    <label class="alternativa-label <?= $disabled ? 'disabled-label' : '' ?>">
                                        <input type="radio" name="alternativa_id" value="<?= $alt['id'] ?>" <?= $isChecked ?> <?= $disabled ?> required>
                                        <span class="alternativa-btn <?= $classeAlt ?>"><?= htmlspecialchars($alt['texto_alternativa']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($progresso && $progresso['status'] === 'aprovado'): ?>
                            <div class="feedback-msg" style="color: #00e5ff; margin-top: 20px;">✔ Você já concluiu esta missão.</div>
                        <?php elseif ($emCooldown): ?>
                            <div class="feedback-msg" style="color: #ff3366; margin-top: 20px;">
                                ⏳ Você errou. Aguarde <strong id="cooldown-timer" data-time="<?= $tempoRestante ?>">--:--</strong> para tentar novamente.
                            </div>
                        <?php else: ?>
                            <button type="submit" class="button btn-submit-quiz">Confirmar Resposta</button>
                            <div id="quiz-feedback" class="feedback-msg"></div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include_once __DIR__ . "/../../footer.php" ?>
</body>

</html>