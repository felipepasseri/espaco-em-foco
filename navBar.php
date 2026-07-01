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
?>
<nav>
    <ul id="logo-container">
        <div id="logo"></div>
        <h1>Espaço em Foco</h1>
    </ul>
    <ul id="main-nav-container">
        <li>Início</li>
        <li>Tópicos</li>
        <li>Sobre</li>
        <li>Equipe</li>
    </ul>
    <ul id="login-container">
        <?php if (!isset($_SESSION['user'])) { ?>
            <li>
                <a href="login/login.php" class="button"><span>Login</span></a>
            </li>
        <?php } ?>
        <?php
            session_start();
            require_once __DIR__ . '/login/verify-user.php';
            $userRoles = verificarUsuario($_SESSION['user']);
            if (isset($_SESSION['user'])) {
                $userRoles = verificarUsuario($_SESSION['user']);

                if ($userRoles['codTypeRoles'] == 1) { ?>
                    <li>
                        <a href="https://www.espacoemfoco.online/admScreen/home-adm.php" class="button"><span>Admin</span></a>
                    </li>
                <?php } 
            } ?>     

        <div id="login-icon" style="background: url('../<?= $userProfilePhoto ?>') center center / cover no-repeat;"></div>
    </ul>
    <label for="menu-header"><img src="/img/menu-header.png" alt="" /></label>
    <input type="checkbox" name="" id="menu-header" />
</nav>