<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';

$email = $_SESSION['user'];
$id_artigo = isset($_GET['id_artigo']) ? (int)$_GET['id_artigo'] : 0;

if ($id_artigo === 0) {
    echo json_encode(['success' => false, 'error' => 'Artigo inválido']);
    exit();
}

try {
    $pdo = getDB();

    // 1. Verifica quantas perguntas o artigo tem no total
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM quiz_pergunta WHERE id_artigo = :id");
    $stmtTotal->execute(['id' => $id_artigo]);
    $totalPerguntasArtigo = (int)$stmtTotal->fetchColumn();

    if ($totalPerguntasArtigo === 0) {
        echo json_encode(['success' => false, 'error' => 'Este artigo não possui perguntas.']);
        exit();
    }

    // 2. Busca todas as perguntas que o usuário já ACERTOU (status='aprovado') em tentativas anteriores
    $stmtAcertos = $pdo->prepare("SELECT id_pergunta FROM usuario_progresso WHERE email_usuario = :email AND id_artigo = :id_artigo AND status = 'aprovado'");
    $stmtAcertos->execute(['email' => $email, 'id_artigo' => $id_artigo]);
    $perguntasAcertadas = $stmtAcertos->fetchAll(PDO::FETCH_COLUMN);

    if (count($perguntasAcertadas) >= $totalPerguntasArtigo) {
        echo json_encode(['success' => false, 'status' => 'bloqueado', 'message' => 'Você já completou 100% deste quiz!']);
        exit();
    }

    // 3. Verifica o cooldown baseado na ÚLTIMA tentativa consolidada no banco
    $stmtUltimaTentativa = $pdo->prepare("
        SELECT data_tentativa, status 
        FROM usuario_progresso 
        WHERE email_usuario = :email AND id_artigo = :id_artigo 
        ORDER BY data_tentativa DESC 
        LIMIT 1
    ");
    $stmtUltimaTentativa->execute(['email' => $email, 'id_artigo' => $id_artigo]);
    $ultimaTentativa = $stmtUltimaTentativa->fetch(PDO::FETCH_ASSOC);

    if ($ultimaTentativa) {
        // Para descobrir se a última tentativa geral foi >= 50% ou < 50%, pegamos os acertos NAQUELA data exata
        $dataTentativa = $ultimaTentativa['data_tentativa'];
        $stmtStatusTentativa = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) as acertos,
                COUNT(*) as total_respondidas
            FROM usuario_progresso
            WHERE email_usuario = :email AND id_artigo = :id_artigo AND data_tentativa = :dt
        ");
        $stmtStatusTentativa->execute(['email' => $email, 'id_artigo' => $id_artigo, 'dt' => $dataTentativa]);
        $statsTentativa = $stmtStatusTentativa->fetch(PDO::FETCH_ASSOC);

        $acertosNaTentativa = (int)$statsTentativa['acertos'];
        $totalNaTentativa = (int)$statsTentativa['total_respondidas'];
        
        $tempoPassado = time() - strtotime($dataTentativa);

        if ($totalNaTentativa > 0) {
            if ($acertosNaTentativa >= ceil($totalNaTentativa / 2)) {
                // Aprovou (>50%), mas não 100%. Cooldown de 3 dias.
                $tresDiasEmSegundos = 3 * 24 * 60 * 60;
            if ($tempoPassado < $tresDiasEmSegundos) {
                echo json_encode([
                    'success' => false, 
                    'status' => 'cooldown', 
                    'message' => 'Você já atingiu a pontuação mínima neste artigo. Tente novamente em 3 dias para fechar 100% e ganhar o restante do XP!'
                ]);
                exit();
            }
        } else {
            // Reprovou (<50%). Cooldown de 10 minutos.
            $dezMinutosEmSegundos = 10 * 60;
            if ($tempoPassado < $dezMinutosEmSegundos) {
                $restante = ceil(($dezMinutosEmSegundos - $tempoPassado) / 60);
                echo json_encode([
                    'success' => false, 
                    'status' => 'cooldown', 
                    'message' => "Você não atingiu a pontuação mínima. Aguarde {$restante} minutos para tentar novamente."
                ]);
                exit();
            }
        }
    }
}

    // Se chegou até aqui, podemos enviar as perguntas que ele AINDA NÃO acertou
    $sqlPerguntas = "SELECT id, texto_pergunta, tipo, dificuldade, xp_recompensa FROM quiz_pergunta WHERE id_artigo = ?";
    
    // Filtra as já acertadas
    if (!empty($perguntasAcertadas)) {
        $inQuery = implode(',', array_fill(0, count($perguntasAcertadas), '?'));
        $sqlPerguntas .= " AND id NOT IN ($inQuery)";
    }

    $stmtPerguntas = $pdo->prepare($sqlPerguntas);
    
    $params = [$id_artigo];
    if (!empty($perguntasAcertadas)) {
        $params = array_merge($params, $perguntasAcertadas);
    }
    
    $stmtPerguntas->execute($params);
    $perguntas = $stmtPerguntas->fetchAll(PDO::FETCH_ASSOC);

    // Para cada pergunta de múltipla escolha, busca as alternativas (SEM is_correct)
    foreach ($perguntas as &$pergunta) {
        if ($pergunta['tipo'] === 'multipla_escolha') {
            $stmtAlt = $pdo->prepare("SELECT id, texto_alternativa FROM quiz_resposta WHERE id_pergunta = :id_pergunta AND tipo_pergunta = 'multipla_escolha' ORDER BY RAND()");
            $stmtAlt->execute(['id_pergunta' => $pergunta['id']]);
            $pergunta['alternativas'] = $stmtAlt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Inicializa a sessão de respostas para este artigo
    $_SESSION['quiz_atual'] = [
        'id_artigo' => $id_artigo,
        'respostas' => [] 
    ];

    echo json_encode(['success' => true, 'perguntas' => $perguntas, 'total_artigo' => $totalPerguntasArtigo]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno ao carregar perguntas.' . $e->getMessage()]);
}
