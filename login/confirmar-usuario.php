<?php
session_start();
require_once '../config.php';

$tokenUrl = $_GET['t'] ?? '';

if (empty($tokenUrl)) {
    header("Location: login.php");
    exit();
}

$pdo = getDB();
$hashedToken = hash('sha256', $tokenUrl);

// Busca o token no BD
$stmt = $pdo->prepare("SELECT id, user_email, expires_at FROM email_verifications WHERE token = ? AND used_at IS NULL");
$stmt->execute([$hashedToken]);
$verif = $stmt->fetch(PDO::FETCH_ASSOC);

$linkInvalido = false;
$linkExpirado = false;

if (!$verif) {
    // Token não existe ou já foi usado
    $linkInvalido = true;
} else {
    $expires = strtotime($verif['expires_at']);
    $now = time();

    if ($now > $expires) {
        // Expirou
        $linkExpirado = true;
        $_SESSION['email_verificacao'] = $verif['user_email']; // pra API de reenvio saber quem é
    } else {
        // Sucesso!
        $pdo->beginTransaction();
        try {
            // Marca user como verificado
            $stmtUpd = $pdo->prepare("UPDATE user SET email_verified = 1 WHERE email = ?");
            $stmtUpd->execute([$verif['user_email']]);

            // Deleta o token (ou seta used_at, mas o user pediu para excluir do bd usado)
            $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE token = ?");
            $stmt->execute([$hashedToken]);

            $pdo->commit();
            
            // Logar o usuário (já que confirmou o e-mail, pode acessar!)
            $_SESSION['user'] = $verif['user_email'];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $linkInvalido = true; // Cai na mensagem genérica
        }
    }
}

if ($linkInvalido) {
    // A instrução diz "se for inválido, ele joga pra tela de login"
    header("Location: login.php");
    exit();
}

?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Espaço em Foco - Verificação</title>
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="../css/verificacao.css" />
    <script src="../scripts/reenviar-email.js" defer></script>
</head>
<body>
    <header id="main-header" class="login-header">
        <?php include __DIR__ . "/../navBar.php"; ?>
    </header>

    <main class="login-main">
        <section class="verification-section">
            <div class="verification-card">
                
                <?php if ($linkExpirado): ?>
                    <div class="icon-symbol">⏳</div>
                    <h2 class="text-danger">Link Expirado</h2>
                    <p>
                        O seu link de verificação expirou após os 20 minutos de limite de segurança.<br>
                        Por favor, clique no botão abaixo para receber um novo link de acesso.
                    </p>
                    <button class="button" id="resendBtn" onclick="reenviarEmail({ btnId: 'resendBtn', feedbackId: 'resendFeedback', apiPath: '../api/api-resend-email.php' })">
                        Reenviar E-mail
                    </button>
                    <div id="resendFeedback" class="feedback-msg"></div>
                    
                <?php else: ?>
                    <div class="icon-symbol">✅</div>
                    <h2 class="text-success">Tudo certo!</h2>
                    <p>
                        O seu e-mail foi verificado com sucesso. Vamos configurar seu perfil no Espaço em Foco!
                    </p>
                    <a href="finalizarCadastro/cadastro04.php" class="button">
                        Completar Perfil
                    </a>
                <?php endif; ?>

            </div>
        </section>
    </main>
</body>
</html>
