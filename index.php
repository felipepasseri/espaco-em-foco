<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="style.css" />
  <script src="scripts/index.js" type="module" defer></script>
  <script src="scripts/apiCards.js"></script>
  <title>Espaço em Foco</title>
</head>

<body>
  <header id="main-header">
    <?php include __DIR__ . "/navBar.php"; ?>
  </header>

  <main>
    <section class="hero">
      <div id="hero-img">
        <h1>Espaço em Foco</h1>
        <h3>Explorando o Universo</h3>
        <a href="" class="button">Aprenda Agora</a>
      </div>
    </section>
    <section class="topics">
      <h1>Descubra os mistérios do nosso universo</h1>
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

    <section class="about">
      <div class="content-container fade-in">
        <div class="text-content">
          <h1>Exploração cósmica</h1>
          <p>
            Descubra os segredos mais profundos do cosmos. Desde buracos
            negros até nebulosas distantes, nosso conteúdo leva você a uma
            jornada através das maravilhas do universo.
          </p>
        </div>
        <div class="img-about">
          <img src="img/planet.png" alt="" />
        </div>
      </div>

      <div class="content-container fade-in">
        <div class="text-content">
          <h1>Ciência Estelar</h1>
          <p>
            Entenda como nascem, vivem e morrem as estrelas. Aprenda sobre
            supernovas, anãs brancas e os fenômenos mais espetaculares do
            espaço profundo.
          </p>
        </div>
        <div class="img-about">
          <img src="img/planet.png" alt="" />
        </div>
      </div>

      <div class="content-container fade-in">
        <div class="text-content">
          <h1>Vida no Universo</h1>
          <p>
            Existe vida além da Terra? Explore as últimas descobertas sobre
            exoplanetas habitáveis, astrobiologia e a busca por inteligência
            extraterrestre.
          </p>
        </div>
        <div class="img-about">
          <img src="img/planet.png" alt="" />
        </div>
      </div>

      <div class="content-container fade-in">
        <div class="text-content">
          <h1>Tecnologia Espacial</h1>
          <p>
            Conheça as missões espaciais mais ambiciosas da humanidade. De
            rovers em Marte a telescópios no espaço profundo, a tecnologia que
            expande nossos horizontes.
          </p>
        </div>
        <div class="img-about">
          <img src="img/planet.png" alt="" />
        </div>
      </div>
    </section>

    <section class="team fade-in">
      <h1>Conheça nossa equipe</h1>
      <h4>Somos alunos da Etec</h4>
      <div id="team-content">
        <div id="caua">
          <div class="img caua fade-in">
            <h2>Cauã Costa de Camargo</h2>
          </div>
        </div>

        <div id="felipe">
          <div class="img felipe fade-in">
            <h2>Felipe Passeri Reis</h2>
          </div>
        </div>

        <div id="lucas">
          <div class="img lucas fade-in">
            <h2>Lucas Guidetti Gonzalez</h2>
          </div>
        </div>

        <div id="guilherme">
          <div class="img guilherme fade-in">
            <h2>Guilherme Moura Gmeiner</h2>
          </div>
        </div>
      </div>
    </section>

    <section class="pre-footer fade-in">
      <div class="pre-footer-img">
        <h1>Você está pronto?</h1>
        <h3>Uma aventura emocionante através do Universo</h3>
        <a class="button" href="#">Aprenda agora</a>
      </div>
    </section>
  </main>

  <footer id="main-footer">
    <div class="main-content">
      <div class="left-content">
        <h1>Espaço em Foco</h1>
        <h4>Explorando nosso universo</h4>
      </div>

      <div class="right-content">
        <div class="social-media">
          <h2>Redes sociais</h2>
          <ul>
            <li>
              <img src="img/instagram-icon.jpg" alt="" />
              <a href="#"> Felipe </a>
            </li>
            <li>
              <img src="img/instagram-icon.jpg" alt="" />
              <a href="#">Lucas</a>
            </li>
            <li>
              <img src="img/instagram-icon.jpg" alt="" />
              <a href="#">Guilherme</a>
            </li>
          </ul>
        </div>

        <div class="suport">
          <h2>Suporte</h2>
          <ul>
            <li>
              <a href="#">Contato</a>
            </li>
            <li>
              <a href="#">WhatsApp</a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="copyright">
      <p>©2026 Espaço em Foco. Todos os direitos reservados.</p>
    </div>
  </footer>
</body>

</html>