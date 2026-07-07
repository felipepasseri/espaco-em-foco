<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

require_once __DIR__ . '/../../config.php';
require_once '../user-functions.php';

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $userData = getUserData($pdo, $_SESSION['user']);
    $fotoAtual = $userData['fotoPerfil'] ? '../' . $userData['fotoPerfil'] : '../img/user-profile-default.jpg';
    $bannerAtual = !empty($userData['bannerPerfil']) ? '../' . $userData['bannerPerfil'] : 'banner-exemplo.jpg';
} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Editar Perfil - Espaço em Foco</title>
    <link rel="stylesheet" href="../../global.css" />
    <link rel="stylesheet" href="../../style.css" />
    <link rel="stylesheet" href="editar-perfil.css" />
    <script src="../scripts/index.js" type="module" defer></script>
    <script src="editar-perfil.js" defer></script>

</head>

<body>
    <header id="main-header">
        <?php include __DIR__ . "/../../navBar.php"; ?>
    </header>

    <main class="edit-profile-main fade-in animate">
        <div class="edit-container glass-card">
            <div class="edit-header">
                <a href="../home-user.php" class="back-link">← Voltar ao Perfil</a>
                <h2>Configurações da Conta</h2>

                <?php if (isset($_GET['erro']) && $_GET['erro'] == 'senha'): ?>
                    <div class="alert-error">A senha atual está incorreta.</div>
                <?php elseif (isset($_GET['erro']) && $_GET['erro'] == 'email'): ?>
                    <div class="alert-error">Este e-mail já está em uso por outra conta.</div>
                <?php endif; ?>
            </div>

            <form action="processa-editar.php" method="POST" enctype="multipart/form-data" class="edit-form">

                <div class="images-section">
                    <div class="banner-upload">
                        <img id="banner-preview" src="../<?= $bannerAtual ?>" alt="Banner Atual">
                        <div class="upload-overlay">
                            <label for="banner-input" class="upload-btn">Alterar Banner</label>
                            <input type="file" id="banner-input" name="banner" accept="image/png, image/jpeg" hidden>
                        </div>
                    </div>

                    <div class="avatar-upload">
                        <img id="avatar-preview" src="/espaco-em-foco/img/<?= $fotoAtual ?>" alt="Foto de Perfil Atual">
                        <div class="upload-overlay">
                            <label for="avatar-input" class="upload-btn avatar-btn">📷</label>
                            <input type="file" id="avatar-input" name="foto" accept="image/png, image/jpeg" hidden>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="input-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($userData['nome']) ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="sobrenome">Sobrenome</label>
                        <input type="text" id="sobrenome" name="sobrenome" value="<?= htmlspecialchars($userData['sobrenome']) ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="username">Nome de Usuário</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($userData['nomeDeUsuario']) ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_SESSION['user']) ?>" required>
                    </div>

                    <div class="input-group full-width security-header">
                        <h3>Segurança</h3>
                    </div>

                    <div class="input-group">
                        <label for="senhaAtual">Senha Atual <span class="required">*</span></label>
                        <input type="password" id="senhaAtual" name="senhaAtual" required placeholder="Digite para autorizar as mudanças">
                        <a href="../esqueci-senha.php" class="forgot-pwd-link">Esqueci minha senha</a>
                    </div>

                    <div class="input-group">
                        <label for="senha">Nova Senha <span class="optional">(Deixe em branco para manter)</span></label>
                        <input type="password" id="senha" name="senha" placeholder="Digite apenas se quiser alterar">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="../home-user.php" class="btn-cancelar">Cancelar</a>
                    <button type="submit" class="btn-salvar">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </main>

    <?php include_once "../../footer.php" ?>

</body>

</html>