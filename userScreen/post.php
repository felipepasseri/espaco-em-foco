<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
  exit();
}
require_once __DIR__ . '/../config.php';

$postId = $_GET['id'] ?? null;
if (!$postId) {
  header("Location: community.php");
  exit();
}

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
  // Pega meu username
  $stmtMe = $pdo->prepare("SELECT nomeDeUsuario FROM user WHERE email = :email");
  $stmtMe->execute(['email' => $_SESSION['user']]);
  $myUsername = $stmtMe->fetchColumn();

  // Busca o post
  $stmtPost = $pdo->prepare("
    SELECT pf.*, u.fotoPerfil, u.nome, u.sobrenome,
           COALESCE(ul.userLevel, 1) as userLevel,
           COALESCE(up.userPoints, 0) as userPoints,
           (SELECT COUNT(*) FROM userFollowers WHERE emailFollowed = u.email) AS total_followers,
           (SELECT COUNT(*) FROM userFollowers WHERE emailFollower = u.email) AS total_following,
           (SELECT COUNT(*) FROM userFollowers uf2 WHERE uf2.emailFollower = :me AND uf2.emailFollowed = u.email) as estou_seguindo
    FROM postagens_forum pf
    JOIN user u ON u.nomeDeUsuario = pf.nome_usuario_post
    LEFT JOIN userLevel ul ON ul.emailLevel = u.email
    LEFT JOIN userPoints up ON up.emailPoints = u.email
    WHERE pf.id = :id AND pf.avaliacao_adm = 'Aprovado'
  ");
  $stmtPost->execute(['me' => $_SESSION['user'], 'id' => $postId]);
  $post = $stmtPost->fetch(PDO::FETCH_ASSOC);

  if (!$post) {
    header("Location: community.php");
    exit();
  }

  $fotoPerfil = !empty($post['fotoPerfil']) ? $post['fotoPerfil'] : 'img/user-profile-default.jpg';

  // Imagens do post
  $stmtImgs = $pdo->prepare("SELECT img_caminho FROM imgs_post WHERE id_post = :id");
  $stmtImgs->execute(['id' => $postId]);
  $imagens = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);

  // Minhas interações com este post
  $stmtMyInteractions = $pdo->prepare("
    SELECT tipo FROM interacao_post WHERE nome_usuario = :user AND id_post = :postId
  ");
  $stmtMyInteractions->execute(['user' => $myUsername, 'postId' => $postId]);
  $myInteractions = $stmtMyInteractions->fetchAll(PDO::FETCH_COLUMN);

  $iLiked = in_array('Like', $myInteractions);
  $iDisliked = in_array('Deslike', $myInteractions);
  $iReposted = in_array('Repost', $myInteractions);

  // Tempo relativo
  function tempoRelativo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'agora';
    if ($diff < 3600) return floor($diff / 60) . ' min atrás';
    if ($diff < 86400) return floor($diff / 3600) . 'h atrás';
    if ($diff < 604800) return floor($diff / 86400) . ' dias atrás';
    return date('d/m/Y', strtotime($datetime));
  }

} catch (PDOException $e) {
  echo 'Erro: ' . $e->getMessage();
  exit();
}
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($post['titulo_post']) ?> - Espaço em Foco</title>
  <meta name="description" content="<?= htmlspecialchars(mb_strimwidth(strip_tags($post['desc_post']), 0, 160, '...')) ?>" />
  <link rel="stylesheet" href="../style.css" />
  <link rel="stylesheet" href="../css/post.css" />
  <script src="../scripts/post.js" defer></script>
</head>

