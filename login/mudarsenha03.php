<?php
    session_start();

    if (!isset($_SESSION['permitido_reset'])) {
        header("Location: mudarsenha.php");
        exit;
    }
    else{
        unset($_SESSION['permitido_reset']);
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
  <script src="mudarsenha03.js" defer></script>
</head>

<body>

    <header id="main-header" class="login-header">
        <?php include __DIR__ . "/../navBar.php"; ?>
    </header>

    <div>
        Redefinir senha<br>
        Um código foi enviado para seu email, digite ele aqui:<br>
        <form method="POST" action="mudarsenha04.php">
            <div class="code">
                <input
                  type="text"
                  id="codeSign"
                  name="codeSign"
                  placeholder="Código" />
                <p class="empty-email">
                  Esse campo não pode estar vazio!
                </p>
                <?php
                  if (isset($_GET['erro'])) {
                    echo "<p style='color:red;'>Código errado!</p>";
                  }
                ?>
              </div>
              <button type="submit" class="button submit-code">
                Enviar
              </button>
        </form>
    </div>
</body>