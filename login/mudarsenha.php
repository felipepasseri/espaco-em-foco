<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mudar Senha</title>
  <link rel="stylesheet" href="../style.css" />
  <script src="../scripts/index.js" type="module" defer></script>
  <script src="mudarsenha.js" defer></script>
</head>

<body>
    <header id="main-header" class="login-header">
        <?php include __DIR__ . "/../navBar.php"; ?>
    </header>

    <div>
        Redefinir senha<br>
        <form method="POST" action="mudarsenha02.php">
            <div class="email">
                <input
                  type="email"
                  id="emailSign"
                  name="emailSign"
                  placeholder="Email" />
                <p class="empty-email">
                  Esse campo não pode estar vazio!
                </p>
              </div>
              <button type="submit" class="button submit-email">
                Criar Conta
              </button>
        </form>
    </div>
</body>