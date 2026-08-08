<?php
    session_start();

    if (!isset($_SESSION['permitido_reset05'])) {
        header("Location: mudarsenha.php");
        exit;
    }
    else{
        unset($_SESSION['permitido_reset05']);
    }
    
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mudar Senha</title>
  <link rel="stylesheet" href="../style.css" />
  <script src="../scripts/index.js" type="module" defer></script>
  <script src="mudarsenha05.js" defer></script>
</head>

<body>

    <header id="main-header" class="login-header">
        <?php include __DIR__ . "/../navBar.php"; ?>
    </header>

    <div>
        Redefinir senha<br>
        Selecione sua nova senha:<br>
        <form method="POST" action="mudarsenha06.php">
            <input
                type="password"
                id="passwordSign"
                name="passwordSign"
                placeholder="Nova Senha" />
            <p class="empty-password" style="display: none; color:red;"> <!-- TIRAR O STYLE, USAR O MUDARSENHA.CSS-->
                Esse campo não pode estar vazio!
            </p>
            <br>
            <input
              type="password"
              id="password2Sign"
              name="password2Sign"
              placeholder="Repita a Nova Senha" />
            <p class="empty-password2" style="display: none; color:red;"> <!-- TIRAR O STYLE, USAR O MUDARSENHA.CSS-->
              Esse campo não pode estar vazio!
            </p>
            <p class="empty-password3" style="display: none; color:red;"> <!-- TIRAR O STYLE, USAR O MUDARSENHA.CSS-->
              As senhas não coincidem!
            </p>

            <?php
              if (isset($_GET['erro'])) {
                echo "<p style='color:red;'>Erro, tente novamente!</p>";
              }
            ?>
            </div>
            <button type="submit" class="button submit-password">
                Enviar
            </button>
                
        </form>
    </div>
</body>