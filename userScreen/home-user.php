<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
}
require_once __DIR__ . '/../login/verify-user.php';
require_once __DIR__ . '/../config.php';
require_once 'user-functions.php';
require_once 'calcularXp.php';

$userRoles = verificarUsuario($_SESSION['user']);
if ($userRoles['codTypeRoles'] == 1) {
  header("Location: ../admScreen/home-adm.php");
}

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
  $userData = getUserData($pdo, $_SESSION['user']);
  $userPoints = getUserPoints($pdo, $_SESSION['user']);
  $userLevel = getUserLevel($pdo, $_SESSION['user']);
  $userFollowing = getFollowingCount($pdo, $_SESSION['user']);
  $userFollowers = getFollowersCount($pdo, $_SESSION['user']);
  $xpNecessario = xpNecessario($userLevel);
  $porcentagem = ($userPoints / $xpNecessario) * 100;
  // =====================================
  // CÁLCULO DA POSIÇÃO NO RANKING
  // =====================================
  $stmtRank = $pdo->prepare("
      SELECT COUNT(*) + 1 
      FROM userLevel ul
      JOIN userPoints up ON ul.emailLevel = up.emailPoints
      WHERE ul.userLevel > :myLevel
         OR (ul.userLevel = :myLevel AND up.userPoints > :myPoints)
  ");
  $stmtRank->execute(['myLevel' => $userLevel, 'myPoints' => $userPoints]);
  $userRank = $stmtRank->fetchColumn();

  // Busca os últimos 6 artigos e faz um JOIN para descobrir se o usuário acertou ou errou
  $stmtArtigos = $pdo->prepare("
      SELECT a.id, a.titulo, a.xp_recompensa, up.status, up.data_tentativa 
      FROM artigo a 
      LEFT JOIN usuario_progresso up ON a.id = up.id_artigo AND up.email_usuario = :email
      ORDER BY a.id DESC LIMIT 6
  ");
  $stmtArtigos->execute(['email' => $_SESSION['user']]);
  $artigos = $stmtArtigos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  echo 'Erro: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Home</title>
  <link rel="stylesheet" href="../global.css" />
  <link rel="stylesheet" href="../topics.css" />
  <link rel="stylesheet" href="../style.css" />
  <script src="../scripts/index.js" type="module" defer></script>
  <script src="../scripts/apiCards.js" defer></script>
  <script src="home-user.js" defer></script>
</head>

<body>
  <header id="main-header">
    <?php include __DIR__ . "/../navBar.php"; ?>
  </header>
  <main class="home-user-main">
    <section class="profile-dashboard">
      <div class="user-info">
        <div class="user-banner">
          <img src="../<?= $userData['bannerPerfil'] ?>" alt="Banner do Perfil" class="banner-img" />
        </div>

        <div class="user-profile">
          <div class="profile-avatar">
            <img src="../<?= $userData['fotoPerfil'] ?>" alt="Foto de Perfil" class="avatar-img" />
          </div>
          <div class="profile-details">
            <h1 class="user-name"><?php echo $userData['nome'] . " " . $userData['sobrenome']; ?></h1>
            <span class="user-handle"><?php echo "@" . $userData['nomeDeUsuario'] ?></span>
          </div>
          <div class="profile-stats">
            <div class="stat-item" id="btn-seguidores" style="cursor: pointer;">
              <span class="stat-count" id="count-seguidores"><?php echo $userFollowers; ?></span>
              <span class="stat-label">Seguidores</span>
            </div>
            <div class="stat-item" id="btn-seguindo" style="cursor: pointer;">
              <span class="stat-count" id="count-seguindo"><?php echo $userFollowing; ?></span>
              <span class="stat-label">Seguindo</span>
            </div>
          </div>
          <div class="profile-actions">
            <a href="edit-profile/editar-perfil.php" class="button btn-edit" style="font-size: 0.9rem;">Editar Perfil</a>
          </div>
        </div>

        <div class="user-status">
          <div class="status-info">
            <span class="level-text">Nível <?php echo $userLevel; ?></span>
            <span class="xp-text"><?php echo $userPoints . " / " . $xpNecessario;   ?> XP</span>
          </div>
          <div class="progress-bar-container">
            <div class="progress-bar-fill" style="width: <?php echo $porcentagem ?>%"></div>
          </div>
        </div>
      </div>

      <div class="user-extra-stats">
        <div class="extra-stats-grid">

          <div class="extra-card">
            <img src="medalha.png" alt="Medalha" class="extra-icon" />
            <span class="extra-title">Conquistas</span>
            <span class="extra-value">24</span>
          </div>

          <div class="extra-card">
            <img src="camada.png" alt="Camada" class="extra-icon" />
            <span class="extra-title">Camada</span>
            <span class="extra-value">Núcleo</span>
          </div>

          <div class="extra-card" id="btn-ranking" style="cursor: pointer;">
            <img src="../img/rank-icon.png" alt="Ranking" class="extra-icon" />
            <span class="extra-title">Ranking</span>
            <span class="extra-value ranking-destaque">#<?= $userRank ?></span>
          </div>

          <div class="extra-card">
            <img src="estrela.png" alt="Estrela" class="extra-icon" />
            <span class="extra-title">XP Atual</span>
            <span class="extra-value xp-value-small"><?php echo $userPoints . " / " . $xpNecessario; ?></span>
          </div>

        </div>
      </div>
    </section>

    <section class="dashboard-row-2">
      <div class="missions-container">
        <h2 class="section-title">Missões Espaciais</h2>

        <ul class="topics-cards-list visible fade-in animate missions-list-custom">
          <li class="topic-card" style="background: url(&quot;mercurio.png&quot;) center center / cover no-repeat;">
            <article>
              <header>
                <h3>Lançamento Diario</h3>
              </header>
              <footer>
                <p>Aqui é uma breve descrição descrevendo o desafio diario.</p>
                <a class="button">Iniciar Missão</a>
              </footer>
            </article>
          </li>

          <li class="topic-card" style="background: url(&quot;mercurio.png&quot;) center center / cover no-repeat;">
            <article>
              <header>
                <h3>Lançamento Semanal</h3>
              </header>
              <footer>
                <p>Aqui é uma breve descrição descrevendo o desafio semanal.</p>
                <a class="button">Iniciar Missão</a>
              </footer>
            </article>
          </li>
        </ul>
      </div>

      <div class="articles-container">
        <h2 class="section-title">Artigos</h2>
        <div class="articles-list">

          <?php if (!empty($artigos)): ?>
            <?php foreach ($artigos as $artigo): ?>
              <?php
              $classeStatus = '';
              $textoXp = '+' . htmlspecialchars($artigo['xp_recompensa']) . ' XP';

              if ($artigo['status'] === 'aprovado') {
                $classeStatus = 'article-aprovado';
                $textoXp = '✔ Concluído';
              } elseif ($artigo['status'] === 'reprovado') {
                $tentativa = strtotime($artigo['data_tentativa']);
                $agora = time();
                if (($agora - $tentativa) < 300) { // Menos de 5 minutos
                  $classeStatus = 'article-bloqueado';
                  $textoXp = '⏳ Tente novamente';
                } else { // Já pode tentar de novo
                  $classeStatus = 'article-tente-novamente';
                  $textoXp = '↻ Tente de Novo';
                }
              }
              ?>
              <a href="article-screen/artigo.php?id=<?= $artigo['id'] ?>" class="article-item <?= $classeStatus ?>">
                <span class="article-name"><?= htmlspecialchars($artigo['titulo']) ?></span>
                <span class="article-xp"><?= $textoXp ?></span>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: #a09bba; padding: 30px 0;">Não há artigos para exibir</p>
          <?php endif; ?>

        </div>
      </div>
    </section>

    <section class="explore-dashboard">
      <h2 class="section-title">Explore mais o nosso universo</h2>

      <ul class="topics-cards-list visible fade-in animate explore-list-custom">
        <li class="topic-card" style="background: url(&quot;mercurio.png&quot;) center center / cover no-repeat;">
          <article>
            <header>
              <h3>Lançamento Semanal</h3>
            </header>
            <footer>
              <p>Aqui é uma breve descrição descrevendo o desafio semanal.</p>
              <a href="#" class="button">Iniciar Missão</a>
            </footer>
          </article>
        </li>

        <li class="topic-card" style="background: url(&quot;mercurio.png&quot;) center center / cover no-repeat;">
          <article>
            <header>
              <h3>Lançamento Semanal</h3>
            </header>
            <footer>
              <p>Aqui é uma breve descrição descrevendo o desafio semanal.</p>
              <a class="button">Iniciar Missão</a>
            </footer>
          </article>
        </li>

        <li class="topic-card" style="background: url(&quot;mercurio.png&quot;) center center / cover no-repeat;">
          <article>
            <header>
              <h3>Lançamento Semanal</h3>
            </header>
            <footer>
              <p>Aqui é uma breve descrição descrevendo o desafio semanal.</p>
              <a class="button">Iniciar Missão</a>
            </footer>
          </article>
        </li>
      </ul>
    </section>

    <section class="topics">
      <h1 class="section-title">Explore por tópicos</h1>
      <ul class="topic-card-menu">
        <li class="pesquisa">
          <input type="text" name="pesquisa" id="pesquisa" placeholder="Pesquisar..." />
        </li>
        <li class="topic-option active">
          <button data-tipo="planets" class="planets-button"><span>🪐 Planetas</span></button>
        </li>
        <li class="topic-option">
          <button data-tipo="stars" class="stars-button"><span>⭐ Estrelas</span></button>
        </li>
        <li class="topic-option">
          <button data-tipo="galaxies"><span>🚀 Galáxias</span></button>
        </li>
        <li class="topic-option">
          <button data-tipo="cosmology"><span>💥 Cosmologia</span></button>
        </li>
        <li class="topic-option">
          <button data-tipo="others"><span>🔭 Outros</span></button>
        </li>
      </ul>

      <ul class="topics-cards-list planets visible fade-in"></ul>
      <ul class="topics-cards-list stars fade-in"></ul>
      <ul class="topics-cards-list galaxies fade-in"></ul>
      <ul class="topics-cards-list cosmology fade-in"></ul>
      <ul class="topics-cards-list others fade-in"></ul>
    </section>

    <div id="follow-modal" class="modal-overlay hidden">
      <div class="modal-content glass-card">
        <div class="modal-header">
          <h3 id="modal-title">Seguidores</h3>
          <button id="close-modal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <ul id="user-list-container" class="user-list">
          </ul>
        </div>
      </div>
    </div>

    <div id="ranking-modal" class="modal-overlay hidden">
      <div class="modal-content glass-card ranking-modal-content">
        <div class="modal-header">
          <h3 id="ranking-modal-title">🏆 Ranking Espacial</h3>
          <button id="close-ranking" class="close-btn">&times;</button>
        </div>

        <div class="ranking-tabs">
          <button class="rank-tab active" data-limit="10">Top 10</button>
          <button class="rank-tab" data-limit="50">Top 50</button>
        </div>

        <div class="modal-body ranking-body">
          <ul id="ranking-list-container" class="user-list">
          </ul>
        </div>

        <div class="ranking-my-position" id="my-ranking-container">
        </div>
      </div>
    </div>

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