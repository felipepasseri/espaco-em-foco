<?php
// 1. Defina um valor padrão caso o usuário não esteja logado
$userProfilePhoto = 'img/user-profile-default.jpg';

if (isset($_SESSION['user'])) {
    require_once __DIR__ . "/config.php";
    $pdo = getDB();
    $stmt7 = $pdo->prepare('SELECT fotoPerfil FROM user WHERE email = :email;');
    $stmt7->execute(['email' => $_SESSION['user']]);
    $user = $stmt7->fetch(PDO::FETCH_ASSOC);
    if ($user && !empty($user['fotoPerfil'])) {
        $userProfilePhoto = $user['fotoPerfil'];
    }
}

$isHomeUser = (basename($_SERVER['PHP_SELF']) == 'home-user.php');
?>

<nav>
    <ul id="logo-container">
        <div id="logo"></div>
        <h1>Espaço em Foco</h1>
    </ul>
    <ul id="main-nav-container">
        <?php if ($isHomeUser) { ?>
            <li><a id="nav-inicio">Início</a></li>
            <li><a id="nav-missoes">Missões</a></li>
            <li><a id="nav-topicos">Tópicos</a></li>
            <li><a href="/espaco-em-foco/userScreen/community.php">Comunidade</a></li>
        <?php } else { ?>
            <li>Início</li>
            <li>Tópicos</li>
            <li>Sobre</li>
            <li>Equipe</li>
        <?php } ?>
        <?php if (isset($_SESSION['user'])) { ?>
            <li class="mobile-profile-container">
                <a href="/espaco-em-foco/userScreen/edit-profile/editar-perfil.php" class="mobile-profile-link">Editar Perfil</a>
                <a href="/espaco-em-foco/logoff.php" class="mobile-profile-link">Sair</a>
            </li>
        <?php } else { ?>
            <li class="mobile-login-link"><a href="/espaco-em-foco/login/login.php" class="button"><span>Login</span></a></li>
        <?php } ?>
    </ul>
    <ul id="login-container">
        <?php if (!isset($_SESSION['user'])) { ?>
            <li>
                <a href="/espaco-em-foco/login/login.php" class="button"><span>Login</span></a>
            </li>
        <?php } else { ?>
            <div class="profile-dropdown">
                <div id="login-icon" style="background: url('/espaco-em-foco/<?= $userProfilePhoto ?>') center center / cover no-repeat; cursor: pointer;" onclick="toggleProfileDropdown(event)"></div>
                <div id="profile-dropdown-content" class="dropdown-content">
                    <a href="/espaco-em-foco/userScreen/edit-profile/editar-perfil.php">Editar Perfil</a>
                    <a href="/espaco-em-foco/logoff.php">Sair</a>
                </div>
            </div>
            <script>
            
            </script>
        <?php } ?>
    </ul>
    <div id="hamburger-btn" class="hamburger-icon">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </div>
</nav>