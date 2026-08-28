<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../user-functions.php';
require_once __DIR__ . '/../calcularXp.php';

$email = $_SESSION['user'];

if (!isset($_SESSION['quiz_atual']) || empty($_SESSION['quiz_atual']['respostas'])) {
    echo json_encode(['success' => false, 'error' => 'Nenhuma resposta encontrada para processar.']);
    exit();
}

$id_artigo = $_SESSION['quiz_atual']['id_artigo'];
$respostas = $_SESSION['quiz_atual']['respostas']; // Array associativo de id_pergunta => [...]

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    // 1. Pega total de perguntas do artigo
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM quiz_pergunta WHERE id_artigo = :id");
    $stmtTotal->execute(['id' => $id_artigo]);
    $totalArtigo = (int)$stmtTotal->fetchColumn();

    // 2. Conta quantas ele já acertou antes (para a regra dos 50%)
    $stmtAcertosAntigos = $pdo->prepare("SELECT COUNT(DISTINCT id_pergunta) FROM usuario_progresso WHERE email_usuario = :email AND id_artigo = :id_artigo AND status = 'aprovado'");
    $stmtAcertosAntigos->execute(['email' => $email, 'id_artigo' => $id_artigo]);
    $acertosAntigos = (int)$stmtAcertosAntigos->fetchColumn();

    // 3. Processa a tentativa atual
    $acertosAtuais = 0;
    $errosAtuais = 0;
    $xpGanhoTotal = 0;
    $idsCorretasAtuais = [];
    $dataTentativa = date('Y-m-d H:i:s'); // Mesma data para todas as inserções desta sessão

    $totalFinalAcertos = $acertosAntigos;

    foreach ($respostas as $id_pergunta => $respData) {
        $is_correct = $respData['is_correct'];
        $resposta_dada = $respData['resposta_dada'];
        
        $status = $is_correct ? 'aprovado' : 'reprovado';

        if ($is_correct) {
            $acertosAtuais++;
            $totalFinalAcertos++;
            $idsCorretasAtuais[] = $id_pergunta;
        } else {
            $errosAtuais++;
        }

        // Insere na tabela usuario_progresso
        $stmtIn = $pdo->prepare("
            INSERT INTO usuario_progresso (email_usuario, id_artigo, id_pergunta, data_tentativa, status, resposta_dada) 
            VALUES (:email, :id_artigo, :id_pergunta, :dt, :status, :resp)
        ");
        $stmtIn->execute([
            'email' => $email,
            'id_artigo' => $id_artigo,
            'id_pergunta' => $id_pergunta,
            'dt' => $dataTentativa,
            'status' => $status,
            'resp' => $resposta_dada
        ]);
    }

    $metade = ceil($totalArtigo / 2);
    $aprovado = ($totalFinalAcertos >= $metade);

    $upouDeNivel = false;
    $novoNivel = 0;

    if ($aprovado) {
        // Pega o XP apenas das perguntas que ele acertou NESTA TENTATIVA
        if (!empty($idsCorretasAtuais)) {
            $inQuery = implode(',', array_fill(0, count($idsCorretasAtuais), '?'));
            $stmtXp = $pdo->prepare("SELECT SUM(xp_recompensa) FROM quiz_pergunta WHERE id IN ($inQuery)");
            $stmtXp->execute($idsCorretasAtuais);
            $xpGanhoTotal = (int)$stmtXp->fetchColumn();

            if ($xpGanhoTotal > 0) {
                // Adiciona os pontos
                $stmtUpdatePoints = $pdo->prepare("UPDATE userpoints SET userpoints = userpoints + :xp WHERE emailPoints = :email");
                $stmtUpdatePoints->execute(['xp' => $xpGanhoTotal, 'email' => $email]);

                // Verifica nivel
                $pontosAtuais = getUserPoints($pdo, $email);
                $nivelAtual = getUserLevel($pdo, $email);
                $nivelCalculado = calcularNivelPorXP($pontosAtuais);

                if ($nivelCalculado > $nivelAtual) {
                    $stmtLevelUp = $pdo->prepare("UPDATE userlevel SET userlevel = :novo_nivel WHERE emailLevel = :email");
                    $stmtLevelUp->execute(['novo_nivel' => $nivelCalculado, 'email' => $email]);
                    $upouDeNivel = true;
                    $novoNivel = $nivelCalculado;
                }
            }
        }

        // Verifica se completou 100% para colocar no artigo_completo
        if ($totalFinalAcertos >= $totalArtigo) {
            // Pega o username
            $stmtUser = $pdo->prepare("SELECT nomeDeUsuario FROM user WHERE email = :email");
            $stmtUser->execute(['email' => $email]);
            $nomeUsuario = $stmtUser->fetchColumn();

            // Verifica se já não está lá
            $stmtCheckCompl = $pdo->prepare("SELECT COUNT(*) FROM artigo_completo WHERE id_artigo = :id_artigo AND nome_usuario_artigo = :username");
            $stmtCheckCompl->execute(['id_artigo' => $id_artigo, 'username' => $nomeUsuario]);
            if ($stmtCheckCompl->fetchColumn() == 0) {
                $stmtInCompl = $pdo->prepare("INSERT INTO artigo_completo (id_artigo, nome_usuario_artigo) VALUES (:id_artigo, :username)");
                $stmtInCompl->execute(['id_artigo' => $id_artigo, 'username' => $nomeUsuario]);
            }
        }
    }

    $pdo->commit();

    // Limpa a sessão do quiz
    unset($_SESSION['quiz_atual']);

    echo json_encode([
        'success' => true,
        'aprovado' => $aprovado,
        'acertos_sessao' => $acertosAtuais,
        'total_sessao' => count($respostas),
        'total_acertos_geral' => $totalFinalAcertos,
        'total_artigo' => $totalArtigo,
        'xp_ganho' => $xpGanhoTotal,
        'upou_de_nivel' => $upouDeNivel,
        'novo_nivel' => $novoNivel
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Erro interno ao processar o quiz: ' . $e->getMessage()]);
}
