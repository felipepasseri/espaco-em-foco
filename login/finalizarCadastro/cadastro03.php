<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Completar Perfil</title>

    <link rel="stylesheet" href="../../style.css" />
    <link rel="stylesheet" href="../login.css" />
    <link rel="stylesheet" href="cadastro03.css">
</head>

<body>
    <header id="main-header" class="login-header">
        <?php include __DIR__ . "/../../navBar.php"; ?>
    </header>

    <main class="login-main">
        <section class="sign-up-section">

            <div class="welcome-panel">
                <div class="welcome-content signup-active">
                    <h1>Quase lá!</h1>
                    <h4>
                        Falta muito pouco para você começar a explorar <span>🚀</span><br /><br />
                        Defina como você quer ser chamado no universo do Espaço em Foco e adicione uma foto para que a comunidade possa te conhecer. <br />
                        (Se você não colocar nada, a foto de perfil e o nickname serão preenchidos de forma automática)
                    </h4>
                </div>
            </div>

            <div class="signup-form-panel">
                <div class="form-content signup-active" style="align-items: center; text-align: center;">
                    <h2>Seu Perfil</h2>
                    <p>Personalize sua identidade no sistema.</p>

                    <form method="POST" action="cadastro04.php" enctype="multipart/form-data" style="max-width: 350px; width: 100%; margin: 0 auto;">

                        <div class="profile-pic-container">
                            <label for="profilePic" class="profile-pic-preview" id="previewContainer">
                                <span id="placeholderText">Adicionar<br>Foto</span>
                                <img id="imagePreview" src="" alt="Pré-visualização" />
                            </label>
                            <input type="file" id="profilePic" name="profilePic" accept="image/*" />
                        </div>

                        <div style="width: 100%;">
                            <input
                                type="text"
                                id="nickname"
                                name="nickname"
                                placeholder="Defina seu Nickname" />
                        </div>

                        <?php if (isset($_GET['erro']) && $_GET['erro'] == 'nickname_exists'): ?>
                            <p style="color: #FF0000; font-size: 0.875rem; margin-top: -20px; text-align: left; margin-bottom: 5px">
                                Um outro usuário já tem esse nickname
                            </p>
                        <?php endif; ?>

                        <div class="buttons-sign" style="width: 100%;">
                            <button type="submit" class="button create-account-btn">
                                Finalizar Cadastro
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
<script src="cadastro03.js" defer></script>

</html>