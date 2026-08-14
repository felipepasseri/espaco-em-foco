<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
}
require_once __DIR__ . '/../login/verify-user.php';
$userRoles = verificarUsuario($_SESSION['user']);
if ($userRoles['codTypeRoles'] == 0) {
    header("Location: ../userScreen/home-user.php");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css" />
    <script src="../scripts/index.js" type="module" defer></script>
    <script src="../scripts/apiCardsAdiciona.js" defer></script>
    <script src="../scripts/adicionaCard.js" type="module" defer></script>
    <title>Document</title>
</head>

<body>
    <header id="main-header">
        <?php include __DIR__ . "/../navBar.php"; ?>
    </header>
    <form id="cardForm" method="POST" action="adicionaCard2.php" enctype="multipart/form-data">
        <label for="image">Imagem:</label>
        <input type="file" id="image" name="image" required>

        <label for="title">Título:</label>
        <input type="text" id="title" name="title" required>

        <label for="description">Descrição:</label>
        <textarea id="description" name="description" required></textarea>

        <label for="type">Tipo:</label>
        <select id="type" name="type" required>
            <option value="planets">Planets</option>
            <option value="stars">Stars</option>
            <option value="galaxies">Galáxias</option>
            <option value="cosmology">Cosmologia</option>
            <option value="other">Other</option>
        </select>

        <input type="submit" value="Submit">
    </form>

    <section class="topics">
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

</body>

</html>