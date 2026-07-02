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
  <script src="../scripts/apiCards.js"></script>
</head>

<body>
  <header id="main-header">
    <?php include __DIR__ . "/../navBar.php"; ?>
  </header>
  <main class="home-user-main">
    <section class="profile-dashboard">
      <div class="user-info">
        <div class="user-banner">
          <img
            src="banner-exemplo.jpg"
            alt="Banner do Perfil"
            class="banner-img" />
        </div>

        <div class="user-profile">
          <div class="profile-avatar">
            <img
              src="../<?= $userData['fotoPerfil'] ?>"
              alt="Foto de Perfil"
              class="avatar-img" />
          </div>
          <div class="profile-details">
            <h1 class="user-name"><?php echo $userData['nome'] . " " . $userData['sobrenome']; ?></h1>
            <span class="user-handle"><?php echo "@" . $userData['nomeDeUsuario'] ?></span>
          </div>
          <div class="profile-stats">
            <div class="stat-item">
              <span class="stat-count"><?php echo $userFollowers; ?></span>
              <span class="stat-label">Seguidores</span>
            </div>
            <div class="stat-item">
              <span class="stat-count"><?php echo $userFollowing; ?></span>
              <span class="stat-label">Seguindo</span>
            </div>
          </div>
          <div class="profile-actions">
            <button class="button btn-edit">Editar Perfil</button>
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
        <div class="extra-stats-row">
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
        </div>

        <div class="extra-card pontos-card">
          <span class="extra-title">Pontos Estelares</span>
          <div class="pontos-destaque">
            <img src="estrela.png" alt="Estrela" class="star-icon" />
            <span class="pontos-value"><?php echo $userPoints . " / " . $xpNecessario; ?> XP</span>
          </div>
        </div>
      </div>
    </section>

    <section class="dashboard-row-2">
      <div class="missions-container">
        <h2 class="section-title">Missões Espaciais</h2>

        <ul
          class="topics-cards-list visible fade-in animate missions-list-custom">
          <li
            class="topic-card"
            style="
                background: url(&quot;mercurio.png&quot;) center center / cover
                  no-repeat;
              ">
            <article>
              <header>
                <h3>Lançamento Diario</h3>
              </header>
              <footer>
                <p>
                  Aqui é uma breve descrição descrevendo o desafio diario.
                </p>
                <a class="button">Iniciar Missão</a>
              </footer>
            </article>
          </li>

          <li
            class="topic-card"
            style="
                background: url(&quot;mercurio.png&quot;) center center / cover
                  no-repeat;
              ">
            <article>
              <header>
                <h3>Lançamento Semanal</h3>
              </header>
              <footer>
                <p>
                  Aqui é uma breve descrição descrevendo o desafio semanal.
                </p>
                <a class="button">Iniciar Missão</a>
              </footer>
            </article>
          </li>
        </ul>
      </div>

      <div class="articles-container">
        <h2 class="section-title">Artigos</h2>
        <div class="articles-list">
          <a href="#" class="article-item">
            <span class="article-name">Como os planetas...</span>
            <span class="article-xp">+150 XP</span>
          </a>
          <a href="#" class="article-item">
            <span class="article-name">Como os planetas...</span>
            <span class="article-xp">+150 XP</span>
          </a>
          <a href="#" class="article-item">
            <span class="article-name">Como os planetas...</span>
            <span class="article-xp">+150 XP</span>
          </a>
          <a href="#" class="article-item">
            <span class="article-name">Como os planetas...</span>
            <span class="article-xp">+150 XP</span>
          </a>
          <a href="#" class="article-item">
            <span class="article-name">Como os planetas...</span>
            <span class="article-xp">+150 XP</span>
          </a>
          <a href="#" class="article-item">
            <span class="article-name">Como os planetas...</span>
            <span class="article-xp">+150 XP</span>
          </a>
        </div>
      </div>
    </section>

    <section class="explore-dashboard">
      <h2 class="section-title">Explore mais o nosso universo</h2>

      <ul
        class="topics-cards-list visible fade-in animate explore-list-custom">
        <li
          class="topic-card"
          style="
              background: url(&quot;mercurio.png&quot;) center center / cover
                no-repeat;
            ">
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

        <li
          class="topic-card"
          style="
              background: url(&quot;mercurio.png&quot;) center center / cover
                no-repeat;
            ">
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

        <li
          class="topic-card"
          style="
              background: url(&quot;mercurio.png&quot;) center center / cover
                no-repeat;
            ">
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
          <input
            type="text"
            name="pesquisa"
            id="pesquisa"
            placeholder="Pesquisar..." />
        </li>
        <li class="topic-option active">
          <button data-tipo="planets" class="planets-button">
            <span>🪐 Planetas</span>
          </button>
        </li>
        <li class="topic-option">
          <button data-tipo="stars" class="stars-button">
            <span>⭐ Estrelas</span>
          </button>
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
  </main>

  <?php include_once "../footer.php" ?>
</body>

</html>