<?php
session_start();
header('Content-Type: application/json');

// Pode ser disparado tanto do fluxo de cadastro (email_verificacao) quanto do usuário já logado (user) querendo reenviar
$emailSessao = $_SESSION['email_verificacao'] ?? $_SESSION['user'] ?? null;

if (!$emailSessao) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida para reenvio.']);
    exit();
}

require_once '../config.php';
require_once '../login/enviar_email.php';

$email = $emailSessao;

try {
    $pdo = getDB();
    
    // Verifica se a conta já não foi verificada
    $stmt = $pdo->prepare("SELECT email_verified FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $verified = $stmt->fetchColumn();

    if ($verified == 1) {
        echo json_encode(['success' => false, 'message' => 'Este e-mail já foi verificado!']);
        exit();
    }

    // Dispara o email
    $enviou = enviarEmailConfirmacao($pdo, $email);

    if ($enviou) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Houve um erro no envio. Tente novamente mais tarde.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}
