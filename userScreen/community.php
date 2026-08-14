<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
  exit();
}
require_once __DIR__ . '/../login/verify-user.php';
require_once __DIR__ . '/../config.php';

$userRoles = verificarUsuario($_SESSION['user']);
if ($userRoles['codTypeRoles'] == 1) {
  header("Location: ../admScreen/home-adm.php");
}
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Comunidade - Espaço em Foco</title>
  <meta name="description" content="Fórum da comunidade Espaço em Foco - Discuta sobre astronomia e espaço" />
  <link rel="stylesheet" href="../style.css" />
  <link rel="stylesheet" href="../css/community.css" />
  <script src="../scripts/community.js" defer></script>
</head>

<body>
  <header id="main-header">
    <?php include __DIR__ . "/../navBar.php"; ?>
  </header>

  <main class="community-main">

    <!-- =========================================
       TABS SUPERIORES (Postagens / Curtidos / Reposts)
    ========================================= -->
    <div class="community-tabs">
      <button class="community-tab active" data-type="all">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        Postagens
      </button>
      <button class="community-tab" data-type="liked">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        Curtidos
      </button>
      <button class="community-tab" data-type="repost">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
        Reposts
      </button>
    </div>

    <div class="community-layout">

      <!-- =========================================
         SIDEBAR ESQUERDA — FILTROS POR TÓPICO
      ========================================= -->
      <aside class="community-sidebar-left">
        <h3 class="sidebar-title">Filtrar por Tópico</h3>
        <ul class="topic-filter-list">
          <li><button class="topic-filter-btn active" data-topic="all">🌌 Todos</button></li>
          <li><button class="topic-filter-btn" data-topic="Planetas">🪐 Planetas</button></li>
          <li><button class="topic-filter-btn" data-topic="Estrelas">⭐ Estrelas</button></li>
          <li><button class="topic-filter-btn" data-topic="Galáxias">🚀 Galáxias</button></li>
          <li><button class="topic-filter-btn" data-topic="Cosmologia">💥 Cosmologia</button></li>
          <li><button class="topic-filter-btn" data-topic="Outros">🔭 Outros</button></li>
        </ul>
      </aside>

      <!-- =========================================
         FEED CENTRAL — POSTS
      ========================================= -->
      <section class="community-feed">
        <div id="posts-container" class="posts-container">
          <div class="loading-indicator">Carregando postagens...</div>
        </div>
        <button id="load-more-btn" class="load-more-btn hidden">Carregar mais</button>
      </section>

      <!-- =========================================
         SIDEBAR DIREITA — PESQUISA + AMIGOS
      ========================================= -->
      <aside class="community-sidebar-right">
        <div class="search-container">
          <input type="text" id="community-search" class="community-search-input" placeholder="Pesquisar postagens..." />
        </div>

        <div class="friends-section">
          <h3 class="sidebar-title">Amigos</h3>
          <ul id="friends-list" class="friends-list"></ul>
          <button id="btn-ver-todos-amigos" class="see-all-btn hidden">Ver todos</button>
        </div>
      </aside>

    </div>

    <!-- =========================================
       FAB — CRIAR POSTAGEM E ARTIGO
    ========================================= -->
    <div class="fab-container">
      <a href="create-post.php" class="fab-btn fab-create-post" id="fab-create-post" title="Criar Postagem">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Criar Postagem</span>
      </a>
      <a href="create-article.php" class="fab-btn fab-create-article" id="fab-create-article" title="Novo Artigo">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        <span>Novo Artigo</span>
      </a>
    </div>

    <!-- =========================================
       MODAL — VER TODOS OS AMIGOS
    ========================================= -->
    <div id="friends-modal" class="modal-overlay hidden">
      <div class="modal-content glass-card">
        <div class="modal-header">
          <h3>Todos os Amigos</h3>
          <button id="close-friends-modal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <ul id="friends-modal-list" class="user-list"></ul>
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
