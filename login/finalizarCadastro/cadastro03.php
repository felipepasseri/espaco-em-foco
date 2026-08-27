<?php
session_start();

if (!isset($_SESSION['email_verificacao']) && !isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../config.php';

$erroEmail = isset($_GET['erro_email']) && $_GET['erro_email'] == '1';
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Espaço em Foco - Verificação de E-mail</title>
    <link rel="stylesheet" href="../../style.css" />
    <link rel="stylesheet" href="../../css/verificacao.css" />
    <script src="../../scripts/reenviar-email.js" defer></script>
</head>

<body>
    <header id="main-header" class="login-header">
        <?php include __DIR__ . "/../../navBar.php"; ?>
    </header>

    <main class="login-main">
        <section class="verification-section">
            <div class="verification-card">
                <div class="icon-symbol">📧</div>
                <h2>Verifique seu E-mail</h2>

                <?php if ($erroEmail): ?>
                    <p class="text-danger">
                        Seu cadastro foi realizado, mas tivemos um problema ao enviar o e-mail de confirmação. Por favor, tente reenviar no botão abaixo.
                    </p>
                <?php else: ?>
                    <p>
                        Seu cadastro foi realizado com sucesso! Acabamos de enviar um e-mail com um link de verificação. Clique no link para ativar a sua conta.
                    </p>
                <?php endif; ?>

                <p class="small-text">Não recebeu o e-mail? Verifique sua caixa de spam.</p>

                <button class="button" id="btn-resend" onclick="reenviarEmail({ btnId: 'btn-resend', textId: 'btn-text', feedbackId: 'feedback-msg', apiPath: '../../api/api-resend-email.php' })">
                    <span id="btn-text">Reenviar E-mail</span>
                </button>
                
                <div id="feedback-msg" class="feedback-msg"></div>
            </div>
        </section>
    </main>
</body>

</html>
