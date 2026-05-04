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
        <div id="login-icon"></div>
    </ul>
    <label for="menu-header"><img src="/img/menu-header.png" alt="" /></label>
    <input type="checkbox" name="" id="menu-header" />
</nav>