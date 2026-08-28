<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
  exit();
}
require_once __DIR__ . '/../login/verify-user.php';
require_once __DIR__ . '/../config.php';
require_once 'user-functions.php';
require_once 'calcularXp.php';

$userroles = verificarUsuario($_SESSION['user']);
if ($userroles['codTypeRoles'] == 1) {
  header("Location: ../admScreen/home-adm.php");
}

$targetUsername = $_GET['user'] ?? null;
if (!$targetUsername) {
  header("Location: home-user.php");
  exit();
}

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
  // Dados do visitante (eu)
  $myData = getUserData($pdo, $_SESSION['user']);
  $myUsername = $myData['nomeDeUsuario'];

  // Redireciona se o perfil é o próprio
  if ($targetUsername === $myUsername) {
    header("Location: home-user.php");
    exit();
  }

  // Dados do usuário alvo (pelo nomeDeUsuario, nunca usa email no client)
  $stmtTarget = $pdo->prepare("
    SELECT u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil, u.bannerPerfil,
           COALESCE(ul.userlevel, 1) as userlevel,
           COALESCE(up.userpoints, 0) as userpoints,
           (SELECT COUNT(*) FROM userfollowers WHERE emailFollowed = u.email) AS total_followers,
           (SELECT COUNT(*) FROM userfollowers WHERE emailFollower = u.email) AS total_following,
           (SELECT COUNT(*) FROM userfollowers uf2 WHERE uf2.emailFollower = :me AND uf2.emailFollowed = u.email) as estou_seguindo
    FROM user u
    LEFT JOIN userlevel ul ON u.email = ul.emailLevel
    LEFT JOIN userpoints up ON u.email = up.emailPoints
    WHERE u.nomeDeUsuario = :username
  ");
  $stmtTarget->execute(['me' => $_SESSION['user'], 'username' => $targetUsername]);
  $target = $stmtTarget->fetch(PDO::FETCH_ASSOC);

  if (!$target) {
    header("Location: home-user.php");
    exit();
  }

  // Pega email do alvo (só no server)
  $stmtEmail = $pdo->prepare("SELECT email FROM user WHERE nomeDeUsuario = :username");
  $stmtEmail->execute(['username' => $targetUsername]);
  $targetEmail = $stmtEmail->fetchColumn();

  // XP e progresso
  $xpNivelAtual = xpNecessario($target['userlevel']);
  $xpProximoNivel = xpNecessario($target['userlevel'] + 1);
  $xpDelta = $xpProximoNivel - $xpNivelAtual;
  $xpProgresso = $target['userpoints'] - $xpNivelAtual;
  $porcentagem = $xpDelta > 0 ? max(0, min(100, ($xpProgresso / $xpDelta) * 100)) : 100;

  // Posição no ranking (por XP)
  $stmtRank = $pdo->prepare("SELECT COUNT(*) + 1 FROM userpoints up WHERE up.userpoints > :myPoints");
  $stmtRank->execute(['myPoints' => $target['userpoints']]);
  $userRank = $stmtRank->fetchColumn();

  // Artigos completados recentemente
  $stmtArtigos = $pdo->prepare("
    SELECT a.id, a.titulo, COALESCE((SELECT SUM(xp_recompensa) FROM quiz_pergunta WHERE id_artigo = a.id), 0) AS xp_recompensa
    FROM artigo_completo ac
    JOIN artigo a ON a.id = ac.id_artigo
    WHERE ac.nome_usuario_artigo = :username AND a.avaliacao_adm = 'Aprovado'
    ORDER BY ac.id DESC LIMIT 6
  ");
  $stmtArtigos->execute(['username' => $targetUsername]);
  $artigosCompletados = $stmtArtigos->fetchAll(PDO::FETCH_ASSOC);

  // Tópicos mais feitos
  $stmtTopicos = $pdo->prepare("
    SELECT tc.nameTopic as topico, COUNT(*) as total
    FROM usuario_progresso up2
    JOIN artigo a ON a.id = up2.id_artigo
    JOIN topiccards tc ON tc.id = a.id_topic
    WHERE up2.email_usuario = :email AND up2.status = 'aprovado' AND a.avaliacao_adm = 'Aprovado'
    GROUP BY tc.id, tc.nameTopic ORDER BY total DESC LIMIT 5
  ");
  $stmtTopicos->execute(['email' => $targetEmail]);
  $topicosFrequentes = $stmtTopicos->fetchAll(PDO::FETCH_ASSOC);

  // Publicações no fórum (aprovadas)
  $stmtPosts = $pdo->prepare("
    SELECT pf.id, pf.topic_post, pf.titulo_post, pf.desc_post, pf.likes_post, pf.deslikes_post, pf.reposts, pf.created_at,
           (SELECT img_caminho FROM imgs_post WHERE id_post = pf.id LIMIT 1) as primeira_img
    FROM postagens_forum pf
    WHERE pf.nome_usuario_post = :username AND pf.avaliacao_adm = 'Aprovado'
    ORDER BY pf.created_at DESC LIMIT 10
  ");
  $stmtPosts->execute(['username' => $targetUsername]);
  $publicacoes = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

  // Reposts
  $stmtReposts = $pdo->prepare("
    SELECT pf.id, pf.nome_usuario_post, pf.topic_post, pf.titulo_post, pf.desc_post, pf.likes_post, pf.reposts, pf.created_at,
           (SELECT img_caminho FROM imgs_post WHERE id_post = pf.id LIMIT 1) as primeira_img
    FROM interacao_post ip
    JOIN postagens_forum pf ON ip.id_post = pf.id
    WHERE ip.nome_usuario = :username AND ip.tipo = 'Repost' AND pf.avaliacao_adm = 'Aprovado'
    ORDER BY pf.created_at DESC LIMIT 10
  ");
  $stmtReposts->execute(['username' => $targetUsername]);
  $reposts = $stmtReposts->fetchAll(PDO::FETCH_ASSOC);

  // Foto padrão
  $fotoPerfil = !empty($target['fotoPerfil']) ? $target['fotoPerfil'] : 'img/user-profile-default.jpg';
  $bannerPerfil = !empty($target['bannerPerfil']) ? $target['bannerPerfil'] : '';

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
  <title>Perfil de @<?= htmlspecialchars($target['nomeDeUsuario']) ?> - Espaço em Foco</title>
  <meta name="description" content="Perfil de <?= htmlspecialchars($target['nome'] . ' ' . $target['sobrenome']) ?> no Espaço em Foco" />
  <link rel="stylesheet" href="../style.css" />
  <link rel="stylesheet" href="../css/profile.css" />
  <script src="../scripts/profile.js" defer></script>
</head>

<body>
  <header id="main-header">
    <?php include __DIR__ . "/../navBar.php"; ?>
  </header>

  <main class="profile-main">
    <!-- Botão Voltar -->
    <a href="javascript:history.back()" class="profile-back-btn">← Voltar</a>

    <!-- =========================================
       SEÇÃO 1: HEADER DO PERFIL
    ========================================= -->
    <section class="profile-hero">
      <div class="profile-hero-banner">
        <?php if ($bannerPerfil): ?>
          <img src="../<?= htmlspecialchars($bannerPerfil) ?>" alt="Banner" class="profile-banner-img" />
        <?php else: ?>
          <div class="profile-banner-placeholder"></div>
        <?php endif; ?>
      </div>

      <div class="profile-hero-content">
        <div class="profile-hero-left">
          <div class="profile-hero-avatar">
            <img src="../<?= htmlspecialchars($fotoPerfil) ?>" alt="Foto de Perfil" />
          </div>
          <div class="profile-hero-info">
            <h1 class="profile-hero-name"><?= htmlspecialchars($target['nome'] . ' ' . $target['sobrenome']) ?></h1>
            <span class="profile-hero-handle">@<?= htmlspecialchars($target['nomeDeUsuario']) ?></span>
          </div>
        </div>

        <div class="profile-hero-right">
          <div class="profile-hero-stats">
            <div class="profile-stat-item" id="profile-btn-seguidores" style="cursor:pointer;">
              <span class="profile-stat-count" id="profile-count-seguidores"><?= $target['total_followers'] ?></span>
              <span class="profile-stat-label">Seguidores</span>
            </div>
            <div class="profile-stat-item" id="profile-btn-seguindo" style="cursor:pointer;">
              <span class="profile-stat-count" id="profile-count-seguindo"><?= $target['total_following'] ?></span>
              <span class="profile-stat-label">Seguindo</span>
            </div>
          </div>
          <button 
            class="btn-action <?= $target['estou_seguindo'] ? 'btn-seguindo' : 'btn-seguir' ?>" 
            id="profile-follow-btn"
            data-username="<?= htmlspecialchars($target['nomeDeUsuario']) ?>"
            data-following="<?= $target['estou_seguindo'] ?>"
          >
            <?= $target['estou_seguindo'] ? 'Seguindo' : 'Seguir' ?>
          </button>
        </div>
      </div>

      <!-- Barra de XP / Level -->
      <div class="profile-xp-bar">
        <div class="profile-xp-info">
          <span class="profile-level-text">Nível <?= $target['userlevel'] ?></span>
          <span class="profile-xp-text"><?= formatarXP($target['userpoints']) ?> / <?= formatarXP($xpProximoNivel) ?> XP</span>
        </div>
        <div class="progress-bar-container">
          <div class="progress-bar-fill" style="width: <?= $porcentagem ?>%"></div>
        </div>
      </div>
    </section>

    <!-- =========================================
       SEÇÃO 2: CARDS DE STATS
    ========================================= -->
    <section class="profile-stats-grid">
      <div class="profile-stat-card">
        <img src="medalha.png" alt="Conquistas" class="profile-stat-icon" />
        <span class="profile-stat-title">Conquistas</span>
        <span class="profile-stat-value">—</span>
      </div>
      <div class="profile-stat-card">
        <img src="camada.png" alt="Camada" class="profile-stat-icon" />
        <span class="profile-stat-title">Camada</span>
        <span class="profile-stat-value">—</span>
      </div>
      <div class="profile-stat-card">
        <img src="../img/rank-icon.png" alt="Ranking" class="profile-stat-icon" />
        <span class="profile-stat-title">Ranking</span>
        <span class="profile-stat-value ranking-destaque">#<?= $userRank ?></span>
      </div>
      <div class="profile-stat-card">
        <img src="estrela.png" alt="XP" class="profile-stat-icon" />
        <span class="profile-stat-title">XP Total</span>
        <span class="profile-stat-value xp-value-small"><?= formatarXP($target['userpoints']) ?></span>
      </div>
    </section>

    <!-- =========================================
       SEÇÃO 3: ARTIGOS COMPLETADOS
    ========================================= -->
    <section class="profile-section">
      <h2 class="section-title">Artigos Completados Recentemente</h2>
      <div class="profile-articles-list">
        <?php if (!empty($artigosCompletados)): ?>
          <?php foreach ($artigosCompletados as $artigo): ?>
            <a href="article-screen/artigo.php?id=<?= $artigo['id'] ?>" class="article-item article-aprovado">
              <span class="article-name"><?= htmlspecialchars($artigo['titulo']) ?></span>
              <span class="article-xp">+<?= $artigo['xp_recompensa'] ?> XP</span>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="profile-empty-state">Nenhum artigo completado ainda.</p>
        <?php endif; ?>
      </div>
    </section>

    <!-- =========================================
       SEÇÃO 4: TÓPICOS MAIS FEITOS
    ========================================= -->
    <?php if (!empty($topicosFrequentes)): ?>
    <section class="profile-section">
      <h2 class="section-title">Tópicos Mais Explorados</h2>
      <div class="profile-topics-grid">
        <?php foreach ($topicosFrequentes as $topico): ?>
          <div class="profile-topic-chip">
            <span class="profile-topic-name"><?= htmlspecialchars($topico['topico']) ?></span>
            <span class="profile-topic-count"><?= $topico['total'] ?> artigo<?= $topico['total'] > 1 ? 's' : '' ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- =========================================
       SEÇÃO 5: ARTIGOS ESCRITOS (FUTURO)
    ========================================= -->
    <section class="profile-section">
      <h2 class="section-title">Artigos Escritos</h2>
      <div class="profile-placeholder-section">
        <span class="profile-placeholder-icon">✍️</span>
        <p class="profile-placeholder-text">Em breve! Esta funcionalidade estará disponível numa próxima atualização.</p>
      </div>
    </section>

    <!-- =========================================
       SEÇÃO 6: PUBLICAÇÕES NO FÓRUM + REPOSTS
    ========================================= -->
    <section class="profile-section">
      <div class="profile-tabs">
        <button class="profile-tab active" data-tab="publicacoes">📝 Publicações</button>
        <button class="profile-tab" data-tab="reposts">🔁 Reposts</button>
      </div>

      <div class="profile-filter-row">
        <button class="profile-filter-btn active" data-order="recentes">Mais recentes</button>
        <button class="profile-filter-btn" data-order="relevantes">Mais relevantes</button>
      </div>

      <!-- Publicações -->
      <div class="profile-tab-content active" id="tab-publicacoes">
        <?php if (!empty($publicacoes)): ?>
          <?php foreach ($publicacoes as $post): ?>
            <a href="post.php?id=<?= $post['id'] ?>" class="profile-post-preview">
              <div class="post-preview-header">
                <span class="post-preview-topic"><?= htmlspecialchars($post['topic_post']) ?></span>
                <span class="post-preview-time"><?= htmlspecialchars($post['created_at']) ?></span>
              </div>
              <h3 class="post-preview-title"><?= htmlspecialchars($post['titulo_post']) ?></h3>
              <p class="post-preview-desc"><?= htmlspecialchars(mb_strimwidth(strip_tags($post['desc_post']), 0, 150, '...')) ?></p>
              <?php if ($post['primeira_img']): ?>
                <img src="../<?= htmlspecialchars($post['primeira_img']) ?>" alt="" class="post-preview-img" />
              <?php endif; ?>
              <div class="post-preview-stats">
                <span>👍 <?= $post['likes_post'] ?></span>
                <span>👎 <?= $post['deslikes_post'] ?></span>
                <span>🔁 <?= $post['reposts'] ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="profile-empty-state">Nenhuma publicação ainda.</p>
        <?php endif; ?>
      </div>

      <!-- Reposts -->
      <div class="profile-tab-content" id="tab-reposts">
        <?php if (!empty($reposts)): ?>
          <?php foreach ($reposts as $post): ?>
            <a href="post.php?id=<?= $post['id'] ?>" class="profile-post-preview">
              <div class="post-preview-header">
                <span class="post-preview-repost-tag">🔁 Repostado</span>
                <span class="post-preview-topic"><?= htmlspecialchars($post['topic_post']) ?></span>
                <span class="post-preview-time"><?= htmlspecialchars($post['created_at']) ?></span>
              </div>
              <h3 class="post-preview-title"><?= htmlspecialchars($post['titulo_post']) ?></h3>
              <p class="post-preview-desc"><?= htmlspecialchars(mb_strimwidth(strip_tags($post['desc_post']), 0, 150, '...')) ?></p>
              <?php if ($post['primeira_img']): ?>
                <img src="../<?= htmlspecialchars($post['primeira_img']) ?>" alt="" class="post-preview-img" />
              <?php endif; ?>
              <div class="post-preview-stats">
                <span>👍 <?= $post['likes_post'] ?></span>
                <span>🔁 <?= $post['reposts'] ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="profile-empty-state">Nenhum repost ainda.</p>
        <?php endif; ?>
      </div>
    </section>

    <!-- =========================================
       BOTÃO DE DENÚNCIA
    ========================================= -->
    <div class="profile-report-area">
      <button class="profile-report-btn" id="btn-denunciar">⚠️ Denunciar usuário</button>
    </div>

    <!-- =========================================
       MODAL DE SEGUIDORES / SEGUINDO
    ========================================= -->
    <div id="profile-follow-modal" class="modal-overlay hidden">
      <div class="modal-content glass-card">
        <div class="modal-header">
          <h3 id="profile-modal-title">Seguidores</h3>
          <button id="profile-close-modal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <ul id="profile-user-list" class="user-list"></ul>
        </div>
      </div>
    </div>

    <!-- =========================================
       MODAL DE DENÚNCIA
    ========================================= -->
    <div id="report-modal" class="modal-overlay hidden">
      <div class="modal-content glass-card report-modal-content">
        <div class="modal-header">
          <h3>Denunciar @<?= htmlspecialchars($target['nomeDeUsuario']) ?></h3>
          <button id="close-report-modal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body report-modal-body">
          <form id="report-form">
            <input type="hidden" name="nome_usuario_alvo" value="<?= htmlspecialchars($target['nomeDeUsuario']) ?>" />
            <input type="hidden" name="tipo_alvo" value="usuario" />

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

    <!-- =========================================
       HOVER CARD (reutilizado)
    ========================================= -->
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