<body>
  <header id="main-header">
    <?php include __DIR__ . "/../navBar.php"; ?>
  </header>

  <main class="post-main">

    <!-- Botão Voltar -->
    <a href="javascript:history.back()" class="post-back-btn">← Voltar</a>

    <!-- =========================================
       ARTIGO / POST
    ========================================= -->
    <article class="post-article" data-post-id="<?= $postId ?>">

      <div class="post-author-header">
        <img src="../<?= htmlspecialchars($fotoPerfil) ?>" alt="Perfil" class="post-author-avatar" />
        <div class="post-author-info">
          <span class="post-author-username hover-trigger"
                data-avatar="../<?= htmlspecialchars($fotoPerfil) ?>" data-user="<?= htmlspecialchars($post['nome_usuario_post']) ?>"
                data-name="<?= htmlspecialchars($post['nome'] . ' ' . $post['sobrenome']) ?>"
                data-level="<?= $post['userLevel'] ?>" data-xp="<?= $post['userPoints'] ?>"
                data-followers="<?= $post['total_followers'] ?>" data-following="<?= $post['total_following'] ?>"
                data-username="<?= htmlspecialchars($post['nome_usuario_post']) ?>"
                data-isfollowing="<?= $post['estou_seguindo'] ?>"
                data-isme="<?= $post['nome_usuario_post'] === $myUsername ? 'true' : 'false' ?>">
            <?= htmlspecialchars($post['nome_usuario_post']) ?>
          </span>
          <span class="post-author-time"><?= tempoRelativo($post['created_at']) ?></span>
        </div>
        <span class="post-topic-badge"><?= htmlspecialchars($post['topic_post']) ?></span>
      </div>

      <h1 class="post-title"><?= htmlspecialchars($post['titulo_post']) ?></h1>

      <div class="post-content">
        <?= $post['desc_post'] ?>
      </div>

      <!-- Carrossel de Imagens -->
      <?php if (!empty($imagens)): ?>
        <div class="post-images-carousel" id="post-carousel">
          <div class="carousel-track" id="carousel-track">
            <?php foreach ($imagens as $img): ?>
              <img src="../<?= htmlspecialchars($img) ?>" alt="Imagem do post" class="carousel-image" />
            <?php endforeach; ?>
          </div>
          <?php if (count($imagens) > 1): ?>
            <button class="carousel-btn carousel-prev" id="carousel-prev">❮</button>
            <button class="carousel-btn carousel-next" id="carousel-next">❯</button>
            <div class="carousel-dots" id="carousel-dots">
              <?php foreach ($imagens as $i => $img): ?>
                <span class="carousel-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Botões de Interação -->
      <div class="post-interactions">
        <button class="interaction-btn <?= $iLiked ? 'active-like' : '' ?>" id="btn-like" data-action="like">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $iLiked ? '#00e5ff' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
          <span id="count-likes"><?= $post['likes_post'] ?></span>
        </button>
        <button class="interaction-btn <?= $iDisliked ? 'active-dislike' : '' ?>" id="btn-deslike" data-action="deslike">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $iDisliked ? '#ff3366' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/></svg>
          <span id="count-deslikes"><?= $post['deslikes_post'] ?></span>
        </button>
        <button class="interaction-btn <?= $iReposted ? 'active-repost' : '' ?>" id="btn-repost" data-action="repost">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          <span id="count-reposts"><?= $post['reposts'] ?></span>
        </button>
        <button class="interaction-btn report-interaction-btn" id="btn-denunciar-post">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
          Denunciar
        </button>
      </div>

    </article>

    <!-- =========================================
       SEÇÃO DE COMENTÁRIOS
    ========================================= -->
    <section class="comments-section">
      <h2 class="section-title">Comentários</h2>

      <!-- Formulário de novo comentário -->
      <div class="comment-form-container">
        <textarea id="new-comment-text" class="comment-textarea" placeholder="Escreva um comentário..."></textarea>
        <button id="btn-submit-comment" class="btn-action btn-seguir comment-submit-btn">Comentar</button>
      </div>

      <!-- Lista de comentários -->
      <div id="comments-container" class="comments-container">
        <div class="loading-indicator">Carregando comentários...</div>
      </div>

      <button id="load-more-comments" class="load-more-btn hidden">Mostrar mais</button>
    </section>

    <!-- =========================================
       MODAL DE DENÚNCIA DO POST
    ========================================= -->
    <div id="report-post-modal" class="modal-overlay hidden">
      <div class="modal-content glass-card report-modal-content">
        <div class="modal-header">
          <h3>Denunciar postagem</h3>
          <button id="close-report-post-modal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body report-modal-body">
          <form id="report-post-form">
            <input type="hidden" name="nome_usuario_alvo" value="<?= htmlspecialchars($post['nome_usuario_post']) ?>" />
            <input type="hidden" name="tipo_alvo" value="post" />

            <label class="report-label">Motivo da denúncia</label>
            <select name="categoria" class="report-select" required>
              <option value="">Selecione uma categoria</option>
              <option value="Spam">Spam</option>
              <option value="Conteúdo impróprio">Conteúdo impróprio</option>
              <option value="Assédio">Assédio</option>
              <option value="Informações falsas">Informações falsas</option>
              <option value="Outro">Outro</option>
            </select>

            <label class="report-label">Descrição</label>
            <textarea name="motivo" class="report-textarea" placeholder="Descreva o motivo da denúncia..." required></textarea>

            <button type="submit" class="btn-action btn-seguir report-submit-btn">Enviar Denúncia</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Hover Card -->
    <div id="user-hover-card" class="hover-card-overlay hidden">
      <div class="hc-header">
        <div class="hc-profile-info">
          <img id="hc-avatar" src="" alt="Avatar">
          <div class="hc-names">
            <span id="hc-username"></span>
            <span id="hc-fullname"></span>
          </div>
        </div>
        <button id="hc-follow-btn" class="btn-action hc-btn-fixed">Seguir</button>
      </div>
      <div class="hc-stats">
        <div class="hc-stat-box">
          <span id="hc-level" class="hc-val hc-destaque"></span>
          <span class="hc-label">Nível</span>
        </div>
        <div class="hc-stat-box">
          <span id="hc-xp" class="hc-val hc-destaque"></span>
          <span class="hc-label">XP</span>
        </div>
      </div>
      <div class="hc-stats hc-stats-bottom">
        <div class="hc-stat-box">
          <span id="hc-followers" class="hc-val"></span>
          <span class="hc-label">Seguidores</span>
        </div>
        <div class="hc-stat-box">
          <span id="hc-following" class="hc-val"></span>
          <span class="hc-label">Seguindo</span>
        </div>
      </div>
    </div>

  </main>

  <?php include_once "../footer.php" ?>
</body>

</html>
