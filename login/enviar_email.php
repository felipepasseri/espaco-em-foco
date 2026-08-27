<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../src/Exception.php';
require_once __DIR__ . '/../src/PHPMailer.php';
require_once __DIR__ . '/../src/SMTP.php';

function enviarEmailConfirmacao($pdo, $emailDestino) {
    // 1. Gera token e salva no banco
    $token = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', strtotime('+20 minutes'));

    // Remove tokens anteriores deste usuário para evitar acumulo
    $stmtDel = $pdo->prepare("DELETE FROM email_verifications WHERE user_email = ?");
    $stmtDel->execute([$emailDestino]);

    // Insere novo token (used_at pode ir null por enquanto)
    $stmt = $pdo->prepare("INSERT INTO email_verifications (user_email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$emailDestino, $hashedToken, $expires]);

    // 2. Envia o e-mail
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'espacoemfoco509@gmail.com';
        $mail->Password   = 'lcrzkjufkhbcyfmk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // 465 usually SMTPS
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom('espacoemfoco509@gmail.com', 'Espaço em Foco');
        $mail->addAddress($emailDestino);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Confirme seu cadastro - Espaço em Foco';
        
        // Tentativa de pegar host dinamico mas fallback para o projeto
        $host = $_SERVER['HTTP_HOST'];
        
        // Se estiver em ambiente local (XAMPP), mantém a estrutura com a pasta do projeto
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $link = "http://localhost/espaco-em-foco/login/confirmar-usuario.php?t=" . $token;
        } else {
            // Em produção (servidor real), direciona diretamente para o domínio oficial usando HTTPS
            $link = "https://espacoemfoco.online/login/confirmar-usuario.php?t=" . $token;
        }
        
        $mail->Body    = "
            <h2>Falta pouco para concluir seu cadastro!</h2>
            <p>Obrigado por se juntar ao Espaço em Foco. Para garantir a segurança da sua conta e concluir o seu registro, precisamos que você verifique este e-mail.</p>
            <p><strong><a href='{$link}' style='display:inline-block; padding:10px 15px; background-color:#ff3366; color:#ffffff; text-decoration:none; border-radius:5px;'>Confirmar E-mail</a></strong></p>
            <p>Ou acesse este link: {$link}</p>
            <p>Este link é válido por 20 minutos.</p>
            <br>
            <p>Equipe Espaço em Foco.</p>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // em caso de erro log (opcional)
        // file_put_contents('error_email.log', $mail->ErrorInfo, FILE_APPEND);
        return $e->getMessage();
    }
}
