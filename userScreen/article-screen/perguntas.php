<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../login/login.php");
    exit();
}

$id_artigo = isset($_GET['id_artigo']) ? (int)$_GET['id_artigo'] : 0;
if ($id_artigo === 0) {
    header("Location: ../home-user.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - Espaço em Foco</title>
    <link rel="stylesheet" href="../../global.css" />
    <link rel="stylesheet" href="../../style.css" />
    <link rel="stylesheet" href="../../css/perguntas.css" />
</head>
<body class="quiz-body fade-in animate">
    
    <!-- HEADER -->
    <header class="quiz-header">
        <a href="artigo.php?id=<?= $id_artigo ?>" class="btn-voltar">← Voltar para o artigo</a>
        <div class="quiz-stats">
            <span class="dificuldade-badge" id="dificuldade-badge">--</span>
            <span class="xp-badge" id="xp-badge">+-- XP</span>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="quiz-main">
        <div id="loader-screen" class="loader-container">
            <div class="spinner"></div>
            <p>Carregando missão...</p>
        </div>

        <div id="error-screen" class="error-container" style="display: none;">
            <h2 id="error-title">Ops!</h2>
            <p id="error-message">Houve um problema ao carregar.</p>
            <a href="../home-user.php" class="btn-back-panel">Voltar para o Painel</a>
        </div>

        <!-- TELA DO QUIZ -->
        <div id="quiz-container" class="quiz-container glass-card" style="display: none;">
            <!-- PROGRESS BAR -->
            <div class="progress-wrapper">
                <div class="progress-text">
                    <span id="current-question-num">1</span> / <span id="total-questions-num">--</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="progress-bar-fill"></div>
                </div>
            </div>

            <!-- CONTAINER DINÂMICO (Pergunta) -->
            <div class="question-wrapper" id="question-wrapper">
                <h2 class="question-title" id="question-title">Carregando pergunta...</h2>
                <div class="answers-wrapper" id="answers-wrapper">
                    <!-- Alternativas ou Lacunas via JS -->
                </div>
            </div>

            <button id="btn-next" class="btn-next" disabled>Confirmar Resposta</button>
        </div>

        <!-- TELA DE RESULTADO (Final) -->
        <div id="result-container" class="result-container glass-card" style="display: none;">
            <h2 id="result-title">Analisando Resultados...</h2>
            
            <div class="result-stats">
                <div class="stat-circle">
                    <span id="result-score">0</span>
                    <small>Acertos</small>
                </div>
                <div class="stat-circle xp-circle">
                    <span id="result-xp">+0</span>
                    <small>XP Ganho</small>
                </div>
            </div>

            <p id="result-message" class="result-message"></p>
            
            <div class="result-actions">
                <a href="../home-user.php" class="btn-back-panel">Voltar ao Painel</a>
            </div>
        </div>

    </main>
    
    <script>
        const id_artigo = <?= $id_artigo ?>;
    </script>
    <script src="../../scripts/perguntas.js" defer></script>
</body>
</html>
