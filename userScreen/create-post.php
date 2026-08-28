<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
  exit();
}
require_once __DIR__ . '/../login/verify-user.php';
$userroles = verificarUsuario($_SESSION['user']);
if ($userroles['codTypeRoles'] == 1) {
  header("Location: ../admScreen/home-adm.php");
}
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Criar Postagem - Espaço em Foco</title>
  <meta name="description" content="Crie uma nova postagem no fórum do Espaço em Foco" />
  <link rel="stylesheet" href="../style.css" />
  <link rel="stylesheet" href="../css/create-post.css" />
  <script src="../scripts/create-post.js" defer></script>
</head>

<body>
  <header id="main-header">
    <?php include __DIR__ . "/../navBar.php"; ?>
  </header>

  <main class="create-post-main">

    <!-- Botão Voltar -->
    <a href="community.php" class="create-back-btn">← Voltar para o Fórum</a>

    <div class="create-post-container">
      <h1 class="create-post-title">Nova Postagem</h1>

      <form id="create-post-form" class="create-post-form">

        <!-- Tópico -->
        <div class="form-group">
          <label class="form-label" for="post-topic">Tópico</label>
          <select name="topic" id="post-topic" class="form-select" required>
            <option value="">Selecione um tópico</option>
            <option value="Planetas">🪐 Planetas</option>
            <option value="Estrelas">⭐ Estrelas</option>
            <option value="Galáxias">🚀 Galáxias</option>
            <option value="Cosmologia">💥 Cosmologia</option>
            <option value="Outros">🔭 Outros</option>
          </select>
        </div>

        <!-- Título -->
        <div class="form-group">
          <label class="form-label" for="post-titulo">Título</label>
          <input type="text" name="titulo" id="post-titulo" class="form-input" placeholder="Digite o título da postagem..." required maxlength="200" />
        </div>

        <!-- Editor de Conteúdo -->
        <div class="form-group">
          <label class="form-label">Conteúdo</label>
          <div class="editor-toolbar" id="editor-toolbar">
            <button type="button" class="toolbar-btn" data-command="bold" title="Negrito"><b>B</b></button>
            <button type="button" class="toolbar-btn" data-command="italic" title="Itálico"><i>I</i></button>
            <button type="button" class="toolbar-btn" data-command="underline" title="Sublinhado"><u>U</u></button>
            <button type="button" class="toolbar-btn" data-command="strikeThrough" title="Tachado"><s>S</s></button>
            <span class="toolbar-separator"></span>
            <button type="button" class="toolbar-btn" data-command="insertUnorderedList" title="Lista">•</button>
            <button type="button" class="toolbar-btn" data-command="insertOrderedList" title="Lista Numerada">1.</button>
          </div>
          <div id="post-editor" class="post-editor" contenteditable="true" data-placeholder="Escreva o conteúdo da sua postagem..."></div>
        </div>

        <!-- Upload de Imagens -->
        <div class="form-group">
          <label class="form-label">Imagens (máx. 5)</label>
          <div class="upload-area" id="upload-area">
            <input type="file" name="imagens[]" id="image-input" accept="image/*" multiple hidden />
            <button type="button" class="upload-btn" id="upload-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              Adicionar imagens
            </button>
            <span class="upload-info">JPG, PNG, GIF ou WebP</span>
          </div>
          <div id="image-previews" class="image-previews"></div>
        </div>

        <!-- Submit -->
        <button type="submit" class="submit-post-btn" id="submit-post-btn">
          Publicar Postagem
        </button>
        <p class="submit-note">Sua postagem será analisada antes de ser publicada.</p>
      </form>
    </div>

  </main>

  <?php include_once "../footer.php" ?>
</body>

</html>
