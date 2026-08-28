<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
  exit();
}
require_once __DIR__ . '/../login/verify-user.php';
require_once __DIR__ . '/../config.php';

$userroles = verificarUsuario($_SESSION['user']);
if ($userroles['codTypeRoles'] == 1) {
  header("Location: ../admScreen/home-adm.php");
  exit();
}

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, nameTopic FROM topiccards ORDER BY nameTopic ASC");
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $topics = [];
}

?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Criar Novo Artigo - Espaço em Foco</title>
  <meta name="description" content="Crie um novo artigo educativo para a comunidade Espaço em Foco" />
  <link rel="stylesheet" href="../style.css" />
  <link rel="stylesheet" href="../css/create-article.css" />
  <script src="../scripts/create-article.js" defer></script>
</head>

<body>
  <header id="main-header">
    <?php include __DIR__ . "/../navBar.php"; ?>
  </header>

  <main class="create-article-main">

    <a href="community.php" class="create-back-btn">← Voltar para o Fórum</a>

    <div class="create-article-container">
      <h1 class="create-article-title">Novo Artigo Educativo</h1>

      <form id="create-article-form" class="create-article-form">
        
        <!-- ==============================
             SEÇÃO DO ARTIGO
        =============================== -->
        <div class="form-section">
            <h2 class="section-title">1. Dados do Artigo</h2>
            
            <div class="form-group">
                <label class="form-label" for="article-title">Nome do Artigo</label>
                <input type="text" id="article-title" class="form-input" placeholder="Digite o título do artigo..." required maxlength="30" />
            </div>

            <div class="form-group">
                <label class="form-label" for="article-topic">Tópico</label>
                <select id="article-topic" class="form-select" required>
                    <option value="">Selecione um tópico</option>
                    <?php foreach ($topics as $topic): ?>
                        <option value="<?= $topic['id'] ?>"><?= htmlspecialchars($topic['nameTopic']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="article-content">Conteúdo (500 a 4000 caracteres)</label>
                <textarea id="article-content" class="form-textarea" placeholder="Escreva o conteúdo do seu artigo aqui..." minlength="500" maxlength="4000" required></textarea>
                <div class="char-counter"><span id="char-count">0</span> / 4000</div>
            </div>
        </div>

        <!-- ==============================
             SEÇÃO DE PERGUNTAS (QUIZ)
        =============================== -->
        <div class="form-section">
            <h2 class="section-title">2. Perguntas de Fixação</h2>
            <p class="section-desc">Crie perguntas para testar o conhecimento do leitor após a leitura do artigo.</p>
            
            <div id="questions-container" class="questions-container">
                <!-- Perguntas geradas dinamicamente via JS -->
            </div>

            <button type="button" class="btn-add-question" id="btn-add-question">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nova Pergunta
            </button>
        </div>

        <!-- ==============================
             SUBMIT
        =============================== -->
        <button type="submit" class="submit-article-btn" id="submit-article-btn">
          Salvar Artigo
        </button>
        <p class="submit-note">O artigo e o quiz serão analisados pela nossa equipe antes da publicação.</p>

      </form>
    </div>

  </main>

  <?php include_once "../footer.php" ?>
</body>

</html>
