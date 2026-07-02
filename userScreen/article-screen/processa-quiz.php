<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit();
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../calcularXp.php';

$email = $_SESSION['user'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['erro' => 'Dados inválidos']);
    exit();
}

$pergunta_id = $data['pergunta_id'];
$artigo_id = $data['artigo_id'];
$resposta_usuario = $data['resposta'];
$tipo = $data['tipo'];

try {
    $pdo = getDB();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ==========================================
    // 1. VERIFICA SE ESTÁ EM COOLDOWN OU JÁ FOI APROVADO
    // ==========================================
    $stmtProg = $pdo->prepare("SELECT status, data_tentativa FROM usuario_progresso WHERE email_usuario = :email AND id_artigo = :artigo_id");
    $stmtProg->execute(['email' => $email, 'artigo_id' => $artigo_id]);
    $progresso = $stmtProg->fetch(PDO::FETCH_ASSOC);

    if ($progresso) {
        if ($progresso['status'] === 'aprovado') {
            echo json_encode(['acertou' => true, 'xp_ganho' => 0, 'ja_feito' => true]);
            exit();
        }

        // Se foi reprovado, checa se passaram 5 minutos (300 segundos)
        $tentativa_time = strtotime($progresso['data_tentativa']);
        $agora = time();
        $diferenca = $agora - $tentativa_time;
        if ($diferenca < 300) {
            echo json_encode(['erro' => 'cooldown', 'restante' => (300 - $diferenca)]);
            exit();
        }
    }

    // ==========================================
    // 2. VALIDA A RESPOSTA
    // ==========================================
    $acertou = false;
    if ($tipo === 'multipla_escolha') {
        $stmt = $pdo->prepare("SELECT is_correct FROM quiz_alternativa WHERE id = :resposta_id AND id_pergunta = :pergunta_id");
        $stmt->execute(['resposta_id' => $resposta_usuario, 'pergunta_id' => $pergunta_id]);
        $alt = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($alt && $alt['is_correct'] == 1) $acertou = true;
    } else {
        $stmt = $pdo->prepare("SELECT resposta_esperada FROM quiz_pergunta WHERE id = :pergunta_id");
        $stmt->execute(['pergunta_id' => $pergunta_id]);
        $perg = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($perg && strtolower(trim($perg['resposta_esperada'])) === strtolower(trim($resposta_usuario))) {
            $acertou = true;
        }
    }

    $pdo->beginTransaction();

    // ==========================================
    // 3. SE ERROU (Salva o erro e aciona cooldown)
    // ==========================================
    if (!$acertou) {
        if ($progresso) { // Já tinha errado antes, atualiza a tentativa
            $stmtUp = $pdo->prepare("UPDATE usuario_progresso SET status = 'reprovado', data_tentativa = NOW(), resposta_dada = :resp WHERE email_usuario = :email AND id_artigo = :artigo_id");
            $stmtUp->execute(['resp' => $resposta_usuario, 'email' => $email, 'artigo_id' => $artigo_id]);
        } else { // Primeira vez que errou
            $stmtIn = $pdo->prepare("INSERT INTO usuario_progresso (email_usuario, id_artigo, data_tentativa, status, resposta_dada) VALUES (:email, :artigo_id, NOW(), 'reprovado', :resp)");
            $stmtIn->execute(['email' => $email, 'artigo_id' => $artigo_id, 'resp' => $resposta_usuario]);
        }
        $pdo->commit();
        echo json_encode(['acertou' => false]);
        exit();
    }

    // ==========================================
    // 4. SE ACERTOU (Salva o acerto e dá o XP)
    // ==========================================
    $stmtArt = $pdo->prepare("SELECT xp_recompensa FROM artigo WHERE id = :artigo_id");
    $stmtArt->execute(['artigo_id' => $artigo_id]);
    $xp_recompensa = $stmtArt->fetchColumn();

    if ($progresso) {
        $stmtUp = $pdo->prepare("UPDATE usuario_progresso SET status = 'aprovado', data_tentativa = NOW(), resposta_dada = :resp WHERE email_usuario = :email AND id_artigo = :artigo_id");
        $stmtUp->execute(['resp' => $resposta_usuario, 'email' => $email, 'artigo_id' => $artigo_id]);
    } else {
        $stmtIn = $pdo->prepare("INSERT INTO usuario_progresso (email_usuario, id_artigo, data_tentativa, status, resposta_dada) VALUES (:email, :artigo_id, NOW(), 'aprovado', :resp)");
        $stmtIn->execute(['email' => $email, 'artigo_id' => $artigo_id, 'resp' => $resposta_usuario]);
    }

    $stmtUpdatePoints = $pdo->prepare("UPDATE userPoints SET userPoints = userPoints + :xp WHERE emailPoints = :email");
    $stmtUpdatePoints->execute(['xp' => $xp_recompensa, 'email' => $email]);

    $stmtGetXP = $pdo->prepare("SELECT userPoints FROM userPoints WHERE emailPoints = :email");
    $stmtGetXP->execute(['email' => $email]);
    $pontosAtuais = $stmtGetXP->fetchColumn();

    $stmtGetLevel = $pdo->prepare("SELECT userLevel FROM userLevel WHERE emailLevel = :email");
    $stmtGetLevel->execute(['email' => $email]);
    $nivelAtual = $stmtGetLevel->fetchColumn();

    $upouDeNivel = false;
    $novoNivel = $nivelAtual;

    while ($pontosAtuais >= xpNecessario($novoNivel)) {
        $pontosAtuais -= xpNecessario($novoNivel);
        $novoNivel++;
        $upouDeNivel = true;
    }

    if ($upouDeNivel) {
        $stmtLvlUp = $pdo->prepare("UPDATE userLevel SET userLevel = :novo_nivel WHERE emailLevel = :email");
        $stmtLvlUp->execute(['novo_nivel' => $novoNivel, 'email' => $email]);

        $stmtUpdateRestante = $pdo->prepare("UPDATE userPoints SET userPoints = :pontos_restantes WHERE emailPoints = :email");
        $stmtUpdateRestante->execute(['pontos_restantes' => $pontosAtuais, 'email' => $email]);
    }

    $pdo->commit();
    echo json_encode(['acertou' => true, 'xp_ganho' => $xp_recompensa, 'ja_feito' => false, 'upou_de_nivel' => $upouDeNivel, 'novo_nivel' => $novoNivel]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['erro' => 'Erro interno.', 'detalhe' => $e->getMessage()]);
}
